<?php
require_once __DIR__."/../Banco.php";

class RelacaoMedicoPaciente
{
    private $id_medico;
    private $id_paciente;

    public function jsonSerialize()
    {
        $objetoResposta = new stdClass();
        $objetoResposta->id_medico = $this->id_medico;
        $objetoResposta->id_paciente = $this->id_paciente;

        return $objetoResposta;
    }

    public function toArray()
    {
        return [
            'id_medico' => $this->getIdMedico(),
            'id_paciente' => $this->getIdPaciente()
        ];
    }

    public function cadastrar()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $stm = $conexao->prepare(
            "INSERT INTO relacao_medico_paciente (id_medico, id_paciente) VALUES (?, ?)"
        );

        $stm->bind_param("ii", $this->id_medico, $this->id_paciente);

        $result = $stm->execute();
        $stm->close();
        return $result;
    }

    public function readByCpfMedico($cpfMedico)
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $sql = "SELECT r.* FROM relacao_medico_paciente r 
            JOIN medico m ON r.id_medico = m.id
            WHERE m.cpf = ?";

        $stm = $conexao->prepare($sql);
        $stm->bind_param("s", $cpfMedico);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $relacoes = [];

            while ($linha = $resultado->fetch_object()) {
                $relacao = new RelacaoMedicoPaciente();
                $relacao->setIdMedico($linha->id_medico);
                $relacao->setIdPaciente($linha->id_paciente);
                $relacoes[] = $relacao;
            }

            $stm->close();

            return $relacoes;
        } else {
            return false;
        }
    }

    public function readByCpfPaciente($cpfPaciente)
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $sql = "SELECT r.* FROM relacao_medico_paciente r 
            JOIN paciente p ON r.id_paciente = p.id
            WHERE p.cpf = ?";

        $stm = $conexao->prepare($sql);
        $stm->bind_param("s", $cpfPaciente);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $relacoes = [];

            while ($linha = $resultado->fetch_object()) {
                $relacao = new RelacaoMedicoPaciente();
                $relacao->setIdMedico($linha->id_medico);
                $relacao->setIdPaciente($linha->id_paciente);
                $relacoes[] = $relacao;
            }

            $stm->close();

            return $relacoes;
        } else {
            return false;
        }
    }


    public function read()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $stm = $conexao->prepare(
            "SELECT * FROM relacao_medico_paciente WHERE id_medico = ? AND id_paciente = ?"
        );

        $stm->bind_param("ii", $this->id_medico, $this->id_paciente);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $linha = $resultado->fetch_object();

            $relacao = new RelacaoMedicoPaciente();
            $relacao->setIdMedico($linha->id_medico);
            $relacao->setIdPaciente($linha->id_paciente);

            return $relacao;
        } else {
            return false;
        }
    }

    public function delete()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $stm = $conexao->prepare(
            "DELETE FROM relacao_medico_paciente WHERE id_medico = ? AND id_paciente = ?"
        );

        $stm->bind_param("ii", $this->id_medico, $this->id_paciente);

        $result = $stm->execute();
        $stm->close();
        return $result;
    }

    // Getters
    public function getIdMedico()
    {
        return $this->id_medico;
    }
    public function getIdPaciente()
    {
        return $this->id_paciente;
    }

    // Setters
    public function setIdMedico($id_medico)
    {
        $this->id_medico = $id_medico;
    }
    public function setIdPaciente($id_paciente)
    {
        $this->id_paciente = $id_paciente;
    }
}
?>