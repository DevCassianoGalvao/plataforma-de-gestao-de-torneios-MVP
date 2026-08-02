<?php $e = static fn (mixed $value): string => App\Core\View::e($value); ?>
<section class="page-stack">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Acesso controlado</p>
            <h1>Prestação de contas</h1>
            <p class="muted"><?= $e($championship['name']) ?> · vincule quem poderá consultar e exportar os dados deste campeonato.</p>
        </div>
        <a class="button secondary" href="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'])) ?>">Voltar ao campeonato</a>
    </div>
    <?php foreach ($errors as $error): ?><p class="alert" role="alert"><?= $e($error) ?></p><?php endforeach; ?>
    <?php if ($message): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>
    <article class="panel">
        <h2>Vincular responsável</h2>
        <p class="muted">O usuário precisa estar ativo e possuir o perfil Prestação de Contas.</p>
        <form method="post" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/prestacao')) ?>" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>">
            <label>Usuário
                <select name="user_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($candidates as $candidate): ?><option value="<?= (int) $candidate['id'] ?>"><?= $e($candidate['name']) ?> · <?= $e($candidate['email']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Vincular acesso</button>
        </form>
        <?php if (!$candidates): ?><p class="muted">Nenhum usuário ativo com esse perfil. Crie o usuário em Usuários antes.</p><?php endif; ?>
    </article>
    <article class="panel">
        <h2>Usuários vinculados</h2>
        <?php if (!$assignments): ?><p class="muted">Nenhum usuário vinculado a este campeonato.</p><?php else: ?>
            <div class="table-wrap"><table><thead><tr><th>Usuário</th><th>Status da conta</th><th>Vinculado em</th><th>Ação</th></tr></thead><tbody>
                <?php foreach ($assignments as $assignment): ?><tr><td><strong><?= $e($assignment['name']) ?></strong><br><small><?= $e($assignment['email']) ?></small></td><td><?= $e($assignment['user_status']) ?></td><td><?= $e($assignment['created_at']) ?></td><td><form method="post" action="<?= $e(App\Core\Config::url('/admin/campeonatos/' . $championship['slug'] . '/prestacao/' . $assignment['user_id'] . '/encerrar')) ?>"><input type="hidden" name="_csrf" value="<?= $e(App\Core\Security::csrfToken()) ?>"><button class="button secondary" type="submit">Encerrar acesso</button></form></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </article>
</section>
