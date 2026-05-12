Attribute VB_Name = "modValidacao"
Option Explicit

' ValidarPlanilha percorre TIMES, JOGADORES e JOGOS e reporta problemas
' antes de qualquer envio à API — evita descobrir erro de cadastro só
' depois de tentar sincronizar (ver README "Validação da importação").

Public Sub ValidarPlanilha()
    Dim erros As New Collection

    ValidarTimes erros
    ValidarJogadores erros
    ValidarJogos erros

    If erros.Count = 0 Then
        MsgBox "Nenhum erro encontrado. A planilha está pronta para sincronizar.", vbInformation, "Validação"
    Else
        Dim msg As String
        Dim i As Long
        msg = erros.Count & " problema(s) encontrado(s):" & vbCrLf & vbCrLf
        For i = 1 To erros.Count
            msg = msg & "- " & erros(i) & vbCrLf
        Next i
        MsgBox msg, vbExclamation, "Validação"
    End If
End Sub

Private Sub ValidarTimes(ByRef erros As Collection)
    Dim tbl As ListObject
    Set tbl = ThisWorkbook.Sheets("TIMES").ListObjects("TabelaTimes")

    Dim row As ListRow
    Dim n As Long
    n = 1
    For Each row In tbl.ListRows
        n = n + 1
        If Not LinhaVazia(row) Then
            If Trim(CStr(row.Range(1, 2).Value)) = "" Then
                erros.Add "TIMES linha " & n & ": Nome é obrigatório."
            End If
        End If
    Next row
End Sub

Private Sub ValidarJogadores(ByRef erros As Collection)
    Dim tbl As ListObject
    Set tbl = ThisWorkbook.Sheets("JOGADORES").ListObjects("TabelaJogadores")

    Dim row As ListRow
    Dim n As Long
    n = 1
    For Each row In tbl.ListRows
        n = n + 1
        If Not LinhaVazia(row) Then
            If Trim(CStr(row.Range(1, 2).Value)) = "" Then
                erros.Add "JOGADORES linha " & n & ": Nome é obrigatório."
            End If
        End If
    Next row
End Sub

Private Sub ValidarJogos(ByRef erros As Collection)
    Dim tbl As ListObject
    Set tbl = ThisWorkbook.Sheets("JOGOS").ListObjects("TabelaJogos")

    Dim timesTbl As ListObject
    Set timesTbl = ThisWorkbook.Sheets("TIMES").ListObjects("TabelaTimes")

    Dim row As ListRow
    Dim n As Long
    n = 1
    For Each row In tbl.ListRows
        n = n + 1
        If Not LinhaVazia(row) Then
            Dim mandante As String, visitante As String
            mandante = Trim(CStr(row.Range(1, 5).Value))
            visitante = Trim(CStr(row.Range(1, 6).Value))

            If mandante = "" Or visitante = "" Then
                erros.Add "JOGOS linha " & n & ": Mandante e Visitante são obrigatórios."
            ElseIf mandante = visitante Then
                erros.Add "JOGOS linha " & n & ": Mandante e Visitante não podem ser o mesmo time."
            Else
                If Not TimeExiste(timesTbl, mandante) Then
                    erros.Add "JOGOS linha " & n & ": time mandante '" & mandante & "' não existe na aba TIMES."
                End If
                If Not TimeExiste(timesTbl, visitante) Then
                    erros.Add "JOGOS linha " & n & ": time visitante '" & visitante & "' não existe na aba TIMES."
                End If
            End If
        End If
    Next row
End Sub

Private Function TimeExiste(ByVal timesTbl As ListObject, ByVal nome As String) As Boolean
    Dim row As ListRow
    For Each row In timesTbl.ListRows
        If Trim(CStr(row.Range(1, 2).Value)) = nome Then
            TimeExiste = True
            Exit Function
        End If
    Next row
    TimeExiste = False
End Function

' Uma linha é considerada vazia quando todas as células da linha estão em branco.
Public Function LinhaVazia(ByVal row As ListRow) As Boolean
    Dim c As Range
    For Each c In row.Range
        If Trim(CStr(c.Value)) <> "" Then
            LinhaVazia = False
            Exit Function
        End If
    Next c
    LinhaVazia = True
End Function
