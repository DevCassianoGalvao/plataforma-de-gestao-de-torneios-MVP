<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Repositories\AthleteDocumentRepository;
use App\Repositories\AthleteRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\RegistrationRepository;
use App\Repositories\RegulationRepository;
use App\Repositories\TeamRepository;

final class RegistrationService
{
    public function __construct(private readonly RegistrationRepository $registrations, private readonly ChampionshipRepository $championships, private readonly TeamRepository $teams, private readonly AthleteRepository $athletes, private readonly AthleteDocumentRepository $documents, private readonly RegulationRepository $regulations, private readonly AuditService $audit)
    {
    }

    public function createDraft(int $userId, int $championshipId, int $teamId, int $athleteId, ?int $number, string $observations, ?Request $request = null): array
    {
        if (!RegistrationRules::validNumber($number)) return ['ok' => false, 'errors' => ['O numero pretendido deve estar entre 1 e 99.']];
        $championship = $this->championships->findForUser($championshipId, 0, true);
        $team = $this->teams->findForUser($teamId, 0, 'administrator');
        $athlete = $this->athletes->findForUser($athleteId, 0, 'administrator');
        $errors = $this->baseIssues($championship, $team, $athlete);
        if ($this->registrations->findByPair($championshipId, $teamId, $athleteId)) $errors[] = 'Ja existe uma inscricao para este atleta nesta equipe e campeonato.';
        if ($errors !== []) return ['ok' => false, 'errors' => array_values(array_unique($errors))];
        $id = $this->registrations->create(['championship_id' => $championshipId, 'team_id' => $teamId, 'athlete_id' => $athleteId, 'category_id' => (int) $championship['category_id'], 'requested_number' => $number, 'observations' => $observations], $userId);
        $this->audit->record('registrations.created', $userId, 'athlete_registration', $id, ['championship_id' => $championshipId, 'team_id' => $teamId, 'athlete_id' => $athleteId], $request);
        return ['ok' => true, 'id' => $id, 'errors' => []];
    }

    public function createSubmitted(int $userId, int $championshipId, int $teamId, int $athleteId, ?int $number, string $observations, ?Request $request = null): array
    {
        $created = $this->createDraft($userId, $championshipId, $teamId, $athleteId, $number, $observations, $request);
        if (!$created['ok']) return $created;
        $registration = $this->registrations->findByPair($championshipId, $teamId, $athleteId);
        if (!$registration) return ['ok' => false, 'errors' => ['Nao foi possivel localizar a inscricao criada.']];
        $this->move($registration, 'submitted', $userId, 'submitted', 'Enviada automaticamente pelo cadastro do atleta.', $request);
        return $created;
    }

