<?php

require_once __DIR__ . "/../modelo/utils/AuditoriaHelper.php";

abstract class BaseController {
    
    protected function registrarAuditoria($acao, $descricao = null, $pacienteId = null) {
        if (isset($GLOBALS['usuario']['id'])) {
            $usuarioId = $GLOBALS['usuario']['id'];
            registrarAuditoria($usuarioId, $pacienteId, $acao, $descricao);
        }
    }

    protected function jsonResponse($data, $status = true, $code = 200) {
        http_response_code($code);
        header("Content-Type: application/json");
        echo json_encode([
            "status" => $status,
            "dados" => $data
        ]);
        exit;
    }

    protected function errorResponse($message, $code = 400) {
        $this->jsonResponse(["msg" => $message], false, $code);
    }
}
