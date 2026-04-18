<?php
require_once __DIR__ . "/../Banco.php";

class ExameFisico
{
    private $id_paciente;
    private $exame_neurologico;
    private $forca_muscular;
    private $reflexos;
    private $coordenacao;
    private $sensibilidade;
    private $equilibrio;
    private $funcao_visual;
    private $outros_exames_fisicos;
    private $cpf;

    public function jsonSerialize()
    {
        $objetoResposta = new stdClass();
        $objetoResposta->id_paciente = $this->id_paciente;
        $objetoResposta->exame_neurologico = $this->exame_neurologico;
        $objetoResposta->forca_muscular = $this->forca_muscular;
        $objetoResposta->reflexos = $this->reflexos;
        $objetoResposta->coordenacao = $this->coordenacao;
        $objetoResposta->sensibilidade = $this->sensibilidade;
        $objetoResposta->equilibrio = $this->equilibrio;
        $objetoResposta->funcao_visual = $this->funcao_visual;
        $objetoResposta->outros_exames_fisicos = $this->outros_exames_fisicos;

        return $objetoResposta;
    }

    public function toArray()
    {
        return [
            'id_paciente' => $this->getIdPaciente(),
            'exame_neurologico' => $this->getExameNeurologico(),
            'forca_muscular' => $this->getForcaMuscular(),
            'reflexos' => $this->getReflexos(),
            'coordenacao' => $this->getCoordenacao(),
            'sensibilidade' => $this->getSensibilidade(),
            'equilibrio' => $this->getEquilibrio(),
            'funcao_visual' => $this->getFuncaoVisual(),
            'outros_exames_fisicos' => $this->getOutrosExamesFisicos()
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


        $stm = $conexao->prepare("INSERT INTO exame_fisico (id_paciente, exame_neurologico, forca_muscular, reflexos, coordenacao, sensibilidade, equilibrio, funcao_visual, outros_exames_fisicos) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stm->bind_param("issssssss", $id_paciente, $this->exame_neurologico, $this->forca_muscular, $this->reflexos, $this->coordenacao, $this->sensibilidade, $this->equilibrio, $this->funcao_visual, $this->outros_exames_fisicos);

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

        $stm = $conexao->prepare("SELECT * FROM exame_fisico WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)");
        $stm->bind_param("s", $this->cpf);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $linha = $resultado->fetch_object();
            $exame = new ExameFisico();

            $exame->setIdPaciente($linha->id_paciente);
            $exame->setCpf($this->cpf);
            $exame->setExameNeurologico($linha->exame_neurologico);
            $exame->setForcaMuscular($linha->forca_muscular);
            $exame->setReflexos($linha->reflexos);
            $exame->setCoordenacao($linha->coordenacao);
            $exame->setSensibilidade($linha->sensibilidade);
            $exame->setEquilibrio($linha->equilibrio);
            $exame->setFuncaoVisual($linha->funcao_visual);
            $exame->setOutrosExamesFisicos($linha->outros_exames_fisicos);

            return $exame;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    public function update()
    {
        $meuBanco = new Banco();
        $sql = "UPDATE exame_fisico SET exame_neurologico = ?, forca_muscular = ?, reflexos = ?, coordenacao = ?, sensibilidade = ?, equilibrio = ?, funcao_visual = ?, outros_exames_fisicos = ?
                WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {
            return false;
        }

        $stm->bind_param("sssssssss", $this->exame_neurologico, $this->forca_muscular, $this->reflexos, $this->coordenacao, $this->sensibilidade, $this->equilibrio, $this->funcao_visual, $this->outros_exames_fisicos, $this->cpf);

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

        $SQL = "DELETE FROM exame_fisico WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)";

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
    public function getExameNeurologico()
    {
        return $this->exame_neurologico;
    }
    public function getForcaMuscular()
    {
        return $this->forca_muscular;
    }
    public function getReflexos()
    {
        return $this->reflexos;
    }
    public function getCoordenacao()
    {
        return $this->coordenacao;
    }
    public function getSensibilidade()
    {
        return $this->sensibilidade;
    }
    public function getEquilibrio()
    {
        return $this->equilibrio;
    }
    public function getFuncaoVisual()
    {
        return $this->funcao_visual;
    }
    public function getOutrosExamesFisicos()
    {
        return $this->outros_exames_fisicos;
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
    public function setExameNeurologico($exame_neurologico)
    {
        $this->exame_neurologico = $exame_neurologico;
    }
    public function setForcaMuscular($forca_muscular)
    {
        $this->forca_muscular = $forca_muscular;
    }
    public function setReflexos($reflexos)
    {
        $this->reflexos = $reflexos;
    }
    public function setCoordenacao($coordenacao)
    {
        $this->coordenacao = $coordenacao;
    }
    public function setSensibilidade($sensibilidade)
    {
        $this->sensibilidade = $sensibilidade;
    }
    public function setEquilibrio($equilibrio)
    {
        $this->equilibrio = $equilibrio;
    }
    public function setFuncaoVisual($funcao_visual)
    {
        $this->funcao_visual = $funcao_visual;
    }
    public function setOutrosExamesFisicos($outros_exames_fisicos)
    {
        $this->outros_exames_fisicos = $outros_exames_fisicos;
    }
    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }
}
?>