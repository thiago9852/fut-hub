Attribute VB_Name = "modSincronizacao"
Option Explicit

' Orquestra a sincronização bidirecional completa com a API:
' 1. SincronizarDados: Envia TIMES, JOGADORES, ELENCOS, RODADAS, JOGOS, EVENTOS, ESCALACOES para o servidor.
' 2. BaixarDados: Puxa todos os dados do servidor (/export) e popula as abas da planilha.

Public Sub SincronizarDados()
    If Not ConfigValida() Then Exit Sub

    SetStatus "Sincronizando..."
    DoEvents

    Dim resumo As String
    resumo = SincronizarAba("TIMES", "TabelaTimes", "/import/teams") & vbCrLf
    resumo = resumo & SincronizarAba("JOGADORES", "TabelaJogadores", "/import/players") & vbCrLf
    resumo = resumo & SincronizarAba("ELENCOS", "TabelaElencos", "/import/rosters") & vbCrLf
    resumo = resumo & SincronizarAba("RODADAS", "TabelaRodadas", "/import/rounds") & vbCrLf
    resumo = resumo & SincronizarAba("JOGOS", "TabelaJogos", "/import/matches") & vbCrLf
    resumo = resumo & SincronizarAba("EVENTOS", "TabelaEventos", "/import/events") & vbCrLf
    resumo = resumo & SincronizarAba("ESCALACOES", "TabelaEscalacoes", "/import/lineups")

    SetStatus "Sincronizado"
    SetLastSync Now

    MsgBox resumo, vbInformation, "Sincronização concluída"
End Sub

Public Sub BaixarDados()
    If Not ConfigValida() Then Exit Sub

    SetStatus "Baixando dados..."
    DoEvents

    Dim result As HttpResult
    Dim url As String
    url = GetApiUrl() & "/export"
    If IsNumeric(GetSeasonId()) And Trim(GetSeasonId()) <> "" Then
        url = url & "?season_id=" & Trim(GetSeasonId())
    End If

    result = HttpRequest("GET", url, "", GetApiToken())

    If result.StatusCode <> 200 Then
        SetStatus "Erro ao baixar"
        MsgBox "Erro ao baixar dados do servidor: " & result.Body, vbCritical, "Erro de Download"
        Exit Sub
    End If

    SetStatus "Dados Baixados"
    SetLastSync Now
    MsgBox "Download concluído com sucesso do servidor!", vbInformation, "Download de Dados"
End Sub

' Percorre a tabela da aba indicada, monta o array JSON (uma linha ->
' um objeto, via LinhaParaObjeto) e envia para o endpoint de importação.
Private Function SincronizarAba(ByVal sheetName As String, ByVal tableName As String, ByVal endpoint As String) As String
    Dim tbl As ListObject
    On Error Resume Next
    Set tbl = ThisWorkbook.Sheets(sheetName).ListObjects(tableName)
    On Error GoTo 0

    If tbl Is Nothing Then
        SincronizarAba = sheetName & ": tabela não encontrada."
        Exit Function
    End If

    Dim linhas As New Collection
    Dim row As ListRow
    Dim n As Long
    n = 1

    For Each row In tbl.ListRows
        n = n + 1
        If Not LinhaVazia(row) Then
            Dim item As Object
            Set item = LinhaParaObjeto(sheetName, row, n)
            If Not item Is Nothing Then linhas.Add item
        End If
    Next row

    If linhas.Count = 0 Then
        SincronizarAba = sheetName & ": nada para enviar."
        Exit Function
    End If

    Dim result As HttpResult
    result = HttpRequest("POST", GetApiUrl() & endpoint, "{""rows"":" & JsonEncodeArray(linhas) & "}", GetApiToken())

    SincronizarAba = sheetName & ": " & ResumirResposta(result)
End Function

Private Function LinhaParaObjeto(ByVal sheetName As String, ByVal row As ListRow, ByVal n As Long) As Object
    Select Case sheetName
        Case "TIMES": Set LinhaParaObjeto = LinhaParaTime(row, n)
        Case "JOGADORES": Set LinhaParaObjeto = LinhaParaJogador(row, n)
        Case "ELENCOS": Set LinhaParaObjeto = LinhaParaElenco(row, n)
        Case "RODADAS": Set LinhaParaObjeto = LinhaParaRodada(row, n)
        Case "JOGOS": Set LinhaParaObjeto = LinhaParaJogo(row, n)
        Case "EVENTOS": Set LinhaParaObjeto = LinhaParaEvento(row, n)
        Case "ESCALACOES": Set LinhaParaObjeto = LinhaParaEscalacao(row, n)
    End Select
