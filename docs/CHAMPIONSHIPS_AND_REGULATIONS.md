# Campeonamentos e Regulamentos

## Temporadas e categorias

Temporadas possuem nome, ano, periodo e status. Categorias possuem nome, slug, descricao, idades opcionais, regra de genero e status. Os catalogos sao selecionados por nome nos formularios.

## Campeonato

O cadastro usa nome, nome curto, slug, descricao, temporada, categoria, datas, periodo de inscricao e visibilidade. O primeiro salvamento cria um campeonato em `draft` e um regulamento inicial em rascunho.

O dashboard mostra dados reais, identidade, versao do regulamento, organizadores e checklist. Equipes e partidas aparecem como indisponiveis, sem metricas ficticias.

## Identidade e uploads

Tema, tres cores, logo, logos para fundos, banner, favicon e imagem social sao armazenados. PNG, JPG, JPEG, WEBP e ICO sao aceitos conforme o campo. O MIME real e validado com `finfo`, o nome final e aleatorio e o arquivo fica em armazenamento privado. SVG nao e aceito.

PDF de regulamento usa o mesmo armazenamento privado, com limite de 10 MB e acesso autenticado pelo escopo do campeonato.

## Escopo

Administrador ve todos os campeonatos. Organizador precisa de vinculo `organizer` em `championship_user_assignments`; alterar a URL nao concede acesso. Organizadores sem vinculo recebem 403. Outros perfis nao acessam o modulo nesta etapa.

## Regulamento estruturado

O editor separa identificacao, formato, pontuacao/W.O., desempates, disciplina e regras de partida. As tabelas sao `regulation_format_settings`, `regulation_points_settings`, `regulation_tiebreakers`, `regulation_discipline_settings` e `regulation_match_settings`. Nao existe textarea JSON.

A versao do regulamento tambem pode definir tamanho minimo e maximo do elenco, minimo de goleiros, permissao de inscricao por multiplas equipes e tipos de documento obrigatorios. Esses dados ficam em `regulation_roster_settings` e `regulation_required_documents`, sem JSON em formularios. A Etapa 6 usa essas configuracoes para validar envio e aprovacao de inscricoes.

## Preset

O preset Copa Brasil de Talentos inicia com 10 equipes, 2 grupos, 5 equipes por grupo, 4 classificadas, quartas, semifinais, final e eliminatorias em jogo unico. Valores nao definidos pelo PRD sao padroes editaveis e identificados no editor.

## Versoes e publicacao

Rascunho pode ser editado. Publicacao marca a versao como `published` e a anterior como `superseded`. Se o regulamento publicado for editado, o sistema cria a proxima versao em rascunho. Apenas uma versao fica ativa; historico permanece disponivel.

## Status

Transicoes validas: `draft -> registration -> configured -> in_progress -> finished -> archived`, com arquivamento permitido antes do inicio. `configured` e `in_progress` exigem regulamento publicado. Campeonato arquivado fica somente para consulta.

## Seed e limitacoes

O seed cria dados ficticios de temporada, categoria, Copa Brasil de Talentos, regulamento publicado e organizador vinculado, sem equipes ou partidas. Ele e idempotente e bloqueado em producao.

Este documento descreve a fundacao de campeonatos e regulamentos. As etapas posteriores adicionaram equipes, atletas, inscricoes, grupos, rodadas, partidas, disciplina, classificacao, mata-mata e sumula digital; conteudo e portal continuam fora do escopo implementado.
