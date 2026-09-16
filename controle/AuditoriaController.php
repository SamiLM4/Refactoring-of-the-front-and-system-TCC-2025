<?php

require_once __DIR__ . "/BaseController.php";

class AuditoriaController extends BaseController {

    public function list() {
        $usuario = $GLOBALS['usuario'];
        $instituicaoId = $usuario['instituicao_id'];

        $banco = new Banco();
        $conn = $banco->getConexao();

        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = (int)($_GET['por_pagina'] ?? 20);
        $porPagina = max(1, min($porPagina, 100));
        $offset = ($pagina - 1) * $porPagina;

        $busca = trim($_GET['busca'] ?? '');
        $filtro = "";
        $params = [$instituicaoId];
        $types = "i";

        if ($busca !== '') {
            $filtro = " AND (a.acao LIKE ? OR a.descricao LIKE ? OR u.email LIKE ? OR p.nome LIKE ?) ";
            $like = "%{$busca}%";
            array_push($params, $like, $like, $like, $like);
            $types .= "ssss";
        }

        // Query unificada de Auditoria (Geral + Pacientes)
        $sqlCount = "
            SELECT COUNT(*) as total
            FROM auditoria_medica a
            INNER JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN pacientes p ON a.paciente_id = p.id
            WHERE a.instituicao_id = ? {$filtro}
        ";
        $stmt = $conn->prepare($sqlCount);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

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
            WHERE a.instituicao_id = ? {$filtro}
            ORDER BY a.data_acao DESC
            LIMIT ? OFFSET ?
        ";
        $paramsPagina = array_merge($params, [$porPagina, $offset]);
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types . "ii", ...$paramsPagina);
        $stmt->execute();
        $result = $stmt->get_result();

        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }

        $this->jsonResponse([
            "registros" => $dados,
            "paginacao" => [
                "pagina" => $pagina,
                "por_pagina" => $porPagina,
                "total" => $total,
                "total_paginas" => (int)ceil($total / $porPagina)
            ]
        ]);
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
