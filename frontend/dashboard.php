<?php
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Obter dados do usuário, incluindo o setor
$stmt = $pdo->prepare("SELECT u.*, s.nome_setor FROM usuarios u LEFT JOIN setores s ON u.id_setor = s.id WHERE u.id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Contar indicadores com base no nível de acesso do usuário
if ($user['nivel_acesso'] === 'Administrador') {
    $stmt_oficios = $pdo->query("SELECT COUNT(*) FROM oficios");
    $total_oficios = $stmt_oficios->fetchColumn();

    $stmt_projetos = $pdo->query("SELECT COUNT(*) FROM projetos");
    $total_projetos = $stmt_projetos->fetchColumn();

    $stmt_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $total_usuarios = $stmt_usuarios->fetchColumn();
} else {
    $id_setor = $user['id_setor'];
    
    $stmt_oficios = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE id_setor = ?");
    $stmt_oficios->execute([$id_setor]);
    $total_oficios = $stmt_oficios->fetchColumn();

    $stmt_projetos = $pdo->prepare("SELECT COUNT(*) FROM projetos WHERE id_setor = ?");
    $stmt_projetos->execute([$id_setor]);
    $total_projetos = $stmt_projetos->fetchColumn();

    $stmt_usuarios = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id_setor = ?");
    $stmt_usuarios->execute([$id_setor]);
    $total_usuarios = $stmt_usuarios->fetchColumn();
}

// Fetch indicators production by sector for charts
$filter_setor = isset($_GET['filter_setor']) ? $_GET['filter_setor'] : '';
$filter_indicador = isset($_GET['filter_indicador']) ? $_GET['filter_indicador'] : '';
$filter_data_inicio = isset($_GET['filter_data_inicio']) ? $_GET['filter_data_inicio'] : '';
$filter_data_final = isset($_GET['filter_data_final']) ? $_GET['filter_data_final'] : '';

$query_indicadores = "
    SELECT 
        s.nome_setor, 
        i.nome as nome_indicador, 
        SUM(iv.valor) as total
    FROM indicadores_valores iv
    JOIN indicadores i ON iv.id_indicador = i.id
    JOIN usuarios u ON iv.id_usuario = u.id
    JOIN setores s ON u.id_setor = s.id
    WHERE iv.tipo_registro = 'lancamento'
";

$params = [];

if ($user['nivel_acesso'] !== 'Administrador') {
    $query_indicadores .= " AND s.id = ?";
    $params[] = $user['id_setor'];
} else {
    if ($filter_setor !== '') {
        $query_indicadores .= " AND s.id = ?";
        $params[] = intval($filter_setor);
    }
    if ($filter_indicador !== '') {
        $query_indicadores .= " AND i.id = ?";
        $params[] = intval($filter_indicador);
    }
    if ($filter_data_inicio !== '') {
        $query_indicadores .= " AND iv.data_registro >= ?";
        $params[] = $filter_data_inicio;
    }
    if ($filter_data_final !== '') {
        $query_indicadores .= " AND iv.data_registro <= ?";
        $params[] = $filter_data_final;
    }
}

$query_indicadores .= " GROUP BY s.nome_setor, i.nome ORDER BY s.nome_setor, i.nome";

$stmt = $pdo->prepare($query_indicadores);
$stmt->execute($params);
$prod_setores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dados_graficos = [];
foreach ($prod_setores as $row) {
    $setor = $row['nome_setor'];
    if (!isset($dados_graficos[$setor])) {
        $dados_graficos[$setor] = ['labels' => [], 'data' => []];
    }
    $dados_graficos[$setor]['labels'][] = $row['nome_indicador'];
    $dados_graficos[$setor]['data'][] = (float)$row['total'];
}

// Fetch sectors and indicators for filters (only for Admin)
$filtro_setores = [];
$filtro_indicadores = [];
if ($user['nivel_acesso'] === 'Administrador') {
    $filtro_setores = $pdo->query("SELECT * FROM setores ORDER BY nome_setor")->fetchAll(PDO::FETCH_ASSOC);
    $filtro_indicadores = $pdo->query("SELECT * FROM indicadores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Plataforma Inteligente</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fa-solid fa-building" style="color: var(--status-green);"></i> SIGDEI
            </div>
            <ul class="nav-menu">
                <li>
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fa-solid fa-chart-pie"></i> Painel de Controle 
                    </a>
                </li>
                <li>
                    <a href="oficios.php" class="nav-link">
                        <i class="fa-solid fa-file-signature"></i> Ofícios
                    </a>
                </li>
                <li>
                    <a href="projetos.php" class="nav-link">
                        <i class="fa-solid fa-project-diagram"></i> Projetos
                    </a>
                </li>
                <li>
                    <a href="indicadores.php" class="nav-link">
                        <i class="fa-solid fa-chart-bar"></i> Indicadores
                    </a>
                </li>
                <?php if ($user['nivel_acesso'] === 'Administrador' || $user['nivel_acesso'] === 'Gestor'): ?>
                <li>
                    <a href="usuarios.php" class="nav-link">
                        <i class="fa-solid fa-users"></i> Usuários
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="configuracoes.php" class="nav-link">
                        <i class="fa-solid fa-gear"></i> Configurações
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header glass" style="padding: 1rem 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h2 class="page-title">Visão Geral</h2>
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

            <div class="stats-grid">
                <div class="stat-card glass">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <p class="stat-title">Ofícios Registrados</p>
                            <h3 class="stat-value"><?= $total_oficios ?></h3>
                        </div>
                        <div style="padding: 0.75rem; background: rgba(252, 255, 255, 0.92); border-radius: 8px; color: var(--primary-color);">
                            <i class="fa-solid fa-file-alt fa-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card glass">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <p class="stat-title">Projetos Ativos</p>
                            <h3 class="stat-value"><?= $total_projetos ?></h3>
                        </div>
                        <div style="padding: 0.75rem; background: rgba(252, 255, 255, 0.92); border-radius: 8px; color: var(--status-yellow);">
                            <i class="fa-solid fa-tasks fa-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card glass">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <p class="stat-title">Usuários do Sistema</p>
                            <h3 class="stat-value"><?= $total_usuarios ?></h3>
                        </div>
                        <div style="padding: 0.75rem; background: rgba(252, 255, 255, 0.92); border-radius: 8px; color: var(--status-green);">
                            <i class="fa-solid fa-users fa-lg"></i>
                        </div>
                    </div>
                </div> <!-- Fecha stat-card -->
            </div> <!-- Fecha stats-grid -->

            <!-- Painel de Filtros (Apenas Admin) -->
            <?php if ($user['nivel_acesso'] === 'Administrador'): ?>
            <div class="glass" style="padding: 1.5rem; margin-top: 2rem;">
                <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; color: #fff;">
                    <i class="fa-solid fa-filter" style="color: var(--status-yellow);"></i> Filtrar Painel Geral (Administrador)
                </h3>
                <form method="GET" action="dashboard.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_setor" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem; color: #fff;">Setor</label>
                        <select name="filter_setor" id="filter_setor" class="form-control" style="font-size: 0.9rem; padding: 0.5rem 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            <option value="">Todos os Setores</option>
                            <?php foreach ($filtro_setores as $setor_opt): ?>
                                <option value="<?= $setor_opt['id'] ?>" <?= $filter_setor == $setor_opt['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($setor_opt['nome_setor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_indicador" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem; color: #fff;">Indicador</label>
                        <select name="filter_indicador" id="filter_indicador" class="form-control" style="font-size: 0.9rem; padding: 0.5rem 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            <option value="">Todos os Indicadores</option>
                            <?php foreach ($filtro_indicadores as $ind_opt): ?>
                                <option value="<?= $ind_opt['id'] ?>" <?= $filter_indicador == $ind_opt['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ind_opt['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_data_inicio" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem; color: #fff;">Data Inicial</label>
                        <input type="date" name="filter_data_inicio" id="filter_data_inicio" class="form-control" value="<?= htmlspecialchars($filter_data_inicio) ?>" style="font-size: 0.9rem; padding: 0.5rem 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_data_final" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem; color: #fff;">Data Final</label>
                        <input type="date" name="filter_data_final" id="filter_data_final" class="form-control" value="<?= htmlspecialchars($filter_data_final) ?>" style="font-size: 0.9rem; padding: 0.5rem 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-start; margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.6rem 1rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 0.35rem; font-weight: 500; font-size: 0.875rem; background-color: var(--primary-color);">
                            <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                        </button>
                        <a href="dashboard.php" class="btn" style="background: rgba(255,255,255,0.15); color: #fff; padding: 0.6rem 1rem; border-radius: 8px; text-decoration: none; display: flex; align-items: center; justify-content: center; font-weight: 500; font-size: 0.875rem; border: 1px solid rgba(255,255,255,0.1);" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Painel de Gráficos por Setor -->
            <div class="glass" style="padding: 2rem; margin-top: 2rem;">
                <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem; color: #fff;">
                    <i class="fa-solid fa-chart-pie" style="color: var(--status-yellow);"></i> 
                    <?= $user['nivel_acesso'] === 'Administrador' ? 'Desempenho dos Indicadores por Setor' : 'Indicadores do Meu Setor (' . htmlspecialchars($user['nome_setor']) . ')' ?>
                </h3>
                
                <?php 
                $has_data = !empty($dados_graficos);
                ?>

                <?php if (!$has_data): ?>
                    <div style="height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px dashed rgba(255,255,255,0.25); border-radius: 12px; background: rgba(255,255,255,0.03); gap: 0.75rem;">
                        <i class="fa-solid fa-chart-line fa-2x" style="color: var(--text-muted); opacity: 0.5;"></i>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">Nenhum dado lançado para o(s) setor(es) selecionado(s) com os filtros aplicados.</p>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
                        
                        <?php 
                        $chart_index = 0;
                        foreach ($dados_graficos as $nome_setor => $dados): 
                            $chart_index++;
                        ?>
                        <div style="background: rgba(255,255,255,0.06); padding: 1.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(8px); display: flex; flex-direction: column; gap: 1rem; transition: transform 0.2s ease-in-out;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                            <h4 style="text-align: center; margin-bottom: 0.5rem; color: #fff; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                                <i class="fa-solid fa-network-wired" style="color: var(--status-green);"></i> Setor: <?= htmlspecialchars($nome_setor) ?>
                            </h4>
                            <div style="height: 280px; position: relative; overflow: hidden; width: 100%">
                                <canvas id="graficoSetor<?= $chart_index ?>"></canvas>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ($has_data): ?>
    <script>
        const dadosGraficos = <?= json_encode($dados_graficos) ?>;
        
        const colors = [
            { fill: 'rgba(16, 185, 129, 0.75)', border: 'rgb(16, 185, 129)' }, // Emerald
            { fill: 'rgba(59, 130, 246, 0.75)', border: 'rgb(59, 130, 246)' },  // Blue
            { fill: 'rgba(245, 158, 11, 0.75)', border: 'rgb(245, 158, 11)' },  // Amber
            { fill: 'rgba(139, 92, 246, 0.75)', border: 'rgb(139, 92, 246)' },  // Violet
            { fill: 'rgba(236, 72, 153, 0.75)', border: 'rgb(236, 72, 153)' },  // Pink
            { fill: 'rgba(20, 184, 166, 0.75)', border: 'rgb(20, 184, 166)' }   // Teal
        ];

        let colorIdx = 0;

        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                 padding: 10
             },
            plugins: {
                legend: { 
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(35, 54, 83, 0.95)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: {
                        family: "'Inter', sans-serif",
                        weight: 'bold'
                    },
                    bodyFont: {
                        family: "'Inter', sans-serif"
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.08)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.85)',
                        font: {
                            family: "'Inter', sans-serif"
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.85)',
                        font: {
                            family: "'Inter', sans-serif"
                        }
                    }
                }
            }
        };

        let index = 1;
        for (const [setor, info] of Object.entries(dadosGraficos)) {
            const canvas = document.getElementById(`graficoSetor${index}`);
            if (canvas) {
                const ctx = canvas.getContext('2d');
                const color = colors[colorIdx % colors.length];
                colorIdx++;
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: info.labels,
                        datasets: [{
                            label: 'Total Acumulado',
                            data: info.data,
                            backgroundColor: color.fill,
                            borderColor: color.border,
                            borderWidth: 1.5,
                            borderRadius: 4,
                            borderSkipped: false
                        }]
                    },
                    options: baseOptions
                });
            }
            index++;
        }
    </script>
    <?php endif; ?>
</body>
</html>
