#!/usr/bin/env python3
from bs4 import BeautifulSoup
import json
import re

def parse_date_range(date_str):
    date_str = date_str.replace('\n', '').replace('\r', '').strip()
    if re.match(r'\d{2}\.\d{2}\.\d{4}\d{2}\.\d{2}\.\d{4}', date_str):
        mid = len(date_str) // 2
        return date_str[:10], date_str[10:]
    match = re.match(r'(\d{1,2})-(\d{1,2})\.(\d{2})\.(\d{4})', date_str)
    if match:
        d1, d2, m, y = match.groups()
        return f"{y}-{m}-{d1.zfill(2)}", f"{y}-{m}-{d2.zfill(2)}"
    match = re.match(r'(\d{2})\.(\d{2})\.(\d{4})', date_str)
    if match:
        d, m, y = match.groups()
        return f"{y}-{m}-{d}", f"{y}-{m}-{d}"
    return None, None

with open('/tmp/rg4u_2025.html', 'r', encoding='utf-8') as f:
    soup = BeautifulSoup(f.read(), 'html.parser')

tournaments = []
for row in soup.find_all('tr')[3:]:
    tds = row.find_all(['td', 'th'])
    if len(tds) >= 4:
        cells = [td.get_text(strip=True) for td in tds]
        if cells[0] and re.match(r'\d{2}\.', cells[0][:3]):
            d1, d2 = parse_date_range(cells[0])
            if d1:
                tournaments.append({
                    'name': cells[2][:255],
                    'date_start': d1,
                    'date_end': d2 or d1,
                    'city': cells[1],
                })

with open('/tmp/tournaments_rg4u.json', 'w', encoding='utf-8') as f:
    json.dump(tournaments, f, ensure_ascii=False, indent=2)

print(f"Saved {len(tournaments)} tournaments")
for t in tournaments[:3]:
    print(f"  {t['date_start']}: {t['name'][:50]}... ({t['city']})")
