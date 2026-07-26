<?php

require_once __DIR__ . "/BaseModel.php";

class UsuarioModel extends BaseModel {
    protected $table = 'usuarios';

    public function getAuthenticatedUser($email) {
        $sql = "
        SELECT 
            u.id,
            u.email,
            u.senha_hash,
            u.instituicao_id,
            u.admin_owner,
            u.nome,
            u.cpf,
            u.crm,
            u.especialidade,
            pl.nome AS tipo_licenca,
            CASE 
                WHEN l.status = 'ativa'
                 AND l.usado = TRUE
                 AND (l.expira_em IS NULL OR l.expira_em > NOW())
                THEN TRUE
                ELSE FALSE
            END AS licenca_ativa

        FROM usuarios u

        LEFT JOIN licencas l 
            ON l.instituicao_id = u.instituicao_id
            AND l.status = 'ativa'

        LEFT JOIN planos pl
            ON pl.id = l.plano_id

        WHERE u.email = ?
        AND u.ativo = 1
        AND u.deleted_at IS NULL

        LIMIT 1;
        ";
        
        $result = $this->query($sql, [$email], "s");
        return $result->fetch_assoc();
    }

    public function create($data) {
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        
        $types = "";
        foreach ($data as $val) {
            if (is_int($val)) $types .= "i";
            elseif (is_double($val)) $types .= "d";
            else $types .= "s";
        }

        $this->query($sql, array_values($data), $types);
        return $this->db->insert_id;
    }

    public function createRole($name, $description, $instituicaoId) {
        $sql = "INSERT INTO papeis (nome, descricao, instituicao_id) VALUES (?, ?, ?)";
        $this->query($sql, [$name, $description, $instituicaoId], "ssi");
        return $this->db->insert_id;
    }

    public function getRoleByName($name, $instituicaoId) {
        $sql = "SELECT * FROM papeis WHERE nome = ? AND instituicao_id = ?";
        $result = $this->query($sql, [$name, $instituicaoId], "si");
        return $result->fetch_assoc();
    }

    public function beginTransaction() {
        $this->db->begin_transaction();
    }

    public function commit() {
        $this->db->commit();
    }

    public function rollback() {
        $this->db->rollback();
    }

    public function assignRole($usuarioId, $papelId, $instituicaoId) {
        $sql = "INSERT INTO usuarios_papeis (usuario_id, papel_id, instituicao_id) VALUES (?, ?, ?)";
        return $this->query($sql, [$usuarioId, $papelId, $instituicaoId], "iii");
    }

    public function update($id, $instituicaoId, $data) {
        $fields = "";
        foreach ($data as $key => $value) {
            $fields .= "{$key} = ?, ";
        }
        $fields = rtrim($fields, ", ");
        $sql = "UPDATE {$this->table} SET {$fields} WHERE id = ? AND instituicao_id = ?";
        
        $params = array_values($data);
        $params[] = $id;
        $params[] = $instituicaoId;

        $types = "";
        foreach ($params as $val) {
            if (is_int($val)) $types .= "i";
            else $types .= "s";
        }

        return $this->query($sql, $params, $types);
    }

    public function saveRefreshToken($usuarioId, $tokenHash, $expiraEm) {
        $sql = "INSERT INTO refresh_tokens (usuario_id, token, expira_em) VALUES (?, ?, ?)";
        return $this->query($sql, [$usuarioId, $tokenHash, $expiraEm], "iss");
    }

    public function revokeRefreshToken($token) {
        $sql = "UPDATE refresh_tokens SET revogado = 1 WHERE token = ?";
        return $this->query($sql, [$token], "s");
    }

    public function createAdminRecord($usuarioId, $instituicaoId, $nome) {
        $sql = "UPDATE usuarios SET nome = ?, admin_owner = 1 WHERE id = ? AND instituicao_id = ?";
        return $this->query($sql, [$nome, $usuarioId, $instituicaoId], "sii");
    }

    public function resetAdminStatus($usuarioId, $instituicaoId) {
        $sql = "UPDATE usuarios SET admin_owner = 0 WHERE id = ? AND instituicao_id = ?";
        return $this->query($sql, [$usuarioId, $instituicaoId], "ii");
    }

    public function ensureDefaultRoles($instituicaoId) {
        $defaultRoles = [
            'ADMIN' => 'Administrador do sistema com acesso total.',
            'MEDICO' => 'Profissional de saúde que realiza diagnósticos.',
            'PACIENTE' => 'Paciente que acessa seus próprios dados e diagnósticos.'
        ];

        foreach ($defaultRoles as $roleName => $desc) {
            $result = $this->query("SELECT id FROM papeis WHERE nome = ? AND instituicao_id = ?", [$roleName, $instituicaoId], "si");
            $role = $result->fetch_assoc();
            if (!$role) {
                $newRoleId = $this->createRole($roleName, $desc, $instituicaoId);
                
                // If it's PACIENTE, ensure it gets the permissions
                if ($roleName === 'PACIENTE') {
                    $this->assignDefaultPacientePermissions($newRoleId);
                }
            }
        }
    }
    public function assignDefaultPacientePermissions($roleId) {
        $permissions = [
            'paciente.visualizar',
            'anamnese.listar',
            'ia.listar',
            'ia.visualizar_imagem',
            'chat.enviar',
            'chat.listar',
            'chat.visualizar'
        ];
        
        $placeholders = implode(',', array_fill(0, count($permissions), '?'));
        $sql = "SELECT id FROM permissoes WHERE nome IN ($placeholders)";
        $types = str_repeat('s', count($permissions));
        
        $result = $this->query($sql, $permissions, $types);
        
        while ($row = $result->fetch_assoc()) {
            $permId = $row['id'];
            $insertSql = "INSERT IGNORE INTO papeis_permissoes (papel_id, permissao_id) VALUES (?, ?)";
            $this->query($insertSql, [$roleId, $permId], "ii");
        }
    }
}
