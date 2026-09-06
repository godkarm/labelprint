<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        $stats = [
            'impresas_hoy'    => Database::fetchOne(
                "SELECT COUNT(*) as total FROM impresiones WHERE DATE(creado_en) = CURDATE() AND estado = 'IMPRESO'"
            )['total'] ?? 0,
            'impresas_mes'    => Database::fetchOne(
                "SELECT COUNT(*) as total FROM impresiones WHERE MONTH(creado_en) = MONTH(CURDATE()) AND estado = 'IMPRESO'"
            )['total'] ?? 0,
            'productos'       => Database::fetchOne(
                "SELECT COUNT(*) as total FROM productos WHERE activo = 1"
            )['total'] ?? 0,
            'subproductos'    => Database::fetchOne(
                "SELECT COUNT(*) as total FROM subproductos WHERE activo = 1"
            )['total'] ?? 0,
        ];

        $ultimasImpresiones = Database::fetchAll(
            "SELECT i.*, p.codigo as producto_codigo
             FROM impresiones i
             LEFT JOIN productos p ON p.id = i.producto_id
             ORDER BY i.creado_en DESC
             LIMIT 10"
        );

        $empresa = Database::fetchOne("SELECT * FROM empresa LIMIT 1");

        View::render('dashboard.index', compact('stats', 'ultimasImpresiones', 'empresa'));
    }
}
