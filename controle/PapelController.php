<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/UsuarioModel.php";

class PapelController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    public function list() {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];

        $this->model->ensureDefaultRoles($instituicaoId);

        $sql = "SELECT id, nome, descricao FROM papeis WHERE instituicao_id = ? AND is_delete = 0";
        $result = $this->model->query($sql, [$instituicaoId], "i");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        $this->jsonResponse($data);
    }

    public function listPermissions() {
        $sql = "SELECT id, nome, descricao FROM permissoes";
        $result = $this->model->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        $this->jsonResponse($data);
    }

    public function store() {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (empty($data['nome'])) {
            $this->errorResponse("Nome do papel é obrigatório");
        }

        $id = $this->model->createRole($data['nome'], $data['descricao'] ?? '', $instituicaoId);
        $this->registrarAuditoria('Criou Papel', "Criou o papel: " . $data['nome']);
        $this->jsonResponse(["id" => $id, "msg" => "Papel criado com sucesso"], true, 201);
    }

    public function delete($id) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];

        $sql = "SELECT id, nome FROM papeis WHERE id = ? AND instituicao_id = ? AND is_delete = 0";
        $res = $this->model->query($sql, [$id, $instituicaoId], "ii");
        $papel = $res->fetch_assoc();

        if (!$papel) {
            $this->errorResponse("Papel não encontrado", 404);
        }

        $nomeUpper = strtoupper($papel['nome']);
        if ($nomeUpper === 'ADMIN' || $nomeUpper === 'PACIENTE') {
            $this->errorResponse("Não é permitido excluir papéis fixos do sistema ({$papel['nome']})", 403);
        }

        $this->model->beginTransaction();
        try {
            // Remove links
            $this->model->query("DELETE FROM usuarios_papeis WHERE papel_id = ? AND instituicao_id = ?", [$id, $instituicaoId], "ii");
            // Soft delete
            $this->model->query("UPDATE papeis SET is_delete = 1 WHERE id = ? AND instituicao_id = ?", [$id, $instituicaoId], "ii");
            
            $this->model->commit();
            $this->registrarAuditoria('Deletou Papel', "Excluiu o papel: " . $papel['nome']);
            $this->jsonResponse(["msg" => "Papel removido com sucesso (soft delete)"]);
        } catch (Exception $e) {
            $this->model->rollback();
            $this->errorResponse("Erro ao remover papel: " . $e->getMessage(), 500);
        }
    }

    public function updatePermissions($papelId) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        
        $json = file_get_contents('php://input');
        $input = json_decode($json, true);

        if (!isset($input['permissoes']) || !is_array($input['permissoes'])) {
            $this->errorResponse("Lista de permissões é obrigatória");
        }

        // Verify role ownership
        $res = $this->model->query("SELECT id FROM papeis WHERE id = ? AND instituicao_id = ?", [$papelId, $instituicaoId], "ii");
        if ($res->num_rows === 0) {
            $this->errorResponse("Papel não encontrado ou não pertence à sua instituição", 403);
        }

        $this->model->beginTransaction();
        try {
            // Clear old
            $this->model->query("DELETE FROM papeis_permissoes WHERE papel_id = ?", [$papelId], "i");

            // Insert new
            foreach ($input['permissoes'] as $permissaoId) {
                $permissaoId = (int)$permissaoId;
                // Just to be safe, verify permission existence
                $check = $this->model->query("SELECT id FROM permissoes WHERE id = ?", [$permissaoId], "i");
                if ($check->num_rows > 0) {
                    $this->model->query("INSERT INTO papeis_permissoes (papel_id, permissao_id) VALUES (?, ?)", [$papelId, $permissaoId], "ii");
                }
            }

            $this->model->commit();
            $this->registrarAuditoria('Alterou Permissões', "Alterou as permissões do papel ID: " . $papelId);
            $this->jsonResponse(["msg" => "Permissões atualizadas com sucesso"]);
        } catch (Exception $e) {
            $this->model->rollback();
            $this->errorResponse("Erro ao atualizar permissões: " . $e->getMessage(), 500);
        }
    }

    public function getRolePermissions($papelId) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];

        $sql = "
            SELECT p.id 
            FROM permissoes p
            JOIN papeis_permissoes pp ON pp.permissao_id = p.id
            JOIN papeis pap ON pap.id = pp.papel_id
            WHERE pp.papel_id = ? AND pap.instituicao_id = ?
        ";
        $result = $this->model->query($sql, [$papelId, $instituicaoId], "ii");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row['id'];
        $this->jsonResponse($data);
    }
}
