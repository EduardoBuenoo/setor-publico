import glob
import re

for f in glob.glob('*.html'):
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # 1. Fix sidebar logo path
    content = content.replace('src="/assets/img/logo_prefeitura.png"', 'src="assets/img/logo_prefeitura.png"')
    
    # 2. Remove the header logo div and restore the h2 title
    # The pattern is:
    # <div style="display: flex; align-items: center; gap: 1rem;">
    #     <img src="assets/img/logo_prefeitura.png" alt="Logo Prefeitura" style="height: 40px; width: auto;">
    #     <h2 class="page-title" style="margin: 0;">TITLE</h2>
    # </div>
    
    # I'll use regex to match this block
    pattern = r'<div style="display: flex; align-items: center; gap: 1rem;">\s*<img src="[^"]*" alt="Logo Prefeitura" style="height: 40px; width: auto;">\s*<h2 class="page-title" style="margin: 0;">(.*?)</h2>\s*</div>'
    content = re.sub(pattern, r'<h2 class="page-title">\1</h2>', content)
    
    with open(f, 'w', encoding='utf-8') as file:
        file.write(content)
