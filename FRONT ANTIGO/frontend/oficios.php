<?php
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$id_setor = $_SESSION['id_setor'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assunto'])) {
    $assunto = trim($_POST['assunto']);
    $data_registro = date('Y-m-d');
    $ano = date('Y');

    // Get latest numero_oficio for the current year
    $stmt_max = $pdo->prepare("SELECT MAX(numero_oficio) FROM oficios WHERE ano = ?");
    $stmt_max->execute([$ano]);
    $max_num = $stmt_max->fetchColumn();
    $numero_oficio = $max_num ? $max_num + 1 : 1;

    $stmt_insert = $pdo->prepare("INSERT INTO oficios (numero_oficio, ano, data_registro, assunto, id_usuario, id_setor) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert->execute([$numero_oficio, $ano, $data_registro, $assunto, $id_usuario, $id_setor]);
    
    header("Location: oficios.php?success=1");
    exit;
}

$filter_setor = isset($_GET['filter_setor']) ? $_GET['filter_setor'] : '';
$filter_numero = isset($_GET['filter_numero']) ? $_GET['filter_numero'] : '';

// Fetch list of sectors for filter
$setores = $pdo->query("SELECT * FROM setores ORDER BY nome_setor")->fetchAll(PDO::FETCH_ASSOC);

// Fetch list of oficios with filters
$query = "SELECT o.*, u.nome as autor, s.nome_setor 
          FROM oficios o 
          JOIN usuarios u ON o.id_usuario = u.id 
          JOIN setores s ON o.id_setor = s.id 
          WHERE 1=1";

$params = [];

if ($_SESSION['nivel_acesso'] !== 'Administrador') {
    $query .= " AND o.id_setor = ?";
    $params[] = intval($id_setor);
} else {
    if ($filter_setor !== '') {
        $query .= " AND o.id_setor = ?";
        $params[] = intval($filter_setor);
    }
}

if ($filter_numero !== '') {
    $query .= " AND o.numero_oficio = ?";
    $params[] = intval($filter_numero);
}

$query .= " ORDER BY o.ano DESC, o.numero_oficio DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$oficios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ofícios | Plataforma Inteligente</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
         <aside class="sidebar">
            <div class="sidebar-logo" style="display: flex; align-items:center; gap: 10px;">
                <img src="/assets/img/logo_prefeitura.png" alt="Logo Prefeitura da Iracemápolis" style="height: 35px; width: auto; object-fit: contain;">
                
                <span>SIGDEI</span>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Painel de Controle</a></li>
                <li><a href="oficios.php" class="nav-link active"><i class="fa-solid fa-file-signature"></i> Ofícios</a></li>
                <li><a href="projetos.php" class="nav-link"><i class="fa-solid fa-project-diagram"></i> Projetos</a></li>
                <li><a href="indicadores.php" class="nav-link"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>
                <?php if ($_SESSION['nivel_acesso'] === 'Administrador' || $_SESSION['nivel_acesso'] === 'Gestor'): ?>
                <li><a href="usuarios.php" class="nav-link"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <?php endif; ?>
                <li><a href="configuracoes.php" class="nav-link"><i class="fa-solid fa-gear"></i> Configurações</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="header">
                <h2 class="page-title">Gerenciamento de Ofícios</h2>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">Ofício registrado com sucesso! Número gerado automaticamente.</div>
            <?php endif; ?>

            <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">Novo Ofício</h3>
                <form method="POST" action="oficios.php" style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label class="form-label" for="assunto">Assunto do Ofício</label>
                        <input type="text" id="assunto" name="assunto" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto;"><i class="fa-solid fa-plus"></i> Registrar</button>
                </form>
            </div>

            <!-- Painel de Filtros -->
            <div class="glass" style="padding: 1.5rem; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; color: #fff;">
                    <i class="fa-solid fa-filter" style="color: var(--status-yellow);"></i> Filtrar Ofícios
                </h3>
                <form method="GET" action="oficios.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; align-items: flex-end;">
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_setor" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem; color: #fff;">Setor</label>
                        <?php if ($_SESSION['nivel_acesso'] === 'Administrador'): ?>
                            <select name="filter_setor" id="filter_setor" class="form-control" style="font-size: 0.9rem; padding: 0.5rem 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                                <option value="">Todos os Setores</option>
                                <?php foreach ($setores as $setor_opt): ?>
                                    <option value="<?= $setor_opt['id'] ?>" <?= $filter_setor == $setor_opt['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($setor_opt['nome_setor']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <!-- Para não-administradores, o setor é fixo -->
                            <select class="form-control" style="font-size: 0.9rem; padding: 0.5rem 0.75rem; background: rgba(15, 23, 42, 0.3); border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.6);" disabled>
                                <?php foreach ($setores as $setor_opt): ?>
                                    <?php if ($setor_opt['id'] == $id_setor): ?>
                                        <option value="<?= $setor_opt['id'] ?>" selected>
                                            <?= htmlspecialchars($setor_opt['nome_setor']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_numero" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem; color: #fff;">Número do Ofício</label>
                        <input type="number" name="filter_numero" id="filter_numero" min="1" class="form-control" value="<?= htmlspecialchars($filter_numero) ?>" placeholder="Ex: 5" style="font-size: 0.9rem; padding: 0.5rem 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-start; margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.6rem 1rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 0.35rem; font-weight: 500; font-size: 0.875rem; background-color: var(--primary-color);">
                            <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                        </button>
                        <a href="oficios.php" class="btn" style="background: rgba(255,255,255,0.15); color: #fff; padding: 0.6rem 1rem; border-radius: 8px; text-decoration: none; display: flex; align-items: center; justify-content: center; font-weight: 500; font-size: 0.875rem; border: 1px solid rgba(255,255,255,0.1);" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <div class="glass" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">Ofícios Registrados</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Número/Ano</th>
                                <th>Data de Registro</th>
                                <th>Assunto</th>
                                <th>Autor</th>
                                <th>Setor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($oficios) > 0): ?>
                                <?php foreach($oficios as $oficio): ?>
                                <tr>
                                    <td><strong><?= str_pad($oficio['numero_oficio'], 4, '0', STR_PAD_LEFT) ?>/<?= $oficio['ano'] ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($oficio['data_registro'])) ?></td>
                                    <td><?= htmlspecialchars($oficio['assunto']) ?></td>
                                    <td><?= htmlspecialchars($oficio['autor']) ?></td>
                                    <td><span class="status-tag status-encerrado"><?= htmlspecialchars($oficio['nome_setor']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-muted);">Nenhum ofício registrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
