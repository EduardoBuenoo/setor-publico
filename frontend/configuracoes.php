<?php
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

// Get user data including sector
$stmt = $pdo->prepare("SELECT u.*, s.nome_setor FROM usuarios u LEFT JOIN setores s ON u.id_setor = s.id WHERE u.id = ?");
$stmt->execute([$id_usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $nome = trim($_POST['nome']);
        $funcao = trim($_POST['funcao']);
        
        $stmt_update = $pdo->prepare("UPDATE usuarios SET nome = ?, funcao = ? WHERE id = ?");
        if ($stmt_update->execute([$nome, $funcao, $id_usuario])) {
            $msg = "Perfil atualizado com sucesso!";
            $user['nome'] = $nome;
            $user['funcao'] = $funcao;
            // Update session name if used elsewhere
            $_SESSION['nome'] = $nome;
        } else {
            $error = "Erro ao atualizar o perfil.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        
        if (password_verify($senha_atual, $user['senha'])) {
            $uppercase = preg_match('@[A-Z]@', $nova_senha);
            $lowercase = preg_match('@[a-z]@', $nova_senha);
            $number    = preg_match('@[0-9]@', $nova_senha);
            $specialChars = preg_match('@[^\w]@', $nova_senha);

            if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($nova_senha) < 6) {
                $error = "A nova senha deve ter no mínimo 6 caracteres, incluindo maiúscula, minúscula, número e caractere especial.";
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt_up_pass = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt_up_pass->execute([$senha_hash, $id_usuario]);
                $msg = "Senha atualizada com sucesso!";
                $user['senha'] = $senha_hash;
            }
        } else {
            $error = "A senha atual está incorreta.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações | Plataforma Inteligente</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .grid-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .config-section {
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .readonly-field {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ccc !important;
            cursor: not-allowed;
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
                <li><a href="indicadores.php" class="nav-link"><i class="fa-solid fa-chart-bar"></i> Indicadores</a></li>
                <?php if ($user['nivel_acesso'] === 'Administrador' || $user['nivel_acesso'] === 'Gestor'): ?>
                <li><a href="usuarios.php" class="nav-link"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <?php endif; ?>
                <li><a href="configuracoes.php" class="nav-link active"><i class="fa-solid fa-gear"></i> Configurações</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="header glass" style="padding: 1rem 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h2 class="page-title">Configurações da Conta</h2>
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

            <?php if($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- DADOS DE PERFIL -->
                <div class="glass config-section">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;"><i class="fa-solid fa-id-card"></i> Meu Cadastro</h3>
                    <form method="POST" action="configuracoes.php">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label class="form-label" for="matricula">Matrícula (Login)</label>
                            <input type="text" id="matricula" class="form-control readonly-field" value="<?= htmlspecialchars($user['matricula']) ?>" readonly title="A matrícula não pode ser alterada">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($user['nome']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="funcao">Função</label>
                            <input type="text" id="funcao" name="funcao" class="form-control" value="<?= htmlspecialchars($user['funcao']) ?>" required>
                        </div>
                        
                        <div class="grid-form">
                            <div class="form-group">
                                <label class="form-label">Setor</label>
                                <input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($user['nome_setor']) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nível de Acesso</label>
                                <input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($user['nivel_acesso']) ?>" readonly>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;"><i class="fa-solid fa-save"></i> Atualizar Dados</button>
                    </form>
                </div>

                <!-- ALTERAR SENHA -->
                <div class="glass config-section">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;"><i class="fa-solid fa-lock"></i> Alterar Senha</h3>
                    <form method="POST" action="configuracoes.php">
                        <input type="hidden" name="action" value="update_password">
                        <div class="form-group">
                            <label class="form-label" for="senha_atual">Senha Atual</label>
                            <input type="password" id="senha_atual" name="senha_atual" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="nova_senha">Nova Senha</label>
                            <input type="password" id="nova_senha" name="nova_senha" class="form-control" placeholder="Min 6 chars, 1 maiúsc, 1 minúsc, 1 num, 1 esp" required>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem; background: var(--status-yellow); color: #000;"><i class="fa-solid fa-key"></i> Redefinir Senha</button>
                    </form>
                </div>
            </div>
            
        </main>
    </div>
</body>
</html>
