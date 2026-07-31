<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TransferRepository;

final class TransferService
{
    public function __construct(private readonly TransferRepository $transfers, private readonly AuditService $audit, private readonly TransferAccessService $access) {}

    public function save(array $user, array $input, ?int $id = null): array
    {
        $record = $id ? $this->transfers->find($id) : null; $errors = []; $championshipId = (int) ($input['championship_id'] ?? ($record['championship_id'] ?? 0)); $athleteId = (int) ($input['athlete_id'] ?? ($record['athlete_id'] ?? 0)); $previous = $this->nullableInt($input['previous_team_id'] ?? ($record['previous_team_id'] ?? null)); $new = $this->nullableInt($input['new_team_id'] ?? ($record['new_team_id'] ?? null)); $type = trim((string) ($input['type'] ?? ($record['type'] ?? ''))); $date = trim((string) ($input['movement_date'] ?? ($record['movement_date'] ?? ''))); $status = trim((string) ($input['status'] ?? ($record['status'] ?? 'draft')));
        if (!$this->access->canManageChampionship($user, $championshipId)) {
            if ($type !== 'transferencia') $errors[] = 'Solicitacoes de treinadores aceitam apenas o tipo transferencia.';
            if ($previous === null || !$this->transfers->isOwnTeam($previous, (int) $user['id'])) $errors[] = 'A equipe de origem deve ser uma equipe sob sua gestao.';
            if (!in_array($status, ['draft', 'pending'], true)) $errors[] = 'Solicitacoes aceitam apenas rascunho ou pendente.';
            if ($record && (int) $record['author_id'] !== (int) $user['id']) $errors[] = 'Voce so pode editar suas proprias solicitacoes.';
        }
        if ($championshipId <= 0) $errors[] = 'Selecione um campeonato.'; if (!$this->transfers->athleteInChampionship($athleteId, $championshipId)) $errors[] = 'Atleta nao pertence ao campeonato.'; if ($previous && !$this->transfers->teamInChampionship($previous, $championshipId)) $errors[] = 'Equipe anterior fora do campeonato.'; if ($new && !$this->transfers->teamInChampionship($new, $championshipId)) $errors[] = 'Nova equipe fora do campeonato.'; if ($previous && $new && $previous === $new && $type !== 'renovacao') $errors[] = 'Equipes iguais so sao permitidas em renovacao.'; if (!TransferRules::validType($type)) $errors[] = 'Tipo de movimentacao invalido.'; if ($type === 'saida' && (!$previous || $new)) $errors[] = 'Saida exige equipe anterior e nao possui nova equipe.'; if ($type !== 'saida' && !$new) $errors[] = 'O tipo informado exige nova equipe.'; if (!$this->validDate($date)) $errors[] = 'Informe uma data valida.'; if (!in_array($status, ['draft', 'pending'], true)) $errors[] = 'Salvamento aceita apenas rascunho ou pendente.'; if ($record && !in_array($record['status'], ['draft', 'pending'], true)) $errors[] = 'Movimentacao aprovada, publicada ou cancelada nao pode ser editada.';
        $window = $this->transfers->transferWindow($championshipId); if ($window && (($window['window_starts_at'] && $date < $window['window_starts_at']) || ($window['window_ends_at'] && $date > $window['window_ends_at']))) $errors[] = 'A data esta fora da janela de movimentacoes.'; if ($window && $window['maximum_movements'] !== null && $this->transfers->movementCount($championshipId, $window['window_starts_at'], $window['window_ends_at'], $id) >= (int) $window['maximum_movements']) $errors[] = 'Limite de movimentacoes atingido.';
        if ($record && $status !== $record['status'] && !TransferRules::canTransition((string) $record['status'], $status)) $errors[] = 'Transicao de status invalida.';
        if ($errors) return ['ok' => false, 'errors' => $errors, 'record' => array_merge($input, ['championship_id' => $championshipId, 'type' => $type])];
        $now = date('Y-m-d H:i:s'); $data = ['championship_id' => $championshipId, 'athlete_id' => $athleteId, 'previous_team_id' => $previous, 'new_team_id' => $new, 'type' => $type, 'movement_date' => $date, 'public_observation' => trim((string) ($input['public_observation'] ?? '')) ?: null, 'internal_notes' => trim((string) ($input['internal_notes'] ?? '')) ?: null, 'status' => $status, 'published_at' => null, 'author_id' => $record['author_id'] ?? (int) $user['id'], 'created_at' => $record['created_at'] ?? $now, 'updated_at' => $now];
        try { if ($id) { $this->transfers->update($id, $data); $saved = $id; $this->transfers->addHistory($saved, (string) $record['status'], $status, 'updated', null, (int) $user['id']); } else { $saved = $this->transfers->create($data); $this->transfers->addHistory($saved, null, $status, 'created', null, (int) $user['id']); } $this->audit->record($id ? 'transfer.updated' : 'transfer.created', (int) $user['id'], 'transfer_movement', $saved, ['championship_id' => $championshipId, 'status' => $status]); return ['ok' => true, 'id' => $saved, 'errors' => []]; } catch (\Throwable $e) { return ['ok' => false, 'errors' => [$e->getMessage()], 'record' => array_merge($input, ['championship_id' => $championshipId])]; }
    }

    public function transition(int $id, int $userId, string $to, string $action, ?string $reason = null): bool
    {
        $record = $this->transfers->find($id); if (!$record || !TransferRules::canTransition((string) $record['status'], $to)) return false; $publishedAt = $to === 'published' ? date('Y-m-d H:i:s') : null; $this->transfers->setStatus($id, $to, $publishedAt); $this->transfers->addHistory($id, $record['status'], $to, $action, $reason, $userId); $this->audit->record('transfer.' . $action, $userId, 'transfer_movement', $id, ['from' => $record['status'], 'to' => $to]); return true;
    }
    public function applyOfficial(int $id, int $userId): bool
    {
        $ok = $this->transfers->applyOfficial($id, $userId);
        if ($ok) $this->audit->record('transfer.official_link_applied', $userId, 'transfer_movement', $id, []);
        return $ok;
    }
    private function nullableInt(mixed $value): ?int { return (int) $value > 0 ? (int) $value : null; }
    private function validDate(string $date): bool { $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date); return $d !== false && $d->format('Y-m-d') === $date; }
}
