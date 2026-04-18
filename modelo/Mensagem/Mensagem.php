<?php
require_once __DIR__ . '/../Banco.php';

class Mensagem
{
    private $cpf_medico;
    private $mensagem;
    private $origem;
    private $instituicao;

    // Setters
    public function setCpfMedico($cpf_medico)
    {
        $this->cpf_medico = $cpf_medico;
    }

    public function setMensagem($mensagem)
    {
        $this->mensagem = $mensagem;
    }

    public function setOrigem($origem)
    {
        $this->origem = $origem;
    }

    public function setInstituicao($instituicao)
    {
        $this->instituicao = $instituicao;
    }
    // Getters
    public function getCpfMedico()
    {
        return $this->cpf_medico;
    }

    public function getMensagem()
    {
        return $this->mensagem;
    }

    public function getOrigem()
    {
        return $this->origem;
    }

    public function getInstituicao()
    {
        return $this->instituicao;
    }

    // Enviar mensagem (usado por médico)
public function enviar()
{
    $meuBanco = new Banco();
    $conn = $meuBanco->getConexao();

    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }

    $sql = "INSERT INTO mensagens_chat (id_instituicao, cpf_medico, mensagem, origem) VALUES (?, ?, ?, ?)";
                                        
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Erro na preparação: " . $conn->error);
        return false;
    }

    // Corrigido bind_param: cpf_medico é string
    $stmt->bind_param("isss", $this->instituicao, $this->cpf_medico, $this->mensagem, $this->origem);

    if (!$stmt->execute()) {
        error_log("Erro ao executar: " . $stmt->error);
        return false;
    }

    return true;
}


    // ADM responder mensagem
    public function responder()
    {
        // origem = 'adm' será automaticamente atribuída
        return $this->enviar();
    }

    // Listar mensagens de um médico
    public function listarMensagensMedico($cpf_medico)
    {
        $meuBanco = new Banco();
        $conn = $meuBanco->getConexao();

        $sql = "SELECT * FROM mensagens_chat WHERE cpf_medico = ? AND id_instituicao = ? ORDER BY data_envio ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cpf_medico, $this->instituicao);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $mensagens = [];

            while ($linha = $result->fetch_assoc()) {
                $mensagens[] = $linha;
            }

            return $mensagens;
        } else {
            return false;
        }
    }

    // Listar todas mensagens para o ADM
    public function listarTodas()
    {
        $meuBanco = new Banco();
        $conn = $meuBanco->getConexao();

        $sql = "SELECT m.*, med.nome AS nome_medico 
        FROM mensagens_chat m 
        JOIN medico med ON m.cpf_medico = med.cpf 
        WHERE m.id_instituicao = ?
        ORDER BY m.data_envio DESC";

        // Prepara e executa a query
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $this->instituicao);
        $stmt->execute();

        // Recupera o resultado do statement
        $result = $stmt->get_result();

        $mensagens = [];
        while ($linha = $result->fetch_assoc()) {
            $mensagens[] = $linha;
        }

        return $mensagens;

    }
}
?>