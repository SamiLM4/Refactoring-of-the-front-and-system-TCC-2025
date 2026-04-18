<?php
require_once __DIR__ . "/modelo/Banco.php";

try {
    $banco = new Banco();
    $conn = $banco->getConexao();

    // 1. Add para_usuario_id
    // 2. Make origem_papel_id NULL
    $sql = "ALTER TABLE mensagens_chat 
            ADD COLUMN para_usuario_id INT NULL AFTER usuario_id,
            MODIFY COLUMN origem_papel_id INT NULL,
            ADD CONSTRAINT fk_mensagens_para_usuario FOREIGN KEY (para_usuario_id) REFERENCES usuarios(id)";

    if ($conn->query($sql) === TRUE) {
        echo "Tabela mensagens_chat alterada com sucesso! Chat P2P habilitado.";
    } else {
        echo "Erro ao alterar tabela: " . $conn->error;
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
