<?php
// db.php
session_start();

// Configurações do Banco de Dados PostgreSQL
$host = 'localhost';
$port = '5433';
$dbname = 'pimois'; // Nome do banco de dados (precisa ser criado previamente no Postgres)
$user = 'postgres'; // Usuário do Postgres
$pass = '250226'; // Senha do Postgres

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se as tabelas já existem (verificando a tabela setores)
    $check = $pdo->query("SELECT to_regclass('public.setores')")->fetchColumn();

    if (!$check) {
        // Criar tabelas para PostgreSQL (usando SERIAL em vez de AUTOINCREMENT)
        $pdo->exec("
            CREATE TABLE setores (
                id SERIAL PRIMARY KEY,
                nome_setor VARCHAR(255) NOT NULL,
                sigla VARCHAR(50) NOT NULL
            );
            
            CREATE TABLE usuarios (
                id SERIAL PRIMARY KEY,
                matricula VARCHAR(100) UNIQUE NOT NULL,
                nome VARCHAR(255) NOT NULL,
                funcao VARCHAR(255),
                id_setor INTEGER,
                nivel_acesso VARCHAR(50) NOT NULL,
                senha VARCHAR(255) NOT NULL,
                FOREIGN KEY(id_setor) REFERENCES setores(id)
            );

            CREATE TABLE oficios (
                id SERIAL PRIMARY KEY,
                numero_oficio INTEGER NOT NULL,
                ano INTEGER NOT NULL,
                data_registro DATE NOT NULL,
                assunto TEXT NOT NULL,
                id_usuario INTEGER,
                id_setor INTEGER,
                FOREIGN KEY(id_usuario) REFERENCES usuarios(id),
                FOREIGN KEY(id_setor) REFERENCES setores(id)
            );

            CREATE TABLE projetos (
                id SERIAL PRIMARY KEY,
                nome_projeto VARCHAR(255) NOT NULL,
                data_inicio DATE NOT NULL,
                data_final DATE NOT NULL,
                status VARCHAR(100) NOT NULL,
                prioridade VARCHAR(50),
                id_responsavel INTEGER,
                id_setor INTEGER,
                FOREIGN KEY(id_responsavel) REFERENCES usuarios(id),
                FOREIGN KEY(id_setor) REFERENCES setores(id)
            );

            CREATE TABLE atividades (
                id SERIAL PRIMARY KEY,
                indicador VARCHAR(255) NOT NULL,
                data_registro DATE NOT NULL,
                id_usuario INTEGER,
                id_setor INTEGER,
                FOREIGN KEY(id_usuario) REFERENCES usuarios(id),
                FOREIGN KEY(id_setor) REFERENCES setores(id)
            );

            -- Inserir setores iniciais
            INSERT INTO setores (nome_setor, sigla) VALUES 
            ('Banco do Povo', 'BDP'),
            ('Posto de Atendimento ao Trabalhador', 'PAT'),
            ('SEBRAE', 'SEBRAE'),
            ('Convênios', 'CONV'),
            ('Poupatempo', 'POUPA'),
            ('PROCON', 'PROCON'),
            ('Junta do Serviço Militar', 'JSM');

            -- Inserir usuário administrador (senha: Admin@123)
            INSERT INTO usuarios (matricula, nome, funcao, id_setor, nivel_acesso, senha) VALUES 
            ('admin', 'Administrador do Sistema', 'Gestor Geral', 1, 'Administrador', '" . password_hash('Admin@123', PASSWORD_DEFAULT) . "');
        ");
    }

    // Verifica se as tabelas de indicadores já existem
    $check_indicadores = $pdo->query("SELECT to_regclass('public.indicadores')")->fetchColumn();
    
    if (!$check_indicadores) {
        $pdo->exec("
            CREATE TABLE indicadores (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                tipo VARCHAR(50) NOT NULL
            );

            CREATE TABLE indicadores_valores (
                id SERIAL PRIMARY KEY,
                id_indicador INTEGER NOT NULL,
                valor NUMERIC(10, 2) NOT NULL,
                data_registro DATE NOT NULL,
                tipo_registro VARCHAR(50) NOT NULL,
                id_usuario INTEGER NOT NULL,
                FOREIGN KEY(id_indicador) REFERENCES indicadores(id),
                FOREIGN KEY(id_usuario) REFERENCES usuarios(id)
            );
        ");
    }

    // Verifica se a tabela tarefas existe
    $check_tarefas = $pdo->query("SELECT to_regclass('public.tarefas')")->fetchColumn();
    if (!$check_tarefas) {
        $pdo->exec("
            CREATE TABLE tarefas (
                id SERIAL PRIMARY KEY,
                id_projeto INTEGER NOT NULL,
                nome_tarefa VARCHAR(255) NOT NULL,
                responsavel VARCHAR(255) NOT NULL,
                data_inicio DATE NOT NULL,
                data_final DATE NOT NULL,
                concluida BOOLEAN DEFAULT FALSE,
                FOREIGN KEY(id_projeto) REFERENCES projetos(id) ON DELETE CASCADE
            );
        ");
    } else {
        // Garante que a coluna concluida existe caso a tabela já estivesse criada
        $pdo->exec("ALTER TABLE tarefas ADD COLUMN IF NOT EXISTS concluida BOOLEAN DEFAULT FALSE");
    }

} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados. Verifique as credenciais no arquivo db.php. Detalhe: " . $e->getMessage());
}
?>
