<?php
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_acesso'] !== 'Administrador' && $_SESSION['nivel_acesso'] !== 'Gestor')) {
    header("Location: dashboard.php");
    exit;
}

$apiUrl = 'http://127.0.0.1:8000';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['nivel_acesso'] !== 'Administrador') {
        $error = "Apenas administradores possuem permissão para realizar alterações de usuários.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_senha') {
        $id_user = $_POST['id_usuario_reset'];
        $nova_senha = trim($_POST['nova_senha_reset']);

        $uppercase = preg_match('@[A-Z]@', $nova_senha);
        $lowercase = preg_match('@[a-z]@', $nova_senha);
        $number    = preg_match('@[0-9]@', $nova_senha);
        $specialChars = preg_match('@[^\w]@', $nova_senha);

        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($nova_senha) < 6) {
            $error = "A nova senha deve ter no mínimo 6 caracteres, incluindo maiúscula, minúscula, número e caractere especial.";
        } else {
            // PATCH to update password via API
            $data = json_encode(['senha' => $nova_senha]);
            $ch = curl_init("$apiUrl/usuarios/$id_user/");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data),
                'Authorization: Bearer ' . $_SESSION['api_token']
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            
            header("Location: usuarios.php?success=Senha redefinida com sucesso!");
            exit;
        }
    } elseif (isset($_POST['matricula'])) {
        $matricula = trim($_POST['matricula']);
        $nome = trim($_POST['nome']);
        $funcao = trim($_POST['funcao']);
        $id_setor = $_POST['id_setor'];
        $nivel_acesso = $_POST['nivel_acesso'];
        $senha = trim($_POST['senha']);
        
        $uppercase = preg_match('@[A-Z]@', $senha);
        $lowercase = preg_match('@[a-z]@', $senha);
        $number    = preg_match('@[0-9]@', $senha);
        $specialChars = preg_match('@[^\w]@', $senha);

        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($senha) < 6) {
            $error = "A senha deve ter no mínimo 6 caracteres, incluindo maiúscula, minúscula, número e caractere especial.";
        } else {
            // POST to create user via API
            $data = json_encode([
                'matricula' => $matricula,
                'nome' => $nome,
                'funcao' => $funcao,
                'id_setor' => $id_setor,
                'nivel_acesso' => $nivel_acesso,
                'senha' => $senha
            ]);
            
            $ch = curl_init("$apiUrl/usuarios/");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data),
                'Authorization: Bearer ' . $_SESSION['api_token']
            ]);
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpcode == 201) {
                header("Location: usuarios.php?success=1");
                exit;
            } else {
                $error = "Erro ao cadastrar: A matrícula pode já estar em uso.";
            }
        }
    }
}

// Fetch list of users based on role and search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$ch = curl_init("$apiUrl/usuarios/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $_SESSION['api_token']
]);
$response_usuarios = curl_exec($ch);
curl_close($ch);
$all_usuarios = json_decode($response_usuarios, true) ?? [];

// Se a API retornou erro (ex: Token inválido ou expirado), deslogar o usuário ou tratar o erro
if (isset($all_usuarios['detail']) || isset($all_usuarios['code'])) {
    header("Location: logout.php");
    exit;
}

$usuarios = [];
foreach ($all_usuarios as $u) {
    if ($_SESSION['nivel_acesso'] === 'Gestor' && $u['id_setor'] != $_SESSION['id_setor']) {
        continue;
    }
    
    if ($search !== '') {
        $match_nome = stripos($u['nome'], $search) !== false;
        $match_matricula = stripos($u['matricula'], $search) !== false;
        if (!$match_nome && !$match_matricula) {
            continue;
        }
    }
    $usuarios[] = $u;
}

usort($usuarios, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});

// Fetch sectors via API
$ch = curl_init("$apiUrl/setores/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $_SESSION['api_token']
]);
$response_setores = curl_exec($ch);
curl_close($ch);
$setores = json_decode($response_setores, true) ?? [];

// Find current user's sector name
$nome_setor = 'Desconhecido';
foreach($setores as $s) {
    if ($s['id'] == $_SESSION['id_setor']) {
        $nome_setor = $s['nome_setor'];
        break;
    }
}

