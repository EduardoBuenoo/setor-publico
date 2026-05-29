import glob
import re
import os

for f in glob.glob('*.html'):
    if f == 'atividades.html': continue
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # Remove the Atividades line
    content = re.sub(r'<li><a href="atividades\.html" class="nav-link.*?><i class="fa-solid fa-list-check"></i> Atividades</a></li>', '', content)
    
    with open(f, 'w', encoding='utf-8') as file:
        file.write(content)

if os.path.exists('atividades.html'):
    os.remove('atividades.html')

