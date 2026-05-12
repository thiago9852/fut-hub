# Configurando o VBA na planilha

O template (`excel/template/FutebolLocal.xlsx`) é um `.xlsx` comum — sem
macros. Isso é proposital: um `.xlsx` gerado por script não consegue
carregar um projeto VBA compilado (`vbaProject.bin`), então o passo de
habilitar as macros é manual, feito uma única vez por quem for usar a
planilha.

## 1. Salvar como `.xlsm`

Abra `FutebolLocal.xlsx` no Excel e salve como **Pasta de Trabalho Habilitada
para Macro do Excel (\*.xlsm)**.

## 2. Importar os módulos

No Excel, pressione **Alt+F11** para abrir o Editor VBA. No painel
"Project", clique com o botão direito na pasta de trabalho → **Import
File...** e importe, nesta ordem, os arquivos de `excel/vba/`:

1. `modJson.bas`
2. `modHttp.bas`
3. `modConfig.bas`
4. `modValidacao.bas`
5. `modSincronizacao.bas`

## 3. Criar os botões

Na aba **CONFIG** da planilha, na área "Ações", já existem três células
estilizadas como botão: `[ Baixar dados ]`, `[ Validar planilha ]` e
`[ Sincronizar ]`. Para associá-las às macros:

1. Guia **Desenvolvedor** → **Inserir** → **Botão (Controle de Formulário)**.
2. Desenhe o botão sobre a célula correspondente.
3. Na caixa "Atribuir macro", escolha:
   - `BaixarDados` para "Baixar dados"
   - `ValidarPlanilha` para "Validar planilha"
   - `SincronizarDados` para "Sincronizar"

(Se a guia Desenvolvedor não aparecer: **Arquivo → Opções → Personalizar
Faixa de Opções** → marque "Desenvolvedor".)

## 4. Preencher a configuração

Ainda na aba CONFIG, preencha:

- **URL da API** — ex.: `http://127.0.0.1:8000/api/v1` (dev local) ou a URL
  de produção.
- **Token da API** — o `apiToken` do usuário (ver `User::apiToken` no
  banco, ou peça ao administrador da organização).

## 5. Habilitar macros ao abrir

Ao reabrir o arquivo `.xlsm`, o Excel vai pedir para habilitar o conteúdo
(macros). Isso é esperado — sem isso, os botões não funcionam.

## O que já funciona

- **Validar planilha**: confere campos obrigatórios e referências (ex.: um
  time citado em JOGOS que não existe em TIMES) antes de qualquer envio.
- **Sincronizar**: envia **TIMES**, **JOGADORES**, **JOGOS** e **EVENTOS**
  para os endpoints de importação em lote da API (`/api/v1/import/*`),
  que fazem upsert por `external_id` — rodar Sincronizar várias vezes com
  os mesmos dados nunca duplica nada. O `external_id` de cada linha é
  montado a partir da própria coluna **ID** da aba (preenchida
  automaticamente se estiver em branco); é esse ID que a coluna "Jogo" de
  EVENTOS usa para apontar pra linha correspondente em JOGOS.
- Depois de sincronizar, a caixa de mensagem mostra quantos registros
  foram criados, atualizados ou falharam em cada aba.

## O que ainda não sincroniza (por design)

**ELENCOS e ESCALAÇÕES** ainda não são enviados pelo botão Sincronizar —
a API não tem endpoints de importação para essas duas abas (só existem
`/import/teams`, `/import/players`, `/import/matches` e `/import/events`,
ver README "🔌 API"). Adicioná-los é uma extensão natural da Fase 10
sempre que fizer sentido para o projeto.
