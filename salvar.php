<?php include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        $_POST['descricao'],
        $_POST['tipo'],
        $_POST['categoria'],
        str_replace(',', '.', $_POST['valor']),
        $_POST['data_transacao']
    ];

    $sql = "INSERT INTO transacoes (descricao, tipo, categoria, valor, data_transacao) VALUES (?, ?, ?, ?, ?)";
    $stmt = $banco->prepare($sql);
    $stmt->execute($dados);
}

header('Location: index.php');
exit;
?>