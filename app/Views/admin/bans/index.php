<?php
$e = static fn (mixed $value): string => App\Core\View::e((string) $value);
$csrf = $e(App\Core\Security::csrfToken());
$groups = [];
foreach ($candidates as $candidate) $groups[$candidate['championship_name'] . ' · ' . $candidate['team_name']][] = $candidate;
?>
<section>
    <div class="section-heading"><div><p class="eyebrow">Disciplina</p><h1>Banidos</h1><p>Jogador banido some do portal público, das listas de inscritos, do elenco das súmulas novas e da seleção do operador. O histórico das partidas já jogadas é mantido na prestação de contas.</p></div></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= $e($message) ?></p><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="error" role="alert"><?php foreach ($errors as $error): ?><p><?= $e($error) ?></p><?php endforeach; ?></div><?php endif; ?>

    <article class="panel">
        <h2>Banir jogador</h2>
        <?php if ($candidates === []): ?>
            <p class="muted">Nenhum campeonato com regulamento publicado que permita banimento. Habilite "Permitir banimento de jogadores" nas regras avançadas do regulamento.</p>
        <?php else: ?>
            <form method="post" action="<?= $e(App\Core\Config::url('/admin/banidos')) ?>" class="form-grid">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <label class="full-width">Jogador
                    <select name="registration" required>
                        <option value="">Selecione</option>
                        <?php foreach ($groups as $label => $items): ?>
                            <optgroup label="<?= $e($label) ?>">
                                <?php foreach ($items as $item): ?><option value="<?= (int) $item['athlete_id'] ?>:<?= (int) $item['championship_id'] ?>"><?= $e($item['full_name']) ?></option><?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Data do banimento <input type="date" name="banned_on" value="<?= $e(date('Y-m-d')) ?>" required></label>
                <label class="full-width">Motivo <textarea name="reason" maxlength="500" required placeholder="Ex.: conduta violenta (briga) na partida Bragantino FC x Santiago FC."></textarea></label>
                <button type="submit" class="button-danger">Banir jogador</button>
            </form>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Jogadores banidos (<?= count($banned) ?>)</h2>
        <?php if ($banned === []): ?>
            <p class="muted">Nenhum jogador banido.</p>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Jogador</th><th>Equipe</th><th>Data</th><th>Motivo</th><th>Banido por</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($banned as $row): ?>
                    <tr>
                        <td><strong><?= $e($row['full_name']) ?></strong><?php if ($row['championship_name']): ?><br><small><?= $e($row['championship_name']) ?></small><?php endif; ?></td>
                        <td><?= $e($row['team_names'] ?: '-') ?></td>
                        <td><?= $e(date('d/m/Y', strtotime((string) $row['banned_at']))) ?></td>
                        <td><?= nl2br($e($row['banned_reason'])) ?></td>
                        <td><?= $e($row['banned_by_name'] ?: '-') ?></td>
                        <td><form method="post" action="<?= $e(App\Core\Config::url('/admin/banidos/' . (int) $row['id'] . '/desfazer')) ?>"><input type="hidden" name="_csrf" value="<?= $csrf ?>"><label class="check"><input type="checkbox" name="confirm" value="1" required> Confirmo</label><button type="submit">Desfazer banimento</button></form></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </article>
</section>
