<?php
require_once __DIR__ . "/modelo/Banco.php";

$banco = new Banco();
$conn = $banco->getConexao();

$result = $conn->query("SELECT * FROM auditoria_medica ORDER BY id DESC LIMIT 10");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows, JSON_PRETTY_PRINT);
