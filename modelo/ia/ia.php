<?php
require_once __DIR__ . "/../Banco.php";

class IAResultado
{
    private $id_paciente;
    private $nome;
    private $cpf;
    private $imagens = [];
    private $diagnostico;
    private $data;

    // CREATE
    public function cadastrar()
    {
        $banco = new Banco();
        $conexao = $banco->getConexao();

        $dataAtual = date("Y-m-d H:i:s");

        $stmt = $conexao->prepare("INSERT INTO ia_results 
            (id_paciente, nome, cpf, imagem, diagnostico, data_diagnostico)
            VALUES (
                (SELECT id FROM paciente WHERE cpf = ?),
                (SELECT nome FROM paciente WHERE cpf = ?),
                ?, ?, ?, ?
            )");

        $imagensJson = json_encode($this->imagens);

        // cpf, cpf, cpf, imagensJson, diagnostico, dataAtual
        $stmt->bind_param("ssssss",
            $this->cpf,
            $this->cpf,
            $this->cpf,
            $imagensJson,
            $this->diagnostico,
            $dataAtual
        );

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            return false;
        }
    }

    // READ (por CPF)
    public function readCPF()
    {
        $banco = new Banco();
        $conexao = $banco->getConexao();

        $stmt = $conexao->prepare("SELECT * FROM ia_results WHERE cpf = ?");
        $stmt->bind_param("s", $this->cpf);

        if ($stmt->execute()) {
            $resultado = $stmt->get_result();
            $stmt->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $linha = $resultado->fetch_object();
            $ia = new IAResultado();
            $ia->setIdPaciente($linha->id_paciente);
            $ia->setNome($linha->nome);
            $ia->setCpf($linha->cpf);

            // garante sempre array
            $imagens = json_decode($linha->imagem, true);
            if (!is_array($imagens)) {
                $imagens = $linha->imagem ? [$linha->imagem] : [];
            }
            $ia->setImagens($imagens);

            $ia->setDiagnostico($linha->diagnostico);
            $ia->setData($linha->data_diagnostico);

            return $ia;
        } else {
            return false;
        }
    }

    // READ ALL
    public function readAll()
    {
        $banco = new Banco();
        $conexao = $banco->getConexao();

        $resultado = $conexao->query("SELECT * FROM ia_results");

        $lista = [];

        while ($linha = $resultado->fetch_object()) {
            $ia = new IAResultado();
            $ia->setIdPaciente($linha->id_paciente);
            $ia->setNome($linha->nome);
            $ia->setCpf($linha->cpf);

            $imagens = json_decode($linha->imagem, true);
            if (!is_array($imagens)) {
                $imagens = $linha->imagem ? [$linha->imagem] : [];
            }
            $ia->setImagens($imagens);

            $ia->setDiagnostico($linha->diagnostico);
            $ia->setData($linha->data_diagnostico);
            $lista[] = $ia;
        }

        return $lista;
    }

    // DELETE (pelo CPF)
    public function delete()
    {
        $banco = new Banco();
        $conexao = $banco->getConexao();

        $stmt = $conexao->prepare("DELETE FROM ia_results WHERE cpf = ?");
        $stmt->bind_param("s", $this->cpf);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            return false;
        }
    }

    // GETTERS
    public function getIdPaciente() { return $this->id_paciente; }
    public function getNome() { return $this->nome; }
    public function getCpf() { return $this->cpf; }
    public function getImagens() { return $this->imagens; }
    public function getDiagnostico() { return $this->diagnostico; }
    public function getData() { return $this->data; }

    // SETTERS
    public function setIdPaciente($id_paciente) { $this->id_paciente = $id_paciente; }
    public function setNome($nome) { $this->nome = $nome; }
    public function setCpf($cpf) { $this->cpf = $cpf; }
    public function setImagens($imagens) { $this->imagens = $imagens; }
    public function setDiagnostico($diagnostico) { $this->diagnostico = $diagnostico; }
    public function setData($data) { $this->data = $data; }
}
