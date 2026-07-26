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
require_once "modelo/Medico/RelacaoMedicoPaciente.php";
require_once "modelo/MeuTokenJWT.php";

use Firebase\JWT\MeuTokenJWT;

$headers = getallheaders();
$authorization = $headers['Authorization'] ?? null;

$vetor = explode("/", $_SERVER['REQUEST_URI']);
$cpf = end($vetor);
$cpf = strip_tags(trim($cpf));

if (!$authorization) {
    http_response_code(401);
    echo json_encode(["cod" => 401, "msg" => "Token não fornecido."]);
    exit;
}

$meutoken = new MeuTokenJWT();
if (!$meutoken->validarToken($authorization)) {
    http_response_code(401);
    echo json_encode(["cod" => 401, "msg" => "Token inválido."]);
    exit;
}


$payload = $meutoken->getPayload();
/*
if (!isset($payload->papel) || $payload->papel !== "medico") {
    http_response_code(403);
    echo json_encode(["cod" => 403, "msg" => "Acesso negado. Apenas médicos podem realizar esta operação."]);
    exit;
}
*/
$jsonRecebidoBodyRequest = file_get_contents('php://input');
$dados = json_decode($jsonRecebidoBodyRequest);

if (!$dados || !isset($cpf)) {
    http_response_code(400);
    echo json_encode(["cod" => 400, "msg" => "Dados de entrada inválidos."]);
    exit;
}

// SINTOMAS
$sintomas = new Sintomas();
$sintomas->setCpf(strip_tags($cpf));
$sintomas->setSintomasIniciais(strip_tags($dados->sintomas->sintomas_iniciais));
$sintomas->setSintomasAtuais(strip_tags($dados->sintomas->sintomas_atuais));
$sintomas->setFadiga(filter_var($dados->sintomas->fadiga, FILTER_VALIDATE_BOOLEAN));
$sintomas->setProblemaVisao(strip_tags($dados->sintomas->problema_visao));
$sintomas->setProblemaEquilibrio(filter_var($dados->sintomas->problema_equilibrio, FILTER_VALIDATE_BOOLEAN));
$sintomas->setProblemaCoordenacao(filter_var($dados->sintomas->problema_coordenacao, FILTER_VALIDATE_BOOLEAN));
$sintomas->setEspaticidade(filter_var($dados->sintomas->espaticidade, FILTER_VALIDATE_BOOLEAN));
$sintomas->setFraquezaMuscular(filter_var($dados->sintomas->fraqueza_muscular, FILTER_VALIDATE_BOOLEAN));
$sintomas->setProblemaSensibilidade(strip_tags($dados->sintomas->problema_sensibilidade));
$sintomas->setProblemaBexiga(filter_var($dados->sintomas->problema_bexiga, FILTER_VALIDATE_BOOLEAN));
$sintomas->setProblemaIntestino(filter_var($dados->sintomas->problema_intestino, FILTER_VALIDATE_BOOLEAN));
$sintomas->setProblemaCognitivo(strip_tags($dados->sintomas->problema_cognitivo));
$sintomas->setProblemaEmocional(strip_tags($dados->sintomas->problema_emocional));

// DIAGNÓSTICO
$diagnostico = new Diagnostico();
$diagnostico->setCpf(strip_tags($cpf));
$diagnostico->setDataDiagnostico(strip_tags($dados->diagnostico->data_diagnostico));
$diagnostico->setTipoEm(strip_tags($dados->diagnostico->tipo_em));
$diagnostico->setSurtos(strip_tags($dados->diagnostico->surtos));

// EXAME FÍSICO
$exame = new ExameFisico();
$exame->setCpf(strip_tags($cpf));
$exame->setExameNeurologico(strip_tags($dados->exame_fisico->exame_neurologico));
$exame->setForcaMuscular(strip_tags($dados->exame_fisico->forca_muscular));
$exame->setReflexos(strip_tags($dados->exame_fisico->reflexos));
$exame->setCoordenacao(strip_tags($dados->exame_fisico->coordenacao));
$exame->setSensibilidade(strip_tags($dados->exame_fisico->sensibilidade));
$exame->setEquilibrio(strip_tags($dados->exame_fisico->equilibrio));
$exame->setFuncaoVisual(strip_tags($dados->exame_fisico->funcao_visual));
$exame->setOutrosExamesFisicos(strip_tags($dados->exame_fisico->outros_exames_fisicos));

