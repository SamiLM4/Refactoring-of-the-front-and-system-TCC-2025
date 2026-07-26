<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/PlanoModel.php";

class PlanoController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new PlanoModel();
    }

    public function list() {
        $planos = $this->model->getAllActive();
        $this->jsonResponse($planos);
    }
}
