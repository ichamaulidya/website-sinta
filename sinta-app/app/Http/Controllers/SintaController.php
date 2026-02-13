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
                    NIDN AS nidn,
                    Bagian AS departemen
                ')
                // Jangan batasi hanya yang punya Sinta_ID agar user tahu datanya ada
                // ->whereNotNull('Sinta_ID')
                // ->where('Sinta_ID', '!=', '')
                ->when($q, function ($query) use ($q) {
                    $query->where('Nama', 'LIKE', "%{$q}%")
                        ->orWhere('NPI', 'LIKE', "%{$q}%")
                        ->orWhere('NIDN', 'LIKE', "%{$q}%")
                        ->orWhere('Sinta_ID', 'LIKE', "%{$q}%");
                })
                ->limit(20)
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
        // Kamu perlu menyesuaikan PublicationsExport sedikit jika ingin fitur ini jalan
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
        $python = 'python'; 

        // PATH FILE PYTHON (ABSOLUTE, PALING AMAN)
        $script = base_path('python/sinta_scraper.py');

        if (!file_exists($script)) {
            return response()->json([
                'success' => false,
                'error' => 'File scraper tidak ditemukan: ' . $script
            ], 500);
        }

        // Gunakan escapeshellarg untuk masing-masing argumen dan arahkan stderr ke stdout
        $command = $python . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($id) . ' 2>&1';
        
        Log::info("Running command: " . $command);

        $output = shell_exec($command);

        if (!$output) {
            return response()->json([
                'success' => false,
                'error' => 'Python tidak mengembalikan output'
            ], 500);
        }

        $json = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Python output is not valid JSON: " . $output);
            return response()->json([
                'success' => false,
                'error' => 'Output scraper tidak valid (bukan JSON)',
                'debug_output' => $output
            ], 500);
        }

        if (isset($json['documents']) && is_array($json['documents'])) {
            foreach ($json['documents'] as $doc) {
                \App\Models\Publication::updateOrCreate(
                    [
                    'sinta_id' => $id,
                    'title'    => $doc['title'],
                    'year' => isset($doc['year']) ? substr(trim($doc['year']), -4) : date('Y')
                    ],
                    [
                    'source'   => $doc['source'],
                    'journal'  => $doc['journal'] ?? '-',
                    'cited'    => (int) ($doc['cited'] ?? 0),
                    'type'     => $doc['type'] ?? '-',
                    // 'created_at' otomatis terisi saat data pertama kali masuk (untuk filter bulan)
                    ]
                );
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

            // Save HTML for debugging (optional, comment out in production)
            // file_put_contents(storage_path('app/debug_search.html'), $html);

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
                // Log HTML structure for debugging
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

        // Get author link and ID
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
            return null; // Skip if no ID found
        }

        // Get name
        $nama = null;
        foreach ($nameSelectors as $selector) {
            $nama = $this->safeText($node, $selector);
            if ($nama && $nama !== '-') break;
        }

        // Get affiliation
        $institusi = null;
        foreach ($affSelectors as $selector) {
            $institusi = $this->safeText($node, $selector);
            if ($institusi && $institusi !== '-') break;
        }

        // Get department
        $departemen = '-';
        foreach ($deptSelectors as $selector) {
            $departemen = $this->safeText($node, $selector);
            if ($departemen && $departemen !== '-') break;
        }

        // Get score
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

    /**
     * Extract SINTA ID from URL
     */
    private function extractSintaId($url)
    {
        // Examples:
        // /authors/profile/6005887
        // https://sinta.kemdikbud.go.id/authors/profile/6005887
        
        if (preg_match('/\/authors\/profile\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/\/(\d{6,})/', $url, $matches)) {
            return $matches[1];
        }
        
        return basename($url);
    }

    /**
     * Make absolute URL
     */
    private function makeAbsoluteUrl($url)
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        return $this->baseUrl . $url;
    }

    /**
     * Ambil detail profil dosen berdasarkan SINTA ID
     */
    public function getProfile($id)
    {
        try {
            // Try cache first (5 minutes)
            $cacheKey = "sinta_profile_{$id}";
            
            if (Cache::has($cacheKey)) {
                return response()->json(Cache::get($cacheKey));
            }

            $url = $this->baseUrl . "/authors/profile/" . $id;
            
            Log::info("Fetching profile: {$url}");
            
            $response = $this->client->request('GET', $url);
            $html = $response->getBody()->getContents();
            
            // Save for debugging
            // file_put_contents(storage_path("app/debug_profile_{$id}.html"), $html);
            
            $crawler = new Crawler($html);

            // Data profil dengan multiple selector fallback
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
                
                // SINTA Score - try to get from multiple locations
                'sinta_overall' => $this->getScoreByIndex($crawler, 0),
                'sinta_3yr' => $this->getScoreByIndex($crawler, 1),
                'affil_overall' => $this->getScoreByIndex($crawler, 2),
                'affil_3yr' => $this->getScoreByIndex($crawler, 3),

                // Metrics - will be filled by scraping the metrics section
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

            // Cache for 5 minutes
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

    /**
     * Get profile field with fallback selectors
     */
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

    /**
     * Get score by index with multiple selector patterns
     */
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

    /**
     * Get metrics from profile page
     */
    private function getMetricsFromPage($crawler, $source)
    {
        try {
            // Look for metrics in various possible locations
            $metrics = [
                'articles' => 0,
                'citations' => 0,
                'cited_docs' => 0,
                'h_index' => 0,
                'i10_index' => 0,
                'g_index' => 0
            ];

            // Try to find metrics section
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
                // Parse metrics from the found section
                // This would need to be customized based on actual HTML structure
                $metrics['articles'] = $this->safeNumber($metricsNode, '.articles, .docs, [data-metric="articles"]');
                $metrics['citations'] = $this->safeNumber($metricsNode, '.citations, [data-metric="citations"]');
                $metrics['h_index'] = $this->safeNumber($metricsNode, '.h-index, [data-metric="hindex"]');
            }

            return $metrics;
        } catch (\Exception $e) {
            Log::warning("Metrics extraction failed for {$source}: " . $e->getMessage());
            return [
                'articles' => 0,
                'citations' => 0,
                'cited_docs' => 0,
                'h_index' => 0,
                'i10_index' => 0,
                'g_index' => 0
            ];
        }
    }

    /**
     * Ambil publikasi berdasarkan source (scopus/scholar/wos/garuda)
     */
    public function getPublications($id, $source = 'scopus')
    {
        try {
            $url = $this->baseUrl . "/authors/profile/{$id}/{$source}";
            
            Log::info("Fetching publications: {$url}");
            
            $response = $this->client->request('GET', $url);
            $html = $response->getBody()->getContents();
            
            $crawler = new Crawler($html);

            $publications = [];

            // Try to find table
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
                        
                        if (count($publications) > 0) {
                            break; // Found working selector
                        }
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

    /**
     * Parse publication row with fallback
     */
    private function parsePublicationRow($node)
    {
        $cells = $node->filter('td');
        
        if ($cells->count() < 4) {
            return null; // Not enough cells
        }

        return [
            'title' => $this->safeText($node, 'td', 0),
            'type' => $this->safeText($node, 'td .badge, td span'),
            'journal' => $this->safeText($node, 'td', 1),
            'author_order' => $this->safeText($node, 'td', 2),
            'year' => $this->safeText($node, 'td', 3),
            'cited' => $cells->count() > 4 ? $this->safeText($node, 'td', 4) : '0',
        ];
    }

    /**
     * Helper: Safely get text from crawler
     */
    private function safeText($crawler, $selector, $index = null)
    {
        try {
            $element = $crawler->filter($selector);
            
            if ($index !== null && $element->count() > $index) {
                return trim($element->eq($index)->text());
            }
            
            if ($element->count() > 0) {
                return trim($element->text());
            }
            
            return '-';
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Helper: Safely get number from crawler
     */
    private function safeNumber($crawler, $selector)
    {
        $text = $this->safeText($crawler, $selector);
        $number = preg_replace('/[^0-9]/', '', $text);
        return $number ? (int)$number : 0;
    }
}