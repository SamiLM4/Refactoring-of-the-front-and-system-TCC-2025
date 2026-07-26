<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/UsuarioModel.php";

class AdminController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    public function list() {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];

        $sql = "
            SELECT u.id, u.nome, u.email, u.admin_owner, u.ativo
            FROM usuarios u
            JOIN usuarios_papeis up ON up.usuario_id = u.id
            JOIN papeis p ON p.id = up.papel_id
            WHERE u.instituicao_id = ? AND p.nome = 'ADMIN' AND u.deleted_at IS NULL
        ";
        $result = $this->model->query($sql, [$instituicaoId], "i");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        $this->jsonResponse($data);
    }

    public function update($id) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['nome'])) {
            $this->errorResponse("Nome é obrigatório");
        }

        $sql = "UPDATE usuarios SET nome = ? WHERE id = ? AND instituicao_id = ? AND deleted_at IS NULL";
        $this->model->query($sql, [$input['nome'], $id, $instituicaoId], "sii");

        $this->registrarAuditoria('Editou Admin', "Editou o administrador ID: " . $id);
        $this->jsonResponse(["msg" => "Administrador atualizado com sucesso"]);
    }

    public function delete($id) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];

        // Enforce safe delete
        $sql = "UPDATE usuarios SET deleted_at = NOW(), ativo = 0 WHERE id = ? AND instituicao_id = ?";
        $this->model->query($sql, [$id, $instituicaoId], "ii");

        $this->registrarAuditoria('Deletou Admin', "Excluiu o administrador ID: " . $id);
        $this->jsonResponse(["msg" => "Administrador removido com sucesso (Safe Delete)"]);
    }

    public function attachRole() {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['usuario_id']) || empty($input['papel_id'])) {
            $this->errorResponse("ID do usuário e ID do papel são obrigatórios");
        }

        $this->model->assignRole($input['usuario_id'], $input['papel_id'], $instituicaoId);
        
        $this->registrarAuditoria('Atribuiu Papel', "Atribuiu papel ID " . $input['papel_id'] . " ao usuário ID " . $input['usuario_id']);
        $this->jsonResponse(["msg" => "Papel atribuído com sucesso"]);
    }
}
