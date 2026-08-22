<?php
$v = $editing ?? [];
$get = static fn (string $key, mixed $default = ''): mixed => $v[$key] ?? $default;
$checked = static fn (string $key, string $default = ''): string => (string) $get($key, $default) === '1' ? 'checked' : '';
?>
<input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
<label>Nome<input name="name" maxlength="180" required value="<?= $e($get('name')) ?>"></label>
<label>Descrição<textarea name="description" maxlength="500"><?= $e($get('description')) ?></textarea></label>
<label>Momento
    <select name="expected_moment">
        <?php foreach ([
            'before_match' => 'Antes da partida',
            'during_match' => 'Durante a partida',
            'after_match' => 'Depois da partida',
            'final_documentation' => 'Documentação final',
            'event_day' => 'Dia do evento',
        ] as $key => $label): ?>
            <option value="<?= $e($key) ?>" <?= $get('expected_moment', 'after_match') === $key ? 'selected' : '' ?>><?= $e($label) ?></option>
        <?php endforeach; ?>
    </select>
</label>
<label>Ordem<input type="number" min="1" name="display_order" value="<?= $e($get('display_order', 1)) ?>"></label>
<label>Formatos aceitos<input name="allowed_mime_types" value="<?= $e($get('allowed_mime_types', 'image/jpeg,image/png,image/webp,application/pdf')) ?>"><small>Separe os tipos MIME por vírgula.</small></label>
<label>Mínimo de arquivos<input type="number" min="1" name="min_files" value="<?= $e($get('min_files', 1)) ?>"></label>
<label>Máximo de arquivos<input type="number" min="1" name="max_files" value="<?= $e($get('max_files', 1)) ?>"></label>
<label>Tamanho máximo em bytes<input type="number" min="1024" name="max_file_size_bytes" value="<?= $e($get('max_file_size_bytes', 10485760)) ?>"></label>
<label><input type="checkbox" name="is_required" value="1" <?= $checked('is_required') ?>> Obrigatório</label>
<label><input type="checkbox" name="is_active" value="1" <?= $checked('is_active', '1') ?>> Ativo</label>
<label><input type="checkbox" name="notes_required" value="1" <?= $checked('notes_required') ?>> Exigir observação</label>
<label><input type="checkbox" name="blocks_operation_start" value="1" <?= $checked('blocks_operation_start') ?>> Bloqueia início</label>
<label><input type="checkbox" name="blocks_approval_submission" value="1" <?= $checked('blocks_approval_submission') ?>> Bloqueia envio para aprovação</label>
<label><input type="checkbox" name="blocks_document_completion" value="1" <?= $checked('blocks_document_completion') ?>> Bloqueia conclusão documental</label>
<label><input type="checkbox" name="show_in_accountability" value="1" <?= $checked('show_in_accountability', '1') ?>> Exibir na prestação de contas</label>
<label>Escopo
    <select name="scope">
        <option value="match" <?= $get('scope', 'match') === 'match' ? 'selected' : '' ?>>Por partida</option>
        <option value="event_day" <?= $get('scope', 'match') === 'event_day' ? 'selected' : '' ?>>Por dia de evento</option>
    </select>
    <small>Escolha onde esta evidência será registrada.</small>
</label>
