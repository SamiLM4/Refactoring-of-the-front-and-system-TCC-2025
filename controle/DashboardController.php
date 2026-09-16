<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/DashboardModel.php";

class DashboardController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new DashboardModel();
    }

    public function getStats() {
        $usuario = $GLOBALS['usuario'];
        $stats = $this->model->getStats($usuario['instituicao_id']);
        $stats['ia_api'] = 'OpenAI GPT-4o';
        $this->jsonResponse($stats);
    }

    public function getCharts() {
        $usuario = $GLOBALS['usuario'];
        $data = $this->model->getMonthlyDiagnostics($usuario['instituicao_id']);
        $this->jsonResponse($data);
    }
}
