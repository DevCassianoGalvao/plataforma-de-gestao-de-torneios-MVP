# Campeonamentos e Regulamentos

## Temporadas e categorias

Temporadas possuem nome, ano, período e status. Categorias possuem nome, slug, descrição, idades opcionais, regra de gênero e status. Os catalogos são selecionados por nome nos formulários.

## Campeonato

O cadastro usa nome, nome curto, slug, descrição, temporada, categoria, datas, período de inscrição e visibilidade. O primeiro salvamento cria um campeonato em `draft` e um regulamento inicial em rascunho.

O dashboard mostra dados reais, identidade, versão do regulamento e checklist. Equipes, elenco, tabela e partidas ficam disponíveis nos módulos integrados, sem métricas ficticias.

## Identidade e uploads

Tema, três cores, logo, logos para fundos, banner, favicon e imagem social são armazenados. PNG, JPG, JPEG, WEBP e ICO são aceitos conforme o campo. Exceto favicon, imagens são corrigidas, reduzidas proporcionalmente e convertidas para WebP. O MIME real e validado com `finfo`, o nome final e aleatório e o arquivo fica em armazenamento privado. SVG não e aceito.

PDF de regulamento usa o mesmo armazenamento privado, com limite de 10 MB e acesso autenticado pelo escopo do campeonato.

## Escopo

Administrador vê todos os campeonatos e é o único perfil com acesso a este módulo; os demais perfis recebem 403.

## Regulamento estruturado

O editor separa identificação, formato, pontuação/W.O., desempates, disciplina e regras de partida. As tabelas são `regulation_format_settings`, `regulation_points_settings`, `regulation_tiebreakers`, `regulation_discipline_settings` e `regulation_match_settings`. Não existe textarea JSON.

A versão do regulamento também pode definir tamanho mínimo e máximo do elenco, mínimo de goleiros, permissão de inscrição por múltiplas equipes e tipos de documento obrigatórios. Esses dados ficam em `regulation_roster_settings` e `regulation_required_documents`, sem JSON em formulários. A Etapa 6 usa essas configurações para validar envio e aprovação de inscrições.

## Preset

O preset Copa Brasil de Talentos inicia com 10 equipes, 2 grupos, 5 equipes por grupo, 4 classificadas, quartas, semifinais, final e eliminatórias em jogo único. Valores não definidos pelo PRD são padroes editáveis e identificados no editor.

## Versoes e publicação

Rascunho pode ser editado. Publicação marca a versão como `published` e a anterior como `superseded`. Se o regulamento publicado for editado, o sistema cria a próxima versão em rascunho. Apenas uma versão fica ativa; histórico permanece disponível.

## Status

Transições validas: `draft -> registration -> configured -> in_progress -> finished -> archived`, com arquivamento permitido antes do início. `configured` e `in_progress` exigem regulamento publicado. Campeonato arquivado fica somente para consulta.

## Seed e limitações

O seed cria dados fictícios de temporada, categoria, Copa Brasil de Talentos e regulamento publicado, sem equipes ou partidas. Ele é idempotente e bloqueado em produção.

Este documento descreve a fundação de campeonatos e regulamentos. As etapas posteriores adicionaram equipes, atletas, inscrições, grupos, rodadas, partidas, disciplina, classificação, mata-mata, súmula digital, conteúdo e portal público.
