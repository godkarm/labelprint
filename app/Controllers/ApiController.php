<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Services\LabelService;
use App\Services\PrinterService;

class ApiController extends BaseController
{
    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    public function productos(): void
    {
        $this->requireAuthApi();
        $buscar = trim($_GET['buscar'] ?? '');
        $params = [];
        $where  = "WHERE activo = 1";

        if ($buscar !== '') {
            $where  .= " AND (codigo LIKE ? OR nombre LIKE ?)";
            $params[] = "%$buscar%";
            $params[] = "%$buscar%";
        }

        $productos = Database::fetchAll("SELECT id, codigo, nombre FROM productos $where ORDER BY nombre", $params);
        View::json(['success' => true, 'data' => $productos]);
    }

    public function subproductos(): void
    {
        $this->requireAuthApi();
        $productoId = (int)($_GET['producto_id'] ?? 0);

        if ($productoId > 0) {
            $subproductos = Database::fetchAll(
                "SELECT s.id, s.descripcion FROM subproductos s
                 JOIN producto_subproducto ps ON ps.subproducto_id = s.id
                 WHERE ps.producto_id = ? AND s.activo = 1
                 ORDER BY s.descripcion",
                [$productoId]
            );
        } else {
            $subproductos = Database::fetchAll(
                "SELECT id, descripcion FROM subproductos WHERE activo = 1 ORDER BY descripcion"
            );
        }

        View::json(['success' => true, 'data' => $subproductos]);
    }

    public function preview(): void
    {
        $this->requireAuthApi();

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $empresa = Database::fetchOne("SELECT nombre, logo FROM empresa LIMIT 1");

        $turnos = [1 => '1 - Mañana', 2 => '2 - Tarde', 3 => '3 - Noche'];
        $turno  = (int)($data['turno'] ?? 0);

        $preview = [
            'empresa_nombre'          => htmlspecialchars($empresa['nombre'] ?? 'EMPRESA', ENT_QUOTES, 'UTF-8'),
            'empresa_logo'            => $empresa['logo'] ?? null,
            'producto_nombre'         => htmlspecialchars($data['producto_nombre'] ?? '', ENT_QUOTES, 'UTF-8'),
            'subproducto_descripcion' => htmlspecialchars($data['subproducto_descripcion'] ?? '', ENT_QUOTES, 'UTF-8'),
            'cantidad'                => (int)($data['cantidad'] ?? 0),
            'turno_str'               => $turnos[$turno] ?? '',
            'fecha'                   => htmlspecialchars($data['fecha'] ?? date('d/m/Y'), ENT_QUOTES, 'UTF-8'),
        ];

        View::json(['success' => true, 'data' => $preview]);
    }

    public function imprimir(): void
    {
        $this->requireAuthApi();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? (json_decode(file_get_contents('php://input'), true)['_csrf'] ?? '');
        if (!Session::verifyCsrf($token)) {
            View::json(['success' => false, 'message' => 'Token de seguridad inválido.'], 403);
            return;
        }

        $data    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $service = new LabelService();
        $valid   = $service->validate($data);

        if (!$valid['valid']) {
            View::json(['success' => false, 'errors' => $valid['errors']]);
            return;
        }

        $result = $service->print($data);
        View::json($result);
    }

    public function estadoImpresora(): void
    {
        $this->requireAuthApi();
        $service = new PrinterService();
        $status  = $service->checkStatus();
        View::json(['success' => true, 'data' => $status]);
    }

    public function crearSubproducto(): void
    {
        $this->requireAuthApi();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($token)) {
            View::json(['success' => false, 'message' => 'Token inválido.'], 403);
            return;
        }

        $data        = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $descripcion = trim($data['descripcion'] ?? '');

        if (empty($descripcion)) {
            View::json(['success' => false, 'message' => 'La descripción es obligatoria.']);
            return;
        }
        if (mb_strlen($descripcion) > 500) {
            View::json(['success' => false, 'message' => 'La descripción no puede superar 500 caracteres.']);
            return;
        }

        Database::query("INSERT INTO subproductos (descripcion) VALUES (?)", [$descripcion]);
        $newId = (int)Database::lastInsertId();

        $sub = Database::fetchOne("SELECT id, descripcion FROM subproductos WHERE id = ?", [$newId]);
        View::json(['success' => true, 'message' => 'Subproducto creado.', 'data' => $sub]);
    }

    private function requireAuthApi(): void
    {
        if (!Session::isLoggedIn()) {
            View::json(['success' => false, 'message' => 'No autenticado.'], 401);
            exit;
        }
    }
}
