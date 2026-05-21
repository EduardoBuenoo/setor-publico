<?php
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$id_setor = $_SESSION['id_setor'];
$nivel_acesso = $_SESSION['nivel_acesso'];

// Handle new project submission and tasks operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nome_projeto'])) {
        $nome_projeto = trim($_POST['nome_projeto']);
        $data_inicio = $_POST['data_inicio'];
        $data_final = $_POST['data_final'];
        $status = $_POST['status'];
        $prioridade = $_POST['prioridade'];
        $id_responsavel = $_POST['id_responsavel'];
        
        // For admin, allow selecting sector, otherwise use current user's sector
        $projeto_id_setor = ($nivel_acesso === 'Administrador' && isset($_POST['id_setor'])) ? $_POST['id_setor'] : $id_setor;

        $stmt_insert = $pdo->prepare("INSERT INTO projetos (nome_projeto, data_inicio, data_final, status, prioridade, id_responsavel, id_setor) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->execute([$nome_projeto, $data_inicio, $data_final, $status, $prioridade, $id_responsavel, $projeto_id_setor]);
        
        header("Location: projetos.php?success=1");
        exit;
    } elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'adicionar_tarefa') {
            $id_projeto = $_POST['id_projeto'];
            $nome_tarefa = trim($_POST['nome_tarefa']);
            $responsavel_tarefa = trim($_POST['responsavel_tarefa']);
            $data_inicio_tarefa = $_POST['data_inicio_tarefa'];
            $data_final_tarefa = $_POST['data_final_tarefa'];
            
            $stmt_insert_tarefa = $pdo->prepare("INSERT INTO tarefas (id_projeto, nome_tarefa, responsavel, data_inicio, data_final) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert_tarefa->execute([$id_projeto, $nome_tarefa, $responsavel_tarefa, $data_inicio_tarefa, $data_final_tarefa]);
            
            header("Location: projetos.php?success=tarefa");
            exit;
        } elseif ($action === 'excluir_tarefa') {
            $id_tarefa = $_POST['id_tarefa'];
            
            $stmt_delete_tarefa = $pdo->prepare("DELETE FROM tarefas WHERE id = ?");
            $stmt_delete_tarefa->execute([$id_tarefa]);
            
            header("Location: projetos.php?success=tarefa_excluida");
            exit;
        } elseif ($action === 'alternar_conclusao') {
            $id_tarefa = $_POST['id_tarefa'];
            
            $stmt_toggle = $pdo->prepare("UPDATE tarefas SET concluida = NOT concluida WHERE id = ?");
            $stmt_toggle->execute([$id_tarefa]);
            
            header("Location: projetos.php?success=tarefa_atualizada");
            exit;
        }
    }
}

// Fetch list of projects
$query = "SELECT p.*, u.nome as responsavel, s.nome_setor 
          FROM projetos p 
          LEFT JOIN usuarios u ON p.id_responsavel = u.id 
          LEFT JOIN setores s ON p.id_setor = s.id";

if ($nivel_acesso !== 'Administrador') {
    $query .= " WHERE p.id_setor = " . intval($id_setor);
}
$query .= " ORDER BY p.data_inicio DESC";

