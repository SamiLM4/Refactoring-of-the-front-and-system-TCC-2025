<?php
require_once __DIR__ . "/modelo/Banco.php";

try {
    $banco = new Banco();
    $conn = $banco->getConexao();

    $sql = "ALTER TABLE auditoria_medica MODIFY paciente_id INT NULL";

    if ($conn->query($sql) === TRUE) {
        echo "Tabela auditoria_medica alterada com sucesso! paciente_id agora é nullable.";
    } else {
        echo "Erro ao alterar tabela: " . $conn->error;
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
