<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/IAModel.php";
require_once __DIR__ . "/../modelo/PacienteModel.php";

class IAController extends BaseController {
    private $model;
    private $pacienteModel;

    public function __construct() {
        $this->model = new IAModel();
        $this->pacienteModel = new PacienteModel();
    }

    public function analyze() {
        $usuario = $GLOBALS['usuario'];
        
        // Verifica se é POST multipart e se tem paciente e arquivo
        $paciente_id = $_POST['paciente_id'] ?? null;
        if (!$paciente_id || !isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            $this->errorResponse("Paciente e arquivo de imagem (MRI) são obrigatórios");
        }

        $paciente = $this->pacienteModel->getById($paciente_id, $usuario['instituicao_id']);
        if (!$paciente) {
            $this->errorResponse("Paciente não encontrado", 404);
        }

        // Criar diretório se não existir
        $uploadDir = __DIR__ . '/../imagens/exames_mri/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Salvar a imagem no servidor temporariamente ou permanente
        $fileName = uniqid() . '_' . basename($_FILES['imagem']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $filePath)) {
            $this->errorResponse("Falha ao salvar o arquivo da Ressonância Magnética.");
        }

        $caminhoRelativo = 'imagens/exames_mri/' . $fileName;

        // --- CONTRATO DE INTEGRAÇÃO COM SERVIÇO EXTERNO (MOCK) ---
        // Aqui realizaremos a chamada HTTP para o microserviço de IA Python.
        /* 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://ai-service:8000/api/analyze-mri");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'paciente_id' => $paciente_id,
            'imagem' => new CURLFile($filePath)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $aiResult = json_decode($response, true);
        curl_close($ch);
        */

        // Por enquanto, simulamos o tempo de resposta e o diagnóstico retornado
        sleep(2); // Simular delay da rede do ML
        
        $diagnosticoText = "Lesão hiperintensa periventricular detectada. Compatível com placa de desmielinização sugestiva de Esclerose Múltipla.";
        $confianca = "92.5%";
        $referencia = "Critérios de McDonald 2017 (T2-weighted MRI)";

        $resultId = $this->model->create([
            "instituicao_id" => $usuario['instituicao_id'],
            "paciente_id" => $paciente['id'],
            "nome" => $paciente['nome'],
            "cpf" => $paciente['cpf'],
            "imagem" => $caminhoRelativo,
            "diagnostico" => $diagnosticoText,
            "data_diagnostico" => date('Y-m-d')
        ]);

        $this->jsonResponse([
            "id" => $resultId,
            "diagnostico" => $diagnosticoText,
            "confianca" => $confianca,
            "referencia" => $referencia,
            "imagem_path" => $caminhoRelativo
        ]);
    }

    public function list() {
        $usuario = $GLOBALS['usuario'];
        $data = $this->model->getAll($usuario['instituicao_id']);
        $this->jsonResponse($data);
    }

    public function listByPaciente($pacienteId) {
        $usuario = $GLOBALS['usuario'];
        $data = $this->model->getByPaciente($pacienteId, $usuario['instituicao_id']);
        $this->jsonResponse($data);
    }

    public function delete($id) {
        $usuario = $GLOBALS['usuario'];
        $this->model->delete($id, $usuario['instituicao_id']);
        $this->registrarAuditoria('Deletou Exame IA', "Excluiu diagnóstico de IA ID: " . $id);
        $this->jsonResponse(["msg" => "Registro de IA removido com sucesso"]);
    }
}
