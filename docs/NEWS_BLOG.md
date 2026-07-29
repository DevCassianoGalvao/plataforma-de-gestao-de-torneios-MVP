# Noticias e Blog

## Escopo

A Etapa 13 entrega noticias editoriais por campeonato, com painel administrativo e portal publico. O conteudo e texto simples: toda exibicao passa por escape HTML, sem aceitar HTML arbitrario no corpo.

## Dados

`news_articles` guarda titulo, slug por campeonato, resumo, conteudo, capa, autor, campeonato, equipe/partida relacionada opcionais, destaque, status, data de publicacao, timestamps e `deleted_at` para exclusao logica.

Status suportados: `draft`, `scheduled`, `published`, `unpublished` e `archived`. Rascunhos, publicacoes futuras e noticias retiradas nunca aparecem no portal. Uma noticia `scheduled` passa a aparecer automaticamente quando `published_at` chega; publicar agora define a data atual.

## Painel

- `GET /admin/noticias`: lista filtravel por busca, status e campeonato;
- `GET /admin/noticias/novo` e `POST /admin/noticias`: criar;
- `GET /admin/noticias/{id}/editar` e `POST /admin/noticias/{id}`: editar;
- `GET /admin/noticias/{id}` e `/preview`: previa administrativa;
- `POST /admin/noticias/{id}/publicar`: publicar agora;
- `POST /admin/noticias/{id}/despublicar`: retirar do portal;
- `POST /admin/noticias/{id}/excluir`: arquivar com exclusao logica.

Todas as mutacoes exigem CSRF. Organizadores e comunicacao somente enxergam campeonatos atribuidos; administrador enxerga todos. Treinador e operador recebem `403`.

## Portal

- `/campeonatos/{slug}/noticias`: lista paginada e busca;
- `/campeonatos/{slug}/noticias/recentes`: lista de noticias recentes;
- `/campeonatos/{slug}/noticias/{newsSlug}`: detalhe publicado;
- `/campeonatos/{slug}/noticias/{newsSlug}/capa`: capa publicada.

O portal exige campeonato publico e noticia publicada/agendada com `published_at` no passado. Rascunhos, agendadas futuras, arquivadas e excluidas logicamente retornam `404`.

## Upload

`NewsImageService` usa `finfo`, `getimagesize` e GD. Aceita JPEG, PNG e WebP ate 5 MB, bloqueia dimensoes acima de 6000x6000, reamostra para no maximo 1600x1000 e grava JPEG com nome aleatorio fora de `public`. A rota publica so le o caminho associado a uma noticia publicada.

## Seed e testes

`NewsSeed` cria uma noticia publicada, uma agendada e um rascunho, atribui comunicacao ao campeonato e pode ser executado repetidamente sem duplicar registros. A cobertura verifica CRUD, slug, estados, publicacao, agendamento, capa otimizada, XSS, CSRF, escopo, IDOR, privacidade do portal, busca, pagina e exclusao logica.

Assinatura digital, editor rico e notificacoes ficam fora desta etapa.
