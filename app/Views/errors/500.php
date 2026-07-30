<section>
    <h1>Erro interno</h1>
    <p><?= App\Core\View::e($message ?? 'Tente novamente mais tarde.') ?></p>
    <?php if (!empty($databaseUpdate)): ?><p>Um administrador deve concluir a atualizacao tecnica antes de continuar.</p><?php endif; ?>
    <?php if (!empty($reference)): ?><p><small>Codigo de suporte: <?= App\Core\View::e($reference) ?></small></p><?php endif; ?>
</section>