$projetos = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch tasks for projects
$tarefas_query = $pdo->query("SELECT * FROM tarefas ORDER BY data_inicio ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$projetos_tarefas = [];
foreach ($tarefas_query as $t) {
    $pid = $t['id_projeto'];
    if (!isset($projetos_tarefas[$pid])) {
        $projetos_tarefas[$pid] = [];
    }
    $projetos_tarefas[$pid][] = [
        'id' => $t['id'],
        'nome_tarefa' => $t['nome_tarefa'],
        'responsavel' => $t['responsavel'],
        'data_inicio' => date('d/m/Y', strtotime($t['data_inicio'])),
        'data_final' => date('d/m/Y', strtotime($t['data_final'])),
        'raw_inicio' => $t['data_inicio'],
        'raw_final' => $t['data_final'],
        'concluida' => (bool)$t['concluida']
    ];
}

// Fetch possible responsaveis (users from the same sector or all if admin)
$q_users = "SELECT id, nome FROM usuarios";
if ($nivel_acesso !== 'Administrador') {
    $q_users .= " WHERE id_setor = " . intval($id_setor);
}
$responsaveis = $pdo->query($q_users)->fetchAll(PDO::FETCH_ASSOC);

// Fetch sectors for admin
$setores = [];
if ($nivel_acesso === 'Administrador') {
    $setores = $pdo->query("SELECT * FROM setores ORDER BY nome_setor")->fetchAll(PDO::FETCH_ASSOC);
}

// Helper function for status color
function getStatusClass($status) {
    switch ($status) {
        case 'Em andamento': return 'status-andamento';
        case 'Concluído': return 'status-concluido';
        case 'Atrasado': return 'status-atrasado';
        case 'Encerrado sem conclusão': return 'status-encerrado';
        default: return 'status-encerrado';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projetos | Plataforma Inteligente</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .grid-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .form-control { background: rgba(30, 41, 59, 0.8); }
        
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
            background: #f8fafc;
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
            background: #eff6ff;
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
            color: var(--text-color);
        }
        .action-btn:hover { background: rgba(255,255,255,0.2); }
        
        table.light-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        table.light-table th, table.light-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        table.light-table th { background: #f1f5f9; color: #475569; font-weight: 600; }
        table.light-table td { color: #334155; }
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
                <li><a href="projetos.php" class="nav-link active"><i class="fa-solid fa-project-diagram"></i> Projetos</a></li>
                <li><a href="indicadores.php" class="nav-link"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>
                <?php if ($nivel_acesso === 'Administrador' || $nivel_acesso === 'Gestor'): ?>
                <li><a href="usuarios.php" class="nav-link"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <?php endif; ?>
                <li><a href="configuracoes.php" class="nav-link"><i class="fa-solid fa-gear"></i> Configurações</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="header">
                <h2 class="page-title">Gerenciamento de Projetos</h2>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <?php if($_GET['success'] == '1'): ?>
                    <div class="alert alert-success">Projeto registrado com sucesso!</div>
                <?php elseif($_GET['success'] == 'tarefa'): ?>
                    <div class="alert alert-success">Tarefa adicionada com sucesso!</div>
                <?php elseif($_GET['success'] == 'tarefa_excluida'): ?>
                    <div class="alert alert-success">Tarefa excluída com sucesso!</div>
                <?php elseif($_GET['success'] == 'tarefa_atualizada'): ?>
                    <div class="alert alert-success">Status da tarefa atualizado com sucesso!</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">Cadastrar Novo Projeto</h3>
                <form method="POST" action="projetos.php">
                    <div class="grid-form">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="nome_projeto">Nome do Projeto</label>
                            <input type="text" id="nome_projeto" name="nome_projeto" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="data_inicio">Data de Início</label>
                            <input type="date" id="data_inicio" name="data_inicio" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="data_final">Data Final</label>
                            <input type="date" id="data_final" name="data_final" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="Em andamento">Em andamento (Amarelo)</option>
                                <option value="Concluído">Concluído (Verde)</option>
                                <option value="Atrasado">Atrasado (Vermelho)</option>
                                <option value="Encerrado sem conclusão">Encerrado sem conclusão (Cinza)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="prioridade">Prioridade</label>
                            <select id="prioridade" name="prioridade" class="form-control" required>
                                <option value="Alta">Alta</option>
                                <option value="Média">Média</option>
                                <option value="Baixa">Baixa</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="id_responsavel">Responsável</label>
                            <select id="id_responsavel" name="id_responsavel" class="form-control" required>
                                <?php foreach($responsaveis as $resp): ?>
                                    <option value="<?= $resp['id'] ?>"><?= htmlspecialchars($resp['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($nivel_acesso === 'Administrador'): ?>
                        <div class="form-group">
                            <label class="form-label" for="id_setor">Setor Responsável</label>
                            <select id="id_setor" name="id_setor" class="form-control" required>
                                <?php foreach($setores as $setor): ?>
                                    <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome_setor']) ?> (<?= $setor['sigla'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: auto; margin-top: 1rem;"><i class="fa-solid fa-save"></i> Salvar Projeto</button>
                </form>
            </div>

            <div class="glass" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">Lista de Projetos</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Projeto</th>
                                <th>Período</th>
                                <th>Status</th>
                                <th>Prioridade</th>
                                <th>Responsável</th>
                                <?php if ($nivel_acesso === 'Administrador'): ?><th>Setor</th><?php endif; ?>
                                <th style="text-align: center;">Tarefas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($projetos) > 0): ?>
                                <?php foreach($projetos as $proj): ?>
                                <?php 
                                    $id_proj = $proj['id'];
                                    $tarefas_proj = $projetos_tarefas[$id_proj] ?? [];
                                    $total_tarefas = count($tarefas_proj);
                                    $tarefas_concluidas = count(array_filter($tarefas_proj, function($t) { return $t['concluida']; }));
                                    $percentual = $total_tarefas > 0 ? round(($tarefas_concluidas / $total_tarefas) * 100) : 0;
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($proj['nome_projeto']) ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($proj['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($proj['data_final'])) ?></td>
                                    <td><span class="status-tag <?= getStatusClass($proj['status']) ?>"><?= htmlspecialchars($proj['status']) ?></span></td>
                                    <td><?= htmlspecialchars($proj['prioridade']) ?></td>
                                    <td><?= htmlspecialchars($proj['responsavel']) ?></td>
                                    <?php if ($nivel_acesso === 'Administrador'): ?>
                                    <td><span class="status-tag status-encerrado"><?= htmlspecialchars($proj['nome_setor']) ?></span></td>
                                    <?php endif; ?>
                                    <td style="text-align: center;">
                                        <button class="action-btn" style="color: #60a5fa; margin-right: 0.5rem;" title="Expandir Detalhes" onclick="toggleDetailsRow(<?= $proj['id'] ?>)">
                                            <i class="fa-solid fa-chevron-down fa-lg" id="chevron-<?= $proj['id'] ?>"></i>
                                        </button>
                                        <button class="action-btn" style="color: var(--status-yellow);" title="Ver e Gerenciar Tarefas" onclick="abrirModalTarefas(<?= $proj['id'] ?>, '<?= htmlspecialchars($proj['nome_projeto'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-list-check fa-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <tr id="details-row-<?= $proj['id'] ?>" class="details-row" style="display: none; background: rgba(15, 23, 42, 0.25);">
                                    <td colspan="<?= $nivel_acesso === 'Administrador' ? '7' : '6' ?>" style="padding: 1.5rem; border-top: none;">
                                        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.05);">
                                            
                                            <!-- Header do Painel -->
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                                                <div>
                                                    <h4 style="font-size: 1.2rem; font-weight: 600; color: #fff; margin-bottom: 0.25rem;">
                                                        <i class="fa-solid fa-folder-open" style="color: #60a5fa; margin-right: 0.5rem;"></i> <?= htmlspecialchars($proj['nome_projeto']) ?>
                                                    </h4>
                                                    <p style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.7);">Detalhamento do progresso e tarefas ativas</p>
                                                </div>
                                                
                                                <!-- Progresso Gráfico -->
                                                <div style="min-width: 250px; flex-grow: 0.5; max-width: 400px; background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.05);">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; font-size: 0.85rem;">
                                                        <span style="font-weight: 500; color: rgba(255, 255, 255, 0.9);">Progresso do Projeto</span>
                                                        <span style="font-weight: bold; color: var(--status-green);"><?= $tarefas_concluidas ?> / <?= $total_tarefas ?> Concluídas (<?= $percentual ?>%)</span>
                                                    </div>
                                                    <div class="progress-bar-container" style="background: rgba(255, 255, 255, 0.1); border-radius: 9999px; height: 10px; width: 100%; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.15);">
                                                        <div class="progress-bar-fill" style="background: linear-gradient(90deg, #3b82f6 0%, #10b981 100%); width: <?= $percentual ?>%; height: 100%; border-radius: 9999px; transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Tabela de Tarefas -->
                                            <h5 style="font-size: 0.95rem; font-weight: 600; color: #fff; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fa-solid fa-list-check" style="color: var(--status-yellow);"></i> Tabela de Tarefas do Projeto
                                            </h5>
                                            
                                            <div style="overflow-x: auto; background: rgba(15, 23, 42, 0.4); border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1);">
                                                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                                                    <thead>
                                                        <tr style="background: rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                                                            <th style="padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.8); font-weight: 600; width: 80px; text-align: center;">Status</th>
                                                            <th style="padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.8); font-weight: 600;">Tarefa</th>
                                                            <th style="padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.8); font-weight: 600; width: 180px;">Responsável</th>
                                                            <th style="padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.8); font-weight: 600; width: 120px;">Início</th>
                                                            <th style="padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.8); font-weight: 600; width: 120px;">Fim</th>
                                                            <th style="padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.8); font-weight: 600; width: 80px; text-align: center;">Ações</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if ($total_tarefas > 0): ?>
                                                            <?php foreach ($tarefas_proj as $t): ?>
                                                                <?php 
                                                                    $statusIcon = $t['concluida'] 
                                                                        ? '<i class="fa-solid fa-square-check" style="color: var(--status-green); font-size: 1.3rem;"></i>' 
                                                                        : '<i class="fa-regular fa-square" style="color: rgba(255,255,255,0.4); font-size: 1.3rem;"></i>';
                                                                    
                                                                    $textStyle = $t['concluida'] 
                                                                        ? 'text-decoration: line-through; color: rgba(255, 255, 255, 0.4);' 
                                                                        : 'color: #ffffff;';
                                                                ?>
                                                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); background: rgba(255,255,255, 0.01);">
                                                                    <td style="padding: 0.75rem 1rem; text-align: center; vertical-align: middle;">
                                                                        <form method="POST" action="projetos.php" style="display: inline-block; margin: 0;">
                                                                            <input type="hidden" name="action" value="alternar_conclusao">
                                                                            <input type="hidden" name="id_tarefa" value="<?= $t['id'] ?>">
                                                                            <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0.25rem; display: inline-flex; align-items: center; justify-content: center;" title="<?= $t['concluida'] ? 'Marcar como Pendente' : 'Marcar como Concluída' ?>">
                                                                                <?= $statusIcon ?>
                                                                            </button>
                                                                        </form>
                                                                    </td>
                                                                    <td style="padding: 0.75rem 1rem; font-weight: 500; <?= $textStyle ?>"><?= htmlspecialchars($t['nome_tarefa']) ?></td>
                                                                    <td style="padding: 0.75rem 1rem;"><span style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: rgba(255,255,255,0.9);"><?= htmlspecialchars($t['responsavel']) ?></span></td>
                                                                    <td style="padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.8);"><?= $t['data_inicio'] ?></td>
                                                                    <td style="padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.8);"><?= $t['data_final'] ?></td>
                                                                    <td style="padding: 0.75rem 1rem; text-align: center; vertical-align: middle;">
                                                                        <form method="POST" action="projetos.php" style="display: inline-block; margin: 0;" onsubmit="return confirm('Tem certeza que deseja excluir esta tarefa?');">
                                                                            <input type="hidden" name="action" value="excluir_tarefa">
                                                                            <input type="hidden" name="id_tarefa" value="<?= $t['id'] ?>">
                                                                            <button type="submit" style="background: none; border: none; cursor: pointer; color: #ef4444; padding: 0.25rem;" title="Excluir Tarefa">
                                                                                <i class="fa-solid fa-trash-can fa-lg"></i>
                                                                            </button>
                                                                        </form>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="6" style="padding: 1.5rem; text-align: center; color: rgba(255, 255, 255, 0.5);">
                                                                    Nenhuma tarefa registrada para este projeto. <a href="javascript:void(0)" onclick="abrirModalTarefas(<?= $proj['id'] ?>, '<?= htmlspecialchars($proj['nome_projeto'], ENT_QUOTES) ?>')" style="color: #60a5fa; text-decoration: underline; font-weight: 500;">Adicione tarefas agora</a>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $nivel_acesso === 'Administrador' ? '7' : '6' ?>" style="text-align: center; color: var(--text-muted);">Nenhum projeto registrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL TAREFAS -->
    <div id="modal-tarefas" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3 id="modal-titulo" style="font-size: 1.25rem; font-weight: bold; color: #1e3a8a;">Tarefas do Projeto</h3>
                    <p style="font-size: 0.85rem; color: #64748b;">Visualize e gerencie a lista de atividades deste projeto</p>
                </div>
                <button class="close-btn" onclick="fecharModal('modal-tarefas')"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <!-- Formulário para Adicionar Tarefa -->
                <div style="background: #f1f5f9; padding: 1.25rem; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #334155; font-size: 1rem;"><i class="fa-solid fa-plus-circle"></i> Adicionar Nova Tarefa</h4>
                    <form method="POST" action="projetos.php">
                        <input type="hidden" name="action" value="adicionar_tarefa">
                        <input type="hidden" name="id_projeto" id="modal_id_projeto">
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: flex-end;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.25rem; color: #475569;">Nome da Tarefa</label>
                                <input type="text" name="nome_tarefa" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem;" required>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.25rem; color: #475569;">Responsável</label>
                                <input type="text" name="responsavel_tarefa" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem;" placeholder="Nome da pessoa" required>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.25rem; color: #475569;">Data de Início</label>
                                <input type="date" name="data_inicio_tarefa" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem;" required>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.25rem; color: #475569;">Data Final</label>
                                <input type="date" name="data_final_tarefa" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem;" required>
                            </div>
                            
                            <div style="margin-bottom: 0;">
                                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.55rem; font-size: 0.875rem; background-color: var(--status-green);">
                                    <i class="fa-solid fa-check"></i> Adicionar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <h4 style="color: #333; margin-bottom: 0.75rem;"><i class="fa-solid fa-tasks"></i> Lista de Tarefas</h4>
                <div style="overflow-x: auto;">
                    <table class="light-table" style="margin-top: 0;">
                        <thead>
                            <tr>
                                <th style="text-align: center; width: 50px;">Status</th>
                                <th>Nome da Tarefa</th>
                                <th>Responsável</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th style="text-align: center; width: 80px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabela-tarefas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const projetosTarefas = <?= json_encode($projetos_tarefas) ?>;
        
        function abrirModalTarefas(idProjeto, nomeProjeto) {
            document.getElementById('modal-titulo').innerHTML = `<i class="fa-solid fa-project-diagram"></i> Projeto: ${nomeProjeto}`;
            document.getElementById('modal_id_projeto').value = idProjeto;
            
            const tbody = document.getElementById('tabela-tarefas');
            tbody.innerHTML = '';
            
            const tarefas = projetosTarefas[idProjeto] || [];
            
            if (tarefas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b; padding: 1.5rem;">Nenhuma tarefa registrada para este projeto.</td></tr>';
            } else {
                tarefas.forEach(t => {
                    const statusIcon = t.concluida 
                        ? '<i class="fa-solid fa-square-check" style="color: var(--status-green); font-size: 1.3rem;"></i>' 
                        : '<i class="fa-regular fa-square" style="color: #64748b; font-size: 1.3rem;"></i>';
                    
                    const textStyle = t.concluida 
                        ? 'font-weight: 500; text-decoration: line-through; color: #94a3b8;' 
                        : 'font-weight: 500; color: #334155;';
                        
                    tbody.innerHTML += `
                        <tr>
                            <td style="text-align: center;">
                                <form method="POST" action="projetos.php" style="display: inline-block; margin: 0;">
                                    <input type="hidden" name="action" value="alternar_conclusao">
                                    <input type="hidden" name="id_tarefa" value="${t.id}">
                                    <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0.25rem; display: inline-flex; align-items: center; justify-content: center;" title="${t.concluida ? 'Marcar como Pendente' : 'Marcar como Concluída'}">
                                        ${statusIcon}
                                    </button>
                                </form>
                            </td>
                            <td style="${textStyle}">${t.nome_tarefa}</td>
                            <td><span style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; color: #475569;">${t.responsavel}</span></td>
                            <td>${t.data_inicio}</td>
                            <td>${t.data_final}</td>
                            <td style="text-align: center;">
                                <form method="POST" action="projetos.php" style="display: inline-block; margin: 0;" onsubmit="return confirm('Tem certeza que deseja excluir esta tarefa?');">
                                    <input type="hidden" name="action" value="excluir_tarefa">
                                    <input type="hidden" name="id_tarefa" value="${t.id}">
                                    <button type="submit" style="background: none; border: none; cursor: pointer; color: #ef4444; padding: 0.25rem;" title="Excluir Tarefa">
                                        <i class="fa-solid fa-trash-can fa-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    `;
                });
            }
            
            document.getElementById('modal-tarefas').classList.add('active');
        }
        
        function fecharModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function toggleDetailsRow(id) {
            const row = document.getElementById('details-row-' + id);
            const chevron = document.getElementById('chevron-' + id);
            if (row.style.display === 'none') {
                row.style.display = 'table-row';
                chevron.className = 'fa-solid fa-chevron-up fa-lg';
            } else {
                row.style.display = 'none';
                chevron.className = 'fa-solid fa-chevron-down fa-lg';
            }
        }
    </script>
</body>
</html>
