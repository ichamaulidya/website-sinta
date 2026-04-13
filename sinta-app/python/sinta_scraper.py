import requests
from bs4 import BeautifulSoup
import json
import sys
import re
import concurrent.futures

# Konfigurasi
MAX_PAGES = 10  # Batasi halaman per kategori agar tidak timeout
# CATEGORIES diperbarui dengan menambahkan 'ipr'
CATEGORIES = ['scopus', 'garuda', 'wos', 'ipr']

def get_soup(url):
    headers = {
        # Ambil bagian Cookie-nya saja
        'Cookie': 'ci_session=v64hju110d7qn0598qldbal91dk2q7s3',
        
        # User-Agent disesuaikan dengan yang kamu kirim tadi
        'User-Agent': 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36',
        
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Referer': 'https://sinta.kemdiktisaintek.go.id',
        
    }
    try:
        import time
        time.sleep(2)

        response = requests.get(url, headers=headers, verify=False, timeout=20)
        response.raise_for_status()
        return BeautifulSoup(response.content, 'html.parser')
    except Exception as e:
        return None

def get_google_scholar_citation(sinta_id, garuda_title):
    """
    Mencari sitasi dari Google Scholar berdasarkan judul dokumen
    """
    try:
        # Akses halaman Google Scholar view
        url = f"https://sinta.kemdiktisaintek.go.id/authors/profile/{sinta_id}/?view=googlescholar"
        soup = get_soup(url)
        
        if not soup:
            return None
        
        # Cari dokumen dengan judul yang sama
        doc_items = soup.find_all('div', class_='ar-list-item')
        
        for item in doc_items:
            # Ambil judul dari item Google Scholar
            title_tag = item.select_one('.ar-title a') or item.select_one('.ar-title')
            if not title_tag:
                continue
                
            gs_title = title_tag.text.strip()
            
            # Cek apakah judul cocok (case-insensitive dan normalisasi whitespace)
            if normalize_title(gs_title) == normalize_title(garuda_title):
                # Ambil data sitasi dari Google Scholar
                cited_tag = item.select_one('.ar-cited')
                if cited_tag:
                    return cited_tag.text.strip()
        
        return None
    except Exception as e:
        print(f"Error fetching Google Scholar citation: {e}", file=sys.stderr)
        return None

def normalize_title(title):
    """
    Normalisasi judul untuk perbandingan
    """
    if not title:
        return ""
    # Lowercase, hapus whitespace berlebih, hapus karakter spesial
    title = title.lower().strip()
    title = re.sub(r'\s+', ' ', title)
    return title

def scrape_documents_page(sinta_id, category, page, gs_citations_cache=None):
    # Fix URL logic:
    # Scholar uses view=googlescholar
    # Garuda uses view=garuda
    # WoS uses view=wos
    # Scopus uses view=scopus
    # IPR uses view=ipr
    
    view_map = {
        'scopus': 'scopus',
        'garuda': 'garuda',
        'wos': 'wos',
        'ipr': 'ipr'
    }
    # Pastikan menggunakan 'v' hasil dari view_map.get
    v = view_map.get(category, category)
    url = f"https://sinta.kemdiktisaintek.go.id/authors/profile/{sinta_id}/?page={page}&view={v}"
    
    soup = get_soup(url)
    docs = []
    
    if not soup:
        return docs

    # Mencari item dokumen/IPR
    doc_items = soup.find_all('div', class_='ar-list-item')
    
    if not doc_items:
        return docs
        
    for item in doc_items:
        doc = {'source': category} # Tag source
        
        # Title
        title_tag = item.select_one('.ar-title a')
        if title_tag:
            doc['title'] = title_tag.text.strip()
            doc['link'] = title_tag.get('href')
        else:
            # Fallback for some items that might not have link
            title_div = item.select_one('.ar-title')
            if title_div:
                 doc['title'] = title_div.text.strip()
            else:
                 continue
        
        # Meta info / Quartile
        quartile = item.select_one('.ar-quartile')
        if quartile:
            doc['type'] = quartile.text.strip()
        
        # Journal / IPR Category
        pub = item.select_one('.ar-pub')
        if pub:
            doc['journal'] = pub.text.strip()
            
            # Khusus untuk IPR, jika kolom 'type' kosong, isi dengan kategori IPR (Paten, Hak Cipta, dll)
            if category == 'ipr' and 'type' not in doc:
                doc['type'] = pub.text.strip()
            
        # Year
        year_tag = item.select_one('.ar-year')
        if year_tag:
            doc['year'] = year_tag.text.strip()
        
        # Cited - untuk Garuda, ambil dari Google Scholar
        if category == 'garuda':
            # Cek cache terlebih dahulu
            if gs_citations_cache and doc['title'] in gs_citations_cache:
                doc['cited'] = gs_citations_cache[doc['title']]
            else:
                # Jika tidak ada di cache, ambil langsung
                gs_citation = get_google_scholar_citation(sinta_id, doc['title'])
                doc['cited'] = gs_citation if gs_citation else "0"
                doc['cited_source'] = 'google_scholar'
        elif category == 'ipr':
            # IPR biasanya tidak memiliki sitasi
            doc['cited'] = "0"
        else:
            # Untuk kategori lain (Scopus/Wos), ambil dari halaman biasa
            cited_tag = item.select_one('.ar-cited')
            if cited_tag:
                doc['cited'] = cited_tag.text.strip()
            
        docs.append(doc)
        
    return docs

