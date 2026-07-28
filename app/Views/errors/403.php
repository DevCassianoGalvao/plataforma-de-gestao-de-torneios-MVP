<section>
    <p class="eyebrow">403</p>
    <h1>Acesso negado</h1>
    <p><?= App\Core\View::e($message ?? 'Voce nao tem permissao para acessar este recurso.') ?></p>
    <p><a href="<?= App\Core\View::e(App\Core\Config::url('/admin')) ?>">Voltar ao painel</a></p>
</section>
