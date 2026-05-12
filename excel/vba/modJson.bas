Attribute VB_Name = "modJson"
Option Explicit

' Codificador/decodificador JSON minimalista, feito sob medida para o formato
' de dados desta planilha (objetos "achatados", sem aninhamento profundo).
' Não é um parser JSON genérico — cobre exatamente o que a API do
' Futebol Local envia e recebe (ver README "Estrutura da planilha").

' Constrói um objeto JSON a partir de um Dictionary de pares chave/valor.
' Valores string são escapados; valores numéricos e booleanos não.
Public Function JsonEncodeObject(ByVal data As Object) As String
    Dim k As Variant
    Dim parts() As String
    Dim i As Long
    Dim n As Long

    n = data.Count
    ReDim parts(n - 1)
    i = 0
    For Each k In data.Keys
        parts(i) = JsonEncodeString(CStr(k)) & ":" & JsonEncodeValue(data(k))
        i = i + 1
    Next k

    JsonEncodeObject = "{" & Join(parts, ",") & "}"
End Function

' Constrói um array JSON "[{...},{...}]" a partir de uma Collection de
' Dictionaries (um por linha) — usado para montar o payload dos endpoints
' de importação em lote (/api/v1/import/*).
Public Function JsonEncodeArray(ByVal items As Collection) As String
    Dim parts() As String
    Dim i As Long

    If items.Count = 0 Then
        JsonEncodeArray = "[]"
        Exit Function
    End If

    ReDim parts(items.Count - 1)
    For i = 1 To items.Count
        parts(i - 1) = JsonEncodeObject(items(i))
    Next i

    JsonEncodeArray = "[" & Join(parts, ",") & "]"
End Function

Private Function JsonEncodeValue(ByVal value As Variant) As String
    Select Case VarType(value)
        Case vbNull, vbEmpty
            JsonEncodeValue = "null"
        Case vbBoolean
            JsonEncodeValue = IIf(value, "true", "false")
        Case vbInteger, vbLong, vbSingle, vbDouble, vbCurrency, vbDecimal
            JsonEncodeValue = Replace(CStr(value), ",", ".") ' garante ponto decimal
        Case Else
            If IsEmpty(value) Or IsNull(value) Or Len(CStr(value)) = 0 Then
                JsonEncodeValue = "null"
            Else
                JsonEncodeValue = JsonEncodeString(CStr(value))
            End If
    End Select
End Function

Private Function JsonEncodeString(ByVal s As String) As String
    Dim result As String
    result = s
    result = Replace(result, "\", "\\")
    result = Replace(result, """", "\""")
    result = Replace(result, vbCrLf, "\n")
    result = Replace(result, vbCr, "\n")
    result = Replace(result, vbLf, "\n")
    result = Replace(result, vbTab, "\t")
    JsonEncodeString = """" & result & """"
End Function

' Extrai o valor de um campo de nível superior de uma resposta JSON simples,
' por exemplo JsonExtractField(body, "id") ou JsonExtractField(body, "error").
' Suficiente para ler as respostas planas da API (id, error, status); não
' tenta interpretar objetos ou arrays aninhados.
Public Function JsonExtractField(ByVal json As String, ByVal fieldName As String) As String
    Dim searchKey As String
    Dim startPos As Long
    Dim valueStart As Long
    Dim c As String

    searchKey = """" & fieldName & """"
    startPos = InStr(1, json, searchKey)
    If startPos = 0 Then
        JsonExtractField = ""
        Exit Function
    End If

    valueStart = InStr(startPos + Len(searchKey), json, ":")
    If valueStart = 0 Then
        JsonExtractField = ""
        Exit Function
    End If
    valueStart = valueStart + 1

    ' pula espaços em branco
    Do While Mid(json, valueStart, 1) = " "
        valueStart = valueStart + 1
    Loop

    c = Mid(json, valueStart, 1)
    If c = """" Then
        JsonExtractField = JsonExtractStringAt(json, valueStart)
    Else
        JsonExtractField = JsonExtractLiteralAt(json, valueStart)
    End If
End Function

Private Function JsonExtractStringAt(ByVal json As String, ByVal quotePos As Long) As String
    Dim i As Long
    Dim result As String
    Dim c As String

    i = quotePos + 1
    result = ""
    Do While i <= Len(json)
        c = Mid(json, i, 1)
        If c = "\" Then
            Dim nextChar As String
            nextChar = Mid(json, i + 1, 1)
            Select Case nextChar
                Case "n": result = result & vbLf
                Case "t": result = result & vbTab
                Case "\": result = result & "\"
                Case """": result = result & """"
                Case Else: result = result & nextChar
            End Select
            i = i + 2
        ElseIf c = """" Then
            Exit Do
        Else
            result = result & c
            i = i + 1
        End If
    Loop

    JsonExtractStringAt = result
End Function

Private Function JsonExtractLiteralAt(ByVal json As String, ByVal startPos As Long) As String
    Dim i As Long
    Dim c As String

    i = startPos
    Do While i <= Len(json)
        c = Mid(json, i, 1)
        If c = "," Or c = "}" Or c = "]" Then Exit Do
        i = i + 1
    Loop

    JsonExtractLiteralAt = Trim(Mid(json, startPos, i - startPos))
End Function
