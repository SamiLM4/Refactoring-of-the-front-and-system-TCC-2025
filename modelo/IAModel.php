<?php

require_once __DIR__ . "/BaseModel.php";

class IAModel extends BaseModel {
    protected $table = 'ia_results';

    public function create($data) {
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return $this->db->insert_id;
    }

    public function getAll($instituicaoId) {
        $sql = "SELECT * FROM {$this->table} WHERE instituicao_id = ? ORDER BY data_diagnostico DESC";
        $result = $this->query($sql, [$instituicaoId], "i");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function getById($id, $instituicaoId) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND instituicao_id = ?";
        $result = $this->query($sql, [$id, $instituicaoId], "ii");
        return $result->fetch_assoc();
    }

    public function getByPaciente($pacienteId, $instituicaoId) {
        $sql = "SELECT * FROM {$this->table} WHERE paciente_id = ? AND instituicao_id = ? ORDER BY data_diagnostico DESC";
        $result = $this->query($sql, [$pacienteId, $instituicaoId], "ii");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function delete($id, $instituicaoId) {
        $sql = "DELETE FROM {$this->table} WHERE id = ? AND instituicao_id = ?";
        return $this->query($sql, [$id, $instituicaoId], "ii");
    }
}
