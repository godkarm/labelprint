<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Services\LabelService;

class EtiquetaController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        $productos = Database::fetchAll(
            "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre"
        );

        $empresa = Database::fetchOne("SELECT * FROM empresa LIMIT 1");
        $config  = require __DIR__ . '/../../config/app.php';

        View::render('etiquetas.index', compact('productos', 'empresa', 'config'));
    }

    public function imprimir(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $service    = new LabelService();
        $validation = $service->validate($_POST);

        if (!$validation['valid']) {
            if ($this->isAjax()) {
                View::json(['success' => false, 'errors' => $validation['errors']]);
                return;
            }
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/etiquetas');
            return;
        }

        $result = $service->print($_POST);

        if ($this->isAjax()) {
            View::json($result);
            return;
        }

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }

        $this->redirect('/etiquetas');
    }
}