def build_google_scholar_cache(sinta_id):
    """
    Membangun cache sitasi dari Google Scholar untuk semua dokumen
    Ini lebih efisien daripada request per dokumen
    """
    cache = {}
    try:
        print(f"Building Google Scholar citation cache...", file=sys.stderr)
        
        # Scrape semua halaman Google Scholar
        for page in range(1, MAX_PAGES + 1):
            url = f"https://sinta.kemdiktisaintek.go.id/authors/profile/{sinta_id}/?page={page}&view=googlescholar"
            soup = get_soup(url)
            
            if not soup:
                break
                
            doc_items = soup.find_all('div', class_='ar-list-item')
            
            if not doc_items:
                break
            
            for item in doc_items:
                # Ambil judul
                title_tag = item.select_one('.ar-title a') or item.select_one('.ar-title')
                if not title_tag:
                    continue
                
                title = title_tag.text.strip()
                
                # Ambil sitasi
                cited_tag = item.select_one('.ar-cited')
                if cited_tag:
                    cache[title] = cited_tag.text.strip()
                else:
                    cache[title] = "0"
        
        print(f"Google Scholar cache built: {len(cache)} documents", file=sys.stderr)
        return cache
    except Exception as e:
        print(f"Error building Google Scholar cache: {e}", file=sys.stderr)
        return {}

def scrape_category_all_pages(sinta_id, category, gs_cache=None):
    all_docs = []
    # Serial loop to be safe
    # We stop when a page returns no documents
    for page in range(1, MAX_PAGES + 1):
        docs = scrape_documents_page(sinta_id, category, page, gs_cache)
        if not docs:
            break
        all_docs.extend(docs)
        
    return all_docs

def scrape_sinta_profile(sinta_id):
    base_url = f"https://sinta.kemdiktisaintek.go.id/authors/profile/{sinta_id}"
    soup = get_soup(base_url)

    data = {
        "sinta_id": sinta_id,
        "profile": {},
        "metrics": {},
        "stats": {},
        "documents": []
    }
    
    if not soup:
        data['error'] = "Failed to load profile page"
        return data

    # 1. Profile Info
    try:
        name_tag = soup.select_one('div.au-profile h3 a') or soup.select_one('div.au-profile h3') or soup.find('h3')
        if name_tag:
            data['profile']['name'] = name_tag.text.strip()
        
        meta_links = soup.select('div.meta-profile a')
        if len(meta_links) >= 3:
            data['profile']['affiliation'] = meta_links[0].text.strip()
            data['profile']['department'] = meta_links[1].text.strip()
            id_text = meta_links[2].text.strip()
            if "SINTA ID" in id_text:
                data['profile']['sinta_id_label'] = id_text
    except Exception as e:
        data['error_profile'] = str(e)

    # 2. Metrics
    try:
        stat_profile = soup.find('div', class_='stat-profile')
        if stat_profile:
            nums = stat_profile.find_all('div', class_='pr-num')
            txts = stat_profile.find_all('div', class_='pr-txt')
            for i in range(len(nums)):
                if i < len(txts):
                    data['metrics'][txts[i].text.strip()] = nums[i].text.strip()
    except Exception as e:
        data['error_metrics'] = str(e)

    # 3. Stats Table
    try:
        stats_table = None
        for table in soup.find_all('table'):
            if table.find('th', string=re.compile('Scopus')):
                stats_table = table
                break
        
        if stats_table:
            rows = stats_table.find('tbody').find_all('tr')
            for row in rows:
                cols = row.find_all('td')
                if len(cols) >= 2:
                    data['stats'][cols[0].text.strip()] = cols[1].text.strip()
    except Exception as e:
        data['error_stats'] = str(e)

    # 4. Build Google Scholar citation cache first (untuk dokumen Garuda)
    gs_cache = build_google_scholar_cache(sinta_id)
    
    # 5. Scrape Documents (Parallel per category)
    try:
        with concurrent.futures.ThreadPoolExecutor(max_workers=4) as executor:
            future_to_cat = {
                executor.submit(scrape_category_all_pages, sinta_id, cat, gs_cache if cat == 'garuda' else None): cat 
                for cat in CATEGORIES
            }
            for future in concurrent.futures.as_completed(future_to_cat):
                cat = future_to_cat[future]
                try:
                    docs = future.result()
                    data['documents'].extend(docs)
                except Exception as exc:
                    print(f"Error scraping {cat}: {exc}", file=sys.stderr)
    except Exception as e:
        data['error_documents'] = str(e)

    return data

if __name__ == "__main__":
    # Disable warnings for unverified HTTPS
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    target_id = "6711412" 
    if len(sys.argv) > 1:
        target_id = sys.argv[1]
    
    result = scrape_sinta_profile(target_id)
    print(json.dumps(result, indent=2))