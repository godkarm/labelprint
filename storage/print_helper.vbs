' print_helper.vbs
' Envía un archivo .prn a la impresora especificada
' Uso: wscript.exe print_helper.vbs "C:\ruta\archivo.prn" "Nombre Impresora"
' Este script se ejecuta con los permisos del usuario actual

If WScript.Arguments.Count < 2 Then
    WScript.Echo "Uso: wscript.exe print_helper.vbs archivo.prn NombreImpresora"
    WScript.Quit 1
End If

Dim prnFile : prnFile = WScript.Arguments(0)
Dim printer : printer = WScript.Arguments(1)

Dim oShell : Set oShell = CreateObject("WScript.Shell")
Dim cmd : cmd = "cmd.exe /c copy /b """ & prnFile & """ """ & printer & """"
Dim rc : rc = oShell.Run(cmd, 0, True)

If rc = 0 Then
    WScript.Echo "OK"
    WScript.Quit 0
Else
    WScript.Echo "ERROR: rc=" & rc
    WScript.Quit rc
End If
