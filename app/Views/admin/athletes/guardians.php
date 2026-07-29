<section>
    <div class="section-heading"><div><p class="eyebrow">Atleta</p><h1>Responsaveis legais</h1><p><?= App\Core\View::e($athlete['full_name']) ?></p></div><a href="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $athlete['id'])) ?>">Voltar ao atleta</a></div>
    <?php foreach (($errors ?? []) as $error): ?><p class="alert" role="alert"><?= App\Core\View::e($error) ?></p><?php endforeach; ?>
    <?php if ($items === []): ?><p>Nenhum responsavel vinculado.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Nome</th><th>Parentesco</th><th>Telefone</th><th>E-mail</th><th>Documento</th><th>Autorizacao</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><?= App\Core\View::e($item['full_name']) ?></td><td><?= App\Core\View::e($item['relationship']) ?></td><td><?= App\Core\View::e($item['phone']) ?></td><td><?= App\Core\View::e($item['email']) ?></td><td><?= App\Core\View::e($item['document_display']) ?></td><td><?= App\Core\View::e($item['authorization_status']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php if ($canManage): ?>
<section><h2>Vincular responsavel</h2><form method="post" action="<?= App\Core\View::e(App\Core\Config::url('/admin/atletas/' . $athlete['id'] . '/responsaveis')) ?>">
    <input type="hidden" name="_csrf" value="<?= App\Core\View::e(App\Core\Security::csrfToken()) ?>">
    <label>Nome completo <input name="guardian_full_name" required></label><label>Parentesco <input name="guardian_relationship" required></label><label>Telefone <input name="guardian_phone" required></label><label>E-mail <input type="email" name="guardian_email"></label><label>Documento protegido <input name="guardian_document" required autocomplete="off"></label><label>Observação da autorizacao <textarea name="guardian_authorization_note" rows="3"></textarea></label><button type="submit">Salvar responsavel</button>
</form></section>
<?php endif; ?>
