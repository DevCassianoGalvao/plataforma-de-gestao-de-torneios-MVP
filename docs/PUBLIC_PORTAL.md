# Portal publico por campeonato

## Objetivo

A Etapa 15 entrega um portal publico isolado por slug de campeonato. O portal consulta dados esportivos publicados nas tabelas existentes e nao cria um segundo cadastro esportivo. O redesign visual definitivo permanece reservado para a Etapa 17.

## Rotas

Todas as rotas abaixo aceitam `APP_BASE_PATH`, por exemplo `/torneio-online`:

- `/campeonatos/{slug}`: home;
- `/proximos-jogos`, `/resultados` e `/partidas/{id}`: agenda, resultados e detalhe;
- `/classificacao`, `/grupos` e `/mata-mata`: competicao;
- `/equipes`, `/equipes/{teamSlug}` e `/atletas`, `/atletas/{id}`: equipes e elenco aprovado;
- `/artilharia`, `/assistencias` e `/cartoes`: rankings;
- `/noticias`, `/noticias/{newsSlug}` e `/vai-e-vem`: conteudo publicado;
- `/regulamento` e `/campeao`: regulamento publicado e resultado final;
- `/campeonatos/{slug}/assets/{asset}`: logo, banner, favicon e imagem social;
- `/sitemap.xml` e `/robots.txt`: SEO tecnico global.

Noticias e Vai e Vem mantem suas rotas publicas especificas e usam o mesmo layout, identidade e metadados SEO do portal.

## Read model e privacidade

`PublicPortalRepository` seleciona explicitamente campos publicos. O portal nunca consulta para as views:

- documentos, CPF ou documento de responsavel;
- telefone, e-mail ou endereco;
- responsavel legal e observacoes privadas;
- arquivos de documentos e demais caminhos privados.

Fotos de atletas, escudos e identidade do campeonato passam por validacao de pertencimento ao campeonato antes de serem lidos pelo `StorageService`. Registros publicados de noticias e transferencias continuam filtrados por status, data de publicacao, exclusao logica e visibilidade do campeonato.

## Dados exibidos

A home agrega fase atual, proximos confrontos, resultados homologados, classificacao, chave, artilharia, assistencias, noticias publicadas e Vai e Vem publicado. Placar publico usa eventos validos, exclui penaltis do tempo normal e respeita resultado administrativo quando existente.

O detalhe de partida exibe apenas escalações confirmadas, nomes esportivos, numeros, eventos esportivos publicos e arbitragem por nome e funcao. O elenco publico usa atletas ativos e aprovados no campeonato.

## Identidade e SEO

Cada pagina usa nome, slug, cores, logo, favicon, banner, imagem social e tema basico cadastrados em `championships`. O layout publica `title`, `description`, `canonical`, Open Graph, Twitter card e favicon. O sitemap lista somente campeonatos publicos e nao rascunhos; `robots.txt` aponta para ele. Rotas inexistentes retornam a view 404 com status HTTP 404.

## Responsividade

O CSS do portal possui grades fluidas, tabelas com rolagem horizontal e ajustes para 320px, 600px e 900px. A Etapa 15 garante funcionamento estrutural; a linguagem visual definitiva, acessibilidade aprofundada e temas refinados ficam para a Etapa 17.

## Validacao

Os testes da Etapa 15 cobrem read model, isolamento por campeonato, ausencia de campos privados, rotas com base path, detalhe de partida/equipe/atleta, noticias, transferencias, SEO, sitemap, robots e 404. A suite usa banco descartavel e os seeds existentes permanecem idempotentes.
