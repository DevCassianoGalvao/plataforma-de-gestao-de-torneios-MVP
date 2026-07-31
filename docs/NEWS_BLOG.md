# Notícias e Blog

## Escopo

A Etapa 13 entrega notícias editoriais por campeonato, com painel administrativo e portal público. O conteúdo e texto simples: toda exibição passa por escape HTML, sem aceitar HTML arbitrário no corpo.

## Dados

`news_articles` guarda título, slug por campeonato, resumo, conteúdo, capa, autor, campeonato, equipe/partida relacionada opcionais, destaque, status, data de publicação, timestamps e `deleted_at` para exclusão lógica.

Status suportados: `draft`, `scheduled`, `published`, `unpublished` e `archived`. Rascunhos, publicações futuras e notícias retiradas nunca aparecem no portal. Uma notícia `scheduled` passa a aparecer automaticamente quando `published_at` chega; publicar agora define a data atual.

## Painel

- `GET /admin/noticias`: lista filtrável por busca, status e campeonato;
- `GET /admin/noticias/novo` e `POST /admin/noticias`: criar;
- `GET /admin/noticias/{id}/editar` e `POST /admin/noticias/{id}`: editar;
- `GET /admin/noticias/{id}` e `/preview`: prévia administrativa;
- `POST /admin/noticias/{id}/publicar`: publicar agora;
- `POST /admin/noticias/{id}/despublicar`: retirar do portal;
- `POST /admin/noticias/{id}/excluir`: arquivar com exclusão lógica.

Todas as mutações exigem CSRF. Organizadores e comunicação somente enxergam campeonatos atribuídos; administrador enxerga todos. Treinador e operador recebem `403`.

## Portal

- `/campeonatos/{slug}/noticias`: lista paginada e busca;
- `/campeonatos/{slug}/noticias/recentes`: lista de notícias recentes;
- `/campeonatos/{slug}/noticias/{newsSlug}`: detalhe publicado;
- `/campeonatos/{slug}/noticias/{newsSlug}/capa`: capa publicada.

O portal exige campeonato público e notícia publicada/agendada com `published_at` no passado. Rascunhos, agendadas futuras, arquivadas e excluidas logicamente retornam `404`.

## Upload

`NewsImageService` usa o processador central com `finfo`, `getimagesize`, EXIF e GD. Aceita JPEG, PNG e WebP ate o limite configurado (12 MiB por padrão), bloqueia imagens acima de 12 MP, reamostra para no máximo 1600x1000 e grava WebP com nome aleatório fora de `public`. A rota pública só le o caminho associado a uma notícia publicada.

## Seed e testes

`NewsSeed` cria uma notícia publicada, uma agendada e um rascunho, atribui comunicação ao campeonato e pode ser executado repetidamente sem duplicar registros. A cobertura verifica CRUD, slug, estados, publicação, agendamento, capa otimizada, XSS, CSRF, escopo, IDOR, privacidade do portal, busca, página e exclusão lógica.

Assinatura digital, editor rico e notificações ficam fora desta etapa.
