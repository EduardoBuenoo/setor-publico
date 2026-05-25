<?php
$host = 'localhost';
$port = '5432';
$dbname = 'pimois';
$user = 'postgres';
$pass = '123';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if sector 1 exists
    $stmt = $pdo->query("SELECT id FROM setores LIMIT 1");
    $setor = $stmt->fetch();
    if (!$setor) {
        $pdo->exec("INSERT INTO setores (nome_setor, sigla) VALUES ('Administração', 'ADM')");
        // Get the ID of the inserted sector. For postgres, we use lastInsertId on sequence or just fetch it.
        $stmt2 = $pdo->query("SELECT id FROM setores ORDER BY id DESC LIMIT 1");
        $setor2 = $stmt2->fetch();
        $id_setor = $setor2['id'];
    } else {
        $id_setor = $setor['id'];
    }

    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Check if admin already exists
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = 'admin'");
    $stmtCheck->execute();
    if (!$stmtCheck->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (matricula, senha, nome, nivel_acesso, id_setor) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $hash, 'Administrador', 'Administrador', $id_setor]);
        echo "Superusuario criado com sucesso!\n";
    } else {
        echo "Usuário 'admin' já existe!\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
