# Atletas, Responsaveis e Documentos

## Escopo

A Etapa 5 cria o cadastro estrutural de atletas sem exigir inscricao em campeonato. O cadastro cobre dados esportivos, equipe atual, status, posicoes, responsavel legal de menor, documentos privados, revisao e historico basico. Inscricoes, partidas, escalacoes, disciplina e portal publico sao modulos integrados ao fluxo geral e nao bloqueiam o cadastro inicial.

## Atleta

`athletes` armazena nome completo, nome esportivo, foto privada, nascimento, genero quando aplicavel, posicao principal, numero preferido, pe dominante, status, observacoes privadas, autor e datas. A idade e calculada a partir do nascimento e a exclusao usa `deleted_at` com status `archived`.

Status permitidos: `draft`, `active`, `inactive`, `blocked`, `transferred` e `archived`.

O atleta pertence a uma equipe existente e a validacao usa a categoria do campeonato da equipe. Para `sub-15-masculino`, por exemplo, a idade e validada no intervalo de 12 a 15 anos e o genero deve ser masculino. A inscricao nao e consultada no cadastro.

## Posicoes

O seed cria 13 posicoes: goleiro, zagueiro, lateral direito, lateral esquerdo, ala direito, ala esquerdo, volante, meio-campista, meia ofensivo, ponta direita, ponta esquerda, segundo atacante e centroavante. Cada registro tem codigo, nome, grupo posicional, ordem e status. Um atleta possui uma principal e pode possuir varias secundarias, sem repetir a principal.

## Responsavel legal

Atletas menores exigem um responsavel no mesmo fluxo de cadastro. O vinculo registra nome, parentesco, telefone, e-mail, autorizacao, observacoes e status. O documento informado pelo responsavel e cifrado com AES-256-GCM; a interface nunca o exibe em texto. Endereco nao foi incluido porque nao esta previsto no PRD atual.

## Documentos

Tipos configuraveis seedados: documento do atleta, autorizacao do responsavel, comprovante, atestado, foto e outros. Cada documento referencia atleta, responsavel quando aplicavel e tipo, alem de arquivo, validade, observacao, motivo de rejeicao, analisador e data da analise.

Status: `pending`, `approved`, `rejected`, `expired`, `replaced` e `archived`.

Arquivos ficam em armazenamento privado, fora de rotas publicas. O servidor valida erro de upload, tamanho maximo de 10 MB, MIME real por `finfo`, extensao coerente e rejeita executaveis. A leitura exige sessao, permissao e escopo do atleta.

## Acesso

- administrador: todos os atletas autorizados pelo sistema;
- organizador: atletas das equipes dos campeonamentos autorizados;
- treinador/gestor: atletas da propria equipe vinculada;
- operador e comunicacao: `403`;
- documentos e dados pessoais: somente em rotas administrativas autorizadas.

## Interface estrutural

As paginas sao `/admin/atletas`, `/admin/atletas/nova`, `/admin/atletas/{id}`, `/admin/atletas/{id}/editar`, `/admin/atletas/{id}/responsaveis`, `/admin/atletas/{id}/documentos` e `/admin/posicoes`. O detalhe separa dados esportivos, equipe, posicoes, responsavel, documentos e atalhos para inscricoes, partidas e disciplina.

UI/UX definitiva, campo visual, inscricoes e integracao com partidas permanecem para rodadas posteriores.
