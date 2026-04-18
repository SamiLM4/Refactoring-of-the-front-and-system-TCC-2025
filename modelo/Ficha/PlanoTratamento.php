<?php
require_once __DIR__ . "/../Banco.php";

class PlanoTratamento
{
    private $id_paciente;
    private $medicamentos_modificadores_doenca;
    private $tratamento_surtos;
    private $tratamento_sintomas;
    private $reabilitacao;
    private $acompanhamento_psicologico;
    private $outras_terapias;
    private $cpf;

    public function jsonSerialize()
    {
        $objetoResposta = new stdClass();
        $objetoResposta->id_paciente = $this->id_paciente;
        $objetoResposta->medicamentos_modificadores_doenca = $this->medicamentos_modificadores_doenca;
        $objetoResposta->tratamento_surtos = $this->tratamento_surtos;
        $objetoResposta->tratamento_sintomas = $this->tratamento_sintomas;
        $objetoResposta->reabilitacao = $this->reabilitacao;
        $objetoResposta->acompanhamento_psicologico = $this->acompanhamento_psicologico;
        $objetoResposta->outras_terapias = $this->outras_terapias;

        return $objetoResposta;
    }

    public function toArray()
    {
        return [
            'id_paciente' => $this->getIdPaciente(),
            'medicamentos_modificadores_doenca' => $this->getMedicamentosModificadoresDoenca(),
            'tratamento_surtos' => $this->getTratamentoSurtos(),
            'tratamento_sintomas' => $this->getTratamentoSintomas(),
            'reabilitacao' => $this->getReabilitacao(),
            'acompanhamento_psicologico' => $this->getAcompanhamentoPsicologico(),
            'outras_terapias' => $this->getOutrasTerapias()
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
        
        $stm = $conexao->prepare("INSERT INTO plano_tratamento (id_paciente, medicamentos_modificadores__doença, tratamnto_surtos, tratamento_sintomas, reabilitacao, acompanhamento_psicologico, outras_terapias) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stm->bind_param("issssss", $id_paciente, $this->medicamentos_modificadores_doenca, $this->tratamento_surtos, $this->tratamento_sintomas, $this->reabilitacao, $this->acompanhamento_psicologico, $this->outras_terapias);

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

        $stm = $conexao->prepare("SELECT * FROM plano_tratamento WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)");
        $stm->bind_param("s", $this->cpf);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $linha = $resultado->fetch_object();
            $plano = new PlanoTratamento();

            $plano->setIdPaciente($linha->id_paciente);
            $plano->setCpf($this->cpf);
            $plano->setMedicamentosModificadoresDoenca($linha->medicamentos_modificadores__doença);
            $plano->setTratamentoSurtos($linha->tratamnto_surtos);
            $plano->setTratamentoSintomas($linha->tratamento_sintomas);
            $plano->setReabilitacao($linha->reabilitacao);
            $plano->setAcompanhamentoPsicologico($linha->acompanhamento_psicologico);
            $plano->setOutrasTerapias($linha->outras_terapias);

            return $plano;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    public function update()
    {
        $meuBanco = new Banco();
        $sql = "UPDATE plano_tratamento SET medicamentos_modificadores__doença = ?, tratamnto_surtos = ?, tratamento_sintomas = ?, reabilitacao = ?, acompanhamento_psicologico = ?, outras_terapias = ?
                WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {
            return false;
        }

        $stm->bind_param("sssssss", $this->medicamentos_modificadores_doenca, $this->tratamento_surtos, $this->tratamento_sintomas, $this->reabilitacao, $this->acompanhamento_psicologico, $this->outras_terapias, $this->cpf);

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

        $SQL = "DELETE FROM plano_tratamento WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)";

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
    public function getMedicamentosModificadoresDoenca()
    {
        return $this->medicamentos_modificadores_doenca;
    }
    public function getTratamentoSurtos()
    {
        return $this->tratamento_surtos;
    }
    public function getTratamentoSintomas()
    {
        return $this->tratamento_sintomas;
    }
    public function getReabilitacao()
    {
        return $this->reabilitacao;
    }
    public function getAcompanhamentoPsicologico()
    {
        return $this->acompanhamento_psicologico;
    }
    public function getOutrasTerapias()
    {
        return $this->outras_terapias;
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
    public function setMedicamentosModificadoresDoenca($medicamentos)
    {
        $this->medicamentos_modificadores_doenca = $medicamentos;
    }
    public function setTratamentoSurtos($tratamento_surtos)
    {
        $this->tratamento_surtos = $tratamento_surtos;
    }
    public function setTratamentoSintomas($tratamento_sintomas)
    {
        $this->tratamento_sintomas = $tratamento_sintomas;
    }
    public function setReabilitacao($reabilitacao)
    {
        $this->reabilitacao = $reabilitacao;
    }
    public function setAcompanhamentoPsicologico($acompanhamento_psicologico)
    {
        $this->acompanhamento_psicologico = $acompanhamento_psicologico;
    }
    public function setOutrasTerapias($outras_terapias)
    {
        $this->outras_terapias = $outras_terapias;
    }
    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }
}
?>