<section>
    <p class="eyebrow">Área administrativa</p>
    <h1><?= App\Core\View::e($title ?? 'Modulo') ?></h1>
    <p>Esta área está integrada ao painel. Use o menu lateral para acessar os dados disponíveis.</p>
    <p><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin')) ?>">Voltar ao painel</a></p>
</section>
