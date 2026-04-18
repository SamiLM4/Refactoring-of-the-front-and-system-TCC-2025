<?php
require_once __DIR__ . "/../Banco.php";

class QualidadeVidaEm
{
    private $id_paciente;
    private $edss;
    private $questionario_msqol54;
    private $outras_avaliacoes;
    private $cpf;

    public function jsonSerialize()
    {
        $objetoResposta = new stdClass();
        $objetoResposta->id_paciente = $this->id_paciente;
        $objetoResposta->edss = $this->edss;
        $objetoResposta->questionario_msqol54 = $this->questionario_msqol54;
        $objetoResposta->outras_avaliacoes = $this->outras_avaliacoes;

        return $objetoResposta;
    }

    public function toArray()
    {
        return [
            'id_paciente' => $this->getIdPaciente(),
            'edss' => $this->getEdss(),
            'questionario_msqol54' => $this->getQuestionarioMsqol54(),
            'outras_avaliacoes' => $this->getOutrasAvaliacoes()
        ];
    }

    public function cadastrar()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        // Buscar id_paciente pelo CPF
        $query = $conexao->prepare("SELECT id FROM paciente WHERE cpf = ?");
        $query->bind_param("s", $this->cpf);
        $query->execute();
        $resultado = $query->get_result();

        if ($resultado->num_rows === 0) {
            return false; // CPF não encontrado
        }

        $row = $resultado->fetch_assoc();
        $id_paciente = $row['id'];
        $query->close();

        $stm = $conexao->prepare("INSERT INTO qualidade_vida_em (id_paciente, edss, questionario_msqol54, outras_avaliacoes) 
                                  VALUES (?, ?, ?, ?)");

        $stm->bind_param("idss", $id_paciente, $this->edss, $this->questionario_msqol54, $this->outras_avaliacoes);

        if ($stm->execute()) {
            $stm->close();
            return true;
        } else {
            return false;
        }
    }

    public function readCPF()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $conexao->prepare("SELECT * FROM qualidade_vida_em WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)");
        $stm->bind_param("s", $this->cpf);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $linha = $resultado->fetch_object();
            $qualidade = new QualidadeVidaEm();

            $qualidade->setIdPaciente($linha->id_paciente);
            $qualidade->setCpf($this->cpf);
            $qualidade->setEdss($linha->edss);
            $qualidade->setQuestionarioMsqol54($linha->questionario_msqol54);
            $qualidade->setOutrasAvaliacoes($linha->outras_avaliacoes);

            return $qualidade;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    public function update()
    {
        $meuBanco = new Banco();
        $sql = "UPDATE qualidade_vida_em SET edss = ?, questionario_msqol54 = ?, outras_avaliacoes = ?
                WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {
            return false;
        }

        $stm->bind_param("dsss", $this->edss, $this->questionario_msqol54, $this->outras_avaliacoes, $this->cpf);

        if ($stm->execute()) {
            $stm->close();
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "DELETE FROM qualidade_vida_em WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("s", $this->cpf);

            if ($prepareSQL->execute()) {
                $prepareSQL->close();
                return true;
            } else {
                echo "Erro na execução da consulta: " . $prepareSQL->error;
                return false;
            }
        } else {
            echo "Erro na preparação da consulta: " . $conexao->error;
            return false;
        }
    }

    // Getters
    public function getIdPaciente()
    {
        return $this->id_paciente;
    }

    public function getEdss()
    {
        return $this->edss;
    }

    public function getQuestionarioMsqol54()
    {
        return $this->questionario_msqol54;
    }

    public function getOutrasAvaliacoes()
    {
        return $this->outras_avaliacoes;
    }

    public function getCpf()
    {
        return $this->cpf;
    }

    // Setters
    public function setIdPaciente($id_paciente)
    {
        $this->id_paciente = $id_paciente;
    }

    public function setEdss($edss)
    {
        $this->edss = $edss;
    }

    public function setQuestionarioMsqol54($questionario_msqol54)
    {
        $this->questionario_msqol54 = $questionario_msqol54;
    }

    public function setOutrasAvaliacoes($outras_avaliacoes)
    {
        $this->outras_avaliacoes = $outras_avaliacoes;
    }

    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }
}
?>