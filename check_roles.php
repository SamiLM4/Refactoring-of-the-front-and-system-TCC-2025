<?php
require_once "modelo/Banco.php";
$banco = new Banco();
$con = $banco->getConexao();
$res = $con->query("SELECT id, nome FROM papeis");
$roles = [];
while($row = $res->fetch_assoc()) {
    $roles[] = $row;
}
echo json_encode($roles, JSON_PRETTY_PRINT);
