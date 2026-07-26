<?php

require_once __DIR__ . "/BaseController.php";

class AnamneseController extends BaseController {

    private function handleRead($table, $pacienteId) {
        $usuario = $GLOBALS['usuario'];
        $instituicaoId = $usuario['instituicao_id'];

        $banco = new Banco();
        $conn = $banco->getConexao();

        // Validar se o paciente pertence à instituição (Segurança Multi-tenant)
        $stmtCheck = $conn->prepare("SELECT id FROM pacientes WHERE id = ? AND instituicao_id = ?");
        $stmtCheck->bind_param("ii", $pacienteId, $instituicaoId);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows === 0) {
            $this->errorResponse("Paciente não encontrado ou acesso negado", 403);
        }

        $sql = "SELECT * FROM {$table} WHERE paciente_id = ? AND deleted_at IS NULL ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pacienteId);
        $stmt->execute();
        $result = $stmt->get_result();

        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }

        $this->jsonResponse($dados);
    }

    private function handleCreate($table, $pacienteId, $auditAction) {
        $usuario = $GLOBALS['usuario'];
        $instituicaoId = $usuario['instituicao_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) $this->errorResponse("Dados inválidos");

        $banco = new Banco();
        $conn = $banco->getConexao();

        // Security check
        $stmtCheck = $conn->prepare("SELECT id FROM pacientes WHERE id = ? AND instituicao_id = ?");
        $stmtCheck->bind_param("ii", $pacienteId, $instituicaoId);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows === 0) {
            $this->errorResponse("Paciente não encontrado", 403);
        }

        $input['paciente_id'] = $pacienteId;
        $input['instituicao_id'] = $instituicaoId; // Ensure multi-tenancy

        $fields = [];
        $placeholders = [];
        $types = "";
        $values = [];

        foreach ($input as $key => $val) {
            $fields[] = $key;
            $placeholders[] = "?";
            if (is_int($val)) $types .= "i";
            else if (is_double($val)) $types .= "d";
            else $types .= "s";
            $values[] = $val;
        }

        $sql = "INSERT INTO {$table} (" . implode(",", $fields) . ") VALUES (" . implode(",", $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $this->registrarAuditoria($auditAction, "Adicionou registro na tabela {$table} para o paciente ID: {$pacienteId}", $pacienteId);
            $this->jsonResponse(["id" => $id, "msg" => "Registro criado com sucesso"], true, 201);
        } else {
            $this->errorResponse("Erro ao criar registro: " . $conn->error);
        }
    }

    private function handleUpdate($table, $id, $auditAction) {
        $usuario = $GLOBALS['usuario'];
        $instituicaoId = $usuario['instituicao_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) $this->errorResponse("Dados inválidos");

        $banco = new Banco();
        $conn = $banco->getConexao();

        // Security check (Check if record exists and belongs to institution via paciente)
        $sqlCheck = "SELECT t.paciente_id FROM {$table} t 
                     JOIN pacientes p ON t.paciente_id = p.id 
                     WHERE t.id = ? AND p.instituicao_id = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("ii", $id, $instituicaoId);
        $stmtCheck->execute();
        $res = $stmtCheck->get_result();
        if ($res->num_rows === 0) {
            $this->errorResponse("Registro não encontrado", 404);
        }
        $pacienteId = $res->fetch_assoc()['paciente_id'];

        $sets = [];
        $types = "";
        $values = [];

        foreach ($input as $key => $val) {
            if ($key === 'id' || $key === 'paciente_id' || $key === 'instituicao_id') continue;
            $sets[] = "{$key} = ?";
            if (is_int($val)) $types .= "i";
            else if (is_double($val)) $types .= "d";
            else $types .= "s";
            $values[] = $val;
        }

        if (empty($sets)) $this->errorResponse("Nada para atualizar");

        $sql = "UPDATE {$table} SET " . implode(",", $sets) . " WHERE id = ?";
        $types .= "i";
        $values[] = $id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $this->registrarAuditoria($auditAction, "Atualizou registro ID {$id} na tabela {$table}", $pacienteId);
            $this->jsonResponse(["msg" => "Registro atualizado com sucesso"]);
        } else {
            $this->errorResponse("Erro ao atualizar registro: " . $conn->error);
        }
    }

    private function handleDelete($table, $id, $auditAction) {
        $usuario = $GLOBALS['usuario'];
        $instituicaoId = $usuario['instituicao_id'];

        $banco = new Banco();
        $conn = $banco->getConexao();

        $sqlCheck = "SELECT t.paciente_id FROM {$table} t 
                     JOIN pacientes p ON t.paciente_id = p.id 
                     WHERE t.id = ? AND p.instituicao_id = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("ii", $id, $instituicaoId);
        $stmtCheck->execute();
        $res = $stmtCheck->get_result();
        if ($res->num_rows === 0) {
            $this->errorResponse("Registro não encontrado", 404);
        }
        $pacienteId = $res->fetch_assoc()['paciente_id'];

        // Soft delete
        $sql = "UPDATE {$table} SET deleted_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->registrarAuditoria($auditAction, "Removeu (soft delete) registro ID {$id} na tabela {$table}", $pacienteId);
            $this->jsonResponse(["msg" => "Registro removido com sucesso"]);
        } else {
            $this->errorResponse("Erro ao remover registro");
        }
    }

    // --- Mappings ---

    // Diagnosticos
    public function listDiagnosticos($pacienteId) { $this->handleRead('diagnosticos', $pacienteId); }
    public function storeDiagnostico($pacienteId) { $this->handleCreate('diagnosticos', $pacienteId, 'Criou Diagnóstico'); }
    public function updateDiagnostico($id) { $this->handleUpdate('diagnosticos', $id, 'Editou Diagnóstico'); }
    public function deleteDiagnostico($id) { $this->handleDelete('diagnosticos', $id, 'Deletou Diagnóstico'); }

    // Sintomas
    public function listSintomas($pacienteId) { $this->handleRead('sintomas', $pacienteId); }
    public function storeSintoma($pacienteId) { $this->handleCreate('sintomas', $pacienteId, 'Criou Sintoma'); }
    public function updateSintoma($id) { $this->handleUpdate('sintomas', $id, 'Editou Sintoma'); }

    // Historico Medico
    public function listHistoricoMedico($pacienteId) { $this->handleRead('historico_medico', $pacienteId); }
    public function storeHistoricoMedico($pacienteId) { $this->handleCreate('historico_medico', $pacienteId, 'Criou Histórico Médico'); }
    public function updateHistoricoMedico($id) { $this->handleUpdate('historico_medico', $id, 'Editou Histórico Médico'); }

    // Historico Social
    public function listHistoricoSocial($pacienteId) { $this->handleRead('historico_social', $pacienteId); }
    public function storeHistoricoSocial($pacienteId) { $this->handleCreate('historico_social', $pacienteId, 'Criou Histórico Social'); }
    public function updateHistoricoSocial($id) { $this->handleUpdate('historico_social', $id, 'Editou Histórico Social'); }

    // Qualidade Vida
    public function listQualidadeVida($pacienteId) { $this->handleRead('qualidade_vida_em', $pacienteId); }
    public function storeQualidadeVida($pacienteId) { $this->handleCreate('qualidade_vida_em', $pacienteId, 'Criou Qualidade Vida'); }
    public function updateQualidadeVida($id) { $this->handleUpdate('qualidade_vida_em', $id, 'Editou Qualidade Vida'); }

    // Exame Fisico
    public function listExameFisico($pacienteId) { $this->handleRead('exame_fisico', $pacienteId); }
    public function storeExameFisico($pacienteId) { $this->handleCreate('exame_fisico', $pacienteId, 'Criou Exame Físico'); }
    public function updateExameFisico($id) { $this->handleUpdate('exame_fisico', $id, 'Editou Exame Físico'); }

    // Exames Complementares
    public function listExamesComplementares($pacienteId) { $this->handleRead('exames_complementares', $pacienteId); }
    public function storeExamesComplementares($pacienteId) { $this->handleCreate('exames_complementares', $pacienteId, 'Criou Exames Complementares'); }
    public function updateExamesComplementares($id) { $this->handleUpdate('exames_complementares', $id, 'Editou Exames Complementares'); }

    // Plano Tratamento
    public function listPlanoTratamento($pacienteId) { $this->handleRead('plano_tratamento', $pacienteId); }
    public function storePlanoTratamento($pacienteId) { $this->handleCreate('plano_tratamento', $pacienteId, 'Criou Plano Tratamento'); }
    public function updatePlanoTratamento($id) { $this->handleUpdate('plano_tratamento', $id, 'Editou Plano Tratamento'); }
}
