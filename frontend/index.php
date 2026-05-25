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
        // Login via API Django
        $data = json_encode(['matricula' => $matricula, 'senha' => $senha]);
        $ch = curl_init('http://127.0.0.1:8000/api/login/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $result = json_decode($response, true);
            $usuario = $result['user'];
            
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
            $_SESSION['id_setor'] = $usuario['id_setor'];
            $_SESSION['api_token'] = $result['access']; // Salvando o token JWT
            
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
