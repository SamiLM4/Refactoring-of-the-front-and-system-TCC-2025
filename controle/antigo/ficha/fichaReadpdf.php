<?php
require_once "modelo/ficha/Sintomas.php";
require_once "modelo/ficha/Diagnostico.php";
require_once "modelo/ficha/ExameFisico.php";
require_once "modelo/ficha/ExamesComplementares.php";
require_once "modelo/ficha/HistoricoMedico.php";
require_once "modelo/ficha/HistoricoSocial.php";
require_once "modelo/ficha/PlanoTratamento.php";
require_once "modelo/ficha/QualidadeVidaEm.php";
require_once "modelo/Medico/Medico.php";
require_once "modelo/Paciente/Paciente.php";
require_once "modelo/Medico/RelacaoMedicoPaciente.php";

function buscarDadosFicha($cpfPaciente, $instituicao)
{
    $sintomas = new Sintomas();
    $sintomas->setCpf($cpfPaciente);
    $dadosSintomas = $sintomas->readCPF();

    $diagnostico = new Diagnostico();
    $diagnostico->setCpf($cpfPaciente);
    $dadosDiagnostico = $diagnostico->readCPF();

    $exame = new ExameFisico();
    $exame->setCpf($cpfPaciente);
    $dadosExame = $exame->readCPF();

    $exames = new ExamesComplementares();
    $exames->setCpf($cpfPaciente);
    $dadosExames = $exames->readCPF();

    $historico = new HistoricoMedico();
    $historico->setCpf($cpfPaciente);
    $dadosHistorico = $historico->readCPF();

    $historicoSocial = new HistoricoSocial();
    $historicoSocial->setCpf($cpfPaciente);
    $dadosHistoricoSocial = $historicoSocial->readCPF();

    $planoTratamento = new PlanoTratamento();
    $planoTratamento->setCpf($cpfPaciente);
    $dadosPlano = $planoTratamento->readCPF();

    $qualidadeVida = new QualidadeVidaEm();
    $qualidadeVida->setCpf($cpfPaciente);
    $dadosQualidade = $qualidadeVida->readCPF();

    $relacao = new relacao();
    $relacao->setcpfpaciente($cpfPaciente);
    $relacoes = $relacao->readCPFpaciente();
    $cpfMedico = isset($relacoes[0]) ? $relacoes[0]->getcpfmedico() : null;

    $medico = new Medico();
    $medico->setinstituicao($instituicao);
    $medico->setcpf($cpfMedico);
    $medicos = $medico->readCPF();
    $nomeMedico = $medicos[0]->getNome();

    $paciente = new Paciente();
    $paciente->setinstituicao($instituicao);
    $pacientes = $paciente->readCPF();
    $nomePaciente = $pacientes[0]->getNome();


    return [
        "paciente" => $nomePaciente,
        "cpf_paciente" => $cpfPaciente,
        "medico" => $nomeMedico,
        "sintomas" => $dadosSintomas ? $dadosSintomas->toArray() : [],
        "diagnostico" => $dadosDiagnostico ? $dadosDiagnostico->toArray() : [],
        "exame_fisico" => $dadosExame ? $dadosExame->toArray() : [],
        "exames_complementares" => $dadosExames ? $dadosExames->toArray() : [],
        "historico_medico" => $dadosHistorico ? $dadosHistorico->toArray() : [],
        "historico_social" => $dadosHistoricoSocial ? $dadosHistoricoSocial->toArray() : [],
        "plano_tratamento" => $dadosPlano ? $dadosPlano->toArray() : [],
        "qualidade_vida_em" => $dadosQualidade ? $dadosQualidade->toArray() : [],
    ];
}