// EXAMES COMPLEMENTARES
$exames = new ExamesComplementares();
$exames->setCpf(strip_tags($cpf));
$exames->setRmCerebroMedula(strip_tags($dados->exames_complementares->rm_cerebro_medula));
$exames->setPotenciaisEvocadosVisuais(strip_tags($dados->exames_complementares->potenciais_evocados_visuais));
$exames->setPotenciaisEvocadosSomatossensoriais(strip_tags($dados->exames_complementares->potenciais_evocados_somatossensoriais));
$exames->setPotenciaisEvocadosAuditivosDeTroncoEncefalico(strip_tags($dados->exames_complementares->potenciais_evocados_auditivos_de_tronco_encefalico));
$exames->setAnaliseLiquidoCefalorraquidiano(strip_tags($dados->exames_complementares->analise_liquido_cefalorraquidiano));
$exames->setOutrosExames(strip_tags($dados->exames_complementares->outros_exames));

// HISTÓRICO MÉDICO
$historico = new HistoricoMedico();
$historico->setCpf(strip_tags($cpf));
$historico->setMedicamentoEmUso(strip_tags($dados->historico_medico->medicamento_em_uso));
$historico->setTratamentosAnteriores(strip_tags($dados->historico_medico->tratamentos_anteriores));
$historico->setAlergias(strip_tags($dados->historico_medico->alergias));
$historico->setHistoricoOutrasDoencas(strip_tags($dados->historico_medico->historico_outras_doencas));
$historico->setHistoricoFamiliar(strip_tags($dados->historico_medico->historico_familiar));

// HISTÓRICO SOCIAL
$historicoSocial = new HistoricoSocial();
$historicoSocial->setCpf(strip_tags($cpf));
$historicoSocial->setTabagismo(strip_tags($dados->historico_social->tabagismo));
$historicoSocial->setAlcool(strip_tags($dados->historico_social->alcool));
$historicoSocial->setAtividadeFisica(strip_tags($dados->historico_social->atividade_fisica));
$historicoSocial->setSuporteSocial(strip_tags($dados->historico_social->suporte_social));
$historicoSocial->setImpactoProfissionalSocial(strip_tags($dados->historico_social->impacto_profissional_social));

// PLANO DE TRATAMENTO
$planoTratamento = new PlanoTratamento();
$planoTratamento->setCpf(strip_tags($cpf));
$planoTratamento->setMedicamentosModificadoresDoenca(strip_tags($dados->plano_tratamento->medicamentos_modificadores_doenca));
$planoTratamento->setTratamentoSurtos(strip_tags($dados->plano_tratamento->tratamento_surtos));
$planoTratamento->setTratamentoSintomas(strip_tags($dados->plano_tratamento->tratamento_sintomas));
$planoTratamento->setReabilitacao(strip_tags($dados->plano_tratamento->reabilitacao));
$planoTratamento->setAcompanhamentoPsicologico(strip_tags($dados->plano_tratamento->acompanhamento_psicologico));
$planoTratamento->setOutrasTerapias(strip_tags($dados->plano_tratamento->outras_terapias));

// QUALIDADE DE VIDA
$qualidadeVida = new QualidadeVidaEm();
$qualidadeVida->setCpf(strip_tags($cpf));
$qualidadeVida->setEdss(strip_tags($dados->qualidade_vida_em->edss));
$qualidadeVida->setQuestionarioMsqol54(strip_tags($dados->qualidade_vida_em->questionario_msqol54));
$qualidadeVida->setOutrasAvaliacoes(strip_tags($dados->qualidade_vida_em->outras_avaliacoes));

$relacao = new Relacao();

if ($payload->papel == "adm" || $payload->papel == "medico") {
    $cpfMedico = $dados->cpf_medicoResponsavel;
    $relacao->setcpfpaciente($cpf);
} else {
    http_response_code(403);
    echo json_encode(["cod" => 403, "msg" => "Acesso negado."]);
    exit;    
}



// FAZENDO O UPDATE
if (
    $sintomas->update() and
    $diagnostico->update() and
    $exame->update() and
    $exames->update() and
    $historico->update() and
    $historicoSocial->update() and
    $planoTratamento->update() and
    $qualidadeVida->update() and
    $relacao->updateRelacao($cpfMedico)
) {
    echo json_encode([
        "cod" => 200,
        "msg" => "Ficha atualizada com sucesso!"
    ]);
} else {
    echo json_encode([
        "cod" => 500,
        "msg" => "Erro ao atualizar ficha"
    ]);
}

?>