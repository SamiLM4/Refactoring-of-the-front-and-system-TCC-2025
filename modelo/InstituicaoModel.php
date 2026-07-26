<?php

require_once __DIR__ . "/BaseModel.php";

class InstituicaoModel extends BaseModel {
    protected $table = 'instituicao';

    public function getByCnpj($cnpj) {
        $sql = "SELECT id FROM {$this->table} WHERE cnpj = ?";
        $result = $this->query($sql, [$cnpj], "s");
        return $result->fetch_assoc();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->query($sql, [$id], "i");
        return $result->fetch_assoc();
    }

    public function create($data) {
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return $this->db->insert_id;
    }

    public function update($id, $data) {
        $fields = "";
        foreach ($data as $key => $value) {
            $fields .= "{$key} = ?, ";
        }
        $fields = rtrim($fields, ", ");
        $sql = "UPDATE {$this->table} SET {$fields} WHERE id = ?";
        $params = array_values($data);
        $params[] = $id;
        return $this->query($sql, $params);
    }
}
