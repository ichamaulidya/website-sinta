import requests
from bs4 import BeautifulSoup
import json
import sys
import re
import concurrent.futures

# Konfigurasi
MAX_PAGES = 10
CATEGORIES = ['scopus', 'garuda', 'wos']

def get_soup(url):
    headers = {
        'Cookie': 'ci_session=9crpff0pj7mltlf80q21volko3dlebt3; _sd_demo_page_promo=true; _sd_cs_visible=true',
        'User-Agent': 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Referer': 'https://sinta.kemdiktisaintek.go.id/',
    }
    try:
        import time
        time.sleep(2)
        response = requests.get(url, headers=headers, verify=False, timeout=20)
        response.raise_for_status()
        return BeautifulSoup(response.content, 'html.parser')
    except Exception as e:
        return None

def scrape_citations(doc_url):
    """
    Fungsi baru untuk scrape sitasi dari halaman detail dokumen
    """
    if not doc_url:
        return []
    
    # Pastikan URL lengkap
    if not doc_url.startswith('http'):
        doc_url = 'https://sinta.kemdiktisaintek.go.id' + doc_url
    
    soup = get_soup(doc_url)
    citations = []
    
    if not soup:
        return citations
    
    # Cari section citations
    # Biasanya ada di bagian bawah halaman detail
    citation_items = soup.find_all('div', class_='citation-item') or \
                     soup.find_all('div', class_='cited-by-item') or \
                     soup.find_all('li', class_='citation')
    
    for item in citation_items:
        citation = {}
        
        # Title sitasi
        title_tag = item.find('a') or item.find('h4') or item.find('strong')
        if title_tag:
            citation['title'] = title_tag.text.strip()
            if item.find('a'):
                citation['link'] = item.find('a').get('href')
        
        # Authors
        authors_tag = item.find('div', class_='authors') or item.find('span', class_='author')
        if authors_tag:
            citation['authors'] = authors_tag.text.strip()
        
        # Year
        year_tag = item.find('span', class_='year')
        if year_tag:
            citation['year'] = year_tag.text.strip()
        
        # Journal/Source
        journal_tag = item.find('span', class_='journal') or item.find('i')
        if journal_tag:
            citation['journal'] = journal_tag.text.strip()
        
        if citation:
            citations.append(citation)
    
    return citations

def scrape_documents_page(sinta_id, category, page, fetch_citations=False):
    view_map = {
        'scopus': 'scopus',
        'garuda': 'garuda',
        'wos': 'wos'
    }
    v = view_map.get(category, category)
    url = f"https://sinta.kemdiktisaintek.go.id/authors/profile/{sinta_id}/?page={page}&view={v}"
    
    soup = get_soup(url)
    docs = []
    
    if not soup:
        return docs

    doc_items = soup.find_all('div', class_='ar-list-item')
    
    if not doc_items:
        return docs
        
    for item in doc_items:
        doc = {'source': category}
        
        # Title
        title_tag = item.select_one('.ar-title a')
        if title_tag:
            doc['title'] = title_tag.text.strip()
            doc['link'] = title_tag.get('href')
        else:
            title_div = item.select_one('.ar-title')
            if title_div:
                doc['title'] = title_div.text.strip()
            else:
                continue
        
        # Meta info
        quartile = item.select_one('.ar-quartile')
        if quartile:
            doc['type'] = quartile.text.strip()
        
        pub = item.select_one('.ar-pub')
        if pub:
            doc['journal'] = pub.text.strip()
            
        # Year
        year_tag = item.select_one('.ar-year')
        if year_tag:
            doc['year'] = year_tag.text.strip()
        
        # Cited
        cited_tag = item.select_one('.ar-cited')
        if cited_tag:
            doc['cited'] = cited_tag.text.strip()
        
        # TAMBAHAN: Scrape citations jika diminta
        if fetch_citations and doc.get('link'):
            print(f"Fetching citations for: {doc['title'][:50]}...")
            doc['citations'] = scrape_citations(doc['link'])
            doc['citations_count'] = len(doc['citations'])
        
        docs.append(doc)
        
    return docs

def scrape_category_all_pages(sinta_id, category, fetch_citations=False):
    all_docs = []
    for page in range(1, MAX_PAGES + 1):
        docs = scrape_documents_page(sinta_id, category, page, fetch_citations)
        if not docs:
            break
        all_docs.extend(docs)
        
    return all_docs

def scrape_sinta_profile(sinta_id, fetch_citations=False):
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

    # 4. Scrape Documents
    try:
        with concurrent.futures.ThreadPoolExecutor(max_workers=4) as executor:
            future_to_cat = {
                executor.submit(scrape_category_all_pages, sinta_id, cat, fetch_citations): cat 
                for cat in CATEGORIES
            }
            for future in concurrent.futures.as_completed(future_to_cat):
                cat = future_to_cat[future]
                try:
                    docs = future.result()
                    data['documents'].extend(docs)
                except Exception as exc:
                    pass
    except Exception as e:
        data['error_documents'] = str(e)

    return data

if __name__ == "__main__":
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    target_id = "6711412"
    fetch_citations = False  # Set True untuk mengambil citations
    
    if len(sys.argv) > 1:
        target_id = sys.argv[1]
    
    # Tambahan: opsi untuk fetch citations via argument
    if len(sys.argv) > 2 and sys.argv[2] == '--citations':
        fetch_citations = True
        print("Mode: Fetching citations enabled (akan lebih lama)")
    
    result = scrape_sinta_profile(target_id, fetch_citations)
    print(json.dumps(result, indent=2))