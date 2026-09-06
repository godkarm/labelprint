<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Services\LogoProcessor;

class EmpresaController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $empresa = Database::fetchOne("SELECT * FROM empresa LIMIT 1");
        View::render('empresa.index', compact('empresa'));
    }

    public function update(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            Session::flash('error', 'El nombre de empresa es obligatorio.');
            $this->redirect('/empresa');
            return;
        }

        if (mb_strlen($nombre) > 255) {
            Session::flash('error', 'El nombre no puede superar 255 caracteres.');
            $this->redirect('/empresa');
            return;
        }

        Database::query(
            "UPDATE empresa SET nombre = ? WHERE id = (SELECT id FROM (SELECT id FROM empresa LIMIT 1) t)",
            [htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8')]
        );

        Session::flash('success', 'Nombre de empresa actualizado correctamente.');
        $this->redirect('/empresa');
    }

    public function uploadLogo(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        if (empty($_FILES['logo'])) {
            Session::flash('error', 'No se recibió ningún archivo.');
            $this->redirect('/empresa');
            return;
        }

        $processor = new LogoProcessor();
        $result    = $processor->processUpload($_FILES['logo']);

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/empresa');
            return;
        }

        // Eliminar logo anterior
        $empresa = Database::fetchOne("SELECT logo FROM empresa LIMIT 1");
        if (!empty($empresa['logo'])) {
            $processor->deleteLogo($empresa['logo']);
        }

        Database::query(
            "UPDATE empresa SET logo = ? WHERE id = (SELECT id FROM (SELECT id FROM empresa LIMIT 1) t)",
            [$result['path']]
        );

        Session::flash('success', 'Logo subido correctamente.');
        $this->redirect('/empresa');
    }

    public function deleteLogo(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $empresa = Database::fetchOne("SELECT logo FROM empresa LIMIT 1");
        if (!empty($empresa['logo'])) {
            $processor = new LogoProcessor();
            $processor->deleteLogo($empresa['logo']);
            Database::query(
                "UPDATE empresa SET logo = NULL WHERE id = (SELECT id FROM (SELECT id FROM empresa LIMIT 1) t)"
            );
        }

        Session::flash('success', 'Logo eliminado correctamente.');
        $this->redirect('/empresa');
    }
}
