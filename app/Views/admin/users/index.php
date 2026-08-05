<section>
    <div class="section-heading"><div><p class="eyebrow">Acesso administrativo</p><h1>Usuários</h1></div><a class="button" href="<?= App\Core\View::e(App\Core\Config::url('/admin/usuarios/novo')) ?>">Novo usuario</a></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= App\Core\View::e($message) ?></p><?php endif; ?>
    <form class="search-form" method="get" action="<?= App\Core\View::e(App\Core\Config::url('/admin/usuarios')) ?>">
        <label>Buscar por nome ou e-mail <input type="search" name="q" value="<?= App\Core\View::e($query ?? '') ?>"></label>
        <button type="submit">Buscar</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Status</th><th>Perfis</th><th>Ultimo acesso</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($usersList as $record): ?>
        <tr>
            <td><?= App\Core\View::e($record['name']) ?></td>
            <td><?= App\Core\View::e($record['email']) ?></td>
            <td><span class="status status-<?= App\Core\View::e($record['status']) ?>"><?= App\Core\View::e($record['status']) ?></span></td>
            <td><?= App\Core\View::e(implode(', ', array_map(static fn (array $role): string => $role['name'], $record['roles']))) ?></td>
            <td><?= App\Core\View::e($record['last_login_at'] ?: 'Ainda não acessou') ?></td>
            <td class="actions"><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/usuarios/' . $record['id'] . '/editar')) ?>">Editar</a>
                <form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/usuarios/' . $record['id'] . '/status')) ?>"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><input type="hidden" name="status" value="<?= $record['status'] === 'active' ? 'inactive' : 'active' ?>"><button type="submit"><?= $record['status'] === 'active' ? 'Inativar' : 'Ativar' ?></button></form>
                <?php if (!empty($canResetPassword)): ?><form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/usuarios/' . $record['id'] . '/reset-password')) ?>" onsubmit="return confirm('Gerar uma nova senha temporaria para este usuario?');"><input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>"><button type="submit">Gerar nova senha</button></form><?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($usersList === []): ?><tr><td colspan="6">Nenhum usuario encontrado.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
