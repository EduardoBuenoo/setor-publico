<?php
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT u.*, s.nome_setor FROM usuarios u LEFT JOIN setores s ON u.id_setor = s.id WHERE u.id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'cadastrar_indicador') {
            $nome = trim($_POST['nome_indicador']);
            $tipo = $_POST['tipo_indicador'];
            
            // Integração com API Django
            $data = json_encode(['nome' => $nome, 'tipo' => $tipo]);
            $ch = curl_init('http://127.0.0.1:8000/indicadores/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            ]);
            curl_exec($ch);
            curl_close($ch);

            header("Location: indicadores.php?success=Indicador cadastrado com sucesso via API");
            exit;
        } elseif ($action === 'lancar_valor') {
            $id_indicador = $_POST['id_indicador'];
            $valor = $_POST['valor'];
            $data_registro = $_POST['data_registro'];
            $id_usuario = $_SESSION['usuario_id']; 
            
            $stmt_insert = $pdo->prepare("INSERT INTO indicadores_valores (id_indicador, valor, data_registro, tipo_registro, id_usuario) VALUES (?, ?, ?, 'lancamento', ?)");
            $stmt_insert->execute([$id_indicador, $valor, $data_registro, $id_usuario]);
            header("Location: indicadores.php?success=Valor lançado com sucesso");
            exit;
        } elseif ($action === 'ajustar_valor' && $user['nivel_acesso'] === 'Administrador') {
            $id_indicador = $_POST['id_indicador'];
            $novo_valor = $_POST['novo_valor'];
            $data_registro = $_POST['data_registro'];
            $id_usuario = $_SESSION['usuario_id'];
            
            $stmt_insert = $pdo->prepare("INSERT INTO indicadores_valores (id_indicador, valor, data_registro, tipo_registro, id_usuario) VALUES (?, ?, ?, 'ajuste', ?)");
            $stmt_insert->execute([$id_indicador, $novo_valor, $data_registro, $id_usuario]);
            header("Location: indicadores.php?success=Ajuste realizado com sucesso");
            exit;
        } elseif ($action === 'excluir_indicador' && $user['nivel_acesso'] === 'Administrador') {
            $id_indicador = $_POST['id_indicador'];
            
            // First delete associated values
            $stmt_delete_valores = $pdo->prepare("DELETE FROM indicadores_valores WHERE id_indicador = ?");
            $stmt_delete_valores->execute([$id_indicador]);
            
            // Then delete the indicator
            $stmt_delete = $pdo->prepare("DELETE FROM indicadores WHERE id = ?");
            $stmt_delete->execute([$id_indicador]);
            
            header("Location: indicadores.php?success=Indicador excluído com sucesso");
            exit;
        }
    }
}

