import os, re, zipfile, html
from pathlib import Path
import xml.etree.ElementTree as ET

DOCX = Path(r"C:\Users\sansm\Downloads\DuongDinhManhTH28.31\DuongDinhManhTH28.31-CNN4.0.docx")
OUT = Path(r"D:\f\Đồ Án tổng hợp\DuongDinhManh_TH28.31\docs\packet_tracer_extraction")
OUT.mkdir(parents=True, exist_ok=True)
NS = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'a': 'http://schemas.openxmlformats.org/drawingml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
    'wp': 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
    'pic': 'http://schemas.openxmlformats.org/drawingml/2006/picture',
}

def text_from_elem(elem):
    parts = []
    for node in elem.iter():
        tag = node.tag.split('}', 1)[-1]
        if tag == 't' and node.text:
            parts.append(node.text)
        elif tag == 'tab':
            parts.append('\t')
        elif tag in ('br', 'cr'):
            parts.append('\n')
    return ''.join(parts).strip()

def para_style(p):
    pPr = p.find('w:pPr', NS)
    if pPr is None:
        return ''
    pStyle = pPr.find('w:pStyle', NS)
    if pStyle is None:
        return ''
    return pStyle.attrib.get('{%s}val' % NS['w'], '')

def cell_text(tc):
    paras = []
    for p in tc.findall('.//w:p', NS):
        t = text_from_elem(p)
        if t:
            paras.append(t)
    return ' / '.join(paras).strip()

lines = []
image_files = []
with zipfile.ZipFile(DOCX) as z:
    document_xml = z.read('word/document.xml')
    root = ET.fromstring(document_xml)
    body = root.find('w:body', NS)
    table_index = 0
    for child in list(body):
        tag = child.tag.split('}', 1)[-1]
        if tag == 'p':
            t = text_from_elem(child)
            if t:
                style = para_style(child)
                if style.lower().startswith('heading') or style.lower().startswith('title'):
                    lines.append(f"\n## {t}")
                else:
                    lines.append(t)
        elif tag == 'tbl':
            table_index += 1
            rows = []
            for tr in child.findall('w:tr', NS):
                row = [cell_text(tc) for tc in tr.findall('w:tc', NS)]
                if any(row):
                    rows.append(row)
            if rows:
                lines.append(f"\n### [TABLE {table_index}]")
                max_cols = max(len(r) for r in rows)
                padded = [r + [''] * (max_cols - len(r)) for r in rows]
                header = padded[0]
                lines.append('| ' + ' | '.join(c.replace('|','/') or ' ' for c in header) + ' |')
                lines.append('| ' + ' | '.join(['---'] * max_cols) + ' |')
                for r in padded[1:]:
                    lines.append('| ' + ' | '.join(c.replace('|','/') or ' ' for c in r) + ' |')
    for name in z.namelist():
        if name.startswith('word/media/'):
            data = z.read(name)
            dest = OUT / Path(name).name
            dest.write_bytes(data)
            image_files.append(dest.name)

text = '\n\n'.join(lines)
# Keep markdown readable: collapse long blank runs.
text = re.sub(r'\n{3,}', '\n\n', text)
md = OUT / 'report_extracted.md'
md.write_text('# Extracted Packet Tracer Report Content\n\nSource: ' + str(DOCX) + '\n\n' + text + '\n\n## Extracted Images\n\n' + '\n'.join(f'- {n}' for n in image_files) + '\n', encoding='utf-8')
print(f"DOCX exists: {DOCX.exists()}")
print(f"Extracted markdown: {md}")
print(f"Paragraph/table lines: {len(lines)}")
print(f"Images extracted: {len(image_files)}")
for n in image_files[:20]:
    print(f"IMAGE {n}")
