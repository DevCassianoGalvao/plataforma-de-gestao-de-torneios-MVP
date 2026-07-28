<?php
$primary=$theme['primary_color']??'#1261a0';
$secondary=$theme['secondary_color']??'#0f7b52';
$accent=$theme['accent_color']??'#f4b942';
$slug=View::e($tournament['slug']);
?>
<main class="public-site" style="--champ-primary:<?=View::e($primary)?>;--champ-secondary:<?=View::e($secondary)?>;--champ-accent:<?=View::e($accent)?>">
    <header class="public-nav"><a class="brand" href="/campeonatos/<?=$slug?>"><span class="brand-mark">TG</span><strong><?=View::e($tournament['name'])?></strong></a><nav><a href="/campeonatos/<?=$slug?>/jogos">Jogos</a><a href="/campeonatos/<?=$slug?>/classificacao">Classificação</a><a href="/campeonatos/<?=$slug?>/equipes">Equipes</a><a href="/campeonatos/<?=$slug?>/noticias">Notícias</a></nav><button class="icon-button" data-theme-toggle aria-label="Alternar tema">◐</button></header>
    <section class="public-hero"><div><span class="eyebrow">Temporada <?=View::e($tournament['season']??'')?></span><h1><?=View::e($tournament['name'])?></h1><p><?=View::e($tournament['description']??'Competição esportiva com informação oficial e organizada.')?></p></div><div class="hero-score"><span>Próximos jogos</span><strong><?=count($data['upcoming']??[])?></strong><small><?=empty($data['upcoming'])?'Nenhuma partida agendada':'Partidas cadastradas'?></small></div></section>
    <section class="public-grid">
        <article class="panel"><span class="eyebrow">Agenda</span><h2>Próximos jogos</h2><?php foreach(array_slice($data['upcoming']??[],0,3) as $match): ?><p><?=View::e($match['home_name'])?> x <?=View::e($match['away_name'])?></p><?php endforeach; ?><?php if(empty($data['upcoming'])): ?><div class="empty">Nenhuma partida publicada.</div><?php endif; ?><a class="link" href="/campeonatos/<?=$slug?>/jogos">Ver jogos</a></article>
        <article class="panel"><span class="eyebrow">Tabela</span><h2>Classificação</h2><?php foreach(array_slice($data['standings']??[],0,3) as $row): ?><p><?=$row['position']?>. <?=View::e($row['team_name'])?> <strong><?=$row['points']?> pts</strong></p><?php endforeach; ?><?php if(empty($data['standings'])): ?><div class="empty">Classificação sem partidas homologadas.</div><?php endif; ?><a class="link" href="/campeonatos/<?=$slug?>/classificacao">Ver classificação</a></article>
        <article class="panel"><span class="eyebrow">Participantes</span><h2>Equipes</h2><?php foreach(array_slice($data['teams']??[],0,3) as $team): ?><p><?=View::e($team['name'])?></p><?php endforeach; ?><?php if(empty($data['teams'])): ?><div class="empty">Nenhuma equipe publicada.</div><?php endif; ?><a class="link" href="/campeonatos/<?=$slug?>/equipes">Ver equipes</a></article>
        <article class="panel"><span class="eyebrow">Conteúdo</span><h2>Notícias</h2><?php foreach(array_slice($data['news']??[],0,2) as $post): ?><p><?=View::e($post['title'])?></p><?php endforeach; ?><?php if(empty($data['news'])): ?><div class="empty">Nenhuma notícia publicada.</div><?php endif; ?><a class="link" href="/campeonatos/<?=$slug?>/noticias">Ver notícias</a></article>
    </section>
    <footer>Informação pública do campeonato · <a href="/login">Área administrativa</a></footer>
</main>