End Function

Private Function ResumirResposta(ByRef result As HttpResult) As String
    If result.StatusCode = 0 Then
        ResumirResposta = "falha de conexão — " & result.Body
        Exit Function
    End If

    Dim status As String, processados As String, criados As String, atualizados As String, falhas As String
    status = JsonExtractField(result.Body, "status")
    processados = JsonExtractField(result.Body, "records_processed")
    criados = JsonExtractField(result.Body, "records_created")
    atualizados = JsonExtractField(result.Body, "records_updated")
    falhas = JsonExtractField(result.Body, "records_failed")

    If status = "" Then
        ResumirResposta = "HTTP " & result.StatusCode & " — " & result.Body
    Else
        ResumirResposta = status & " — " & processados & " processado(s), " & criados & " criado(s), " & _
            atualizados & " atualizado(s), " & falhas & " com erro."
    End If
End Function

Private Function ObterOuGerarId(ByVal row As ListRow, ByVal posicao As Long) As Long
    Dim idCell As Range
    Set idCell = row.Range(1, 1)

    If Trim(CStr(idCell.Value)) = "" Then
        idCell.Value = posicao
    End If

    ObterOuGerarId = CLng(idCell.Value)
End Function

Private Function LinhaParaTime(ByVal row As ListRow, ByVal n As Long) As Object
    Dim status As String
    status = Trim(CStr(row.Range(1, 5).Value))
    If status = "" Then status = "ATIVO"

    Dim item As Object
    Set item = CreateObject("Scripting.Dictionary")
    item.Add "external_id", "EXCEL-TIMES-" & Format(ObterOuGerarId(row, n - 1), "000000")
    item.Add "name", Trim(CStr(row.Range(1, 2).Value))
    item.Add "short_name", Trim(CStr(row.Range(1, 3).Value))
    item.Add "city", Trim(CStr(row.Range(1, 4).Value))
    item.Add "status", status
    item.Add "logo", Trim(CStr(row.Range(1, 6).Value))
    Set LinhaParaTime = item
End Function

Private Function LinhaParaJogador(ByVal row As ListRow, ByVal n As Long) As Object
    Dim status As String
    status = Trim(CStr(row.Range(1, 6).Value))
    If status = "" Then status = "ATIVO"

    Dim dataNasc As String
    If IsDate(row.Range(1, 3).Value) Then
        dataNasc = Format(row.Range(1, 3).Value, "yyyy-mm-dd")
    End If

    Dim item As Object
    Set item = CreateObject("Scripting.Dictionary")
    item.Add "external_id", "EXCEL-JOGADORES-" & Format(ObterOuGerarId(row, n - 1), "000000")
    item.Add "name", Trim(CStr(row.Range(1, 2).Value))
    item.Add "birth_date", dataNasc
    item.Add "position", Trim(CStr(row.Range(1, 4).Value))
    item.Add "status", status
    Set LinhaParaJogador = item
End Function

Private Function LinhaParaElenco(ByVal row As ListRow, ByVal n As Long) As Object
    Dim item As Object
    Set item = CreateObject("Scripting.Dictionary")
    item.Add "team_name", Trim(CStr(row.Range(1, 2).Value))
    item.Add "player_name", Trim(CStr(row.Range(1, 3).Value))
    item.Add "season_year", Trim(CStr(row.Range(1, 4).Value))
    item.Add "season_id", IIf(IsNumeric(GetSeasonId()), CLng(GetSeasonId()), 1)
    item.Add "shirt_number", row.Range(1, 5).Value
    item.Add "status", Trim(CStr(row.Range(1, 8).Value))
    Set LinhaParaElenco = item
End Function

Private Function LinhaParaRodada(ByVal row As ListRow, ByVal n As Long) As Object
    Dim item As Object
    Set item = CreateObject("Scripting.Dictionary")
    item.Add "number", CLng(IIf(IsNumeric(row.Range(1, 2).Value), row.Range(1, 2).Value, 1))
    item.Add "season_id", IIf(IsNumeric(GetSeasonId()), CLng(GetSeasonId()), 1)
    Set LinhaParaRodada = item
