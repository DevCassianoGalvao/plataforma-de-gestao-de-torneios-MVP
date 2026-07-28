<section>
    <p class="eyebrow">Painel administrativo</p>
    <h1>Ola, <?= App\Core\View::e($user['name']) ?></h1>
    <p>A fundacao de acesso esta pronta. Os modulos esportivos serao adicionados nas proximas etapas.</p>
    <div class="grid-links">
        <a class="link-card" href="<?= App\Core\View::e(App\Core\Config::url('/admin/usuarios')) ?>"><strong>Usuarios</strong><span>Gerenciar acessos e perfis</span></a>
        <a class="link-card" href="<?= App\Core\View::e(App\Core\Config::url('/admin/auditoria')) ?>"><strong>Auditoria</strong><span>Consultar eventos importantes</span></a>
        <a class="link-card" href="<?= App\Core\View::e(App\Core\Config::url('/admin/perfil')) ?>"><strong>Meu perfil</strong><span>Dados e senha</span></a>
    </div>
</section>
