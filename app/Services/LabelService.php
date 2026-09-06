<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

/**
 * LabelService
 *
 * Responsabilidades:
 * - Preparar datos de la etiqueta
 * - Validar campos del formulario
 * - Coordinar con PrinterService y LogoProcessor
 * - Registrar impresiones en historial
 */
class LabelService
{
    private PrinterService $printerService;
    private LogoProcessor  $logoProcessor;
    private array          $appConfig;

    public function __construct()
    {
        $this->printerService = new PrinterService();
        $this->logoProcessor  = new LogoProcessor();
        $this->appConfig      = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Valida los datos del formulario de etiqueta.
     * Retorna ['valid' => bool, 'errors' => []]
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Empresa (debe existir nombre configurado)
        $empresa = Database::fetchOne("SELECT nombre FROM empresa LIMIT 1");
        if (empty($empresa['nombre'])) {
            $errors['empresa'] = 'No hay nombre de empresa configurado. Configure la empresa antes de imprimir.';
        }

        // Producto
        if (empty($data['producto_id'])) {
            $errors['producto_id'] = 'Seleccione un producto.';
        } else {
            $prod = Database::fetchOne(
                "SELECT id FROM productos WHERE id = ? AND activo = 1",
                [(int)$data['producto_id']]
            );
            if (!$prod) {
                $errors['producto_id'] = 'Producto no válido o inactivo.';
            }
        }

        // Subproducto
        if (empty($data['subproducto_id'])) {
            $errors['subproducto_id'] = 'Seleccione un subproducto.';
        } else {
            $sub = Database::fetchOne(
                "SELECT id FROM subproductos WHERE id = ? AND activo = 1",
                [(int)$data['subproducto_id']]
            );
            if (!$sub) {
                $errors['subproducto_id'] = 'Subproducto no válido o inactivo.';
            }
        }

        // Cantidad
        if (!isset($data['cantidad']) || !is_numeric($data['cantidad']) || (int)$data['cantidad'] <= 0) {
            $errors['cantidad'] = 'La cantidad debe ser un número mayor que cero.';
        }

        // Turno
        if (!isset($data['turno']) || !in_array((int)$data['turno'], [1, 2, 3], true)) {
            $errors['turno'] = 'Seleccione un turno válido (1, 2 o 3).';
        }

        // Fecha
        if (empty($data['fecha'])) {
            $errors['fecha'] = 'La fecha es obligatoria.';
        } else {
            $d = \DateTime::createFromFormat('Y-m-d', $data['fecha']);
            if (!$d || $d->format('Y-m-d') !== $data['fecha']) {
                $errors['fecha'] = 'Fecha no válida.';
            }
        }

        // Copias
        $maxCopias = $this->appConfig['print']['max_copies'];
        if (!isset($data['copias']) || !is_numeric($data['copias']) || (int)$data['copias'] <= 0) {
            $errors['copias'] = 'Las copias deben ser un número mayor que cero.';
        } elseif ((int)$data['copias'] > $maxCopias) {
            $errors['copias'] = "Las copias no pueden superar {$maxCopias}.";
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Procesa la impresión completa.
     *
     * @param array $data  Datos del formulario validados
     * @return array       ['success', 'message', 'impresion_id']
     */
    public function print(array $data): array
    {
        // 1. Cargar datos completos
        $producto = Database::fetchOne(
            "SELECT id, nombre FROM productos WHERE id = ?",
            [(int)$data['producto_id']]
        );

        $subproducto = Database::fetchOne(
            "SELECT id, descripcion FROM subproductos WHERE id = ?",
            [(int)$data['subproducto_id']]
        );

        $empresa = Database::fetchOne("SELECT nombre, logo FROM empresa LIMIT 1");

        if (!$producto || !$subproducto) {
            return ['success' => false, 'message' => 'Datos de producto o subproducto no encontrados.'];
        }

        // 2. Preparar datos de etiqueta
        $fechaObj   = \DateTime::createFromFormat('Y-m-d', $data['fecha']);
        $fechaLabel = $fechaObj ? $fechaObj->format('d/m/Y') : $data['fecha'];

        $labelData = [
            'empresa_nombre'          => $empresa['nombre'] ?? 'EMPRESA',
            'empresa_logo'            => $empresa['logo'] ?? null,
            'producto_nombre'         => $producto['nombre'],
            'subproducto_descripcion' => $subproducto['descripcion'],
            'cantidad'                => (int)$data['cantidad'],
            'turno'                   => (int)$data['turno'],
            'fecha'                   => $fechaLabel,
        ];

        $copias = (int)$data['copias'];

        // 3. Procesar logo si existe
        $bitmapCmd = null;
        if (!empty($labelData['empresa_logo'])) {
            $logoPath = __DIR__ . '/../../public/' . $labelData['empresa_logo'];
            $bitmapCmd = $this->logoProcessor->toTsplBitmap($logoPath);
        }

        // 4. Generar TSPL2
        $tspl = $this->printerService->generateTspl($labelData, $copias, $bitmapCmd);

        // 5. Registrar en historial (PENDIENTE antes de enviar)
        $impresionId = $this->registrarImpresion($labelData, $data, 'PENDIENTE');

        // 6. Enviar a impresora
        $result = $this->printerService->send($tspl);

        // 7. Actualizar estado
        $estado = $result['success'] ? 'IMPRESO' : 'ERROR';
        $error  = $result['success'] ? null : ($result['message'] . ' ' . ($result['detail'] ?? ''));

        Database::query(
            "UPDATE impresiones SET estado = ?, error_detalle = ? WHERE id = ?",
            [$estado, $error, $impresionId]
        );

        return [
            'success'      => $result['success'],
            'message'      => $result['message'],
            'impresion_id' => $impresionId,
            'tspl'         => $tspl,  // Para debug
        ];
    }

    /**
     * Registra la impresión en el historial.
     * Guarda snapshot de nombre_empresa para preservar datos históricos.
     */
    private function registrarImpresion(array $labelData, array $formData, string $estado): int
    {
        $impresora = Database::fetchOne(
            "SELECT id FROM configuracion_impresora WHERE activo = 1 LIMIT 1"
        );

        Database::query(
            "INSERT INTO impresiones 
            (nombre_empresa, producto_id, producto_nombre, subproducto_id, subproducto_descripcion, cantidad, turno, fecha_etiqueta, copias, usuario_id, impresora_id, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $labelData['empresa_nombre'],           // snapshot del nombre de empresa
                (int)$formData['producto_id'],
                $labelData['producto_nombre'],
                (int)$formData['subproducto_id'],
                $labelData['subproducto_descripcion'],
                (int)$formData['cantidad'],
                (int)$formData['turno'],
                $formData['fecha'],
                (int)$formData['copias'],
                Session::userId(),
                $impresora['id'] ?? null,
                $estado,
            ]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Reimprime una etiqueta del historial.
     */
    public function reprint(int $impresionId, int $copias = 0): array
    {
        $original = Database::fetchOne(
            "SELECT * FROM impresiones WHERE id = ?",
            [$impresionId]
        );

        if (!$original) {
            return ['success' => false, 'message' => 'Impresión no encontrada.'];
        }

        // Si no se especifican copias, usar las originales
        if ($copias <= 0) {
            $copias = (int)$original['copias'];
        }

        $fechaObj   = \DateTime::createFromFormat('Y-m-d', $original['fecha_etiqueta']);
        $fechaLabel = $fechaObj ? $fechaObj->format('d/m/Y') : $original['fecha_etiqueta'];

        // IMPORTANTE: usar el nombre de empresa del registro original (snapshot histórico)
        // No consultar la empresa actual para preservar la fidelidad histórica
        $empresaNombreOriginal = $original['nombre_empresa'] ?? '';
        if (empty($empresaNombreOriginal)) {
            // Fallback solo si el registro antiguo no tiene snapshot
            $empresaActual = Database::fetchOne("SELECT nombre FROM empresa LIMIT 1");
            $empresaNombreOriginal = $empresaActual['nombre'] ?? 'EMPRESA';
        }

        // El logo siempre se toma del estado actual (es un archivo físico)
        $empresaLogo = Database::fetchOne("SELECT logo FROM empresa LIMIT 1");

        $labelData = [
            'empresa_nombre'          => $empresaNombreOriginal,
            'empresa_logo'            => $empresaLogo['logo'] ?? null,
            'producto_nombre'         => $original['producto_nombre'],
            'subproducto_descripcion' => $original['subproducto_descripcion'],
            'cantidad'                => (int)$original['cantidad'],
            'turno'                   => (int)$original['turno'],
            'fecha'                   => $fechaLabel,
        ];

        $bitmapCmd = null;
        if (!empty($labelData['empresa_logo'])) {
            $logoPath  = __DIR__ . '/../../public/' . $labelData['empresa_logo'];
            $bitmapCmd = $this->logoProcessor->toTsplBitmap($logoPath);
        }

        $tspl = $this->printerService->generateTspl($labelData, $copias, $bitmapCmd);

        // Registrar reimpresión
        $nuevaId = $this->registrarReimpresion($original, $copias);

        $result = $this->printerService->send($tspl);

        $estado = $result['success'] ? 'IMPRESO' : 'ERROR';
        $error  = $result['success'] ? null : $result['message'];

        Database::query(
            "UPDATE impresiones SET estado = ?, error_detalle = ? WHERE id = ?",
            [$estado, $error, $nuevaId]
        );

        return [
            'success'      => $result['success'],
            'message'      => $result['message'],
            'impresion_id' => $nuevaId,
        ];
    }

    private function registrarReimpresion(array $original, int $copias): int
    {
        $impresora = Database::fetchOne(
            "SELECT id FROM configuracion_impresora WHERE activo = 1 LIMIT 1"
        );

        // Preservar el nombre de empresa del registro original (snapshot histórico)
        $nombreEmpresa = $original['nombre_empresa'] ?? '';
        if (empty($nombreEmpresa)) {
            $empresaActual = Database::fetchOne("SELECT nombre FROM empresa LIMIT 1");
            $nombreEmpresa = $empresaActual['nombre'] ?? 'EMPRESA';
        }

        Database::query(
            "INSERT INTO impresiones 
            (nombre_empresa, producto_id, producto_nombre, subproducto_id, subproducto_descripcion, cantidad, turno, fecha_etiqueta, copias, usuario_id, impresora_id, estado, es_reimpresion, impresion_original_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE', 1, ?)",
            [
                $nombreEmpresa,
                $original['producto_id'],
                $original['producto_nombre'],
                $original['subproducto_id'],
                $original['subproducto_descripcion'],
                (int)$original['cantidad'],
                (int)$original['turno'],
                $original['fecha_etiqueta'],
                $copias,
                Session::userId(),
                $impresora['id'] ?? null,
                (int)$original['id'],
            ]
        );

        return (int)Database::lastInsertId();
    }
}
