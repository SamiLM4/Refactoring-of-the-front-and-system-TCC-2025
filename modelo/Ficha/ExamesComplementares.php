<?php
require_once __DIR__ . "/../Banco.php";

class ExamesComplementares
{
    private $id_paciente;
    private $rm_cerebro_medula;
    private $potenciais_evocados_visuais;
    private $potenciais_evocados_somatossensoriais;
    private $potenciais_evocados_auditivos_de_tronco_encefalico;
    private $analise_liquido_cefalorraquidiano;
    private $outros_exames;
    private $cpf;

    public function jsonSerialize()
    {
        $objetoResposta = new stdClass();
        $objetoResposta->id_paciente = $this->id_paciente;
        $objetoResposta->rm_cerebro_medula = $this->rm_cerebro_medula;
        $objetoResposta->potenciais_evocados_visuais = $this->potenciais_evocados_visuais;
        $objetoResposta->potenciais_evocados_somatossensoriais = $this->potenciais_evocados_somatossensoriais;
        $objetoResposta->potenciais_evocados_auditivos_de_tronco_encefalico = $this->potenciais_evocados_auditivos_de_tronco_encefalico;
        $objetoResposta->analise_liquido_cefalorraquidiano = $this->analise_liquido_cefalorraquidiano;
        $objetoResposta->outros_exames = $this->outros_exames;

        return $objetoResposta;
    }

    public function toArray()
    {
        return [
            'id_paciente' => $this->getIdPaciente(),
            'rm_cerebro_medula' => $this->getRmCerebroMedula(),
            'potenciais_evocados_visuais' => $this->getPotenciaisEvocadosVisuais(),
            'potenciais_evocados_somatossensoriais' => $this->getPotenciaisEvocadosSomatossensoriais(),
            'potenciais_evocados_auditivos_de_tronco_encefalico' => $this->getPotenciaisEvocadosAuditivosDeTroncoEncefalico(),
            'analise_liquido_cefalorraquidiano' => $this->getAnaliseLiquidoCefalorraquidiano(),
            'outros_exames' => $this->getOutrosExames()
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

        $stm = $conexao->prepare("INSERT INTO exames_complementares (id_paciente, rm_cerebro_medula, potenciais_evocados_visuais, potenciais_evocados_somatossensoriais, potenciais_evocados_auditivos_de_tronco_encefálico, análise_do_líquido_cefalorraquidiano, outros_exames) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stm->bind_param("issssss", $id_paciente, $this->rm_cerebro_medula, $this->potenciais_evocados_visuais, $this->potenciais_evocados_somatossensoriais, $this->potenciais_evocados_auditivos_de_tronco_encefalico, $this->analise_liquido_cefalorraquidiano, $this->outros_exames);

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

        $stm = $conexao->prepare("SELECT * FROM exames_complementares WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)");
        $stm->bind_param("s", $this->cpf);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $linha = $resultado->fetch_object();
            $exames = new ExamesComplementares();

            $exames->setIdPaciente($linha->id_paciente);
            $exames->setCpf($this->cpf);
            $exames->setRmCerebroMedula($linha->rm_cerebro_medula);
            $exames->setPotenciaisEvocadosVisuais($linha->potenciais_evocados_visuais);
            $exames->setPotenciaisEvocadosSomatossensoriais($linha->potenciais_evocados_somatossensoriais);
            $exames->setPotenciaisEvocadosAuditivosDeTroncoEncefalico($linha->potenciais_evocados_auditivos_de_tronco_encefálico);
            $exames->setAnaliseLiquidoCefalorraquidiano($linha->análise_do_líquido_cefalorraquidiano);
            $exames->setOutrosExames($linha->outros_exames);

            return $exames;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    public function update()
    {
        $meuBanco = new Banco();
        $sql = "UPDATE exames_complementares SET rm_cerebro_medula = ?, potenciais_evocados_visuais = ?, potenciais_evocados_somatossensoriais = ?, potenciais_evocados_auditivos_de_tronco_encefálico = ?, análise_do_líquido_cefalorraquidiano = ?, outros_exames = ?
                WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {
            return false;
        }

        $stm->bind_param("sssssss", $this->rm_cerebro_medula, $this->potenciais_evocados_visuais, $this->potenciais_evocados_somatossensoriais, $this->potenciais_evocados_auditivos_de_tronco_encefalico, $this->analise_liquido_cefalorraquidiano, $this->outros_exames, $this->cpf);

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

        $SQL = "DELETE FROM exames_complementares WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?)";

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
    public function getRmCerebroMedula()
    {
        return $this->rm_cerebro_medula;
    }
    public function getPotenciaisEvocadosVisuais()
    {
        return $this->potenciais_evocados_visuais;
    }
    public function getPotenciaisEvocadosSomatossensoriais()
    {
        return $this->potenciais_evocados_somatossensoriais;
    }
    public function getPotenciaisEvocadosAuditivosDeTroncoEncefalico()
    {
        return $this->potenciais_evocados_auditivos_de_tronco_encefalico;
    }
    public function getAnaliseLiquidoCefalorraquidiano()
    {
        return $this->analise_liquido_cefalorraquidiano;
    }
    public function getOutrosExames()
    {
        return $this->outros_exames;
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
    public function setRmCerebroMedula($rm)
    {
        $this->rm_cerebro_medula = $rm;
    }
    public function setPotenciaisEvocadosVisuais($visuais)
    {
        $this->potenciais_evocados_visuais = $visuais;
    }
    public function setPotenciaisEvocadosSomatossensoriais($somato)
    {
        $this->potenciais_evocados_somatossensoriais = $somato;
    }
    public function setPotenciaisEvocadosAuditivosDeTroncoEncefalico($auditivos)
    {
        $this->potenciais_evocados_auditivos_de_tronco_encefalico = $auditivos;
    }
    public function setAnaliseLiquidoCefalorraquidiano($analise)
    {
        $this->analise_liquido_cefalorraquidiano = $analise;
    }
    public function setOutrosExames($outros)
    {
        $this->outros_exames = $outros;
    }
    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }
}
?>