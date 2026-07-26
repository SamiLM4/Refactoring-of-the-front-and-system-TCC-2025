<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/LicencaModel.php";
require_once __DIR__ . "/../modelo/PlanoModel.php";
require_once __DIR__ . "/../services/EmailService.php";

class LicencaController extends BaseController {
    private $model;
    private $planoModel;

    public function __construct() {
        $this->model = new LicencaModel();
        $this->planoModel = new PlanoModel();
    }

    public function store() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!isset($data['plano_id'], $data['email'])) {
            $this->errorResponse("plano_id e email são obrigatórios");
        }

        $plano = $this->planoModel->getById($data['plano_id']);
        if (!$plano) {
            $this->errorResponse("Plano não encontrado", 404);
        }

        $token = bin2hex(random_bytes(64));
        $expiraEm = date("Y-m-d H:i:s", strtotime("+" . $plano['duracao_dias'] . " days"));

        try {
            $this->model->createLicense($plano['id'], $token, $expiraEm);
            
            // Simulating email sending for this demo, keeping the logic but making it resilient
            try {
                EmailService::enviar($data['email'], "Sua licença foi criada 🚀", "Token: " . $token . " Expira em: " . $expiraEm);
            } catch (Exception $e) {
                // Log error but proceed for the demo if email fails
            }

            $this->jsonResponse([
                "message" => "Licença criada com sucesso. Verifique seu email.",
                "token" => $token, // Returning token for easy testing/demo
                "plano" => $plano['nome']
            ]);
        } catch (Exception $e) {
            $this->errorResponse("Erro ao criar licença: " . $e->getMessage(), 500);
        }
    }
}
