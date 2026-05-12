Attribute VB_Name = "modConfig"
Option Explicit

' Leitura/escrita das configurações da aba CONFIG, através dos nomes
' definidos (cfgApiUrl, cfgApiToken etc.) criados pelo template — assim o
' resto do código nunca depende do endereço exato das células.

Public Function GetApiUrl() As String
    GetApiUrl = Trim(CStr(ThisWorkbook.Names("cfgApiUrl").RefersToRange.Value))
End Function

Public Function GetApiToken() As String
    GetApiToken = Trim(CStr(ThisWorkbook.Names("cfgApiToken").RefersToRange.Value))
End Function

Public Function GetSeasonId() As String
    GetSeasonId = Trim(CStr(ThisWorkbook.Names("cfgSeasonId").RefersToRange.Value))
End Function

Public Sub SetStatus(ByVal texto As String)
    ThisWorkbook.Names("cfgStatus").RefersToRange.Value = texto
End Sub

Public Sub SetLastSync(ByVal quando As Date)
    ThisWorkbook.Names("cfgLastSync").RefersToRange.Value = Format(quando, "dd/mm/yyyy hh:nn:ss")
End Sub

' Confere se URL e token foram preenchidos antes de qualquer chamada à API.
Public Function ConfigValida() As Boolean
    If Len(GetApiUrl()) = 0 Then
        MsgBox "Preencha a URL da API na aba CONFIG antes de continuar.", vbExclamation
        ConfigValida = False
        Exit Function
    End If

    If Len(GetApiToken()) = 0 Then
        MsgBox "Preencha o Token da API na aba CONFIG antes de continuar.", vbExclamation
        ConfigValida = False
        Exit Function
    End If

    ConfigValida = True
End Function
