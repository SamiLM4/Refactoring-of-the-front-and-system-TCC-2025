<?php

require_once __DIR__ . "/BaseModel.php";

class PacienteModel extends BaseModel {
    protected $table = 'pacientes';

    public function getAll($instituicaoId) {
        $sql = "
            SELECT 
                p.*, 
                u.email, 
                u.ativo,
                i.nome AS instituicao
            FROM pacientes p
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            JOIN instituicao i ON i.id = p.instituicao_id
            WHERE p.deleted_at IS NULL 
            AND p.instituicao_id = ?
        ";
        $result = $this->query($sql, [$instituicaoId], "i");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function getByCpf($cpf, $instituicaoId) {
        $sql = "
            SELECT p.*, u.email, u.ativo
            FROM pacientes p
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.cpf = ? 
            AND p.instituicao_id = ? 
            AND p.deleted_at IS NULL
        ";
        $result = $this->query($sql, [$cpf, $instituicaoId], "si");
        return $result->fetch_assoc();
    }

    public function getById($id, $instituicaoId) {
        $sql = "
            SELECT p.*, u.email, u.ativo
            FROM pacientes p
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.id = ? 
            AND p.instituicao_id = ? 
            AND p.deleted_at IS NULL
        ";
        $result = $this->query($sql, [$id, $instituicaoId], "ii");
        return $result->fetch_assoc();
    }

    public function create($data) {
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        
        $types = "";
        foreach ($data as $val) {
            if (is_int($val)) $types .= "i";
            else $types .= "s";
        }

        $this->query($sql, array_values($data), $types);
        return $this->db->insert_id;
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
    public function delete($id, $instituicaoId) {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ? AND instituicao_id = ?";
        return $this->query($sql, [$id, $instituicaoId], "ii");
    }
}
