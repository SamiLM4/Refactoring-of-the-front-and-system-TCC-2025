<?php

require_once __DIR__ . "/BaseModel.php";

class PlanoModel extends BaseModel {
    protected $table = 'planos';

    public function getAllActive() {
        $sql = "SELECT * FROM {$this->table} WHERE ativo = TRUE";
        $result = $this->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND ativo = TRUE";
        $result = $this->query($sql, [$id], "i");
        return $result->fetch_assoc();
    }
}
