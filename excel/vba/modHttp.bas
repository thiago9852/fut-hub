Attribute VB_Name = "modHttp"
Option Explicit

' Camada HTTP. Usa WinHTTP (nativo do Windows, não exige instalar nada).
' Requer a referência "Microsoft WinHTTP Services, version 5.1"
' (Editor VBA > Ferramentas > Referências) ou usa CreateObject, que dispensa
' marcar a referência manualmente — optamos por CreateObject para simplificar
' a instalação em qualquer máquina.

Public Type HttpResult
    StatusCode As Long
    Body As String
    Success As Boolean
End Type

Public Function HttpRequest(ByVal method As String, ByVal url As String, _
    Optional ByVal jsonBody As String = "", Optional ByVal token As String = "") As HttpResult

    Dim http As Object
    Dim result As HttpResult

    On Error GoTo ErrorHandler

    Set http = CreateObject("WinHttp.WinHttpRequest.5.1")
    http.Open method, url, False

    If Len(token) > 0 Then
        http.SetRequestHeader "Authorization", "Bearer " & token
    End If
    http.SetRequestHeader "Content-Type", "application/json"
    http.SetRequestHeader "Accept", "application/json"

    If Len(jsonBody) > 0 Then
        http.Send jsonBody
    Else
        http.Send
    End If

    result.StatusCode = http.Status
    result.Body = http.ResponseText
    result.Success = (http.Status >= 200 And http.Status < 300)

    HttpRequest = result
    Exit Function

ErrorHandler:
    result.StatusCode = 0
    result.Body = "Falha de conexão: " & Err.Description
    result.Success = False
    HttpRequest = result
End Function
