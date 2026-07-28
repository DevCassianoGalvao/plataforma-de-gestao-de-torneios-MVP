<?php
use App\Support\Env;
use App\Support\View;

$base = rtrim((string) Env::get('APP_BASE_PATH', ''), '/');
$titleMap = [
    'equipes' => 'Equipes',
    'atletas' => 'Atletas',
    'comissao' => 'Comissao tecnica',
    'responsaveis' => 'Responsaveis legais',
    'inscricoes' => 'Inscricoes',
    'documentos' => 'Documentos',
    'configuracoes' => 'Campeonato e regulamento',
];
$labels = $titleMap[$module] ?? ucfirst($module);
$action = $base.'/admin/campeonatos/'.$tournament['slug'].'/'.$module.'/salvar';
?>
<div class="admin-layout">
  <button class="drawer-overlay" type="button" data-drawer-close="#admin-navigation" aria-label="Fechar navegacao"></button>
  <aside id="admin-navigation" class="sidebar" data-drawer>
    <div class="brand-mark">TG</div>
    <strong>Gestao de Torneios</strong>
    <div class="sidebar-context">
      <strong><?= View::e($tournament['name']) ?></strong>
      <small><?= View::e($tournament['project_name']) ?></small>
      <small><?= View::e($tournament['category_name'] ?: 'Categoria nao definida') ?></small>
    </div>
    <nav aria-label="Navegacao administrativa">
      <?php foreach ($navigation as $item): ?>
        <a class="<?= $item['key'] === $module ? 'active' : '' ?>" href="<?= View::e($base.$item['href']) ?>"><?= View::e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="logout" href="<?= View::e($base.'/logout') ?>">Sair</a>
  </aside>

  <main id="conteudo" class="content dashboard-page">
    <header class="topbar">
      <button class="button ghost admin-nav-toggle" type="button" data-drawer-toggle="#admin-navigation" aria-controls="admin-navigation" aria-expanded="false">Menu</button>
      <div>
        <nav class="breadcrumbs" aria-label="Caminho"><a href="<?= View::e($base.'/admin/campeonatos/'.$tournament['slug']) ?>">Campeonato</a><span aria-hidden="true">/</span><span aria-current="page"><?= View::e($labels) ?></span></nav>
        <span class="eyebrow">Cadastro assistido</span>
        <h1><?= View::e($labels) ?></h1>
        <p class="muted">Dados vinculados a <?= View::e($tournament['name']) ?>. Relacoes e permissoes sao validadas no servidor.</p>
      </div>
      <button class="icon-button" data-theme-toggle aria-label="Alternar tema" aria-pressed="false">Tema</button>
    </header>

    <?php if ($module === 'equipes'): ?>
      <section class="panel"><h2>Nova equipe</h2>
        <form method="post" action="<?= View::e($action) ?>" class="form-grid">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
          <label>Nome<input required name="name" autocomplete="organization"></label>
          <label>Nome curto<input name="short_name"></label>
          <label>Sigla<input name="acronym" maxlength="10"></label>
          <label>Categoria<select name="category_id"><option value="">Sem categoria</option><?php foreach ($data['categories'] as $category): ?><option value="<?= (int) $category['id'] ?>"><?= View::e($category['name']) ?></option><?php endforeach; ?></select></label>
          <label>Cidade<input name="city"></label>
          <label>Responsavel<input name="contact_name"></label>
          <label>Telefone<input name="contact_phone" inputmode="tel"></label>
          <label>E-mail<input name="contact_email" type="email"></label>
          <label>Cor principal<input name="primary_color" type="color" value="#1672b8"></label>
          <label>Cor secundaria<input name="secondary_color" type="color" value="#0b8668"></label>
          <button class="button" type="submit">Cadastrar equipe</button>
        </form>
      </section>
      <section class="panel"><h2>Equipes participantes</h2><div class="table-wrap"><table><thead><tr><th>Equipe</th><th>Categoria</th><th>Situacao</th><th>Acao</th></tr></thead><tbody>
        <?php foreach ($data['teams'] as $team): ?><tr><td><?= View::e($team['name']) ?><small><?= View::e((string) $team['short_name']) ?></small></td><td><?= View::e((string) $team['category_name']) ?></td><td><?= View::e($team['status']) ?></td><td><form method="post" action="<?= View::e($action) ?>"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>"><input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>"><select name="team_action" aria-label="Acao para <?= View::e($team['name']) ?>"><option value="activate">Ativar</option><option value="deactivate">Inativar</option><option value="delete">Excluir</option><option value="restore">Restaurar</option></select><button class="button ghost" type="submit">Aplicar</button></form></td></tr><?php endforeach; ?>
      </tbody></table></div></section>
    <?php elseif ($module === 'atletas'): ?>
      <section class="panel"><h2>Novo atleta</h2><form method="post" action="<?= View::e($action) ?>" class="form-grid"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
        <label>Nome completo<input required name="full_name" autocomplete="name"></label><label>Nome esportivo<input name="public_name"></label><label>Data de nascimento<input name="birth_date" type="date"></label>
        <label>Equipe<select required name="team_id"><option value="">Selecione</option><?php foreach ($data['teams'] as $team): ?><option value="<?= (int) $team['id'] ?>"><?= View::e($team['name']) ?></option><?php endforeach; ?></select></label>
        <label>Posicao<select name="position"><option value="">Nao informada</option><option>Goleiro</option><option>Defensor</option><option>Meio-campista</option><option>Atacante</option></select></label><label>Numero<input name="preferred_number" type="number" min="1" max="99"></label><label>Pe dominante<select name="dominant_foot"><option value="">Nao informado</option><option>Direito</option><option>Esquerdo</option><option>Ambidestro</option></select></label><label>Telefone<input name="phone" inputmode="tel"></label><label>E-mail<input name="email" type="email"></label><button class="button" type="submit">Cadastrar atleta</button>
      </form></section>
      <section class="panel"><h2>Atletas no campeonato</h2><div class="table-wrap"><table><thead><tr><th>Atleta</th><th>Equipe</th><th>Posicao</th><th>Numero</th><th>Situacao</th></tr></thead><tbody><?php foreach ($data['athletes'] as $person): ?><tr><td><?= View::e($person['public_name'] ?: $person['full_name']) ?></td><td><?= View::e($person['team_name']) ?></td><td><?= View::e((string) $person['primary_position']) ?></td><td><?= View::e((string) $person['preferred_number']) ?></td><td><?= View::e($person['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php elseif ($module === 'comissao'): ?>
      <section class="panel"><h2>Novo integrante</h2><form method="post" action="<?= View::e($action) ?>" class="form-grid"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>"><label>Nome completo<input required name="full_name"></label><label>Equipe<select required name="team_id"><option value="">Selecione</option><?php foreach ($data['teams'] as $team): ?><option value="<?= (int) $team['id'] ?>"><?= View::e($team['name']) ?></option><?php endforeach; ?></select></label><label>Funcao<select required name="role"><option value="">Selecione</option><option>Treinador</option><option>Auxiliar</option><option>Preparador fisico</option><option>Fisioterapeuta</option><option>Medico</option><option>Massagista</option><option>Dirigente</option><option>Responsavel da equipe</option><option>Outro</option></select></label><label>Telefone<input name="phone"></label><label>E-mail<input name="email" type="email"></label><button class="button" type="submit">Cadastrar integrante</button></form></section>
      <section class="panel"><h2>Comissao cadastrada</h2><div class="table-wrap"><table><thead><tr><th>Nome</th><th>Equipe</th><th>Funcao</th></tr></thead><tbody><?php foreach ($data['staff'] as $staff): ?><tr><td><?= View::e($staff['full_name']) ?></td><td><?= View::e($staff['team_name']) ?></td><td><?= View::e($staff['role']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php elseif ($module === 'responsaveis'): ?>
      <section class="panel"><h2>Vincular responsavel legal</h2><form method="post" action="<?= View::e($action) ?>" class="form-grid"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>"><label>Atleta<select required name="person_id"><option value="">Selecione</option><?php foreach ($data['athletes'] as $person): ?><option value="<?= (int) $person['id'] ?>"><?= View::e($person['full_name'].' - '.$person['team_name']) ?></option><?php endforeach; ?></select></label><label>Nome<input required name="full_name"></label><label>Parentesco<input required name="relationship_type"></label><label>Documento<input name="document_number"></label><label>Telefone<input name="phone"></label><label>E-mail<input name="email" type="email"></label><label><input name="authorized" type="checkbox" value="1"> Autoriza a inscricao</label><label><input required name="accepted" type="checkbox" value="1"> Termo aceito</label><button class="button" type="submit">Vincular responsavel</button></form></section>
      <section class="panel"><h2>Responsaveis vinculados</h2><div class="table-wrap"><table><thead><tr><th>Atleta</th><th>Responsavel</th><th>Parentesco</th><th>Contato</th></tr></thead><tbody><?php foreach ($data['guardians'] as $guardian): ?><tr><td><?= View::e($guardian['athlete_name']) ?></td><td><?= View::e($guardian['full_name']) ?></td><td><?= View::e($guardian['relationship_type']) ?></td><td><?= View::e((string) $guardian['phone']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php elseif ($module === 'inscricoes'): ?>
      <section class="panel"><h2>Nova inscricao</h2><form method="post" action="<?= View::e($action) ?>" class="form-grid"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>"><input type="hidden" name="action" value="create"><label>Atleta<select required name="person_id"><option value="">Selecione</option><?php foreach ($data['athletes'] as $person): ?><option value="<?= (int) $person['id'] ?>"><?= View::e($person['full_name'].' - '.$person['team_name']) ?></option><?php endforeach; ?></select></label><label>Equipe<select required name="team_id"><option value="">Selecione</option><?php foreach ($data['teams'] as $team): ?><option value="<?= (int) $team['id'] ?>"><?= View::e($team['name']) ?></option><?php endforeach; ?></select></label><label>Numero da camisa<input name="shirt_number" type="number" min="1" max="99"></label><button class="button" type="submit">Enviar para analise</button></form></section>
      <section class="panel"><h2>Central de inscricoes</h2><div class="table-wrap"><table><thead><tr><th>Atleta</th><th>Equipe</th><th>Status</th><th>Analise</th></tr></thead><tbody><?php foreach ($data['registrations'] as $registration): ?><tr><td><?= View::e($registration['full_name']) ?></td><td><?= View::e($registration['team_name']) ?></td><td><?= View::e($registration['status']) ?></td><td><form method="post" action="<?= View::e($action) ?>"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>"><input type="hidden" name="registration_id" value="<?= (int) $registration['id'] ?>"><select name="action"><option value="submitted">Enviar</option><option value="in_review">Em analise</option><option value="pending">Solicitar correcao</option><option value="approved">Aprovar</option><option value="rejected">Rejeitar</option><option value="suspended">Suspender</option><option value="cancelled">Cancelar</option></select><label class="sr-only">Motivo<input name="reason"></label><button class="button ghost" type="submit">Atualizar</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php elseif ($module === 'documentos'): ?>
      <section class="panel"><h2>Enviar documento</h2><form method="post" action="<?= View::e($action) ?>" enctype="multipart/form-data" class="form-grid"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>"><label>Titulo<input required name="title"></label><label>Tipo<select name="document_category"><option value="anexo">Anexo</option><option value="regulamento">Regulamento</option><option value="comunicado">Comunicado</option><option value="decisao">Decisao</option><option value="relatorio">Relatorio</option></select></label><label>Visibilidade<select name="visibility"><option value="private">Privado</option><option value="public">Publico</option></select></label><label>Validade<input name="expires_at" type="date"></label><label>Arquivo<input required name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></label><button class="button" type="submit">Enviar arquivo</button></form></section>
      <section class="panel"><h2>Documentos do campeonato</h2><div class="table-wrap"><table><thead><tr><th>Titulo</th><th>Tipo</th><th>Visibilidade</th><th>Status</th><th>Validade</th></tr></thead><tbody><?php foreach ($data['documents'] as $document): ?><tr><td><?= View::e($document['title']) ?></td><td><?= View::e($document['document_category']) ?></td><td><?= View::e($document['visibility']) ?></td><td><?= View::e($document['status']) ?></td><td><?= View::e((string) $document['expires_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php elseif ($module === 'configuracoes'): ?>
      <section class="panel"><h2>Regulamento estruturado</h2><p class="muted">A versao ativa e preservada. Alteracoes apos o inicio exigem justificativa e autorizacao.</p><form method="post" action="<?= View::e($action) ?>" class="form-grid"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>"><label>Quantidade de grupos<input name="groups_count" type="number" min="1" value="<?= (int) ($rules['format']['groups_count'] ?? 2) ?>"></label><label>Pontos por vitoria<input name="points_win" type="number" min="0" value="<?= (int) ($rules['points']['win'] ?? 3) ?>"></label><label>Pontos por empate<input name="points_draw" type="number" min="0" value="<?= (int) ($rules['points']['draw'] ?? 1) ?>"></label><label>Amarelos para suspensao<input name="yellow_limit" type="number" min="1" value="<?= (int) ($rules['discipline']['yellow_limit'] ?? 3) ?>"></label><label>Substituicoes<input name="substitutions" type="number" min="0" value="<?= (int) ($rules['substitutions']['max_used'] ?? 5) ?>"></label><label><input name="penalties" type="checkbox" value="1" <?= !empty($rules['knockout']['penalties']) ? 'checked' : '' ?>> Disputa por penaltis</label><label>Justificativa<textarea required name="reason"></textarea></label><label><input name="authorize_after_start" type="checkbox" value="1"> Autorizar versao apos inicio</label><button class="button" type="submit">Criar nova versao</button></form><form method="post" action="<?= View::e($action) ?>"><input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>"><input type="hidden" name="preset" value="copa-brasil"><input type="hidden" name="reason" value="Aplicacao do preset Copa Brasil de Talentos"><button class="button ghost" type="submit">Aplicar preset Copa Brasil de Talentos</button></form></section>
    <?php endif; ?>
  </main>
</div>
