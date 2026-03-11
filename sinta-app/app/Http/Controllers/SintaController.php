<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use App\Models\Dosen;
use App\Models\Publication; 
use App\Models\Ipr; // Menambahkan model IPR
use App\Exports\PublicationsExport; 
use Maatwebsite\Excel\Facades\Excel; 

class SintaController extends Controller
{
    private $client;
    private $baseUrl = 'https://sinta.kemdiktisaintek.go.id'; // Updated URL

    public function beranda()
    {
        return view('beranda');
    }

    public function __construct()
    {
        $this->client = new Client([
            'verify' => false,
            'timeout' => 300,
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Cache-Control' => 'max-age=0'
            ]
        ]);
    }

    /**
     * Get all dosen from database for autocomplete
     */
    public function getAllDosen(Request $request)
    {
        try {
            $q = $request->input('q');

            $dosens = Dosen::selectRaw('
                    Nama AS nama,
                    Sinta_ID AS sinta_id,
                    NPI AS npi,
                    NIDN AS nidn
                ')
                ->whereNotNull('Sinta_ID')
                ->where('Sinta_ID', '!=', '')
                ->when($q, function ($query) use ($q) {
                    $query->where('Nama', 'LIKE', "%{$q}%")
                        ->orWhere('NPI', 'LIKE', "%{$q}%")
                        ->orWhere('NIDN', 'LIKE', "%{$q}%");
                })
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $dosens
            ]);
        } catch (\Exception $e) {
            Log::error("Autocomplete dosen error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);
        
        $fileName = "Laporan_Publikasi_SINTA_{$year}_{$month}.xlsx";
        
        return Excel::download(new PublicationsExport($year, $month), $fileName);
    }

    public function exportExcelSingle(Request $request)
    {
        $id = $request->query('id');
    
        // Nama file berdasarkan ID SINTA agar unik
        $fileName = "Data_Publikasi_{$id}.xlsx";
    
        // Kita gunakan class export yang sama, tapi kirim ID SINTA
        return Excel::download(new \App\Exports\PublicationsExport($id, null, true), $fileName);
    }

    /**
     * Scrape data SINTA by ID using Python script
     */
    public function scrape(Request $request)
    {
        set_time_limit(300);
        $id = $request->query('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'error' => 'SINTA ID tidak ditemukan'
            ], 400);
        }

        // PATH PYTHON (Windows)
        $python = 'py'; // ganti 'py' kalau python tidak dikenali

        // PATH FILE PYTHON (ABSOLUTE, PALING AMAN)
        $script = base_path('/python/sinta_scraper.py');

        if (!file_exists($script)) {
            return response()->json([
                'success' => false,
                'error' => 'File scraper tidak ditemukan: ' . $script
            ], 500);
        }

        // Escape path (PENTING DI WINDOWS)
        $command = escapeshellcmd("$python \"$script\" $id");

        $output = shell_exec($command);

        if (!$output) {
            return response()->json([
                'success' => false,
                'error' => 'Python tidak mengembalikan output'
            ], 500);
        }

        $json = json_decode($output, true);

        // BAGIAN YANG DIUBAH: Memisahkan penyimpanan IPR dan Publikasi
        if (isset($json['documents']) && is_array($json['documents'])) {
            foreach ($json['documents'] as $doc) {
                if ($doc['source'] == 'ipr') {
                    // Simpan ke tabel IPR
                    \App\Models\Ipr::updateOrCreate(
                        ['title' => $doc['title'], 'sinta_id' => $id],
                        [
                            'category' => $doc['type'] ?? 'HKI',
                            'year'     => isset($doc['year']) ? substr(trim($doc['year']), -4) : date('Y'),
                            'link'     => $doc['link'] ?? null,
                        ]
                    );
                } else {
                    // Simpan ke tabel Publications (Scopus, Garuda, WoS)
                    \App\Models\Publication::updateOrCreate(
                        [
                            'sinta_id' => $id,
                            'title'    => $doc['title'],
                            'year'     => isset($doc['year']) ? substr(trim($doc['year']), -4) : date('Y')
                        ],
                        [
                            'source'   => $doc['source'],
                            'journal'  => $doc['journal'] ?? '-',
                            'cited'    => (int) ($doc['cited'] ?? 0),
                            'type'     => $doc['type'] ?? '-',
                        ]
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $json
        ]);
    }

    /**
     * Cari dosen berdasarkan nama atau NIP
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'error' => 'Query tidak boleh kosong'
            ], 400);
        }

        try {
            // Try multiple possible URLs
            $urls = [
                $this->baseUrl . '/authors',
                'https://sinta.kemdiktisaintek.go.id/authors',
                'https://sinta.ristekbrin.go.id/authors'
            ];

            $html = null;
            $usedUrl = null;

            foreach ($urls as $url) {
                try {
                    Log::info("Trying URL: {$url}?q={$query}");
                    
                    $response = $this->client->request('GET', $url, [
                        'query' => ['q' => $query]
                    ]);

                    $html = $response->getBody()->getContents();
                    $usedUrl = $url;
                    
                    Log::info("Success with URL: {$url}, HTML length: " . strlen($html));
                    break;
                } catch (\Exception $e) {
                    Log::warning("Failed URL {$url}: " . $e->getMessage());
                    continue;
                }
            }

            if (!$html) {
                throw new \Exception('Tidak dapat mengakses SINTA dari semua URL yang dicoba');
            }

            $crawler = new Crawler($html);
            $results = [];

            // Try multiple selector patterns
            $selectors = [
                '.au-item',           // Original selector
                '.author-item',       // Alternative
                '.result-item',       // Alternative
                'div[class*="author"]', // Wildcard
                'article',            // Generic
            ];

            $foundSelector = null;

            foreach ($selectors as $selector) {
                $items = $crawler->filter($selector);
                
                if ($items->count() > 0) {
                    $foundSelector = $selector;
                    Log::info("Found items with selector: {$selector}, count: " . $items->count());
                    
                    $items->each(function (Crawler $node) use (&$results) {
                        try {
                            $result = $this->parseAuthorItem($node);
                            if ($result) {
                                $results[] = $result;
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error parsing item: ' . $e->getMessage());
                        }
                    });
                    
                    if (count($results) > 0) {
                        break; // Found working selector
                    }
                }
            }

            if (empty($results)) {
                $allClasses = [];
                $crawler->filter('*')->each(function (Crawler $node) use (&$allClasses) {
                    $class = $node->attr('class');
                    if ($class && !in_array($class, $allClasses)) {
                        $allClasses[] = $class;
                    }
                });
                
                Log::error('No results found. Available classes: ' . implode(', ', array_slice($allClasses, 0, 50)));
                
                return response()->json([
                    'success' => false,
                    'error' => 'Tidak ada hasil ditemukan. Struktur website mungkin telah berubah.',
                    'debug' => [
                        'query' => $query,
                        'url_used' => $usedUrl,
                        'selectors_tried' => $selectors,
                        'html_length' => strlen($html),
                        'sample_classes' => array_slice($allClasses, 0, 20)
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'query' => $query,
                'count' => count($results),
                'data' => $results,
                'meta' => [
                    'selector_used' => $foundSelector,
                    'url_used' => $usedUrl
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse author item dengan multiple selector fallback
     */
    private function parseAuthorItem(Crawler $node)
    {
        $linkSelectors = ['a[href*="authors"]', '.au-name a', '.author-name a', 'h3 a', 'a'];
        $nameSelectors = ['.au-name', '.author-name', 'h3', 'h4', 'strong'];
        $affSelectors = ['.au-aff', '.affiliation', '.institution', 'p'];
        $deptSelectors = ['.au-dept', '.department', 'small'];
        $scoreSelectors = ['.pr-num', '.score', '.sinta-score', 'span[class*="score"]'];

        $authorLink = null;
        $sintaId = null;
        
        foreach ($linkSelectors as $selector) {
            try {
                if ($node->filter($selector)->count() > 0) {
                    $authorLink = $node->filter($selector)->first()->attr('href');
                    if ($authorLink) {
                        $sintaId = $this->extractSintaId($authorLink);
                        if ($sintaId) break;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if (!$sintaId) {
            return null; 
        }

        $nama = null;
        foreach ($nameSelectors as $selector) {
            $nama = $this->safeText($node, $selector);
            if ($nama && $nama !== '-') break;
        }

        $institusi = null;
        foreach ($affSelectors as $selector) {
            $institusi = $this->safeText($node, $selector);
            if ($institusi && $institusi !== '-') break;
        }

        $departemen = '-';
        foreach ($deptSelectors as $selector) {
            $departemen = $this->safeText($node, $selector);
            if ($departemen && $departemen !== '-') break;
        }

        $sintaScore = '0';
        foreach ($scoreSelectors as $selector) {
            $sintaScore = $this->safeText($node, $selector);
            if ($sintaScore && $sintaScore !== '-' && is_numeric($sintaScore)) break;
        }

        return [
            'sinta_id' => $sintaId,
            'nama' => $nama ?: 'Unknown',
            'institusi' => $institusi ?: '-',
            'departemen' => $departemen,
            'sinta_score' => $sintaScore,
            'profile_url' => $this->makeAbsoluteUrl($authorLink)
        ];
    }

    private function extractSintaId($url)
    {
        if (preg_match('/\/authors\/profile\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\/(\d{6,})/', $url, $matches)) {
            return $matches[1];
        }
        return basename($url);
    }

    private function makeAbsoluteUrl($url)
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        return $this->baseUrl . $url;
    }

    public function getProfile($id)
    {
        try {
            $cacheKey = "sinta_profile_{$id}";
            
            if (Cache::has($cacheKey)) {
                return response()->json(Cache::get($cacheKey));
            }

            $url = $this->baseUrl . "/authors/profile/" . $id;
            
            Log::info("Fetching profile: {$url}");
            
            $response = $this->client->request('GET', $url);
            $html = $response->getBody()->getContents();
            
            $crawler = new Crawler($html);

            $data = [
                'sinta_id' => $id,
                'nama' => $this->getProfileField($crawler, [
                    '.author-name', 
                    'h1.name', 
                    'h1', 
                    '.profile-name'
                ]),
                'institusi' => $this->getProfileField($crawler, [
                    '.affilation-name a',
                    '.affiliation a',
                    'a[href*="affiliations"]',
                    '.institution'
                ]),
                'departemen' => $this->getProfileField($crawler, [
                    '.affilation-dept',
                    '.department',
                    '.dept'
                ]),
                
                'sinta_overall' => $this->getScoreByIndex($crawler, 0),
                'sinta_3yr' => $this->getScoreByIndex($crawler, 1),
                'affil_overall' => $this->getScoreByIndex($crawler, 2),
                'affil_3yr' => $this->getScoreByIndex($crawler, 3),

                'scopus' => $this->getMetricsFromPage($crawler, 'scopus'),
                'scholar' => $this->getMetricsFromPage($crawler, 'scholar'),
                'wos' => $this->getMetricsFromPage($crawler, 'wos'),
                'garuda' => $this->getMetricsFromPage($crawler, 'garuda'),
                
                'profile_url' => $url
            ];

            $result = [
                'success' => true,
                'data' => $data
            ];

            Cache::put($cacheKey, $result, now()->addMinutes(5));

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error("Profile error for ID {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Gagal mengambil profil: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getProfileField($crawler, array $selectors)
    {
        foreach ($selectors as $selector) {
            $value = $this->safeText($crawler, $selector);
            if ($value && $value !== '-') {
                return $value;
            }
        }
        return '-';
    }

    private function getScoreByIndex($crawler, $index)
    {
        $selectors = [
            '.pr-num',
            '.score-value',
            'div[class*="score"]',
            '.metric-value'
        ];

        foreach ($selectors as $selector) {
            try {
                $elements = $crawler->filter($selector);
                if ($elements->count() > $index) {
                    $value = trim($elements->eq($index)->text());
                    if (is_numeric($value) || $value === '-') {
                        return $value;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return '-';
    }

    private function getMetricsFromPage($crawler, $source)
    {
        try {
            $metrics = [
                'articles' => 0,
                'citations' => 0,
                'cited_docs' => 0,
                'h_index' => 0,
                'i10_index' => 0,
                'g_index' => 0
            ];

            $metricSelectors = [
                "div[data-source='{$source}']",
                ".{$source}-metrics",
                "#{$source}",
                "div[class*='{$source}']"
            ];

            $metricsNode = null;
            foreach ($metricSelectors as $selector) {
                try {
                    if ($crawler->filter($selector)->count() > 0) {
                        $metricsNode = $crawler->filter($selector)->first();
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            if ($metricsNode) {
                $metrics['articles'] = $this->safeNumber($metricsNode, '.articles, .docs, [data-metric="articles"]');
                $metrics['citations'] = $this->safeNumber($metricsNode, '.citations, [data-metric="citations"]');
                $metrics['h_index'] = $this->safeNumber($metricsNode, '.h-index, [data-metric="hindex"]');
            }

            return $metrics;
        } catch (\Exception $e) {
            Log::warning("Metrics extraction failed for {$source}: " . $e->getMessage());
            return $metrics;
        }
    }

    public function getPublications($id, $source = 'scopus')
    {
        try {
            $url = $this->baseUrl . "/authors/profile/{$id}/{$source}";
            
            Log::info("Fetching publications: {$url}");
            
            $response = $this->client->request('GET', $url);
            $html = $response->getBody()->getContents();
            
            $crawler = new Crawler($html);
            $publications = [];

            $tableSelectors = [
                'table tbody tr',
                '.publication-item',
                '.pub-item',
                'div[class*="publication"] tr'
            ];

            foreach ($tableSelectors as $selector) {
                try {
                    $rows = $crawler->filter($selector);
                    if ($rows->count() > 0) {
                        Log::info("Found {$rows->count()} publications with selector: {$selector}");
                        $rows->each(function (Crawler $node) use (&$publications) {
                            try {
                                $pub = $this->parsePublicationRow($node);
                                if ($pub) {
                                    $publications[] = $pub;
                                }
                            } catch (\Exception $e) {
                                Log::warning('Error parsing publication: ' . $e->getMessage());
                            }
                        });
                        if (count($publications) > 0) break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'source' => $source,
                'count' => count($publications),
                'data' => $publications
            ]);

        } catch (\Exception $e) {
            Log::error("Publications error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Gagal mengambil publikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    private function parsePublicationRow($node)
    {
        $cells = $node->filter('td');
        if ($cells->count() < 4) return null;

        return [
            'title' => $this->safeText($node, 'td', 0),
            'type' => $this->safeText($node, 'td .badge, td span'),
            'journal' => $this->safeText($node, 'td', 1),
            'author_order' => $this->safeText($node, 'td', 2),
            'year' => $this->safeText($node, 'td', 3),
            'cited' => $cells->count() > 4 ? $this->safeText($node, 'td', 4) : '0',
        ];
    }

    private function safeText($crawler, $selector, $index = null)
    {
        try {
            $element = $crawler->filter($selector);
            if ($index !== null && $element->count() > $index) {
                return trim($element->eq($index)->text());
            }
            if ($element->count() > 0) return trim($element->text());
            return '-';
        } catch (\Exception $e) {
            return '-';
        }
    }

    private function safeNumber($crawler, $selector)
    {
        $text = $this->safeText($crawler, $selector);
        $number = preg_replace('/[^0-9]/', '', $text);
        return $number ? (int)$number : 0;
    }
}