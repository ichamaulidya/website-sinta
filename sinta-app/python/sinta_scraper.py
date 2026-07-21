import requests
from bs4 import BeautifulSoup
import json
import sys
import re
import time
import concurrent.futures

# Konfigurasi
MAX_PAGES = 10
CATEGORIES = ['scopus', 'garuda', 'wos', 'ipr']

# ✅ FIX: sleep dikurangi dari 2s → 0.3s
REQUEST_DELAY = 0.3

def get_soup(url):
    headers = {
        # Menggunakan Cookie lama dari file aslimu
        'Cookie': 'ci_session=592l7v6pkatmsa8k4eqfds1q4060tqg4',
        # Menggunakan User-Agent lama dari file aslimu
        'User-Agent': 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Referer': 'https://sinta.kemdiktisaintek.go.id',
    }
    try:
        # ✅ FIX: sleep dikurangi drastis, cukup untuk hindari rate-limit
        time.sleep(REQUEST_DELAY)

        response = requests.get(url, headers=headers, verify=False, timeout=20)
        response.raise_for_status()
        return BeautifulSoup(response.content, 'html.parser')
    except Exception as e:
        print(f"[WARN] get_soup error: {e} | url: {url}", file=sys.stderr)
        return None

def normalize_title(title):
    if not title:
        return ""
    title = title.lower().strip()
    title = re.sub(r'\s+', ' ', title)
    return title

def build_google_scholar_cache(sinta_id):
    """Build cache sitasi GS — dijalankan paralel dengan kategori lain."""
    cache = {}
    try:
        print(f"[INFO] Building Google Scholar cache...", file=sys.stderr)
        for page in range(1, MAX_PAGES + 1):
            url = f"https://sinta.kemdiktisaintek.go.id/authors/profile/{sinta_id}/?page={page}&view=googlescholar"
            soup = get_soup(url)

            if not soup:
                break

            doc_items = soup.find_all('div', class_='ar-list-item')
            if not doc_items:
                break  # ✅ FIX: stop langsung kalau sudah kosong, tidak lanjut ke page berikutnya

            for item in doc_items:
                title_tag = item.select_one('.ar-title a') or item.select_one('.ar-title')
                if not title_tag:
                    continue
                title = title_tag.text.strip()
                cited_tag = item.select_one('.ar-cited')
                cache[title] = cited_tag.text.strip() if cited_tag else "0"

        print(f"[INFO] GS cache selesai: {len(cache)} dokumen", file=sys.stderr)
    except Exception as e:
        print(f"[WARN] Error build GS cache: {e}", file=sys.stderr)
    return cache

def scrape_documents_page(sinta_id, category, page, gs_citations_cache=None):
    view_map = {'scopus': 'scopus', 'garuda': 'garuda', 'wos': 'wos', 'ipr': 'ipr'}
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

        quartile = item.select_one('.ar-quartile')
        if quartile:
            doc['type'] = quartile.text.strip()

        pub = item.select_one('.ar-pub')
        if pub:
            doc['journal'] = pub.text.strip()
            if category == 'ipr' and 'type' not in doc:
                doc['type'] = pub.text.strip()

        year_tag = item.select_one('.ar-year')
        if year_tag:
            doc['year'] = year_tag.text.strip()

        if category == 'garuda':
            title_key = doc.get('title', '')
            if gs_citations_cache and title_key in gs_citations_cache:
                doc['cited'] = gs_citations_cache[title_key]
            else:
                doc['cited'] = "0"
            doc['cited_source'] = 'google_scholar'
        elif category == 'ipr':
            doc['cited'] = "0"
        else:
            cited_tag = item.select_one('.ar-cited')
            if cited_tag:
                doc['cited'] = cited_tag.text.strip()

        docs.append(doc)

    return docs

def scrape_category_all_pages(sinta_id, category, gs_cache=None):
    all_docs = []
    empty_streak = 0  # ✅ FIX: stop lebih cepat kalau 2 halaman berturut kosong

    for page in range(1, MAX_PAGES + 1):
        docs = scrape_documents_page(sinta_id, category, page, gs_cache)
        if not docs:
            empty_streak += 1
            if empty_streak >= 2:
                break
        else:
            empty_streak = 0
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

    # ✅ FIX: GS cache dan kategori lain dijalankan PARALEL sekaligus
    # bukan GS cache dulu → baru scrape kategori
    try:
        with concurrent.futures.ThreadPoolExecutor(max_workers=5) as executor:
            # Submit GS cache builder sebagai task terpisah
            gs_future = executor.submit(build_google_scholar_cache, sinta_id)

            # Submit semua kategori non-garuda langsung (tidak perlu tunggu GS cache)
            non_garuda_futures = {
                executor.submit(scrape_category_all_pages, sinta_id, cat, None): cat
                for cat in CATEGORIES if cat != 'garuda'
            }

            # Tunggu GS cache selesai, lalu submit garuda
            gs_cache = gs_future.result()
            garuda_future = executor.submit(scrape_category_all_pages, sinta_id, 'garuda', gs_cache)

            # Kumpulkan hasil non-garuda
            for future in concurrent.futures.as_completed(non_garuda_futures):
                cat = non_garuda_futures[future]
                try:
                    docs = future.result()
                    data['documents'].extend(docs)
                    print(f"[INFO] {cat}: {len(docs)} dokumen", file=sys.stderr)
                except Exception as exc:
                    print(f"[WARN] Error scraping {cat}: {exc}", file=sys.stderr)

            # Kumpulkan hasil garuda
            try:
                garuda_docs = garuda_future.result()
                data['documents'].extend(garuda_docs)
                print(f"[INFO] garuda: {len(garuda_docs)} dokumen", file=sys.stderr)
            except Exception as exc:
                print(f"[WARN] Error scraping garuda: {exc}", file=sys.stderr)

    except Exception as e:
        data['error_documents'] = str(e)

    return data

if __name__ == "__main__":
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

    target_id = "6711412"
    if len(sys.argv) > 1:
        target_id = sys.argv[1]

    result = scrape_sinta_profile(target_id)
    print(json.dumps(result, indent=2))