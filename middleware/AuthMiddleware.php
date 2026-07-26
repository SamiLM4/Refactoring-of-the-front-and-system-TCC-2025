<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../config/jwt.php";
require_once __DIR__ . "/../modelo/Banco.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function authMiddleware(
    array $permissoesNecessarias = [],
    bool $exigirLicenca = false
) {
    header("Content-Type: application/json");

    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["erro" => "Token não informado"]);
        exit;
    }

    $token = str_replace("Bearer ", "", $headers['Authorization']);

    try {

        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));

        if (!isset($decoded->sub)) {
            http_response_code(401);
            echo json_encode(["erro" => "Token inválido"]);
            exit;
        }

        $con = (new Banco())->getConexao();

        /* =====================================================
           BUSCAR USUÁRIO
        ===================================================== */

        $stmt = $con->prepare("
            SELECT 
                u.id,
                u.email,
                u.ativo,
                u.deleted_at,
                u.instituicao_id,
                u.admin_owner,
                u.nome,
                u.cpf,
                u.crm,
                u.especialidade,
                p.id AS paciente_id
            FROM usuarios u
            LEFT JOIN pacientes p ON p.usuario_id = u.id
            WHERE u.id = ?
            AND u.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->bind_param("i", $decoded->sub);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if (!$usuario) {
            http_response_code(401);
            echo json_encode(["erro" => "Usuário não encontrado"]);
            exit;
        }

        if (!(int)$usuario['ativo']) {
            http_response_code(403);
            echo json_encode(["erro" => "Usuário inativo"]);
            exit;
        }

        if (!$usuario['instituicao_id']) {
            http_response_code(403);
            echo json_encode(["erro" => "Usuário sem instituição vinculada"]);
            exit;
        }

        /* =====================================================
           VALIDAR LICENÇA
        ===================================================== */

        $stmtLicenca = $con->prepare("
            SELECT id
            FROM licencas
            WHERE instituicao_id = ?
            AND status = 'ativa'
            AND usado = TRUE
            AND (expira_em IS NULL OR expira_em > NOW())
            ORDER BY criado_em DESC
            LIMIT 1
        ");

        $stmtLicenca->bind_param("i", $usuario['instituicao_id']);
        $stmtLicenca->execute();
        $licencaAtiva = $stmtLicenca->get_result()->fetch_assoc();

        $usuario['licenca_ativa'] = $licencaAtiva ? 1 : 0;
        $usuario['licenca_id'] = $licencaAtiva['id'] ?? null;

        if ($exigirLicenca && !$usuario['licenca_ativa']) {
            http_response_code(403);
            echo json_encode(["erro" => "Licença ativa obrigatória"]);
            exit;
        }

        /* =====================================================
           BUSCAR PAPÉIS
        ===================================================== */

        $stmtPapeis = $con->prepare("
            SELECT p.id, p.nome
            FROM usuarios_papeis up
            JOIN papeis p ON p.id = up.papel_id
            WHERE up.usuario_id = ?
        ");

        $stmtPapeis->bind_param("i", $usuario['id']);
        $stmtPapeis->execute();
        $resultPapeis = $stmtPapeis->get_result();

        $papeisUsuario = [];

        while ($row = $resultPapeis->fetch_assoc()) {
            $papeisUsuario[] = [
                "id" => (int)$row['id'],
                "nome" => $row['nome']
            ];
        }

        /* =====================================================
           BUSCAR PERMISSÕES
        ===================================================== */

        $stmtPerm = $con->prepare("
            SELECT DISTINCT perm.nome
            FROM usuarios_papeis up
            JOIN papeis_permissoes pp ON pp.papel_id = up.papel_id
            JOIN permissoes perm ON perm.id = pp.permissao_id
            WHERE up.usuario_id = ?
        ");

        $stmtPerm->bind_param("i", $usuario['id']);
        $stmtPerm->execute();
        $result = $stmtPerm->get_result();

        $permissoesUsuario = [];

        while ($row = $result->fetch_assoc()) {
            $permissoesUsuario[] = $row['nome'];
        }

        /* HARDCODED PACIENTE PERMISSIONS */
        foreach ($papeisUsuario as $papel) {
            if (strtoupper($papel['nome']) === 'PACIENTE') {
                $pacientePerms = [
                    'paciente.visualizar',
                    'anamnese.listar',
                    'ia.listar',
                    'ia.visualizar_imagem',
                    'chat.enviar',
                    'chat.listar',
                    'chat.visualizar'
                ];
                foreach ($pacientePerms as $perm) {
                    if (!in_array($perm, $permissoesUsuario)) {
                        $permissoesUsuario[] = $perm;
                    }
                }
            }
        }

        /* =====================================================
           ADMIN OWNER IGNORA VALIDAÇÃO DE PERMISSÕES
        ===================================================== */

        if (!((int)$usuario['id'] === 1)) {

            if (!empty($permissoesNecessarias)) {

                foreach ($permissoesNecessarias as $perm) {

                    if (!in_array($perm, $permissoesUsuario)) {

                        http_response_code(403);
                        echo json_encode([
                            "erro" => "Sem permissão",
                            "permissao_necessaria" => $perm
                        ]);
                        exit;

                    }

                }

            }

        }

        /* =====================================================
           RETORNAR USUÁRIO AUTENTICADO
        ===================================================== */

        return montarRetornoUsuario(
            $usuario,
            $papeisUsuario,
            $permissoesUsuario
        );

    } catch (Exception $e) {

        http_response_code(401);
        echo json_encode(["erro" => "Token inválido ou expirado"]);
        exit;

    }
}

/* =====================================================
   RETORNO DO USUÁRIO
===================================================== */

function montarRetornoUsuario($usuario, $papeis = [], $permissoes = [])
{
    return [

        "id" => (int)$usuario['id'],
        "email" => $usuario['email'],

        "instituicao_id" => (int)$usuario['instituicao_id'],

        "licenca_id" => $usuario['licenca_id'] ? (int)$usuario['licenca_id'] : null,
        "licenca_ativa" => (bool)$usuario['licenca_ativa'],

        "admin_owner" => (bool)$usuario['admin_owner'],
        "paciente_id" => isset($usuario['paciente_id']) ? (int)$usuario['paciente_id'] : null,

        "papeis" => $papeis,
        "permissoes" => $permissoes
    ];
}