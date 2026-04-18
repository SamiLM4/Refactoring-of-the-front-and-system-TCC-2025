<?php

require_once __DIR__ . "/BaseController.php";

class AuditoriaController extends BaseController {

    public function list() {
        $usuario = $GLOBALS['usuario'];
        $instituicaoId = $usuario['instituicao_id'];

        $banco = new Banco();
        $conn = $banco->getConexao();

        // Query unificada de Auditoria (Geral + Pacientes)
        $sql = "
            SELECT 
                a.id, 
                a.acao, 
                a.descricao, 
                a.ip, 
                a.data_acao,
                u.email,
                p.nome as paciente_nome
            FROM auditoria_medica a
            INNER JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN pacientes p ON a.paciente_id = p.id
            WHERE a.instituicao_id = ?
            ORDER BY a.data_acao DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $instituicaoId);
        $stmt->execute();
        $result = $stmt->get_result();

        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }

        $this->jsonResponse($dados);
    }

    public function listByPaciente($pacienteId) {
        $usuario = $GLOBALS['usuario'];
        $instituicaoId = $usuario['instituicao_id'];

        $banco = new Banco();
        $conn = $banco->getConexao();

        $sql = "
            SELECT a.*, u.email
            FROM auditoria_medica a
            INNER JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.paciente_id = ? AND a.instituicao_id = ?
            ORDER BY a.data_acao DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $pacienteId, $instituicaoId);
        $stmt->execute();
        $result = $stmt->get_result();

        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }

        $this->jsonResponse($dados);
    }
}
