<?php
require_once __DIR__ . '/../../fpdf/fpdf.php';
require_once __DIR__ . '/fichaReadpdf.php';
require_once __DIR__ . '/../../modelo/MeuTokenJWT.php';

use Firebase\JWT\MeuTokenJWT;

// Verifica o token JWT para obter o CPF do médico
$headers = getallheaders();
$token = trim($headers['Authorization'] ?? '');

if (!$token) {
    http_response_code(401);
    exit('Token ausente.');
}

$jwt = new MeuTokenJWT();

if (!$jwt->validarToken($token)) {
    http_response_code(401);
    exit('Token inválido.');
}

// Lê o CPF do paciente enviado no corpo da requisição (JSON)
$json = file_get_contents('php://input');
$body = json_decode($json);

if (!isset($body->cpf) || empty(trim($body->cpf))) {
    http_response_code(400);
    exit('CPF do paciente ausente ou inválido.');
}

$payload = $jwt->getPayload();

/*
if ($payload->papel == "medico") {
    $cpfMedico = $payload->cpf_medico ?? null;
} else if ($payload->papel == "adm"){
    $cpfMedico = trim($body->cpfMedico);
}
*/

$cpfPaciente = trim($body->cpf);
$instituicao = $payload->instituicao;

// Busca os dados da ficha
$dados = buscarDadosFicha($cpfPaciente, $instituicao);

/*
    echo "<pre>";
    print_r($dados['diagnostico']);
    echo "</pre>";
    die;
*/

ob_clean();
ob_start();

// Geração do PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 14);
$titulo = 'Ficha médica do paciente: ' . $dados['paciente'] . '  CPF (' . $dados['cpf_paciente'] . ')';
$pdf->Cell(0, 10, utf8_decode($titulo), 0, 1, 'C');

$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 8, utf8_decode('Campo'), 1, 0);
$pdf->Cell(130, 8, utf8_decode('Valor'), 1, 1);

$pdf->SetFont('Arial', '', 12);

function linha($pdf, $campo, $valor)
{
    $pdf->Cell(60, 8, utf8_decode($campo), 1, 0);
    $pdf->MultiCell(130, 8, utf8_decode($valor), 1);
}

linha($pdf, 'Médico responsável', $dados['medico']);

// Estilo de Título de seção
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(200, 200, 200); // Cor de fundo cinza claro
$pdf->Cell(0, 10, utf8_decode('Sintomas'), 1, 1, 'L', true);

// Conteúdo da seção
$pdf->SetFont('Arial', '', 12);
$sintomas = $dados['sintomas'];
$diagnostico = $dados['diagnostico'];
$exame_fisico = $dados['exame_fisico'];
$exames_complementares = $dados['exames_complementares'];
$historico_social = $dados['historico_social'];
$plano_tratamento = $dados['plano_tratamento'];
$qualidade_vida_em = $dados['qualidade_vida_em'];