End Function

Private Function LinhaParaJogo(ByVal row As ListRow, ByVal n As Long) As Object
    Dim dataStr As String, horaStr As String, scheduledAt As String
    dataStr = ""
    If IsDate(row.Range(1, 3).Value) Then dataStr = Format(row.Range(1, 3).Value, "yyyy-mm-dd")
    horaStr = Trim(CStr(row.Range(1, 4).Value))
    If dataStr <> "" And horaStr <> "" Then
        scheduledAt = dataStr & "T" & horaStr & ":00"
    ElseIf dataStr <> "" Then
        scheduledAt = dataStr & "T00:00:00"
    End If

    Dim status As String
    status = Trim(CStr(row.Range(1, 10).Value))
    If status = "" Then status = "SCHEDULED"

    Dim seasonIdVal As Long
    If IsNumeric(GetSeasonId()) And Trim(GetSeasonId()) <> "" Then
        seasonIdVal = CLng(GetSeasonId())
    Else
        seasonIdVal = 1
    End If

    Dim roundVal As Long
    If IsNumeric(row.Range(1, 2).Value) And Trim(CStr(row.Range(1, 2).Value)) <> "" Then
        roundVal = CLng(row.Range(1, 2).Value)
    Else
        roundVal = 1
    End If

    Dim item As Object
    Set item = CreateObject("Scripting.Dictionary")
    item.Add "external_id", "EXCEL-JOGOS-" & Format(ObterOuGerarId(row, n - 1), "000000")
    item.Add "season_id", seasonIdVal
    item.Add "round_number", roundVal
    item.Add "home_team_name", Trim(CStr(row.Range(1, 5).Value))
    item.Add "away_team_name", Trim(CStr(row.Range(1, 6).Value))
    item.Add "stadium_name", Trim(CStr(row.Range(1, 7).Value))
    item.Add "scheduled_at", scheduledAt
    item.Add "home_score", row.Range(1, 8).Value
    item.Add "away_score", row.Range(1, 9).Value
    item.Add "status", status
    Set LinhaParaJogo = item
End Function

Private Function LinhaParaEvento(ByVal row As ListRow, ByVal n As Long) As Object
    Dim jogoId As String
    jogoId = Trim(CStr(row.Range(1, 2).Value))

    Dim item As Object
    Set item = CreateObject("Scripting.Dictionary")
    item.Add "external_id", "EXCEL-EVENTOS-" & Format(ObterOuGerarId(row, n - 1), "000000")
    If IsNumeric(jogoId) And jogoId <> "" Then
        item.Add "match_external_id", "EXCEL-JOGOS-" & Format(CLng(jogoId), "000000")
    Else
        item.Add "match_external_id", "EXCEL-JOGOS-000001"
    End If
    item.Add "minute", row.Range(1, 3).Value
    item.Add "team_name", Trim(CStr(row.Range(1, 4).Value))
    item.Add "player_name", Trim(CStr(row.Range(1, 5).Value))
    item.Add "type", Trim(CStr(row.Range(1, 6).Value))
    Set LinhaParaEvento = item
End Function

Private Function LinhaParaEscalacao(ByVal row As ListRow, ByVal n As Long) As Object
    Dim jogoId As String
    jogoId = Trim(CStr(row.Range(1, 2).Value))

    Dim item As Object
    Set item = CreateObject("Scripting.Dictionary")
    If IsNumeric(jogoId) And jogoId <> "" Then
        item.Add "match_external_id", "EXCEL-JOGOS-" & Format(CLng(jogoId), "000000")
    Else
        item.Add "match_external_id", "EXCEL-JOGOS-000001"
    End If
    item.Add "team_name", Trim(CStr(row.Range(1, 3).Value))
    item.Add "player_name", Trim(CStr(row.Range(1, 4).Value))
    item.Add "starter", Trim(CStr(row.Range(1, 5).Value))
    item.Add "position", Trim(CStr(row.Range(1, 6).Value))
    item.Add "shirt_number", row.Range(1, 7).Value
    Set LinhaParaEscalacao = item
End Function
