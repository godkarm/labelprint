<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * PrinterService — TSC TE200 / TSPL2
 *
 * Etiqueta real (material "ETIQUETAS 4X8"):
 *   Ancho:  100 mm = 803 dots @ 203 DPI
 *   Alto:   200 mm = 1606 dots @ 203 DPI
 *   Orientación: Vertical
 *   Área útil (95%): márgenes x=20 dots (2.5mm), y=40 dots (5mm)
 *   Marco interior: BOX 20,40,783,1566
 *
 * 1 mm = 8.0315 dots @ 203 DPI
 */
class PrinterService
{
    private array $config;
    private const MM_TO_DOTS = 8.0315;

    // ── Dimensiones reales de la etiqueta ──────────────────────────────
    private const LABEL_W_MM   = 100.0;
    private const LABEL_H_MM   = 200.0;
    private const LABEL_W_DOTS = 803;   // round(100 * 8.0315)
    private const LABEL_H_DOTS = 1606;  // round(200 * 8.0315)

    // ── Márgenes para área útil del 95% ────────────────────────────────
    private const MX = 20;   // margen horizontal: 2.5mm cada lado
    private const MY = 40;   // margen vertical:   5mm arriba/abajo

    // ── Coordenadas Y del layout (orientación vertical) ─────────────────
    // Zona útil: y=[40..1566] = 1526 dots disponibles
    private const Y_EMPRESA_LABEL = 55;
    private const Y_EMPRESA_VAL   = 100;
    private const Y_SEP1          = 200;
    private const Y_PRODUCTO_LABEL= 230;
    private const Y_PRODUCTO_VAL  = 280;
    private const Y_SEP2          = 420;
    private const Y_COLOR_LABEL   = 450;
    private const Y_COLOR_VAL     = 510;
    private const Y_SEP3          = 680;
    private const Y_CANT_LABEL    = 710;
    private const Y_CANT_VAL      = 780;
    private const Y_SEP4          = 920;
    private const Y_TURNO_LABEL   = 950;
    private const Y_TURNO_VAL     = 1020;
    private const Y_SEP5          = 1160;
    private const Y_FECHA_LABEL   = 1190;
    private const Y_FECHA_VAL     = 1260;
    private const Y_SEP6          = 1400;
    private const Y_COPIAS_LABEL  = 1430;
    private const Y_COPIAS_VAL    = 1500;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $config = Database::fetchOne(
                "SELECT * FROM configuracion_impresora WHERE activo = 1 ORDER BY id LIMIT 1"
            );
        }
        $this->config = $config ?: $this->defaultConfig();
    }

    private function defaultConfig(): array
    {
        return [
            'nombre'           => 'TSC TE200',
            'modelo'           => 'TE200',
            'dpi'              => 203,
            'ancho_mm'         => self::LABEL_W_MM,
            'alto_mm'          => self::LABEL_H_MM,
            'velocidad'        => 4,
            'densidad'         => 8,
            'orientacion'      => 'vertical',
            'tipo_conexion'    => 'usb',
            'ip'               => null,
            'puerto'           => 9100,
            'nombre_compartido'=> null,
        ];
    }

    // ====================================================================
    // GENERACIÓN TSPL2 — Etiqueta de producción
    // ====================================================================

    public function generateTspl(array $label, int $copies = 1, ?string $bitmapData = null): string
    {
        // Usar dimensiones reales del material
        $ancho     = self::LABEL_W_MM;
        $alto      = self::LABEL_H_MM;
        $velocidad = (int)($this->config['velocidad'] ?? 4);
        $densidad  = (int)($this->config['densidad']  ?? 8);

        $W  = self::LABEL_W_DOTS;   // 803
        $H  = self::LABEL_H_DOTS;   // 1606
        $MX = self::MX;             // 20
        $MY = self::MY;             // 40
        $X1 = $MX + 10;            // x inicio de texto = 30
        $XR = $W - $MX;            // 783 (borde derecho útil)

        $cmds = [];

        // ── Configuración ────────────────────────────────────────────────
        $cmds[] = "SIZE {$ancho} mm, {$alto} mm";
        $cmds[] = "GAP 2 mm, 0 mm";
        $cmds[] = "DIRECTION 0";
        $cmds[] = "REFERENCE 0,0";
        $cmds[] = "SPEED {$velocidad}";
        $cmds[] = "DENSITY {$densidad}";
        $cmds[] = "SET TEAR ON";
        $cmds[] = "CLS";

        // ── Marco exterior al 95% del área ──────────────────────────────
        $cmds[] = "BOX {$MX},{$MY},{$XR}," . ($H - $MY) . ",3";

        // ── Logo (bitmap opcional) ───────────────────────────────────────
        $logoX = $X1;
        $logoW = 100; // dots ~12mm
        if ($bitmapData !== null) {
            $cmds[] = $bitmapData;
            $logoX  = $X1 + $logoW + 10; // texto empresa a la derecha del logo
        }

        // ── EMPRESA ──────────────────────────────────────────────────────
        $empresa = $this->tspl($label['empresa_nombre'] ?? 'EMPRESA');
        $cmds[] = "TEXT {$X1}," . self::Y_EMPRESA_LABEL . ",\"2\",0,1,1,\"EMPRESA:\"";
        $cmds[] = "TEXT {$X1}," . self::Y_EMPRESA_VAL   . ",\"4\",0,1,1,\"{$empresa}\"";
        $cmds[] = "BAR 0," . self::Y_SEP1 . ",{$W},2";

        // ── PRODUCTO ─────────────────────────────────────────────────────
        $producto = $this->tspl($label['producto_nombre'] ?? '');
        $cmds[] = "TEXT {$X1}," . self::Y_PRODUCTO_LABEL . ",\"2\",0,1,1,\"PRODUCTO:\"";
        // Fuente grande — reducir si es muy largo
        $pFont = mb_strlen($producto) > 18 ? '3' : '4';
        $cmds[] = "TEXT {$X1}," . self::Y_PRODUCTO_VAL   . ",\"{$pFont}\",0,1,1,\"{$producto}\"";
        $cmds[] = "BAR 0," . self::Y_SEP2 . ",{$W},2";

        // ── COLOR / SUBPRODUCTO ──────────────────────────────────────────
        $color = $this->tspl($label['subproducto_descripcion'] ?? '');
        $cmds[] = "TEXT {$X1}," . self::Y_COLOR_LABEL . ",\"2\",0,1,1,\"COLOR:\"";
        // Ajuste de fuente según longitud
        $len   = mb_strlen($color);
        $cFont = $len > 22 ? '3' : '4';
        $cmds[] = "TEXT {$X1}," . self::Y_COLOR_VAL   . ",\"{$cFont}\",0,1,1,\"{$color}\"";
        $cmds[] = "BAR 0," . self::Y_SEP3 . ",{$W},2";

        // ── CANTIDAD ─────────────────────────────────────────────────────
        $cant = $this->tspl((string)($label['cantidad'] ?? '0'));
        $cmds[] = "TEXT {$X1}," . self::Y_CANT_LABEL . ",\"2\",0,1,1,\"CANTIDAD:\"";
        $cmds[] = "TEXT {$X1}," . self::Y_CANT_VAL   . ",\"5\",0,1,1,\"{$cant}\"";
        $cmds[] = "BAR 0," . self::Y_SEP4 . ",{$W},2";

        // ── TURNO ────────────────────────────────────────────────────────
        $turnos   = [1 => '1 - MANANA', 2 => '2 - TARDE', 3 => '3 - NOCHE'];
        $turno    = $this->tspl($turnos[(int)($label['turno'] ?? 1)] ?? '');
        $cmds[] = "TEXT {$X1}," . self::Y_TURNO_LABEL . ",\"2\",0,1,1,\"TURNO:\"";
        $cmds[] = "TEXT {$X1}," . self::Y_TURNO_VAL   . ",\"4\",0,1,1,\"{$turno}\"";
        $cmds[] = "BAR 0," . self::Y_SEP5 . ",{$W},2";

        // ── FECHA ────────────────────────────────────────────────────────
        $fecha  = $this->tspl($label['fecha'] ?? date('d/m/Y'));
        $cmds[] = "TEXT {$X1}," . self::Y_FECHA_LABEL . ",\"2\",0,1,1,\"FECHA:\"";
        $cmds[] = "TEXT {$X1}," . self::Y_FECHA_VAL   . ",\"4\",0,1,1,\"{$fecha}\"";
        $cmds[] = "BAR 0," . self::Y_SEP6 . ",{$W},2";

        // ── COPIAS ───────────────────────────────────────────────────────
        $cmds[] = "TEXT {$X1}," . self::Y_COPIAS_LABEL . ",\"2\",0,1,1,\"COPIAS:\"";
        $cmds[] = "TEXT {$X1}," . self::Y_COPIAS_VAL   . ",\"4\",0,1,1,\"{$copies}\"";

        // ── IMPRIMIR ─────────────────────────────────────────────────────
        $cmds[] = "PRINT {$copies},1";
        $cmds[] = "";

        return implode("\r\n", $cmds);
    }

    // ====================================================================
    // GENERACIÓN TSPL2 — Etiqueta de prueba/calibración
    // ====================================================================

    public function generateTestTspl(): string
    {
        $ancho     = self::LABEL_W_MM;
        $alto      = self::LABEL_H_MM;
        $W         = self::LABEL_W_DOTS;
        $H         = self::LABEL_H_DOTS;
        $MX        = self::MX;
        $MY        = self::MY;
        $velocidad = (int)($this->config['velocidad'] ?? 4);
        $densidad  = (int)($this->config['densidad']  ?? 8);

        $cmds   = [];
        $cmds[] = "SIZE {$ancho} mm, {$alto} mm";
        $cmds[] = "GAP 2 mm, 0 mm";
        $cmds[] = "DIRECTION 0";
        $cmds[] = "SPEED {$velocidad}";
        $cmds[] = "DENSITY {$densidad}";
        $cmds[] = "CLS";

        // Marco exterior al 95%
        $cmds[] = "BOX {$MX},{$MY}," . ($W - $MX) . "," . ($H - $MY) . ",4";

        // Líneas de referencia cada 20mm horizontales
        for ($mm = 20; $mm < 200; $mm += 20) {
            $y = (int)round($mm * self::MM_TO_DOTS);
            $cmds[] = "BAR 0,{$y},{$W},1";
        }

        // Líneas de referencia cada 20mm verticales
        for ($mm = 20; $mm < 100; $mm += 20) {
            $x = (int)round($mm * self::MM_TO_DOTS);
            $cmds[] = "BAR {$x},0,1,{$H}";
        }

        $cmds[] = "TEXT 30,60,\"3\",0,1,1,\"TSC TE200 - PRUEBA\"";
        $cmds[] = "TEXT 30,120,\"2\",0,1,1,\"Ancho: {$ancho} mm\"";
        $cmds[] = "TEXT 30,160,\"2\",0,1,1,\"Alto:  {$alto} mm\"";
        $cmds[] = "TEXT 30,200,\"2\",0,1,1,\"DPI:   {$this->config['dpi']}\"";
        $cmds[] = "TEXT 30,240,\"2\",0,1,1,\"Vel:   {$velocidad}  Den: {$densidad}\"";
        $cmds[] = "TEXT 30,280,\"2\",0,1,1,\"Tipo:  " . strtoupper($this->config['tipo_conexion'] ?? 'USB') . "\"";
        $cmds[] = "TEXT 30,340,\"2\",0,1,1,\"Area util: 95%\"";
        $cmds[] = "TEXT 30,380,\"2\",0,1,1,\"Margen X: " . $MX . " dots (" . round($MX / self::MM_TO_DOTS, 1) . "mm)\"";
        $cmds[] = "TEXT 30,420,\"2\",0,1,1,\"Margen Y: " . $MY . " dots (" . round($MY / self::MM_TO_DOTS, 1) . "mm)\"";
        $cmds[] = "TEXT 30,500,\"1\",0,1,1,\"" . date('d/m/Y H:i:s') . "\"";
        $cmds[] = "TEXT 30,540,\"1\",0,1,1,\"labelprint v1.6\"";
        $cmds[] = "PRINT 1,1";
        $cmds[] = "";

        return implode("\r\n", $cmds);
    }

    // ====================================================================
    // ENVÍO A IMPRESORA
    // ====================================================================

    public function send(string $tsplData): array
    {
        $prnFile = $this->savePrnFile($tsplData);
        $tipo    = $this->config['tipo_conexion'] ?? 'usb';

        try {
            switch ($tipo) {
                case 'tcp':    $result = $this->sendTcp($tsplData);           break;
                case 'usb':    $result = $this->sendUsb($tsplData, $prnFile); break;
                case 'shared': $result = $this->sendShared($tsplData);        break;
                default:       $result = ['success' => false, 'message' => "Tipo desconocido: {$tipo}"];
            }
        } catch (\Throwable $e) {
            error_log('[PrinterService] ' . $e->getMessage());
            $result = ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }

        $result['prn_file'] = $prnFile ? basename($prnFile) : null;
        $result['tspl']     = $tsplData;
        return $result;
    }

    // ── TCP/IP ────────────────────────────────────────────────────────────

    private function sendTcp(string $data): array
    {
        $ip   = trim($this->config['ip'] ?? '');
        $port = (int)($this->config['puerto'] ?? 9100);

        if (empty($ip)) {
            return ['success' => false, 'message' => 'IP de impresora no configurada.'];
        }

        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
        if (!$socket) {
            return ['success' => false, 'message' => "No se pudo conectar a {$ip}:{$port} ({$errstr})."];
        }

        $written = @fwrite($socket, $data);
        fclose($socket);

        return $written
            ? ['success' => true,  'message' => "Etiqueta enviada a {$ip}:{$port}."]
            : ['success' => false, 'message' => 'Conectado pero no se pudieron enviar datos.'];
    }

    // ── USB ───────────────────────────────────────────────────────────────

    private function sendUsb(string $data, ?string $prnFile = null): array
    {
        // Linux
        if (PHP_OS_FAMILY !== 'Windows') {
            $dev = '/dev/usb/lp0';
            if (file_exists($dev) && is_writable($dev)) {
                return file_put_contents($dev, $data) !== false
                    ? ['success' => true,  'message' => 'Enviado por USB.']
                    : ['success' => false, 'message' => "No se pudo escribir en {$dev}."];
            }
            return ['success' => false, 'message' => 'Dispositivo USB no encontrado. Use TCP/IP.'];
        }

        return $this->sendUsbWindows($data, $prnFile);
    }

    private function sendUsbWindows(string $data, ?string $prnFile = null): array
    {
        if (!$prnFile || !file_exists($prnFile)) {
            $prnFile = $this->savePrnFile($data);
        }
        if (!$prnFile) {
            return ['success' => false, 'message' => 'No se pudo crear archivo temporal.'];
        }

        $tmpWin  = str_replace('/', '\\', $prnFile);
        $printer = trim($this->config['nombre'] ?? 'TSC TE200');
        $puerto  = trim($this->config['nombre_compartido'] ?? '');
        $log     = [];

        // Método 1: PowerShell inline RawPrinterHelper (API Win32 del spooler)
        $psCmd = $this->buildPsInlineCmd($tmpWin, $printer);
        exec($psCmd, $out1, $rc1);
        $log[] = "PS-RAW: rc={$rc1}";
        if ($rc1 === 0) {
            error_log('[PrinterService] OK via PS-RAW. ' . implode(' | ', $log));
            return ['success' => true, 'message' => "Etiqueta enviada a '{$printer}' (PowerShell RAW)."];
        }

        // Método 2: PowerShell script externo
        $ps1 = $this->helperPath('print_raw.ps1');
        if ($ps1) {
            $cmd2 = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "'
                  . str_replace("'", "''", $ps1) . '" -PrnFile "' . $tmpWin
                  . '" -PrinterName "' . $printer . '" 2>&1';
            exec($cmd2, $out2, $rc2);
            $log[] = "PS-file: rc={$rc2}";
            if ($rc2 === 0) {
                error_log('[PrinterService] OK via PS-file. ' . implode(' | ', $log));
                return ['success' => true, 'message' => "Etiqueta enviada a '{$printer}' (PS script)."];
            }
        }

        // Método 3: copy /b al nombre de impresora
        $cmd3 = 'copy /b "' . $tmpWin . '" "' . $printer . '" > nul 2>&1';
        exec($cmd3, $out3, $rc3);
        $log[] = "copy-nombre: rc={$rc3}";
        if ($rc3 === 0) {
            error_log('[PrinterService] OK via copy. ' . implode(' | ', $log));
            return ['success' => true, 'message' => "Etiqueta enviada a '{$printer}' (copy /b)."];
        }

        // Método 4: copy /b al puerto USB (USB001, COM3…)
        if (!empty($puerto) && preg_match('/^(USB\d+|COM\d+|LPT\d+)$/i', $puerto)) {
            $cmd4 = 'copy /b "' . $tmpWin . '" ' . strtoupper($puerto) . ' > nul 2>&1';
            exec($cmd4, $out4, $rc4);
            $log[] = "copy-puerto({$puerto}): rc={$rc4}";
            if ($rc4 === 0) {
                error_log('[PrinterService] OK via puerto. ' . implode(' | ', $log));
                return ['success' => true, 'message' => "Etiqueta enviada al puerto {$puerto}."];
            }
        }

        // Método 5: VBScript
        $vbs = $this->helperPath('print_helper.vbs');
        if ($vbs) {
            $cmd5 = 'wscript.exe "' . $vbs . '" "' . $tmpWin . '" "' . $printer . '" 2>&1';
            exec($cmd5, $out5, $rc5);
            $log[] = "VBS: rc={$rc5}";
            if ($rc5 === 0) {
                error_log('[PrinterService] OK via VBS. ' . implode(' | ', $log));
                return ['success' => true, 'message' => "Etiqueta enviada a '{$printer}' (VBScript)."];
            }
        }

        error_log('[PrinterService] TODOS FALLARON: ' . implode(' | ', $log));

        return [
            'success' => false,
            'message' => "No se pudo enviar a '{$printer}' desde Apache.\n"
                       . "CAUSA: Apache/XAMPP corre como NT AUTHORITY\\SYSTEM sin acceso a impresoras.\n"
                       . "SOLUCIONES:\n"
                       . "1. Use TCP/IP: active la red en la TE200 y configure la IP.\n"
                       . "2. Cambie la cuenta de Apache: Servicios → Apache2.4 → Inicio de sesión → su usuario.\n"
                       . "3. Descargue el .prn y arrástrelo a la impresora manualmente.",
            'detail'  => implode(' | ', $log),
        ];
    }

    private function buildPsInlineCmd(string $prnFile, string $printerName): string
    {
        $prnEsc = addslashes($prnFile);
        $prtEsc = addslashes($printerName);

        $ps = '$b=[IO.File]::ReadAllBytes(\'%s\');'
            . '$p=[Runtime.InteropServices.Marshal]::AllocHGlobal($b.Length);'
            . '[Runtime.InteropServices.Marshal]::Copy($b,0,$p,$b.Length);'
            . 'Add-Type -TypeDefinition \'using System;using System.Runtime.InteropServices;'
            . 'public class RP{'
            . '[DllImport("winspool.drv",CharSet=CharSet.Ansi)]public static extern bool OpenPrinter(string n,out IntPtr h,IntPtr p);'
            . '[DllImport("winspool.drv")]public static extern bool ClosePrinter(IntPtr h);'
            . '[StructLayout(LayoutKind.Sequential,CharSet=CharSet.Ansi)]public class DI{public string pDocName;public string pOutputFile;public string pDataType;}'
            . '[DllImport("winspool.drv")]public static extern bool StartDocPrinter(IntPtr h,int l,[In,MarshalAs(UnmanagedType.LPStruct)]DI d);'
            . '[DllImport("winspool.drv")]public static extern bool EndDocPrinter(IntPtr h);'
            . '[DllImport("winspool.drv")]public static extern bool StartPagePrinter(IntPtr h);'
            . '[DllImport("winspool.drv")]public static extern bool EndPagePrinter(IntPtr h);'
            . '[DllImport("winspool.drv")]public static extern bool WritePrinter(IntPtr h,IntPtr b,int c,out int w);}\';'
            . '$h=[IntPtr]::Zero;'
            . 'if([RP]::OpenPrinter(\'%s\',[ref]$h,[IntPtr]::Zero)){'
            . '$di=New-Object RP+DI;$di.pDocName=\'LabelPrint\';$di.pDataType=\'RAW\';'
            . '[RP]::StartDocPrinter($h,1,$di)|Out-Null;'
            . '[RP]::StartPagePrinter($h)|Out-Null;'
            . '$w=0;[RP]::WritePrinter($h,$p,$b.Length,[ref]$w)|Out-Null;'
            . '[RP]::EndPagePrinter($h)|Out-Null;[RP]::EndDocPrinter($h)|Out-Null;'
            . '[RP]::ClosePrinter($h)|Out-Null;'
            . '[Runtime.InteropServices.Marshal]::FreeHGlobal($p);'
            . 'Write-Output "OK:$w"}else{Write-Error "FAIL:$(Get-LastError)";exit 1}';

        $code    = sprintf($ps, $prnEsc, $prtEsc);
        $encoded = base64_encode(mb_convert_encoding($code, 'UTF-16LE', 'UTF-8'));
        return 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand ' . $encoded . ' 2>&1';
    }

    // ── Impresora compartida ─────────────────────────────────────────────

    private function sendShared(string $data): array
    {
        $nombre = trim($this->config['nombre_compartido'] ?? $this->config['nombre'] ?? '');
        if (empty($nombre)) {
            return ['success' => false, 'message' => 'Nombre de impresora compartida no configurado.'];
        }

        $prnFile = $this->savePrnFile($data);
        if (!$prnFile) {
            return ['success' => false, 'message' => 'No se pudo crear archivo temporal.'];
        }

        $cmd = PHP_OS_FAMILY === 'Windows'
            ? 'copy /b "' . str_replace('/', '\\', $prnFile) . '" "' . $nombre . '" > nul 2>&1'
            : 'lpr -P ' . escapeshellarg($nombre) . ' -l ' . escapeshellarg($prnFile) . ' 2>&1';

        exec($cmd, $out, $rc);
        return $rc === 0
            ? ['success' => true,  'message' => "Enviado a '{$nombre}'."]
            : ['success' => false, 'message' => "Error enviando a '{$nombre}'.", 'detail' => implode("\n", $out)];
    }

    // ====================================================================
    // UTILIDADES
    // ====================================================================

    private function savePrnFile(string $data): ?string
    {
        $dir = defined('ROOT_PATH')
            ? ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp'
            : sys_get_temp_dir();

        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . DIRECTORY_SEPARATOR . 'label_' . date('Ymd_His') . '_' . rand(100, 999) . '.prn';
        return file_put_contents($file, $data) !== false ? $file : null;
    }

    private function helperPath(string $filename): ?string
    {
        if (!defined('ROOT_PATH')) return null;
        $p = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $filename;
        return file_exists($p) ? $p : null;
    }

    /**
     * Transliteración UTF-8 → ASCII para TSPL2.
     * TSC TE200 usa CP850 — tildes y Ñ generan caracteres corruptos.
     */
    private function tspl(string $text): string
    {
        static $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
            'ñ'=>'n','Ñ'=>'N','ü'=>'u','Ü'=>'U',
            'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o',
            'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
            '°'=>' ',
        ];
        $text = strtr($text, $map);
        $text = str_replace('"', "'", $text);
        $text = preg_replace('/[\x00-\x1F\x7F-\xFF]/', ' ', $text) ?? $text;
        return trim($text);
    }

    public function checkStatus(): array
    {
        if (($this->config['tipo_conexion'] ?? 'usb') !== 'tcp') {
            return ['available' => true, 'message' => 'Use "Prueba de impresión" para verificar la conexión.'];
        }

        $ip   = trim($this->config['ip'] ?? '');
        $port = (int)($this->config['puerto'] ?? 9100);

        if (empty($ip)) return ['available' => false, 'message' => 'IP no configurada.'];

        $s = @fsockopen($ip, $port, $errno, $errstr, 3);
        if ($s) { fclose($s); return ['available' => true, 'message' => "Disponible en {$ip}:{$port}"]; }
        return ['available' => false, 'message' => "No responde en {$ip}:{$port} ({$errstr})"];
    }

    public static function mmToDots(float $mm): int
    {
        return (int)round($mm * self::MM_TO_DOTS);
    }
}
