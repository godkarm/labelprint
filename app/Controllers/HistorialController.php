<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Services\LabelService;

class HistorialController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        $buscar  = trim($_GET['buscar'] ?? '');
        $estado  = $_GET['estado'] ?? '';
        $fecha   = $_GET['fecha'] ?? '';
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if ($buscar !== '') {
            $where[]  = "(i.producto_nombre LIKE ? OR i.subproducto_descripcion LIKE ? OR i.nombre_empresa LIKE ?)";
            $params[] = "%$buscar%";
            $params[] = "%$buscar%";
            $params[] = "%$buscar%";
        }
        if (in_array($estado, ['PENDIENTE', 'IMPRESO', 'ERROR', 'CANCELADO'], true)) {
            $where[]  = "i.estado = ?";
            $params[] = $estado;
        }
        if ($fecha) {
            $where[]  = "DATE(i.creado_en) = ?";
            $params[] = $fecha;
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = Database::fetchOne(
            "SELECT COUNT(*) as total FROM impresiones i $whereStr",
            $params
        )['total'] ?? 0;

        $impresiones = Database::fetchAll(
            "SELECT i.* FROM impresiones i $whereStr ORDER BY i.creado_en DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        $totalPages = (int)ceil($total / $perPage);

        View::render('historial.index', compact('impresiones', 'buscar', 'estado', 'fecha', 'page', 'totalPages', 'total'));
    }

    public function show(string $id): void
    {
        $this->requireAuth();

        $impresion = Database::fetchOne("SELECT * FROM impresiones WHERE id = ?", [(int)$id]);
        if (!$impresion) {
            Session::flash('error', 'Registro no encontrado.');
            $this->redirect('/historial');
            return;
        }

        // Obtener el logo actual de la empresa (el logo es un archivo físico, no se guarda por impresión)
        $empresa = Database::fetchOne("SELECT logo FROM empresa LIMIT 1");
        $logoEmpresa = $empresa['logo'] ?? null;

        View::render('historial.show', compact('impresion', 'logoEmpresa'));
    }

    public function reimprimir(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $copias  = (int)($_POST['copias'] ?? 0);
        $service = new LabelService();
        $result  = $service->reprint((int)$id, $copias);

        if ($this->isAjax()) {
            View::json($result);
            return;
        }

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }

        $this->redirect('/historial');
    }

    public function eliminar(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$id;

        // Validar que el ID sea numérico positivo
        if ($id <= 0) {
            if ($this->isAjax()) {
                $this->jsonError('ID de impresión no válido.');
                return;
            }
            Session::flash('error', 'ID de impresión no válido.');
            $this->redirect('/historial');
            return;
        }

        // Verificar que el registro exista
        $impresion = Database::fetchOne(
            "SELECT id, producto_nombre, nombre_empresa, fecha_etiqueta FROM impresiones WHERE id = ?",
            [$id]
        );

        if (!$impresion) {
            if ($this->isAjax()) {
                $this->jsonError('La impresión seleccionada no existe o ya fue eliminada.');
                return;
            }
            Session::flash('error', 'La impresión seleccionada no existe o ya fue eliminada.');
            $this->redirect('/historial');
            return;
        }

        try {
            // Eliminar SOLO el registro de impresión
            // No se eliminan: productos, subproductos, empresa, usuarios
            Database::query("DELETE FROM impresiones WHERE id = ?", [$id]);

            error_log(sprintf(
                '[HISTORIAL] Impresión eliminada: ID=%d, Empresa=%s, Producto=%s, Usuario=%d',
                $id,
                $impresion['nombre_empresa'],
                $impresion['producto_nombre'],
                Session::userId() ?? 0
            ));

            if ($this->isAjax()) {
                $this->jsonSuccess('La impresión se eliminó correctamente.');
                return;
            }

            Session::flash('success', 'La impresión se eliminó correctamente.');

        } catch (\Throwable $e) {
            error_log('[HISTORIAL] Error al eliminar impresión ID=' . $id . ': ' . $e->getMessage());

            if ($this->isAjax()) {
                $this->jsonError('No se pudo eliminar la impresión. Inténtelo nuevamente.');
                return;
            }

            Session::flash('error', 'No se pudo eliminar la impresión. Inténtelo nuevamente.');
        }

        $this->redirect('/historial');
    }
}
