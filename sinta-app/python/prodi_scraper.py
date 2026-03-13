import requests
from bs4 import BeautifulSoup
import json
import sys
import re
import time

def get_soup(url):
    headers = {
        'Cookie': '_ga=GA1.1.2145658494.1770084967; _ga_YZBSYK71LL=GS2.1.s1771899418$o3$g1$t1771904566$j60$l0$h0; ci_session=ur9h3t9rniu4vhm1j0puqeleo33b0eus',
        'User-Agent': 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Referer': 'https://sinta.kemdiktisaintek.go.id',
        
    }
    try:
        time.sleep(1)
        response = requests.get(url, headers=headers, verify=False, timeout=20)
        response.raise_for_status()
        return BeautifulSoup(response.content, 'html.parser')
    except Exception:
        return None

def scrape_prodi_ipr(dept_id):
    all_documents = []
    page = 1
    
    while True:
        # Looping halaman secara dinamis
        url = f"https://sinta.kemdiktisaintek.go.id/departments/profile/{dept_id}/?page={page}&view=iprs"
        print(f"Scraping page {page}...", file=sys.stderr)

        soup = get_soup(url)
        if not soup:
            break

        items = soup.find_all('div', class_='ar-list-item')
        if not items:
            items = soup.select('.list-item')

        if not items:
            break

        for item in items:
            doc = {'source': 'ipr'}
            
            # 1. Cari Judul
            title_tag = item.find('a')
            if title_tag:
                doc['title'] = title_tag.text.strip()
                doc['link'] = title_tag.get('href')
            else:
                title_div = item.select_one('.ar-title')
                if title_div:
                    doc['title'] = title_div.text.strip()
                else:
                    continue

            # 2. Ambil Inventor
            item_text = item.get_text()
            if "Inventor :" in item_text:
                # Mengambil teks di antara 'Inventor :' dan baris baru berikutnya
                inventor_part = item_text.split("Inventor :")[1].split("\n")[0].strip()
                doc['inventor'] = inventor_part
            else:
                doc['inventor'] = "-"
                
            # 3. Cari Jenis (Hak Cipta / Paten)
            if 'Hak Cipta' in item_text:
                doc['type'] = 'Hak Cipta'
            elif 'Paten' in item_text:
                doc['type'] = 'Paten'
            elif 'Merk' in item_text:
                doc['type'] = 'Merk'
            else:
                doc['type'] = "HKI"
                
            # 4. Cari Tahun
            year_match = re.search(r'\b(20\d{2})\b', item_text)
            doc['year'] = year_match.group(1) if year_match else None
                
            all_documents.append(doc)
        
        # Cek Pagination
        pagination = soup.find('ul', class_='pagination')
        if not pagination:
            break
            
        next_button = pagination.find('li', class_='next')
        if not next_button or 'disabled' in next_button.get('class', []):
            break # Berhenti jika tidak ada tombol 'Next'
        
        page += 1 # Lanjut ke halaman berikutnya

    return {
        "dept_id": dept_id,
        "total_scraped": len(all_documents),
        "documents": all_documents
    }

if __name__ == "__main__":
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    dept_id = sys.argv[1] if len(sys.argv) > 1 else "428/828FB966-3733-430E-86FF-909B764E2523/CF6DA465-2847-4446-9994-49CCF4E5382D"
    result = scrape_prodi_ipr(dept_id)
    print(json.dumps(result, indent=2))