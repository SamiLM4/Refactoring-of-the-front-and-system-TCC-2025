<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class Banco {
    private $host = "127.0.0.1";
    private $usuario = "root";
    private $senha = "";
    private $banco = "TCC25";
    private $porta = "3306";
    private $con = null;

    public function conectar() {
        $this->host = $_ENV['DB_HOST'] ?? "127.0.0.1";
        $this->usuario = $_ENV['DB_USER'] ?? "root";
        $this->senha = $_ENV['DB_PASS'] ?? "";
        $this->banco = $_ENV['DB_NAME'] ?? "TCC25";
        $this->porta = $_ENV['DB_PORT'] ?? "3306";

        $this->con = new mysqli($this->host, $this->usuario, $this->senha, $this->banco, $this->porta);

        if ($this->con->connect_error) {
            $arrayResposta['status'] = "erro";
            $arrayResposta['cod'] = "1";
            $arrayResposta['msg'] = "Erro ao estabelecer conexão: " . $this->con->connect_error;
            echo json_encode($arrayResposta);
            die();
        }
    }

    public function getConexao() {
        if ($this->con == null) {
            $this->conectar();
        }
        return $this->con;
    }

    public function setConexao($conexao) {
        $this->con = $conexao;
        return $this->con;
    }
}
?>
