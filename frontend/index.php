<?php
require_once 'db.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = trim($_POST['matricula'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($matricula && $senha) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
            $_SESSION['id_setor'] = $usuario['id_setor'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Matrícula ou senha incorretos.';
        }
    } else {
        $error = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Plataforma Inteligente</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card glass">
            <h1>S.I.G.D.E.I</h1>
            <p>Sistema Integrado de Gestão do Desenvolvimento Econômico de IracemápolisS </p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="form-group">
                    <label class="form-label" for="matricula">Matrícula</label>
                    <input type="text" id="matricula" name="matricula" class="form-control" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Entrar no Sistema</button>
            </form>
        </div>
    </div>
</body>
</html>
