<?php
require_once __DIR__ . "/../Banco.php";

function registrarAuditoria($usuario_id, $paciente_id, $acao, $descricao = null) {

    $banco = new Banco();
    $conn = $banco->getConexao();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $instituicao_id = isset($GLOBALS['usuario']['instituicao_id']) ? $GLOBALS['usuario']['instituicao_id'] : null;

    if (!$instituicao_id) {
        // Tentar obter a instituição do token ou por query caso falte no escopo global
        // Não podemos auditar sem instituição
        return false;
    }

    $sql = "INSERT INTO auditoria_medica
            (usuario_id, paciente_id, acao, descricao, ip, instituicao_id)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "iisssi",
        $usuario_id,
        $paciente_id,
        $acao,
        $descricao,
        $ip,
        $instituicao_id
    );

    return $stmt->execute();
}
