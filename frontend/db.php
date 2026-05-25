<?php
// db.php
session_start();

// Configurações do Banco de Dados PostgreSQL
$host = 'localhost';
$port = '5432';
$dbname = 'pimois'; // Nome do banco de dados (precisa ser criado previamente no Postgres)
$user = 'postgres'; // Usuário do Postgres
$pass = '123'; // Senha do Postgres

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // As tabelas agora são gerenciadas pelas migrations do Django.


} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados. Verifique as credenciais no arquivo db.php. Detalhe: " . $e->getMessage());
}
?>
