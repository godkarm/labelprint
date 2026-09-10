<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * PrinterService — TSC TE200 / TSPL2
 *
 * DIMENSIONES DEL MATERIAL (confirmadas por usuario):
 *   SIZE 200 mm, 100 mm
 *   Ancho de avance: 200 mm = 1606 dots @ 203 DPI
 *   Ancho de papel:  100 mm =  803 dots @ 203 DPI
 *   Orientación: Horizontal
 *   Área útil 95%: márgenes MX=40 dots (5mm), MY=20 dots (2.5mm)
 *   Marco: BOX 40,20,1566,783
 *
 * FUENTE DE VERDAD: configuracion_impresora en BD.
 * Las constantes son los valores por defecto del material real.
 * generateTspl() lee SIEMPRE de $this->config (desde BD).
 */
class PrinterService
{
    private array $config;
    private const MM_TO_DOTS = 8.0315; // @203 DPI

    // Valores por defecto del material real (solo para defaultConfig)
    private const DEFAULT_W_MM = 200.0;
    private const DEFAULT_H_MM = 100.0;

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
            'ancho_mm'         => self::DEFAULT_W_MM,
            'alto_mm'          => self::DEFAULT_H_MM,
            'velocidad'        => 4,
            'densidad'         => 8,
            'orientacion'      => 'horizontal',
            'tipo_conexion'    => 'usb',
            'ip'               => null,
            'puerto'           => 9100,
            'nombre_compartido'=> null,
        ];
    }

    // ====================================================================
    // GENERACIÓN TSPL2 — Lee SIEMPRE desde $this->config (BD)
    // ====================================================================

    /**
     * Genera comandos TSPL2 para la etiqueta de producción.
     * Lee ancho_mm, alto_mm, velocidad, densidad desde $this->config.
     * NO usa constantes hardcodeadas para las dimensiones.
     * NO incluye campo COPIAS en la etiqueta.
     */
    public function generateTspl(array $label, int $copies = 1, ?string $bitmapData = null): string
    {
        // ── Leer configuración desde BD ──────────────────────────────────
        $ancho     = (float)($this->config['ancho_mm']  ?? self::DEFAULT_W_MM);
        $alto      = (float)($this->config['alto_mm']   ?? self::DEFAULT_H_MM);
        $velocidad = (int)  ($this->config['velocidad'] ?? 4);
        $densidad  = (int)  ($this->config['densidad']  ?? 8);

        // ── Calcular dots desde configuración real ───────────────────────
        $W  = (int)round($ancho * self::MM_TO_DOTS);  // dots eje largo (avance)
        $H  = (int)round($alto  * self::MM_TO_DOTS);  // dots eje corto (papel)

        // Márgenes al 2.5% por lado → área útil 95%
        $MX = (int)round($W * 0.025);
        $MY = (int)round($H * 0.025);
        $X1 = $MX + 10;     // x inicio de texto
        $XR = $W - $MX;     // x borde derecho útil

        // Separadores horizontales (líneas completas de borde a borde)
        $cmds = [];

        // ── Configuración de etiqueta ────────────────────────────────────
        $cmds[] = "SIZE {$ancho} mm, {$alto} mm";
        $cmds[] = "GAP 2 mm, 0 mm";
        $cmds[] = "DIRECTION 0";
        $cmds[] = "REFERENCE 0,0";
        $cmds[] = "SPEED {$velocidad}";
        $cmds[] = "DENSITY {$densidad}";
        $cmds[] = "SET TEAR ON";
        $cmds[] = "CLS";

        // ── Marco exterior al 95% ────────────────────────────────────────
        $cmds[] = "BOX {$MX},{$MY},{$XR}," . ($H - $MY) . ",3";

        // ── LOGO ─────────────────────────────────────────────────────────
        $empresaTextX = $X1;

        if ($bitmapData !== null) {
            // Logo en esquina superior izquierda
            // El bitmap ya posicionado en X1,MY+5 por LogoProcessor
            $cmds[] = $bitmapData;
            // El texto de empresa va a la derecha del logo (~120 dots)
            $empresaTextX = $X1 + 130;
        }

        // ── EMPRESA ──────────────────────────────────────────────────────
        $empresa = $this->tspl($label['empresa_nombre'] ?? 'EMPRESA');
        $y_empresa = $MY + 15;
        $cmds[] = "TEXT {$empresaTextX},{$y_empresa},\"3\",0,1,1,\"{$empresa}\"";

        // ── SEP 1 ────────────────────────────────────────────────────────
        $y_sep1 = $MY + 90;
        $cmds[] = "BAR 0,{$y_sep1},{$W},2";

        // ── PRODUCTO ─────────────────────────────────────────────────────
        $producto = $this->tspl($label['producto_nombre'] ?? '');
        $y_prod_l = $y_sep1 + 15;
        $y_prod_v = $y_sep1 + 38;
        $pFont    = mb_strlen($producto) > 20 ? '3' : '4';
        $cmds[] = "TEXT {$X1},{$y_prod_l},\"2\",0,1,1,\"PRODUCTO:\"";
        $cmds[] = "TEXT {$X1},{$y_prod_v},\"{$pFont}\",0,1,1,\"{$producto}\"";

        // ── SEP 2 ────────────────────────────────────────────────────────
        $y_sep2 = $y_prod_v + 80;
        $cmds[] = "BAR 0,{$y_sep2},{$W},2";

        // ── COLOR / SUBPRODUCTO ──────────────────────────────────────────
        $color  = $this->tspl($label['subproducto_descripcion'] ?? '');
        $y_col_l = $y_sep2 + 15;
        $y_col_v = $y_sep2 + 38;
        $cLen    = mb_strlen($color);
        $cFont   = $cLen > 22 ? '3' : '4';
        $cmds[] = "TEXT {$X1},{$y_col_l},\"2\",0,1,1,\"COLOR:\"";
        $cmds[] = "TEXT {$X1},{$y_col_v},\"{$cFont}\",0,1,1,\"{$color}\"";

        // ── SEP 3 ────────────────────────────────────────────────────────
        $y_sep3 = $y_col_v + 80;
        $cmds[] = "BAR 0,{$y_sep3},{$W},2";

        // ── FILA FINAL: CANTIDAD | TURNO | FECHA en 3 columnas ───────────
        // Dividir el ancho útil en 3 columnas
        $col_w   = (int)(($XR - $X1) / 3);
        $y_lbl   = $y_sep3 + 15;
        $y_val   = $y_sep3 + 38;

        $cant  = $this->tspl((string)($label['cantidad'] ?? '0'));
        $turnos = [1 => '1-MANANA', 2 => '2-TARDE', 3 => '3-NOCHE'];
        $turno  = $this->tspl($turnos[(int)($label['turno'] ?? 1)] ?? '');
        $fecha  = $this->tspl($label['fecha'] ?? date('d/m/Y'));

        $x_col1 = $X1;
        $x_col2 = $X1 + $col_w;
        $x_col3 = $X1 + $col_w * 2;

        $cmds[] = "TEXT {$x_col1},{$y_lbl},\"2\",0,1,1,\"CANTIDAD\"";
        $cmds[] = "TEXT {$x_col2},{$y_lbl},\"2\",0,1,1,\"TURNO\"";
        $cmds[] = "TEXT {$x_col3},{$y_lbl},\"2\",0,1,1,\"FECHA\"";

        $cmds[] = "TEXT {$x_col1},{$y_val},\"4\",0,1,1,\"{$cant}\"";
        $cmds[] = "TEXT {$x_col2},{$y_val},\"3\",0,1,1,\"{$turno}\"";
        $cmds[] = "TEXT {$x_col3},{$y_val},\"3\",0,1,1,\"{$fecha}\"";

        // ── IMPRIMIR (sin campo COPIAS visible en etiqueta) ──────────────
        $cmds[] = "PRINT {$copies},1";
        $cmds[] = "";

        return implode("\r\n", $cmds);
    }

    /**
     * Etiqueta de prueba — también lee dimensiones de $this->config.
     */
    public function generateTestTspl(): string
    {
        $ancho     = (float)($this->config['ancho_mm']  ?? self::DEFAULT_W_MM);
        $alto      = (float)($this->config['alto_mm']   ?? self::DEFAULT_H_MM);
        $velocidad = (int)  ($this->config['velocidad'] ?? 4);
        $densidad  = (int)  ($this->config['densidad']  ?? 8);
        $W  = (int)round($ancho * self::MM_TO_DOTS);
        $H  = (int)round($alto  * self::MM_TO_DOTS);
        $MX = (int)round($W * 0.025);
        $MY = (int)round($H * 0.025);

        $cmds   = [];
        $cmds[] = "SIZE {$ancho} mm, {$alto} mm";
        $cmds[] = "GAP 2 mm, 0 mm";
        $cmds[] = "DIRECTION 0";
        $cmds[] = "SPEED {$velocidad}";
        $cmds[] = "DENSITY {$densidad}";
        $cmds[] = "CLS";
        $cmds[] = "BOX {$MX},{$MY}," . ($W - $MX) . "," . ($H - $MY) . ",4";

        // Líneas de referencia
        for ($mm = 20; $mm < $ancho; $mm += 20) {
            $x = (int)round($mm * self::MM_TO_DOTS);
            if ($x < $W) $cmds[] = "BAR {$x},0,1,{$H}";
        }
        for ($mm = 10; $mm < $alto; $mm += 10) {
            $y = (int)round($mm * self::MM_TO_DOTS);
            if ($y < $H) $cmds[] = "BAR 0,{$y},{$W},1";
        }

        $cmds[] = "TEXT " . ($MX+10) . "," . ($MY+15) . ",\"3\",0,1,1,\"TSC TE200 - PRUEBA\"";
        $cmds[] = "TEXT " . ($MX+10) . "," . ($MY+55) . ",\"2\",0,1,1,\"Ancho: {$ancho} mm  Alto: {$alto} mm\"";
        $cmds[] = "TEXT " . ($MX+10) . "," . ($MY+85) . ",\"2\",0,1,1,\"DPI: {$this->config['dpi']}  Vel: {$velocidad}  Den: {$densidad}\"";
        $cmds[] = "TEXT " . ($MX+10) . "," . ($MY+115) . ",\"2\",0,1,1,\"Tipo: " . strtoupper($this->config['tipo_conexion'] ?? 'USB') . "  Area: 95%\"";
        $cmds[] = "TEXT " . ($MX+10) . "," . ($MY+145) . ",\"1\",0,1,1,\"" . date('d/m/Y H:i:s') . "  labelprint v1.6\"";
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

    private function sendTcp(string $data): array
    {
        $ip   = trim($this->config['ip'] ?? '');
        $port = (int)($this->config['puerto'] ?? 9100);
        if (empty($ip)) return ['success' => false, 'message' => 'IP no configurada.'];

        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
        if (!$socket) return ['success' => false, 'message' => "No se pudo conectar a {$ip}:{$port} ({$errstr})."];

        $written = @fwrite($socket, $data);
        fclose($socket);
        return $written
            ? ['success' => true,  'message' => "Enviado a {$ip}:{$port}."]
            : ['success' => false, 'message' => 'Sin datos enviados.'];
    }

    private function sendUsb(string $data, ?string $prnFile = null): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $dev = '/dev/usb/lp0';
            if (file_exists($dev) && is_writable($dev)) {
                return file_put_contents($dev, $data) !== false
                    ? ['success' => true,  'message' => 'Enviado por USB.']
                    : ['success' => false, 'message' => "Error escribiendo en {$dev}."];
            }
            return ['success' => false, 'message' => 'Dispositivo USB no encontrado. Use TCP/IP.'];
        }
        return $this->sendUsbWindows($data, $prnFile);
    }

    private function sendUsbWindows(string $data, ?string $prnFile = null): array
    {
        if (!$prnFile || !file_exists($prnFile)) $prnFile = $this->savePrnFile($data);
        if (!$prnFile) return ['success' => false, 'message' => 'No se pudo crear archivo temporal.'];

        $tmpWin  = str_replace('/', '\\', $prnFile);
        $printer = trim($this->config['nombre'] ?? 'TSC TE200');
        $puerto  = trim($this->config['nombre_compartido'] ?? '');
        $log     = [];

        // Método 1: PowerShell inline RawPrinterHelper (API Win32 del spooler)
        $psCmd = $this->buildPsInlineCmd($tmpWin, $printer);
        exec($psCmd, $out1, $rc1);
        $log[] = "PS-RAW:rc={$rc1}";
        if ($rc1 === 0) { error_log('[PS-RAW OK] ' . implode('|', $log)); return ['success' => true, 'message' => "Enviado a '{$printer}' (PowerShell RAW)."]; }

        // Método 2: PowerShell script externo
        $ps1 = $this->helperPath('print_raw.ps1');
        if ($ps1) {
            $cmd2 = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' . $ps1 . '" -PrnFile "' . $tmpWin . '" -PrinterName "' . $printer . '" 2>&1';
            exec($cmd2, $out2, $rc2);
            $log[] = "PS-file:rc={$rc2}";
            if ($rc2 === 0) { return ['success' => true, 'message' => "Enviado a '{$printer}' (PS script)."]; }
        }

        // Método 3: copy /b nombre impresora
        exec('copy /b "' . $tmpWin . '" "' . $printer . '" > nul 2>&1', $out3, $rc3);
        $log[] = "copy-nombre:rc={$rc3}";
        if ($rc3 === 0) { return ['success' => true, 'message' => "Enviado a '{$printer}' (copy /b)."]; }

        // Método 4: copy /b puerto USB
        if (!empty($puerto) && preg_match('/^(USB\d+|COM\d+|LPT\d+)$/i', $puerto)) {
            exec('copy /b "' . $tmpWin . '" ' . strtoupper($puerto) . ' > nul 2>&1', $out4, $rc4);
            $log[] = "copy-puerto({$puerto}):rc={$rc4}";
            if ($rc4 === 0) { return ['success' => true, 'message' => "Enviado al puerto {$puerto}."]; }
        }

        // Método 5: VBScript
        $vbs = $this->helperPath('print_helper.vbs');
        if ($vbs) {
            exec('wscript.exe "' . $vbs . '" "' . $tmpWin . '" "' . $printer . '" 2>&1', $out5, $rc5);
            $log[] = "VBS:rc={$rc5}";
            if ($rc5 === 0) { return ['success' => true, 'message' => "Enviado a '{$printer}' (VBScript)."]; }
        }

        error_log('[PrinterService] TODOS FALLARON: ' . implode(' | ', $log));
        return [
            'success' => false,
            'message' => "No se pudo enviar a '{$printer}'.\n"
                       . "CAUSA: Apache corre como NT AUTHORITY\\SYSTEM sin acceso a impresoras.\n"
                       . "SOLUCIÓN 1: Use TCP/IP (active la red en la TE200).\n"
                       . "SOLUCIÓN 2: Servicios → Apache2.4 → Inicio de sesión → su usuario de Windows.\n"
                       . "El archivo .prn está en storage/temp/ para impresión manual.",
            'detail'  => implode(' | ', $log),
        ];
    }

    private function buildPsInlineCmd(string $prnFile, string $printerName): string
    {
        $prnEsc = addslashes($prnFile);
        $prtEsc = addslashes($printerName);
        $ps = '$b=[IO.File]::ReadAllBytes(\'%s\');$p=[Runtime.InteropServices.Marshal]::AllocHGlobal($b.Length);[Runtime.InteropServices.Marshal]::Copy($b,0,$p,$b.Length);Add-Type -TypeDefinition \'using System;using System.Runtime.InteropServices;public class RP{[DllImport("winspool.drv",CharSet=CharSet.Ansi)]public static extern bool OpenPrinter(string n,out IntPtr h,IntPtr p);[DllImport("winspool.drv")]public static extern bool ClosePrinter(IntPtr h);[StructLayout(LayoutKind.Sequential,CharSet=CharSet.Ansi)]public class DI{public string pDocName;public string pOutputFile;public string pDataType;}[DllImport("winspool.drv")]public static extern bool StartDocPrinter(IntPtr h,int l,[In,MarshalAs(UnmanagedType.LPStruct)]DI d);[DllImport("winspool.drv")]public static extern bool EndDocPrinter(IntPtr h);[DllImport("winspool.drv")]public static extern bool StartPagePrinter(IntPtr h);[DllImport("winspool.drv")]public static extern bool EndPagePrinter(IntPtr h);[DllImport("winspool.drv")]public static extern bool WritePrinter(IntPtr h,IntPtr b,int c,out int w);}\';$h=[IntPtr]::Zero;if([RP]::OpenPrinter(\'%s\',[ref]$h,[IntPtr]::Zero)){$di=New-Object RP+DI;$di.pDocName=\'LabelPrint\';$di.pDataType=\'RAW\';[RP]::StartDocPrinter($h,1,$di)|Out-Null;[RP]::StartPagePrinter($h)|Out-Null;$w=0;[RP]::WritePrinter($h,$p,$b.Length,[ref]$w)|Out-Null;[RP]::EndPagePrinter($h)|Out-Null;[RP]::EndDocPrinter($h)|Out-Null;[RP]::ClosePrinter($h)|Out-Null;[Runtime.InteropServices.Marshal]::FreeHGlobal($p);Write-Output "OK:$w"}else{Write-Error "FAIL";exit 1}';
        $code = sprintf($ps, $prnEsc, $prtEsc);
        $encoded = base64_encode(mb_convert_encoding($code, 'UTF-16LE', 'UTF-8'));
        return 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand ' . $encoded . ' 2>&1';
    }

    private function sendShared(string $data): array
    {
        $nombre = trim($this->config['nombre_compartido'] ?? $this->config['nombre'] ?? '');
        if (empty($nombre)) return ['success' => false, 'message' => 'Nombre de impresora compartida no configurado.'];
        $prnFile = $this->savePrnFile($data);
        if (!$prnFile) return ['success' => false, 'message' => 'No se pudo crear archivo temporal.'];
        $cmd = PHP_OS_FAMILY === 'Windows'
            ? 'copy /b "' . str_replace('/', '\\', $prnFile) . '" "' . $nombre . '" > nul 2>&1'
            : 'lpr -P ' . escapeshellarg($nombre) . ' -l ' . escapeshellarg($prnFile) . ' 2>&1';
        exec($cmd, $out, $rc);
        return $rc === 0
            ? ['success' => true,  'message' => "Enviado a '{$nombre}'."]
            : ['success' => false, 'message' => "Error enviando a '{$nombre}'.", 'detail' => implode("\n", $out)];
    }

    private function savePrnFile(string $data): ?string
    {
        $dir = defined('ROOT_PATH')
            ? ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp'
            : sys_get_temp_dir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . DIRECTORY_SEPARATOR . 'label_' . date('Ymd_His') . '_' . rand(100,999) . '.prn';
        return file_put_contents($file, $data) !== false ? $file : null;
    }

    private function helperPath(string $filename): ?string
    {
        if (!defined('ROOT_PATH')) return null;
        $p = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $filename;
        return file_exists($p) ? $p : null;
    }

    /**
     * Transliteración UTF-8 → ASCII para TSPL2 (TE200 usa CP850).
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
            return ['available' => true, 'message' => 'Use "Prueba de impresión" para verificar.'];
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