    public function updateDraft(array $registration, int $userId, ?int $number, string $observations, ?Request $request = null): array
    {
        if (!in_array($registration['status'], ['draft', 'pending_correction'], true)) return ['ok' => false, 'errors' => ['Somente rascunhos ou inscricoes pendentes podem ser corrigidos.']];
        if (!RegistrationRules::validNumber($number)) return ['ok' => false, 'errors' => ['O numero pretendido deve estar entre 1 e 99.']];
        $this->registrations->updateDraft((int) $registration['id'], $number, $observations, $userId);
        $this->audit->record('registrations.corrected', $userId, 'athlete_registration', (int) $registration['id'], [], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function submit(array $registration, int $userId, ?Request $request = null): array
    {
        if (!in_array($registration['status'], ['draft', 'pending_correction'], true)) return ['ok' => false, 'errors' => ['Esta inscricao nao pode ser enviada neste status.']];
        $issues = $this->submissionIssues($registration);
        if ($issues !== []) {
            $this->registrations->setIssues((int) $registration['id'], $issues, $userId);
            $this->audit->record('registrations.validation_failed', $userId, 'athlete_registration', (int) $registration['id'], ['issues' => $issues], $request);
            return ['ok' => false, 'errors' => $issues];
        }
        $this->move($registration, 'submitted', $userId, 'submitted', null, $request);
        return ['ok' => true, 'errors' => []];
    }

    public function startReview(array $registration, int $userId, ?Request $request = null): array
    {
        return $this->moveResult($registration, 'under_review', $userId, 'review_started', null, $request);
    }

    public function requestCorrection(array $registration, int $userId, string $issues, ?Request $request = null): array
    {
        $issues = trim($issues);
        if ($issues === '') return ['ok' => false, 'errors' => ['Informe as pendencias para solicitar correcao.']];
        return $this->moveResult($registration, 'pending_correction', $userId, 'correction_requested', $issues, $request);
    }

    public function approve(array $registration, int $userId, ?Request $request = null): array
    {
        if ($registration['status'] === 'submitted') {
            $this->move($registration, 'under_review', $userId, 'review_started', 'Analise iniciada automaticamente pela aprovacao do cadastro.', $request);
            $registration['status'] = 'under_review';
        }
        $issues = $this->approvalIssues($registration);
        if ($issues !== []) {
            $this->registrations->setIssues((int) $registration['id'], $issues, $userId);
            $this->audit->record('registrations.approval_blocked', $userId, 'athlete_registration', (int) $registration['id'], ['issues' => $issues], $request);
            return ['ok' => false, 'errors' => $issues];
        }
        return $this->moveResult($registration, 'approved', $userId, 'approved', null, $request);
    }

    public function reject(array $registration, int $userId, string $reason, ?Request $request = null): array
    {
        $reason = trim($reason);
        if ($reason === '') return ['ok' => false, 'errors' => ['Informe o motivo da rejeicao.']];
        return $this->moveResult($registration, 'rejected', $userId, 'rejected', $reason, $request);
    }

    public function cancel(array $registration, int $userId, ?Request $request = null): array
    {
        return $this->moveResult($registration, 'cancelled', $userId, 'cancelled', null, $request);
    }

    public function officialRoster(int $championshipId, ?int $teamId = null): array
    {
        return $this->registrations->officialRoster($championshipId, $teamId);
    }

    public function history(int $registrationId): array
    {
        return $this->registrations->history($registrationId);
    }

    public function rosterIssues(int $championshipId, int $teamId): array
    {
        $regulation = $this->publishedRegulation($championshipId);
        $settings = $regulation['roster_settings'] ?? [];
        if ($settings === []) return ['Regulamento sem configuracao de elenco.'];
        $issues = [];
        $approved = $this->registrations->approvedCount($championshipId, $teamId);
        $goalkeepers = $this->registrations->approvedGoalkeeperCount($championshipId, $teamId);
        if ($approved < (int) $settings['minimum_roster_size']) $issues[] = 'Elenco abaixo do minimo de ' . (int) $settings['minimum_roster_size'] . ' atletas.';
        if ($goalkeepers < (int) $settings['minimum_goalkeepers']) $issues[] = 'Elenco abaixo do minimo de ' . (int) $settings['minimum_goalkeepers'] . ' goleiro(s).';
        return $issues;
    }

    private function submissionIssues(array $registration): array
    {
        $championship = $this->championships->findForUser((int) $registration['championship_id'], 0, true);
        $team = $this->teams->findForUser((int) $registration['team_id'], 0, 'administrator');
        $athlete = $this->athletes->findForUser((int) $registration['athlete_id'], 0, 'administrator');
        $issues = $this->baseIssues($championship, $team, $athlete);
        $regulation = $this->publishedRegulation((int) $registration['championship_id']);
        $advanced = $regulation['advanced_settings'] ?? [];
        if (!$championship || !in_array($championship['status'], ['registration', 'configured'], true)) $issues[] = 'O campeonato nao aceita inscricoes neste status.';
        if ($championship && $advanced !== [] && empty($advanced['allow_registration_after_start']) && !empty($championship['starts_at']) && substr((string) $championship['starts_at'], 0, 10) <= date('Y-m-d')) $issues[] = 'O regulamento nao permite inscricoes apos o inicio do campeonato.';
        if ($championship && !RegistrationRules::windowOpen($championship)) $issues[] = 'O periodo de inscricoes esta fechado.';
        if ($athlete && $championship) $issues = array_merge($issues, $this->categoryIssues($athlete, $championship));
        if ($registration['requested_number'] !== null && $this->registrations->numberTaken((int) $registration['championship_id'], (int) $registration['team_id'], (int) $registration['requested_number'], (int) $registration['id'])) $issues[] = 'O numero pretendido ja esta em uso pela equipe.';
        if ($championship && $athlete) $issues = array_merge($issues, $this->duplicateIssues($registration, $championship));
        if ($championship && $athlete && ($advanced === [] || !empty($advanced['require_complete_documents']))) $issues = array_merge($issues, $this->documentIssues($athlete, $regulation));
        return array_values(array_unique($issues));
    }

    private function approvalIssues(array $registration): array
    {
        if ($registration['status'] !== 'under_review') return ['Somente inscricoes em analise podem ser aprovadas.'];
        $issues = $this->submissionIssues($registration);
        $regulation = $this->publishedRegulation((int) $registration['championship_id']);
        $settings = $regulation['roster_settings'] ?? [];
        if ($settings !== [] && $this->registrations->approvedCount((int) $registration['championship_id'], (int) $registration['team_id'], (int) $registration['id']) >= (int) $settings['maximum_roster_size']) $issues[] = 'O elenco atingiu o limite maximo configurado.';
        return array_values(array_unique($issues));
    }

    private function baseIssues(?array $championship, ?array $team, ?array $athlete): array
    {
        $issues = [];
        if (!$championship) $issues[] = 'Campeonato invalido.';
        if (!$team) $issues[] = 'Equipe invalida.';
        if (!$athlete) $issues[] = 'Atleta invalido.';
        if ($championship && $team && (int) $team['championship_id'] !== (int) $championship['id']) $issues[] = 'A equipe nao pertence ao campeonato.';
        if ($team && $athlete && (int) $athlete['team_id'] !== (int) $team['id']) $issues[] = 'O atleta nao pertence a equipe selecionada.';
        if ($team && ($team['status'] ?? '') !== 'active') $issues[] = 'A equipe precisa estar ativa.';
        return $issues;
    }

    private function categoryIssues(array $athlete, array $championship): array
    {
        $issues = [];
        $age = (int) ($athlete['age'] ?? $athlete['athlete_age'] ?? -1);
        $minimumAge = $championship['minimum_age'] !== null ? (int) $championship['minimum_age'] : null;
        $allowsMinorInAdultCategory = !empty($championship['allow_underage_athletes']) && $age < 18 && $minimumAge !== null && $minimumAge >= 18;
        if ($minimumAge !== null && $age < $minimumAge && !$allowsMinorInAdultCategory) $issues[] = 'A idade do atleta esta abaixo do minimo da categoria.';
        if ($championship['maximum_age'] !== null && $age > (int) $championship['maximum_age']) $issues[] = 'A idade do atleta supera o maximo da categoria.';
        if (!empty($championship['gender_rule']) && (string) $athlete['gender'] !== (string) $championship['gender_rule']) $issues[] = 'O genero do atleta nao e compativel com a categoria.';
        return $issues;
    }

    private function duplicateIssues(array $registration, array $championship): array
    {
        $regulation = $this->publishedRegulation((int) $championship['id']);
        $settings = $regulation['roster_settings'] ?? [];
        if (!empty($settings['allow_multiple_team_registration'])) return [];
        return $this->registrations->hasOtherTeamRegistration((int) $registration['championship_id'], (int) $registration['athlete_id'], (int) $registration['team_id']) ? ['O atleta ja possui inscricao ativa por outra equipe neste campeonato.'] : [];
    }

    private function documentIssues(array $athlete, ?array $regulation): array
    {
        if (!$regulation) return ['Nao existe regulamento publicado para validar documentos.'];
        $required = $regulation['required_documents'] ?? [];
        if ($required === []) return [];
        $documents = $this->documents->listForAthlete((int) $athlete['id']);
        $isMinor = AthleteRules::isMinor((string) $athlete['birth_date']);
        $issues = [];
        foreach ($required as $requiredDocument) {
            if ((int) ($requiredDocument['required_for_minor'] ?? 0) === 1 && !$isMinor) continue;
            $valid = false;
            foreach ($documents as $document) {
                if ((int) $document['document_type_id'] !== (int) $requiredDocument['document_type_id']) continue;
                if ($document['status'] === 'approved') $valid = true;
            }
            if (!$valid) $issues[] = 'Documento obrigatorio ausente ou pendente: ' . $requiredDocument['name'] . '.';
        }
        return $issues;
    }

    private function publishedRegulation(int $championshipId): ?array
    {
        $published = $this->regulations->published($championshipId);
        return $published ? $this->regulations->findWithSettings((int) $published['id']) : null;
    }

    private function moveResult(array $registration, string $to, int $userId, string $action, ?string $notes, ?Request $request): array
    {
        if (!RegistrationRules::transition((string) $registration['status'], $to)) return ['ok' => false, 'errors' => ['Transicao de inscricao invalida.']];
        $this->move($registration, $to, $userId, $action, $notes, $request);
        return ['ok' => true, 'errors' => []];
    }

    private function move(array $registration, string $to, int $userId, string $action, ?string $notes, ?Request $request): void
    {
        $from = (string) $registration['status'];
        if (!RegistrationRules::transition($from, $to)) throw new \RuntimeException('Transicao de inscricao invalida.');
        $this->registrations->transition((int) $registration['id'], $from, $to, $userId, $action, $notes);
        $this->audit->record('registrations.' . $action, $userId, 'athlete_registration', (int) $registration['id'], ['from' => $from, 'to' => $to], $request);
    }
}
