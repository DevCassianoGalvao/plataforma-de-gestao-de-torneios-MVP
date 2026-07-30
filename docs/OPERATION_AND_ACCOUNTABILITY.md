# Operacao, Dados Derivados e Prestacao de Contas

## Fonte de verdade esportiva

O operador registra a partida na Central Operacional: escalações confirmadas, gols, assistencias, cartões, substituicoes, arbitragem, horarios e ocorrencias. A partida so entra nos dados oficiais depois da homologacao por organizador ou administrador. O operador nao homologa a propria partida.

Na homologacao, o sistema executa a cadeia derivada: processa disciplina, gera a versao imutavel da sumula, recalcula a classificacao do grupo e, quando todas as partidas da fase de grupos estao homologadas, gera a chave de mata-mata. Resultados de mata-mata homologados avancam o vencedor pela configuracao de cruzamentos do regulamento. Esses calculos nao dependem de botao manual.

## Operador e evidencias

`/minhas-partidas` mostra somente partidas atribuidas ao usuario operador. A atribuicao e feita pelo organizador na pagina da partida. O operador pode enviar fotografias PNG, JPEG ou WebP de ate 12 MiB por padrao em `Evidencias`; o MIME e verificado no servidor, a orientacao e corrigida, a imagem e reduzida e convertida para WebP no armazenamento privado. Cada upload gera auditoria.

Visibilidades: `private`, `accountability` e `public`. A interface publica nao carrega arquivos privados. A publicacao de uma foto exige rota publica especifica em etapa posterior; nesta entrega ela permanece protegida no painel e no modulo de prestacao.

## Prestacao de contas

O perfil `Prestacao de contas` acessa apenas campeonatos aos quais estiver vinculado com o tipo `accountability`. O modulo oferece CSV para Excel/LibreOffice de partidas, atletas e inscricoes, versoes de sumulas e evidencias. Todo download fica em `accountability_export_logs` e na auditoria.

Isto e uma base tecnica auditavel, nao uma declaracao de conformidade juridica com um convenio especifico. Para uso junto ao Ministerio do Esporte, o responsavel deve fornecer o plano de trabalho, modelo de planilha, campos obrigatorios e periodo de guarda documental aplicaveis.

## Governanca e portal

Administradores podem criar organizacoes e projetos em `/admin/governanca`. Campeonatos ganharam `project_id` opcional, preservando os existentes. O portal passou a ter consulta publica de suspensoes com campos exclusivamente esportivos; patrocinadores possuem estrutura de dados publica, sem expor informacoes privadas.

## Transferencias

Uma transferencia aprovada ou publicada pode receber a decisao explicita `Aplicar vinculo oficial`. Essa acao altera a equipe atual do atleta em transacao e deixa registro no historico; a publicacao editorial continua separada da decisao esportiva.