$user = [
    'nome' => $_SESSION['nome'],
    'nivel_acesso' => $_SESSION['nivel_acesso'],
    'nome_setor' => $nome_setor
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários | Plataforma Inteligente</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .grid-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
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
            max-width: 400px;
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
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
        }
        .close-btn:hover { color: #ef4444; }
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
                <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Painel de Controle </a></li>
                <li><a href="oficios.php" class="nav-link"><i class="fa-solid fa-file-signature"></i> Ofícios</a></li>
                <li><a href="projetos.php" class="nav-link"><i class="fa-solid fa-project-diagram"></i> Projetos</a></li>
                <li><a href="indicadores.php" class="nav-link"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>
                <li><a href="usuarios.php" class="nav-link active"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <li><a href="configuracoes.php" class="nav-link"><i class="fa-solid fa-gear"></i> Configurações</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="header glass" style="padding: 1rem 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h2 class="page-title">Gerenciamento de Usuários</h2>
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
                <div class="alert alert-success"><?= is_numeric($_GET['success']) ? 'Usuário cadastrado com sucesso!' : htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($_SESSION['nivel_acesso'] === 'Administrador'): ?>
            <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">Cadastrar Novo Usuário</h3>
                <form method="POST" action="usuarios.php">
                    <div class="grid-form">
                        <div class="form-group">
                            <label class="form-label" for="matricula">Matrícula (Login)</label>
                            <input type="text" id="matricula" name="matricula" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="funcao">Função</label>
                            <input type="text" id="funcao" name="funcao" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="id_setor">Setor</label>
                            <select id="id_setor" name="id_setor" class="form-control" required>
                                <?php foreach($setores as $setor): ?>
                                    <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome_setor']) ?> (<?= $setor['sigla'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="nivel_acesso">Nível de Acesso</label>
                            <select id="nivel_acesso" name="nivel_acesso" class="form-control" required>
                                <option value="Colaborador">Colaborador</option>
                                <option value="Gestor">Gestor</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="senha">Senha Temporária</label>
                            <input type="password" id="senha" name="senha" class="form-control" placeholder="Min 6 chars, 1 maiúsc, 1 minúsc, 1 num, 1 esp" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: auto; margin-top: 1rem;"><i class="fa-solid fa-user-plus"></i> Cadastrar Usuário</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- FILTRO DE BUSCA -->
            <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.2rem;"><i class="fa-solid fa-magnifying-glass"></i> Consultar Usuários</h3>
                <form method="GET" action="usuarios.php" style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label class="form-label" for="search">Pesquisar por Nome ou Matrícula</label>
                        <input type="text" id="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Digite o nome ou a matrícula...">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto;"><i class="fa-solid fa-search"></i> Buscar</button>
                    <?php if ($search !== ''): ?>
                        <a href="usuarios.php" class="btn" style="width: auto; background: var(--status-grey); color: white; display: flex; align-items: center; justify-content: center; text-decoration: none;"><i class="fa-solid fa-eraser"></i> Limpar</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="glass" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">
                    <?php if ($_SESSION['nivel_acesso'] === 'Gestor'): ?>
                        Lista de Usuários do Setor: <?= htmlspecialchars($user['nome_setor']) ?>
                    <?php else: ?>
                        Lista de Usuários
                    <?php endif; ?>
                </h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nome</th>
                                <th>Função</th>
                                <th>Setor</th>
                                <th>Nível</th>
                                <?php if ($_SESSION['nivel_acesso'] === 'Administrador'): ?>
                                    <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach($usuarios as $u_item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u_item['matricula']) ?></td>
                                    <td><strong><?= htmlspecialchars($u_item['nome']) ?></strong></td>
                                    <td><?= htmlspecialchars($u_item['funcao']) ?></td>
                                    <td><span class="status-tag status-encerrado"><?= htmlspecialchars($u_item['nome_setor']) ?></span></td>
                                    <td>
                                        <?php if($u_item['nivel_acesso'] === 'Administrador'): ?>
                                            <span style="color: var(--status-green); font-weight: bold;"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($u_item['nivel_acesso']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($_SESSION['nivel_acesso'] === 'Administrador'): ?>
                                    <td>
                                        <button class="btn btn-sm" style="background: var(--status-yellow); color: #000;" onclick="abrirModalSenha(<?= $u_item['id'] ?>, '<?= htmlspecialchars($u_item['nome'], ENT_QUOTES) ?>')" title="Redefinir Senha"><i class="fa-solid fa-key"></i></button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $_SESSION['nivel_acesso'] === 'Administrador' ? 6 : 5 ?>" style="text-align: center; color: var(--text-muted);">Nenhum usuário encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <?php if ($_SESSION['nivel_acesso'] === 'Administrador'): ?>
    <!-- MODAL REDEFINIR SENHA -->
    <div id="modal-senha" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header" style="background: #fefce8; border-bottom-color: #fef08a;">
                <h3 style="font-size: 1.1rem; font-weight: bold; color: #854d0e;"><i class="fa-solid fa-key"></i> Redefinir Senha</h3>
                <button class="close-btn" onclick="fecharModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1rem;">Definir nova senha para o usuário <strong id="nome_usuario_modal"></strong>.</p>
                <form method="POST" action="usuarios.php">
                    <input type="hidden" name="action" value="reset_senha">
                    <input type="hidden" name="id_usuario_reset" id="id_usuario_reset">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; color: #333;">Nova Senha:</label>
                        <input type="password" name="nova_senha_reset" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem;" placeholder="Min 6 chars, 1 maiúsc, 1 minúsc, 1 num, 1 esp" required>
                    </div>
                    <button type="submit" style="width: 100%; background: #eab308; color: white; border: none; padding: 0.75rem; border-radius: 8px; font-weight: bold; cursor: pointer;">
                        <i class="fa-solid fa-check"></i> Salvar Nova Senha
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalSenha(id, nome) {
            document.getElementById('id_usuario_reset').value = id;
            document.getElementById('nome_usuario_modal').innerText = nome;
            document.getElementById('modal-senha').classList.add('active');
        }

        function fecharModal() {
            document.getElementById('modal-senha').classList.remove('active');
        }
    </script>
    <?php endif; ?>
</body>
</html>
