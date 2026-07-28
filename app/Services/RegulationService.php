<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Repositories\RegulationRepository;
use PDO;

final class RegulationService
{
    public function __construct(private readonly PDO $pdo, private readonly RegulationRepository $regulations, private readonly AuditService $audit)
    {
    }

    public function createInitialDraft(int $championshipId, int $userId, ?Request $request = null): int
    {
        if ($this->regulations->latest($championshipId)) {
            return (int) $this->regulations->latest($championshipId)['id'];
        }
        $id = $this->regulations->create($championshipId, 1, 'Regulamento inicial', 'draft', $userId);
        $this->regulations->saveSettings($id, ...$this->split(RegulationRules::preset()));
        $this->saveRoster($id, RegulationRules::preset());
        $this->audit->record('regulations.created', $userId, 'regulation', $id, ['championship_id' => $championshipId], $request);
        return $id;
    }

    public function ensureDraft(int $championshipId, int $userId, ?Request $request = null): int
    {
        $draft = $this->regulations->draft($championshipId);
        if ($draft) {
            return (int) $draft['id'];
        }
        $source = $this->regulations->published($championshipId) ?: $this->regulations->latest($championshipId);
        if (!$source) {
            return $this->createInitialDraft($championshipId, $userId, $request);
        }
        $loaded = $this->regulations->findWithSettings((int) $source['id']);
        $newVersion = (int) $source['version_number'] + 1;
        $id = $this->regulations->create($championshipId, $newVersion, (string) $source['name'] . ' - Revisao', 'draft', $userId);
        $this->regulations->saveSettings($id, $loaded['format_settings'], $loaded['points_settings'], $loaded['discipline_settings'], $loaded['match_settings'], $loaded['tiebreakers']);
        $this->regulations->saveRosterSettings($id, $loaded['roster_settings'] ?: RegulationRules::preset()['roster'], array_column($loaded['required_documents'], 'document_type_id'));
        $this->audit->record('regulations.version_created', $userId, 'regulation', $id, ['based_on' => (int) $source['id'], 'championship_id' => $championshipId], $request);
        return $id;
    }

    public function save(int $championshipId, int $userId, array $data, ?Request $request = null): array
    {
        $errors = RegulationRules::validate($data);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }
        $id = $this->ensureDraft($championshipId, $userId, $request);
        $this->regulations->updateMain($id, (string) $data['name'], $data['effective_from'] ?: null);
        $this->regulations->saveSettings($id, ...$this->split($data));
        $this->saveRoster($id, $data);
        $this->audit->record('regulations.updated', $userId, 'regulation', $id, ['championship_id' => $championshipId], $request);
        return ['ok' => true, 'id' => $id];
    }

    public function applyPreset(int $championshipId, int $userId, ?Request $request = null): int
    {
        $id = $this->ensureDraft($championshipId, $userId, $request);
        $this->regulations->saveSettings($id, ...$this->split(RegulationRules::preset()));
        $this->saveRoster($id, RegulationRules::preset());
        $this->audit->record('regulations.preset_applied', $userId, 'regulation', $id, ['championship_id' => $championshipId, 'preset' => 'copa_brasil_de_talentos'], $request);
        return $id;
    }

    public function publish(int $championshipId, int $userId, ?Request $request = null): array
    {
        $draft = $this->regulations->draft($championshipId);
        if (!$draft) {
            return ['ok' => false, 'errors' => ['Crie ou edite um rascunho antes de publicar.']];
        }
        $loaded = $this->regulations->findWithSettings((int) $draft['id']);
        $errors = RegulationRules::validate([
            'format' => $loaded['format_settings'],
            'points' => $loaded['points_settings'],
            'discipline' => $loaded['discipline_settings'],
            'match' => $loaded['match_settings'],
            'tiebreakers' => $loaded['tiebreakers'],
        ]);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }
        $this->regulations->publishDraft($championshipId, (int) $draft['id']);
        $this->audit->record('regulations.published', $userId, 'regulation', (int) $draft['id'], ['championship_id' => $championshipId], $request);
        return ['ok' => true, 'id' => (int) $draft['id']];
    }

    private function split(array $data): array
    {
        return [$data['format'], $data['points'], $data['discipline'], $data['match'], $data['tiebreakers']];
    }

    private function saveRoster(int $id, array $data): void
    {
        $roster = $data['roster'] ?? RegulationRules::preset()['roster'];
        $this->regulations->saveRosterSettings($id, $roster, (array) ($roster['required_document_type_ids'] ?? []));
    }
}
