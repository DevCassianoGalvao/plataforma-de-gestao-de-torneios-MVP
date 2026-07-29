# Mapeamento da Sumula

Fonte analisada: `docs/REFERENCIA_SUMULA.xlsx`, com as abas `Súmula`, `Atletas` e `ArtilhariaCartões`.

## Aba Súmula

| Bloco da planilha | Dados que o futuro banco deve suportar |
|---|---|
| titulo e competicao | campeonato, temporada, categoria e identificacao oficial |
| placar final | partida, status, gols de cada equipe e resultado |
| horario | inicio, termino, primeiro tempo, segundo tempo e contagem |
| equipes | dois participantes, nomes e identificadores internos |
| relacao de jogadores | atleta, numero, amarelo, vermelho e marcacoes de gol |
| assinaturas | tecnico, auxiliar/capitao e confirmacao por equipe |
| arbitragem | arbitro, arbitros assistentes, mesario e organizacao |
| desempate | indicacao e resultado de disputa por penalti |
| verso | ocorrencias livres e confirmacao da partida |

## Aba Atletas

Campos observados: equipe, funcao, nome completo, posicao, CPF, data de nascimento, WhatsApp, gols, amarelo, vermelho, dia da suspensao e ocorrencia.

No sistema, CPF, telefone e documentos serao privados. Estatisticas publicas devem ser derivadas de eventos homologados, nao copiadas de dados pessoais da planilha.

## Aba ArtilhariaCartões

Campos observados: time, atleta, gols, amarelo, vermelho, dia da suspensao e ocorrencias. O futuro ledger disciplinar deve separar evento, acumulacao, suspensao e cumprimento.

## Contrato futuro de geracao

1. Resolver a partida e os dois elencos pelo escopo autorizado.
2. Derivar gols, cartoes e placar de eventos homologados.
3. Renderizar cabecalho, duas equipes, arbitragem, penaltis e ocorrencias.
4. Manter verso/segunda pagina para ocorrencias.
5. Registrar versao da sumula, homologador e data.

## Implementacao da Etapa 12

O contrato acima foi implementado por `MatchReportRepository`, `MatchReportService`, `MatchReportHtmlRenderer` e `MatchReportPdf`. A aba principal vira preview HTML e PDF A4 com duas paginas; a aba de atletas alimenta titulares/reservas, numero, AM, VM e gols; a aba de artilharia/cartoes permanece fonte de referencia, enquanto estatisticas sao derivadas de eventos homologados.

PDFs sao privados, versionados e identificados por codigo de verificacao. A versao atual e baixada por partida; pacotes ZIP podem ser montados por rodada ou campeonato. Nao ha assinatura digital oficial nem retificacao completa no MVP.
