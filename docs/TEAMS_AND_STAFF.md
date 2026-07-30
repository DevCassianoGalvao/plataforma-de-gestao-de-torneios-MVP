# Equipes e Comissao Tecnica

## Escopo

A Etapa 4 entrega o cadastro administrativo de equipes vinculadas a campeonamentos, seus responsaveis, membros da comissao tecnica, identidade visual basica, status e formacao tatica padrao. A pagina da equipe tambem exibe o elenco oficial aprovado e atalhos para inscricoes e partidas.

## Equipes

Uma equipe possui campeonato, nome, nome curto, slug, sigla, descricao, cidade, estado, cores primaria e secundaria, escudo privado, status, autor e timestamps. O nome e o slug sao unicos dentro do campeonato. O seletor de campeonato mostra somente campeonamentos autorizados; nenhum formulario pede `championship_id` digitado.

Status validos: `draft`, `active`, `inactive`, `withdrawn` e `archived`. As transicoes passam por `TeamStatusService`, e a alteracao registra auditoria. Equipes retiradas ou arquivadas permanecem no historico e nao sao apagadas fisicamente.

## Responsaveis e escopo

`team_user_assignments` registra vinculos `manager`, `head_coach`, `assistant_coach` e `viewer`, com inicio, fim, status e autor. O mesmo usuario pode ter mais de uma equipe quando cada acesso for concedido por vinculo explicito.

- Administrador acessa e administra todas as equipes.
- Organizador acessa equipes de campeonamentos aos quais esta vinculado.
- Treinador ou gestor acessa somente equipes com vinculo ativo e pode editar o escopo operacional permitido.
- Operador e comunicacao recebem `403` neste modulo por padrao.

Responsaveis sao atribuidos a partir de usuarios ativos existentes. Atribuicoes, encerramentos e alteracoes geram auditoria. Um treinador ou gestor nao pode mudar o campeonato da propria equipe, acessar outra equipe ou atribuir responsaveis administrativos.

## Comissao tecnica

`staff_roles` e um catalogo idempotente com as funcoes: treinador, auxiliar tecnico, preparador fisico, preparador de goleiros, fisioterapeuta, medico, massagista, dirigente, supervisor, roupeiro e outro. `team_staff` permite nome, nome de exibicao, email, telefone, foto privada, identificacao profissional, status, periodo e observacoes; `user_id` e opcional, portanto um membro pode existir sem login.

O campo `document_number` permanece na estrutura para uma futura implementacao protegida, mas nao e solicitado nem armazenado nesta etapa. Nao ha coleta de documento pessoal sem a camada de protecao correspondente.

## Uploads e auditoria

Escudo da equipe e foto de membro aceitam PNG, JPG, JPEG e WEBP. O MIME, a extensao e o tamanho sao validados; imagens sao corrigidas, reduzidas proporcionalmente e convertidas para WebP. SVG e executaveis sao recusados. Os arquivos recebem nomes aleatorios, ficam fora da area publica e sao acessiveis somente por rota autenticada e autorizada. Substituicoes usam armazenamento privado e limpeza segura do arquivo anterior.

A auditoria cobre criacao, edicao, identidade, status, atribuicao e encerramento de responsaveis, criacao/edicao/status da comissao e alteracao da formacao padrao. A interface mostra textos compreensiveis, sem exibir metadata JSON bruto.

## Integracao atual

Atletas, documentos, inscricoes, elenco oficial, grupos, rodadas, tabela, partidas, escalacoes, cartoes, suspensoes e portal publico possuem modulos proprios e sao acessados pelo painel conforme a permissao do usuario.