function campoValor($pdf, $campo, $valor)
{
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(60, 8, utf8_decode($campo), 1, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(130, 8, utf8_decode($valor), 1);
}

function campoValor2($pdf, $campo, $valor)
{
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(105, 8, utf8_decode($campo), 1, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(85, 8, utf8_decode($valor), 1);
}

campoValor($pdf, 'Sintomas iniciais', $sintomas['sintomas_iniciais'] ?? 'N/A');
campoValor($pdf, 'Sintomas atuais', $sintomas['sintomas_atuais'] ?? 'N/A');
campoValor($pdf, 'Fadiga', !empty($sintomas['fadiga']) ? 'Sim' : 'Não');
campoValor($pdf, 'Problema de visão', $sintomas['problema_visao'] ?? 'N/A');
campoValor($pdf, 'Problema de coordenação', !empty($sintomas['problema_coordenacao']) ? 'Sim' : 'Não');
campoValor($pdf, 'espaticidade', !empty($sintomas['espaticidade']) ? 'Sim' : 'Não');
campoValor($pdf, 'Fraqueza Muscular', !empty($sintomas['fraqueza_muscular']) ? 'Sim' : 'Não');
campoValor($pdf, 'Problema Sensibilidade', $sintomas['problema_sensibilidade'] ?? 'N/A');
campoValor($pdf, 'Problema Bexiga', !empty($sintomas['problema_bexiga']) ? 'Sim' : 'Não');
campoValor($pdf, 'Problema Intestino', !empty($sintomas['problema_intestino']) ? 'Sim' : 'Não');
campoValor($pdf, 'Problema Cognitivo', $sintomas['problema_cognitivo'] ?? 'N/A');
campoValor($pdf, 'Problema Emocional', $sintomas['problema_emocional'] ?? 'N/A');



// Diagnóstico
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(0, 10, utf8_decode('Diagnóstico'), 1, 1, 'L', true);

campoValor($pdf, 'Id do Paciente', $diagnostico['id do paciente'] ?? 'N/A');
campoValor($pdf, 'CPF', $diagnostico['cpf'] ?? 'N/A');

$dataOriginal = $diagnostico['data_diagnostico'] ?? null;
$dataFormatada = 'N/A';
if ($dataOriginal) {
    $dataFormatada = DateTime::createFromFormat('Y-m-d', $dataOriginal)?->format('d/m/Y') ?? 'Inválida';
}
campoValor($pdf, 'Data do diagnóstico', $dataFormatada);

campoValor($pdf, 'Tipo da EM', $diagnostico['tipo_em'] ?? 'N/A');
campoValor($pdf, 'Surtos', $diagnostico['surtos'] ?? 'N/A');


// exame_fisico
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(0, 10, utf8_decode('Exame Fisico'), 1, 1, 'L', true);
campoValor($pdf, 'Id do Paciente', $exame_fisico['id_paciente'] ?? 'N/A');
campoValor($pdf, 'Exame Neurologico', $exame_fisico['exame_neurologico'] ?? 'N/A');
campoValor($pdf, 'Forca Muscular', $exame_fisico['forca_muscular'] ?? 'N/A');
campoValor($pdf, 'Reflexos', $exame_fisico['reflexos'] ?? 'N/A');
campoValor($pdf, 'Coordenacao', $exame_fisico['coordenacao'] ?? 'N/A');
campoValor($pdf, 'Sensibilidade', $exame_fisico['sensibilidade'] ?? 'N/A');
campoValor($pdf, 'Equilibrio', $exame_fisico['equilibrio'] ?? 'N/A');
campoValor($pdf, 'Função Visual', $exame_fisico['funcao_visual'] ?? 'N/A');
campoValor($pdf, 'Outros exames fisicos', $exame_fisico['outros_exames_fisicos'] ?? 'N/A');


// exames_complementares
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(0, 10, utf8_decode('Exames Complementares'), 1, 1, 'L', true);
campoValor($pdf, 'Id do Paciente', $exames_complementares['id_paciente'] ?? 'N/A');
campoValor($pdf, 'RM Cerebro Medula', $exames_complementares['rm_cerebro_medula'] ?? 'N/A');
campoValor2($pdf, 'Potenciais Evocados Visuais', $exames_complementares['potenciais_evocados_visuais'] ?? 'N/A');
campoValor2($pdf, 'Potenciais Evocados Somatossensoriais', $exames_complementares['potenciais_evocados_somatossensoriais'] ?? 'N/A');
campoValor2($pdf, 'Potenciais Evocados Auditivos de Tronco Encefalico', $exames_complementares['potenciais_evocados_auditivos_de_tronco_encefalico'] ?? 'N/A');
campoValor2($pdf, 'Analise Liquido Cefalorraquidiano', $exames_complementares['analise_liquido_cefalorraquidiano'] ?? 'N/A');
campoValor($pdf, 'Outros Exames', $exames_complementares['outros_exames'] ?? 'N/A');


// historico_social
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(0, 10, utf8_decode('Historico Social'), 1, 1, 'L', true);
campoValor($pdf, 'Id do Paciente', $historico_social['id_paciente'] ?? 'N/A');
campoValor($pdf, 'Tabagismo', $historico_social['tabagismo'] ?? 'N/A');
campoValor($pdf, 'Alcool', $historico_social['alcool'] ?? 'N/A');
campoValor($pdf, 'Atividade Fisica', $historico_social['atividade_fisica'] ?? 'N/A');
campoValor($pdf, 'Suporte Social', $historico_social['suporte_social'] ?? 'N/A');
campoValor($pdf, 'Impacto profissional social', $historico_social['impacto_profissional_social'] ?? 'N/A');


// plano_tratamento
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(0, 10, utf8_decode('Plano Tratamento'), 1, 1, 'L', true);
campoValor($pdf, 'Id do Paciente', $plano_tratamento['id_paciente'] ?? 'N/A');
campoValor2($pdf, 'Medicamentos Modificadores Doença', $plano_tratamento['medicamentos_modificadores_doenca'] ?? 'N/A');
campoValor($pdf, 'Tratamento Surtos', $plano_tratamento['tratamento_surtos'] ?? 'N/A');
campoValor($pdf, 'Tratamento Sintomas', $plano_tratamento['tratamento_sintomas'] ?? 'N/A');
campoValor($pdf, 'Reabilitaçao', $plano_tratamento['reabilitacao'] ?? 'N/A');
campoValor($pdf, 'Acompanhamento Psicologico', $plano_tratamento['acompanhamento_psicologico'] ?? 'N/A');
campoValor($pdf, 'Outras Terapias', $plano_tratamento['outras_terapias'] ?? 'N/A');


// qualidade_vida_em
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(0, 10, utf8_decode('Qualidade de Vida em'), 1, 1, 'L', true);
campoValor($pdf, 'Id do Paciente', $qualidade_vida_em['id_paciente'] ?? 'N/A');
campoValor($pdf, 'EDSS', $qualidade_vida_em['edss'] ?? 'N/A');
campoValor($pdf, 'Questionario msqol54', $qualidade_vida_em['questionario_msqol54'] ?? 'N/A');
campoValor($pdf, 'Outras Avaliacoes', $qualidade_vida_em['outras_avaliacoes'] ?? 'N/A');

//$pdf->Output('D', 'ficha_paciente.pdf'); // "D" de Download

$pdfData = $pdf->Output('S'); // Retorna como string
ob_end_clean();
if (ob_get_length())
    ob_end_clean(); // limpa qualquer buffer anterior
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="ficha_paciente.pdf"');
header('Content-Length: ' . strlen($pdfData));
//return $pdfData;
echo $pdfData;
exit;
