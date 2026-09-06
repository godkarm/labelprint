<?php

declare(strict_types=1);

namespace App\Services;

/**
 * LogoProcessor
 *
 * Responsabilidades:
 * - Validar imagen subida (tipo, tamaño, dimensiones)
 * - Guardar logo de forma segura
 * - Convertir imagen a monocromo/BMP para TSPL2
 * - Generar comando BITMAP TSPL2
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
     * Retorna ['success' => bool, 'path' => string, 'message' => string]
     */
    public function processUpload(array $file): array
    {
        // Validar error de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => $this->uploadErrorMessage($file['error'])];
        }

        // Validar tamaño
        $maxSize = $this->config['upload']['max_size'];
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo ' . ($maxSize / 1024 / 1024) . ' MB.'];
        }

        // Validar extensión
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->config['upload']['allowed_ext'], true)) {
            return ['success' => false, 'message' => 'Formato no permitido. Use: PNG, JPG, JPEG, WEBP.'];
        }

        // Validar MIME real
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $this->config['upload']['allowed_mime'], true)) {
            return ['success' => false, 'message' => 'Tipo de archivo no válido.'];
        }

        // Validar que sea imagen real
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'message' => 'El archivo no es una imagen válida.'];
        }

        // Nombre seguro
        $safeName  = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadDir = $this->config['upload']['logo_dir'];

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'No se pudo guardar el logo.'];
        }

        // Opcional: redimensionar para que no sea enorme
        $this->resizeImage($destination, 300, 150);

        return [
            'success' => true,
            'path'    => 'uploads/logos/' . $safeName,
            'message' => 'Logo subido correctamente.',
        ];
    }

    /**
     * Redimensiona imagen si supera las dimensiones máximas.
     */
    private function resizeImage(string $path, int $maxW, int $maxH): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        [$w, $h, $type] = getimagesize($path);

        if ($w <= $maxW && $h <= $maxH) {
            return; // No necesita redimensionar
        }

        $ratio  = min($maxW / $w, $maxH / $h);
        $newW   = (int)round($w * $ratio);
        $newH   = (int)round($h * $ratio);

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

    /**
     * Convierte el logo a un comando BITMAP TSPL2 monocromo.
     * Posición: x=10, y=5, máximo 60 dots ancho × 45 dots alto.
     *
     * @param string $logoPath Ruta física al logo
     * @return string|null Comando BITMAP TSPL2 o null si no se pudo procesar
     */
    public function toTsplBitmap(string $logoPath, int $x = 10, int $y = 5, int $maxW = 60, int $maxH = 45): ?string
    {
        if (!extension_loaded('gd') || !file_exists($logoPath)) {
            return null;
        }

        $imageInfo = @getimagesize($logoPath);
        if (!$imageInfo) return null;

        [, , $type] = $imageInfo;

        $src = match ($type) {
            IMAGETYPE_PNG  => @imagecreatefrompng($logoPath),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($logoPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($logoPath),
            default        => null,
        };

        if (!$src) return null;

        // Redimensionar a maxW × maxH manteniendo proporción
        [$origW, $origH] = getimagesize($logoPath);
        $ratio = min($maxW / $origW, $maxH / $origH);
        $newW  = (int)round($origW * $ratio);
        $newH  = (int)round($origH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        // Convertir a monocromo (1 bit por pixel)
        // TSPL2 BITMAP: cada byte = 8 pixels horizontales (MSB primero)
        $bytesPerRow = (int)ceil($newW / 8);
        $bitmapBytes = [];

        for ($row = 0; $row < $newH; $row++) {
            for ($byteIdx = 0; $byteIdx < $bytesPerRow; $byteIdx++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $col = $byteIdx * 8 + $bit;
                    if ($col < $newW) {
                        $rgb   = imagecolorat($dst, $col, $row);
                        $r     = ($rgb >> 16) & 0xFF;
                        $g     = ($rgb >> 8)  & 0xFF;
                        $b     = $rgb & 0xFF;
                        $luma  = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                        // Umbral: pixel oscuro = 1 en bitmap
                        if ($luma < 128) {
                            $byte |= (0x80 >> $bit);
                        }
                    }
                }
                $bitmapBytes[] = $byte;
            }
        }

        imagedestroy($dst);

        // Generar hex string
        $hexData = implode('', array_map(fn($b) => sprintf('%02X', $b), $bitmapBytes));

        // Comando TSPL2: BITMAP x,y,width_bytes,height,mode,data
        // mode 1 = OR (sobreescribir)
        return "BITMAP {$x},{$y},{$bytesPerRow},{$newH},1,{$hexData}";
    }

    /**
     * Elimina el logo anterior si existe.
     */
    public function deleteLogo(string $relativePath): bool
    {
        $fullPath = __DIR__ . '/../../public/' . ltrim($relativePath, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return true;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL => 'La subida fue interrumpida.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
            default => 'Error al subir el archivo.',
        };
    }
}
