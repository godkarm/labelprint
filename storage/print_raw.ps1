# print_raw.ps1
# Envía datos RAW (TSPL) directamente al spooler de Windows
# Uso: powershell -ExecutionPolicy Bypass -File print_raw.ps1 "archivo.prn" "NombreImpresora"

param(
    [string]$PrnFile,
    [string]$PrinterName
)

if (-not (Test-Path $PrnFile)) {
    Write-Error "Archivo no encontrado: $PrnFile"
    exit 1
}

try {
    # Método 1: RawPrinterHelper via .NET
    Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;

public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }
    [DllImport("winspool.Drv", EntryPoint="OpenPrinterA", SetLastError=true)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);
    [DllImport("winspool.Drv", EntryPoint="ClosePrinter", SetLastError=true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint="StartDocPrinterA", SetLastError=true)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);
    [DllImport("winspool.Drv", EntryPoint="EndDocPrinter", SetLastError=true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint="StartPagePrinter", SetLastError=true)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint="EndPagePrinter", SetLastError=true)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint="WritePrinter", SetLastError=true)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);
}
"@

    $bytes = [System.IO.File]::ReadAllBytes($PrnFile)
    $ptr = [System.Runtime.InteropServices.Marshal]::AllocHGlobal($bytes.Length)
    [System.Runtime.InteropServices.Marshal]::Copy($bytes, 0, $ptr, $bytes.Length)

    $hPrinter = [IntPtr]::Zero
    $di = New-Object RawPrinterHelper+DOCINFOA
    $di.pDocName = "LabelPrint-TSPL"
    $di.pDataType = "RAW"

    if ([RawPrinterHelper]::OpenPrinter($PrinterName, [ref]$hPrinter, [IntPtr]::Zero)) {
        if ([RawPrinterHelper]::StartDocPrinter($hPrinter, 1, $di)) {
            if ([RawPrinterHelper]::StartPagePrinter($hPrinter)) {
                $written = 0
                [RawPrinterHelper]::WritePrinter($hPrinter, $ptr, $bytes.Length, [ref]$written) | Out-Null
                [RawPrinterHelper]::EndPagePrinter($hPrinter) | Out-Null
            }
            [RawPrinterHelper]::EndDocPrinter($hPrinter) | Out-Null
        }
        [RawPrinterHelper]::ClosePrinter($hPrinter) | Out-Null
        [System.Runtime.InteropServices.Marshal]::FreeHGlobal($ptr)
        Write-Output "OK: $($bytes.Length) bytes enviados a '$PrinterName'"
        exit 0
    } else {
        throw "No se pudo abrir la impresora '$PrinterName'"
    }
} catch {
    Write-Error "ERROR: $_"
    exit 1
}