// Fetch Indicators via API
$ch = curl_init('http://127.0.0.1:8000/indicadores/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$indicadores = json_decode($response, true) ?? [];
usort($indicadores, function($a, $b) { return strcmp($a['nome'], $b['nome']); });
// Fetch all values and calculate totals
$valores_query = $pdo->query("SELECT iv.*, u.nome as responsavel FROM indicadores_valores iv JOIN usuarios u ON iv.id_usuario = u.id ORDER BY data_registro ASC, iv.id ASC")->fetchAll(PDO::FETCH_ASSOC);

$totais = [];
$datas_ultima = [];
$historico = [];
$producao_usuarios = [];

foreach ($valores_query as $v) {
    $id_ind = $v['id_indicador'];
    if (!isset($totais[$id_ind])) {
        $totais[$id_ind] = 0;
        $producao_usuarios[$id_ind] = [];
    }
    
    if ($v['tipo_registro'] === 'ajuste') {
        $totais[$id_ind] = (float)$v['valor'];
    } else {
        $totais[$id_ind] += (float)$v['valor'];
        
        $resp = $v['responsavel'];
        if (!isset($producao_usuarios[$id_ind][$resp])) {
            $producao_usuarios[$id_ind][$resp] = 0;
        }
        $producao_usuarios[$id_ind][$resp] += (float)$v['valor'];
    }
    
    $v['saldo_momento'] = $totais[$id_ind];
    $historico[$id_ind][] = $v;
    $datas_ultima[$id_ind] = $v['data_registro'];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores | SIGDEI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }
        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            color: var(--text-color);
            background: rgba(255, 255, 255, 0.1);
        }
        .tab-btn.active {
            color: var(--primary-color);
            background: #ffffff;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .grid-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        .modal-box {
            background: var(--bg-color);
            color: #333;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-box {
            transform: scale(1);
        }
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
        }
        .close-btn:hover { color: #ef4444; }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            transition: background 0.2s;
        }
        .action-btn:hover { background: rgba(255,255,255,0.2); }
        
        table.light-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        table.light-table th, table.light-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        table.light-table th { background: #f1f5f9; color: #475569; font-weight: 600; }
        
        .grafico-container {
            width: 100%;
            height: 300px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fa-solid fa-building" style="color: var(--status-green);"></i> SIGDEI
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Painel de Controle</a></li>
                <li><a href="oficios.php" class="nav-link"><i class="fa-solid fa-file-signature"></i> Ofícios</a></li>
                <li><a href="projetos.php" class="nav-link"><i class="fa-solid fa-project-diagram"></i> Projetos</a></li>
                <li><a href="indicadores.php" class="nav-link active"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>
                <?php if ($user['nivel_acesso'] === 'Administrador' || $user['nivel_acesso'] === 'Gestor'): ?>
                <li><a href="usuarios.php" class="nav-link"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <?php endif; ?>
                <li><a href="configuracoes.php" class="nav-link"><i class="fa-solid fa-gear"></i> Configurações</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="header glass" style="padding: 1rem 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h2 class="page-title">Módulo de Indicadores</h2>
                <div class="user-profile">
                    <div class="user-info" style="text-align: right;">
                        <h4><?= htmlspecialchars($user['nome']) ?></h4>
                        <p><?= htmlspecialchars($user['nome_setor']) ?> | <?= htmlspecialchars($user['nivel_acesso']) ?></p>
                    </div>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem;">
                        <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                    </div>
                    <a href="logout.php" style="color: var(--text-muted); margin-left: 1rem;" title="Sair"><i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('visualizacao')">Painel de Indicadores</button>
                <button class="tab-btn" onclick="openTab('lancamento')">Lançar Valores (Soma)</button>
                <button class="tab-btn" onclick="openTab('cadastro')">Cadastrar Novo</button>
            </div>

            <!-- TAB 1: VISUALIZAÇÃO -->
            <div id="tab-visualizacao" class="tab-content active glass" style="padding: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0;"><i class="fa-solid fa-bar-chart"></i> Desempenho Atual</h3>
                    <button class="btn btn-primary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.9rem; background-color: var(--status-green);" onclick="openTab('cadastro')">
                        <i class="fa-solid fa-plus"></i> Novo Indicador
                    </button>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome do Indicador</th>
                                <th>Tipo</th>
                                <th>Total Acumulado</th>
                                <th>Última Atualização</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($indicadores as $ind): 
                                $id = $ind['id'];
                                $sufixo = $ind['tipo'] === 'Porcentagem' ? '%' : ($ind['tipo'] === 'Horários' ? 'h' : '');
                                $total = isset($totais[$id]) ? $totais[$id] : 0;
                                $ultima = isset($datas_ultima[$id]) ? date('d/m/Y', strtotime($datas_ultima[$id])) : '-';
                                $hist_json = htmlspecialchars(json_encode(isset($historico[$id]) ? $historico[$id] : []));
                                $prod_json = htmlspecialchars(json_encode(isset($producao_usuarios[$id]) ? $producao_usuarios[$id] : []));
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ind['nome']) ?></strong></td>
                                <td><span class="status-tag status-andamento"><?= htmlspecialchars($ind['tipo']) ?></span></td>
                                <td style="font-size: 1.2rem; font-weight: bold;"><?= $total . $sufixo ?></td>
                                <td><?= $ultima ?></td>
                                <td>
                                    <button class="action-btn" style="color: var(--status-grey);" title="Ver Gráfico e Histórico" onclick="abrirModalHistorico(<?= $id ?>, '<?= htmlspecialchars($ind['nome'], ENT_QUOTES) ?>', '<?= $sufixo ?>', <?= $hist_json ?>, <?= $prod_json ?>)">
                                        <i class="fa-solid fa-chart-line"></i>
                                    </button>
                                    <?php if($user['nivel_acesso'] === 'Administrador'): ?>
                                    <button class="action-btn" style="color: var(--status-grey);" title="Ajustar Manualmente" onclick="abrirModalEdicao(<?= $id ?>, <?= $total ?>)">
                                        <i class="fa-solid fa-cog"></i>
                                    </button>
                                    <form method="POST" action="indicadores.php" style="display: inline-block; margin: 0;" onsubmit="return confirm('Tem certeza que deseja excluir este indicador e todo o seu histórico? Esta ação não pode ser desfeita.');">
                                        <input type="hidden" name="action" value="excluir_indicador">
                                        <input type="hidden" name="id_indicador" value="<?= $id ?>">
                                        <button type="submit" class="action-btn" style="color: var(--status-grey);" title="Excluir Indicador">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($indicadores) === 0): ?>
                            <tr><td colspan="5" style="text-align: center;">Nenhum indicador cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: LANÇAMENTO -->
            <div id="tab-lancamento" class="tab-content glass" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-plus-circle"></i> Lançar Atividade</h3>
                <form method="POST" action="indicadores.php">
                    <input type="hidden" name="action" value="lancar_valor">
                    <div class="grid-form">
                        <div class="form-group">
                            <label class="form-label" for="id_indicador">Selecione o Indicador</label>
                            <select name="id_indicador" class="form-control" required>
                                <option value="" disabled selected>Escolha um Indicador...</option>
                                <?php foreach($indicadores as $ind): ?>
                                    <option value="<?= $ind['id'] ?>"><?= htmlspecialchars($ind['nome']) ?> (<?= $ind['tipo'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="valor">Quantidade Adicionada</label>
                            <input type="number" step="0.01" name="valor" class="form-control" placeholder="Ex: 15" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="data_registro">Data do Lançamento</label>
                            <input type="date" name="data_registro" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto; margin-top: 1rem; background: var(--status-green);"><i class="fa-solid fa-check"></i> Adicionar ao Total</button>
                </form>
            </div>

            <!-- TAB 3: CADASTRO -->
            <div id="tab-cadastro" class="tab-content glass" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-folder-plus"></i> Cadastrar Novo Indicador</h3>
                <form method="POST" action="indicadores.php">
                    <input type="hidden" name="action" value="cadastrar_indicador">
                    <div class="grid-form">
                        <div class="form-group">
                            <label class="form-label" for="nome_indicador">Nome do Indicador</label>
                            <input type="text" name="nome_indicador" class="form-control" placeholder="Ex: Atendimentos Diários" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="tipo_indicador">Tipo de Dado</label>
                            <select name="tipo_indicador" class="form-control" required>
                                <option value="Quantidade">Quantidade</option>
                                <option value="Horários">Horários</option>
                                <option value="Porcentagem">Porcentagem (%)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto; margin-top: 1rem;"><i class="fa-solid fa-save"></i> Salvar Indicador</button>
                </form>
            </div>
            
        </main>
    </div>

    <!-- MODAL HISTÓRICO -->
    <div id="modal-historico" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3 id="modal-titulo" style="font-size: 1.25rem; font-weight: bold; color: #1e3a8a;">Detalhes do Indicador</h3>
                    <p style="font-size: 0.85rem; color: #64748b;">Análise de produção e histórico de alterações</p>
                </div>
                <button class="close-btn" onclick="fecharModal('modal-historico')"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="grafico-container">
                    <h4 style="text-align: center; margin-bottom: 1rem; color: #333;"><i class="fa-solid fa-users"></i> Produção por Colaborador</h4>
                    <canvas id="graficoHistorico"></canvas>
                </div>
                
                <h4 style="margin-bottom: 1rem; color: #333;"><i class="fa-solid fa-list"></i> Histórico Detalhado</h4>
                <table class="light-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Responsável</th>
                            <th>Ação</th>
                            <th style="text-align: right;">Valor</th>
                            <th style="text-align: right;">Saldo Final</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-historico"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL EDIÇÃO/AJUSTE -->
    <div id="modal-edicao" class="modal-overlay">
        <div class="modal-box" style="max-width: 400px;">
            <div class="modal-header" style="background: #fefce8; border-bottom-color: #fef08a;">
                <h3 style="font-size: 1.1rem; font-weight: bold; color: #854d0e;"><i class="fa-solid fa-cog"></i> Ajuste Manual</h3>
                <button class="close-btn" onclick="fecharModal('modal-edicao')"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1rem;">Insira o novo valor total deste indicador. Ficará registrado no histórico como um Ajuste pelo Administrador.</p>
                <form method="POST" action="indicadores.php">
                    <input type="hidden" name="action" value="ajustar_valor">
                    <input type="hidden" name="id_indicador" id="ajusteIdIndicador">
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; color: #333;">Novo Saldo Total:</label>
                        <input type="number" step="0.01" name="novo_valor" id="ajusteValor" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1.2rem; font-weight: bold;" required>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: #333;">Data do Ajuste:</label>
                        <input type="date" name="data_registro" id="ajusteData" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;" required>
                    </div>
                    <button type="submit" style="width: 100%; background: #686660; color: white; border: none; padding: 0.75rem; border-radius: 8px; font-weight: bold; cursor: pointer;">
                        <i class="fa-solid fa-check"></i> Confirmar Alteração
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById('tab-' + tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        let graficoModalInstancia = null;

        function abrirModalHistorico(id, nome, sufixo, historico, producao) {
            document.getElementById('modal-titulo').innerText = nome;
            
            const tbody = document.getElementById('tabela-historico');
            tbody.innerHTML = '';
            
            if (historico.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum registro encontrado.</td></tr>';
            } else {
                historico.forEach(v => {
                    const isAjuste = v.tipo_registro === 'ajuste';
                    const acaoTag = isAjuste 
                        ? '<span style="color:#d97706; font-weight:bold; font-size:0.8rem;"><i class="fa-solid fa-cog"></i> Ajuste</span>'
                        : '<span style="color:#16a34a; font-weight:bold; font-size:0.8rem;"><i class="fa-solid fa-plus"></i> Adição</span>';
                    
                    const p = v.data_registro.split('-');
                    const dataFormatada = `${p[2]}/${p[1]}/${p[0]}`;
                    
                    const displayLancado = isAjuste ? `<span style="font-size:0.8rem; color:#94a3b8;">Definido:</span> ${v.valor}` : `+${v.valor}`;
                    
                    tbody.innerHTML += `
                        <tr>
                            <td>${dataFormatada}</td>
                            <td><span style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight:bold;">${v.responsavel}</span></td>
                            <td>${acaoTag}</td>
                            <td style="text-align: right; font-weight: bold;">${displayLancado}${isAjuste ? '' : sufixo}</td>
                            <td style="text-align: right; color: #1e40af; font-weight: bold; background: #eff6ff;">${v.saldo_momento}${sufixo}</td>
                        </tr>
                    `;
                });
            }
            
            // Renderizar Gráfico
            const ctx = document.getElementById('graficoHistorico').getContext('2d');
            if (graficoModalInstancia) graficoModalInstancia.destroy();
            
            const autores = Object.keys(producao);
            const quantidades = Object.values(producao);
            
            if (autores.length === 0) {
                graficoModalInstancia = new Chart(ctx, {
                    type: 'bar',
                    data: { labels: ['Sem dados'], datasets: [{ data: [0], backgroundColor: '#e2e8f0' }] },
                    options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            } else {
                const barColors = ['rgba(236, 72, 153, 0.7)', 'rgba(59, 130, 246, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(245, 158, 11, 0.7)'];
                
                graficoModalInstancia = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: autores,
                        datasets: [{
                            label: 'Lançamentos',
                            data: quantidades,
                            backgroundColor: barColors,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { 
                            y: { 
                                beginAtZero: true,
                                ticks: { callback: function(value) { return value + sufixo; } }
                            } 
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }
            
            document.getElementById('modal-historico').classList.add('active');
        }

        function abrirModalEdicao(id, valorAtual) {
            document.getElementById('ajusteIdIndicador').value = id;
            document.getElementById('ajusteValor').value = valorAtual;
            document.getElementById('ajusteData').value = new Date().toISOString().split('T')[0];
            
            document.getElementById('modal-edicao').classList.add('active');
        }

        function fecharModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
    </script>
</body>
</html>
