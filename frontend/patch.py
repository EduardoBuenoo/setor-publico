import glob
for f in glob.glob('*.html'):
    if f == 'atividades.html': continue
    content = open(f, 'r', encoding='utf-8').read()
    content = content.replace('<li><a href="indicadores.html" class="nav-link"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>', '<li><a href="indicadores.html" class="nav-link"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>\n                <li><a href="atividades.html" class="nav-link"><i class="fa-solid fa-list-check"></i> Atividades</a></li>')
    content = content.replace('<li><a href="indicadores.html" class="nav-link active"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>', '<li><a href="indicadores.html" class="nav-link active"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>\n                <li><a href="atividades.html" class="nav-link"><i class="fa-solid fa-list-check"></i> Atividades</a></li>')
    open(f, 'w', encoding='utf-8').write(content)
