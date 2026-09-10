<?php

declare(strict_types=1);

namespace App\Services;

/**
 * LogoProcessor
 *
 * Convierte el logo de la empresa a formato BITMAP monocromo para TSPL2.
 * Para la etiqueta 200×100mm:
 *   Logo posicionado en x=50, y=25 (dentro del margen MX=40, MY=20)
 *   Tamaño máximo: 100×60 dots ≈ 12×7.5mm
 */
class LogoProcessor
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Valida y guarda el logo subido.
     */
    public function processUpload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => $this->uploadErrorMessage($file['error'])];
        }

        $maxSize = $this->config['upload']['max_size'];
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Archivo demasiado grande. Máximo ' . ($maxSize / 1024 / 1024) . ' MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->config['upload']['allowed_ext'], true)) {
            return ['success' => false, 'message' => 'Formato no permitido. Use: PNG, JPG, JPEG, WEBP.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $this->config['upload']['allowed_mime'], true)) {
            return ['success' => false, 'message' => 'Tipo de archivo no válido.'];
        }

        if (@getimagesize($file['tmp_name']) === false) {
            return ['success' => false, 'message' => 'El archivo no es una imagen válida.'];
        }

        $safeName  = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadDir = $this->config['upload']['logo_dir'];

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'No se pudo guardar el logo.'];
        }

        // Redimensionar a máximo 300×150px para evitar archivos enormes
        $this->resizeImage($destination, 300, 150);

        return [
            'success' => true,
            'path'    => 'uploads/logos/' . $safeName,
            'message' => 'Logo subido correctamente.',
        ];
    }

    /**
     * Convierte el logo a comando BITMAP TSPL2 monocromo 1bpp.
     *
     * Para la etiqueta 200×100mm horizontal:
     *   x=50, y=25 (justo dentro del marco MX=40, MY=20)
     *   maxW=100 dots (~12mm), maxH=60 dots (~7mm)
     *
     * Si GD no está disponible, retorna null (sin logo en la etiqueta).
     */
    public function toTsplBitmap(
        string $logoPath,
        int $x    = 50,
        int $y    = 25,
        int $maxW = 100,
        int $maxH = 60
    ): ?string {
        if (!extension_loaded('gd')) {
            error_log('[LogoProcessor] Extensión GD no disponible — el logo no se incluirá en la etiqueta.');
            return null;
        }

        if (!file_exists($logoPath)) {
            error_log('[LogoProcessor] Logo no encontrado: ' . $logoPath);
            return null;
        }

        $imageInfo = @getimagesize($logoPath);
        if (!$imageInfo) {
            error_log('[LogoProcessor] No se pudo obtener información de la imagen: ' . $logoPath);
            return null;
        }

        [, , $type] = $imageInfo;

        $src = match ($type) {
            IMAGETYPE_PNG  => @imagecreatefrompng($logoPath),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($logoPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($logoPath),
            default        => null,
        };

        if (!$src) {
            error_log('[LogoProcessor] No se pudo cargar la imagen tipo ' . $type);
            return null;
        }

        // Redimensionar manteniendo proporción
        [$origW, $origH] = getimagesize($logoPath);
        $ratio = min($maxW / max($origW, 1), $maxH / max($origH, 1));
        $newW  = max(1, (int)round($origW * $ratio));
        $newH  = max(1, (int)round($origH * $ratio));

        $dst   = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        // Convertir a monocromo 1bpp (MSB first)
        // En TSPL2 BITMAP: 0=negro (imprime), 1=blanco (no imprime)
        // Umbral: luminancia < 128 → negro
        $bytesPerRow = (int)ceil($newW / 8);
        $bitmapBytes = [];

        for ($row = 0; $row < $newH; $row++) {
            for ($byteIdx = 0; $byteIdx < $bytesPerRow; $byteIdx++) {
                $byte = 0xFF; // por defecto blanco (no imprime)
                for ($bit = 0; $bit < 8; $bit++) {
                    $col = $byteIdx * 8 + $bit;
                    if ($col < $newW) {
                        $rgb  = imagecolorat($dst, $col, $row);
                        $r    = ($rgb >> 16) & 0xFF;
                        $g    = ($rgb >> 8)  & 0xFF;
                        $b    = $rgb & 0xFF;
                        $luma = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                        if ($luma < 128) {
                            // Pixel oscuro → imprimir → bit = 0
                            $byte &= ~(0x80 >> $bit);
                        }
                    }
                }
                $bitmapBytes[] = $byte;
            }
        }

        imagedestroy($dst);

        // Hex string para el comando BITMAP
        $hexData = implode('', array_map(fn($b) => sprintf('%02X', $b), $bitmapBytes));

        // Comando TSPL2: BITMAP x,y,width_bytes,height,mode,data
        // mode=1: OR (sobreescribir pixels)
        error_log("[LogoProcessor] Bitmap generado: {$newW}x{$newH}px, {$bytesPerRow} bytes/fila, " . strlen($hexData) . " hex chars");

        return "BITMAP {$x},{$y},{$bytesPerRow},{$newH},1,{$hexData}";
    }

    /**
     * Redimensiona imagen si supera maxW×maxH.
     */
    private function resizeImage(string $path, int $maxW, int $maxH): void
    {
        if (!extension_loaded('gd')) return;

        [$w, $h, $type] = getimagesize($path);
        if ($w <= $maxW && $h <= $maxH) return;

        $ratio = min($maxW / $w, $maxH / $h);
        $newW  = (int)round($w * $ratio);
        $newH  = (int)round($h * $ratio);

        $src = match ($type) {
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => null,
        };
        if (!$src) return;

        $dst = imagecreatetruecolor($newW, $newH);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

        match ($type) {
            IMAGETYPE_PNG  => imagepng($dst, $path),
            IMAGETYPE_JPEG => imagejpeg($dst, $path, 90),
            IMAGETYPE_WEBP => imagewebp($dst, $path, 90),
            default        => null,
        };

        imagedestroy($src);
        imagedestroy($dst);
    }

    public function deleteLogo(string $relativePath): bool
    {
        $fullPath = __DIR__ . '/../../public/' . ltrim($relativePath, '/');
        return file_exists($fullPath) ? unlink($fullPath) : true;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo.',
            UPLOAD_ERR_PARTIAL => 'La subida fue interrumpida.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
            default            => 'Error al subir el archivo.',
        };
    }
}
