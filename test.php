<?php
require_once __DIR__ . "/modelo/Banco.php";
$banco = new Banco();
$db = $banco->getConexao();

// Verificando registros em papeis
$res = $db->query("SELECT * FROM papeis");
$roles = [];
while($row = $res->fetch_assoc()) {
    $roles[] = $row;
}

echo json_encode($roles, JSON_PRETTY_PRINT);
