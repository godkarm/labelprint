<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;

class ProductoController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $buscar = trim($_GET['buscar'] ?? '');
        $params = [];
        $where  = '';

        if ($buscar !== '') {
            $where  = "WHERE (p.codigo LIKE ? OR p.nombre LIKE ?)";
            $params = ["%$buscar%", "%$buscar%"];
        }

        $productos = Database::fetchAll(
            "SELECT p.*, COUNT(ps.subproducto_id) as total_subproductos
             FROM productos p
             LEFT JOIN producto_subproducto ps ON ps.producto_id = p.id
             $where
             GROUP BY p.id
             ORDER BY p.nombre ASC",
            $params
        );

        View::render('productos.index', compact('productos', 'buscar'));
    }

    public function create(): void
    {
        $this->requireAuth();
        View::render('productos.form', ['producto' => null, 'accion' => 'crear']);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $errors = $this->validate($_POST);
        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('/productos/crear');
            return;
        }

        $codigo = strtoupper(trim($_POST['codigo']));
        $nombre = trim($_POST['nombre']);

        $exists = Database::fetchOne(
            "SELECT id FROM productos WHERE codigo = ?",
            [$codigo]
        );

        if ($exists) {
            Session::flash('error', 'Ya existe un producto con ese código.');
            $this->redirect('/productos/crear');
            return;
        }

        Database::query(
            "INSERT INTO productos (codigo, nombre) VALUES (?, ?)",
            [$codigo, $nombre]
        );

        Session::flash('success', 'Producto creado correctamente.');
        $this->redirect('/productos');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $producto = Database::fetchOne("SELECT * FROM productos WHERE id = ?", [(int)$id]);
        if (!$producto) {
            Session::flash('error', 'Producto no encontrado.');
            $this->redirect('/productos');
            return;
        }

        // Subproductos asociados
        $subproductos = Database::fetchAll(
            "SELECT s.* FROM subproductos s
             JOIN producto_subproducto ps ON ps.subproducto_id = s.id
             WHERE ps.producto_id = ?
             ORDER BY s.descripcion",
            [(int)$id]
        );

        // Subproductos disponibles para asociar
        $disponibles = Database::fetchAll(
            "SELECT s.* FROM subproductos s
             WHERE s.activo = 1
             AND s.id NOT IN (SELECT subproducto_id FROM producto_subproducto WHERE producto_id = ?)
             ORDER BY s.descripcion",
            [(int)$id]
        );

        View::render('productos.form', compact('producto', 'subproductos', 'disponibles') + ['accion' => 'editar']);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $producto = Database::fetchOne("SELECT * FROM productos WHERE id = ?", [(int)$id]);
        if (!$producto) {
            Session::flash('error', 'Producto no encontrado.');
            $this->redirect('/productos');
            return;
        }

        $errors = $this->validate($_POST);
        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect("/productos/{$id}/editar");
            return;
        }

        $codigo = strtoupper(trim($_POST['codigo']));
        $nombre = trim($_POST['nombre']);

        $exists = Database::fetchOne(
            "SELECT id FROM productos WHERE codigo = ? AND id != ?",
            [$codigo, (int)$id]
        );

        if ($exists) {
            Session::flash('error', 'Ya existe otro producto con ese código.');
            $this->redirect("/productos/{$id}/editar");
            return;
        }

        Database::query(
            "UPDATE productos SET codigo = ?, nombre = ? WHERE id = ?",
            [$codigo, $nombre, (int)$id]
        );

        Session::flash('success', 'Producto actualizado correctamente.');
        $this->redirect('/productos');
    }

    public function toggle(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $producto = Database::fetchOne("SELECT activo FROM productos WHERE id = ?", [(int)$id]);
        if (!$producto) {
            $this->jsonError('Producto no encontrado.');
            return;
        }

        $nuevoEstado = $producto['activo'] ? 0 : 1;
        Database::query("UPDATE productos SET activo = ? WHERE id = ?", [$nuevoEstado, (int)$id]);

        $msg = $nuevoEstado ? 'Producto activado.' : 'Producto desactivado.';
        $this->jsonSuccess($msg, ['activo' => $nuevoEstado]);
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty(trim($data['codigo'] ?? ''))) {
            $errors[] = 'El código es obligatorio.';
        } elseif (mb_strlen(trim($data['codigo'])) > 100) {
            $errors[] = 'El código no puede superar 100 caracteres.';
        }
        if (empty(trim($data['nombre'] ?? ''))) {
            $errors[] = 'El nombre es obligatorio.';
        } elseif (mb_strlen(trim($data['nombre'])) > 255) {
            $errors[] = 'El nombre no puede superar 255 caracteres.';
        }
        return $errors;
    }
}
