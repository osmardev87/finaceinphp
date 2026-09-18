<?php
// Conexão com banco SQLite
$banco = new PDO('sqlite:gastos.db');
$banco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Criar tabela se não existir
$criarTabela = "
CREATE TABLE IF NOT EXISTS transacoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    descricao TEXT NOT NULL,
    tipo TEXT NOT NULL CHECK(tipo IN ('ENTRADA', 'SAIDA')),
    categoria TEXT NOT NULL,
    valor REAL NOT NULL,
    data_transacao DATE NOT NULL
);
";

$banco->exec($criarTabela);
?>