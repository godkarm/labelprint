<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;

class SubproductoController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $buscar = trim($_GET['buscar'] ?? '');
        $params = [];
        $where  = '';

        if ($buscar !== '') {
            $where  = "WHERE s.descripcion LIKE ?";
            $params = ["%$buscar%"];
        }

        $subproductos = Database::fetchAll(
            "SELECT s.*, GROUP_CONCAT(p.nombre ORDER BY p.nombre SEPARATOR ', ') as productos_asociados
             FROM subproductos s
             LEFT JOIN producto_subproducto ps ON ps.subproducto_id = s.id
             LEFT JOIN productos p ON p.id = ps.producto_id
             $where
             GROUP BY s.id
             ORDER BY s.descripcion ASC",
            $params
        );

        View::render('subproductos.index', compact('subproductos', 'buscar'));
    }

    public function create(): void
    {
        $this->requireAuth();
        $productos = Database::fetchAll("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre");
        View::render('subproductos.form', ['subproducto' => null, 'accion' => 'crear', 'productos' => $productos]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $descripcion = trim($_POST['descripcion'] ?? '');
        if (empty($descripcion)) {
            Session::flash('error', 'La descripción del subproducto es obligatoria.');
            $this->redirect('/subproductos/crear');
            return;
        }
        if (mb_strlen($descripcion) > 500) {
            Session::flash('error', 'La descripción no puede superar 500 caracteres.');
            $this->redirect('/subproductos/crear');
            return;
        }

        Database::query("INSERT INTO subproductos (descripcion) VALUES (?)", [$descripcion]);
        $newId = (int)Database::lastInsertId();

        // Asociar a productos si se seleccionaron
        $productoIds = $_POST['productos'] ?? [];
        if (!empty($productoIds) && is_array($productoIds)) {
            foreach ($productoIds as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    Database::query(
                        "INSERT IGNORE INTO producto_subproducto (producto_id, subproducto_id) VALUES (?, ?)",
                        [$pid, $newId]
                    );
                }
            }
        }

        Session::flash('success', 'Subproducto creado correctamente.');
        $this->redirect('/subproductos');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $subproducto = Database::fetchOne("SELECT * FROM subproductos WHERE id = ?", [(int)$id]);
        if (!$subproducto) {
            Session::flash('error', 'Subproducto no encontrado.');
            $this->redirect('/subproductos');
            return;
        }

        $productos = Database::fetchAll("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre");
        $asociados = Database::fetchAll(
            "SELECT producto_id FROM producto_subproducto WHERE subproducto_id = ?",
            [(int)$id]
        );
        $asociadosIds = array_column($asociados, 'producto_id');

        View::render('subproductos.form', compact('subproducto', 'productos', 'asociadosIds') + ['accion' => 'editar']);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $subproducto = Database::fetchOne("SELECT * FROM subproductos WHERE id = ?", [(int)$id]);
        if (!$subproducto) {
            Session::flash('error', 'Subproducto no encontrado.');
            $this->redirect('/subproductos');
            return;
        }

        $descripcion = trim($_POST['descripcion'] ?? '');
        if (empty($descripcion)) {
            Session::flash('error', 'La descripción es obligatoria.');
            $this->redirect("/subproductos/{$id}/editar");
            return;
        }
        if (mb_strlen($descripcion) > 500) {
            Session::flash('error', 'La descripción no puede superar 500 caracteres.');
            $this->redirect("/subproductos/{$id}/editar");
            return;
        }

        Database::query("UPDATE subproductos SET descripcion = ? WHERE id = ?", [$descripcion, (int)$id]);

        // Actualizar asociaciones
        Database::query("DELETE FROM producto_subproducto WHERE subproducto_id = ?", [(int)$id]);
        $productoIds = $_POST['productos'] ?? [];
        if (!empty($productoIds) && is_array($productoIds)) {
            foreach ($productoIds as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    Database::query(
                        "INSERT IGNORE INTO producto_subproducto (producto_id, subproducto_id) VALUES (?, ?)",
                        [$pid, (int)$id]
                    );
                }
            }
        }

        Session::flash('success', 'Subproducto actualizado correctamente.');
        $this->redirect('/subproductos');
    }

    public function toggle(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $sub = Database::fetchOne("SELECT activo FROM subproductos WHERE id = ?", [(int)$id]);
        if (!$sub) {
            $this->jsonError('Subproducto no encontrado.');
            return;
        }

        $nuevoEstado = $sub['activo'] ? 0 : 1;
        Database::query("UPDATE subproductos SET activo = ? WHERE id = ?", [$nuevoEstado, (int)$id]);

        $msg = $nuevoEstado ? 'Subproducto activado.' : 'Subproducto desactivado.';
        $this->jsonSuccess($msg, ['activo' => $nuevoEstado]);
    }

    public function vincular(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $productoId    = (int)($_POST['producto_id'] ?? 0);
        $subproductoId = (int)$id;

        if ($productoId <= 0) {
            $this->jsonError('Producto no válido.');
            return;
        }

        Database::query(
            "INSERT IGNORE INTO producto_subproducto (producto_id, subproducto_id) VALUES (?, ?)",
            [$productoId, $subproductoId]
        );

        $this->jsonSuccess('Subproducto vinculado correctamente.');
    }
}
