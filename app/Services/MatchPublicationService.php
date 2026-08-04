<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\MatchPublicationRepository;

final class MatchPublicationService
{
    public function __construct(private readonly MatchPublicationRepository $publications, private readonly AuditService $audit) {}

    public function state(int $matchId): array
    {
        return $this->publications->find($matchId) ?: ['status' => 'internal', 'scheduled_at' => null, 'published_at' => null, 'reason' => null];
    }

    public function publish(array $user, array $match, ?string $reason = null): array
    {
        if (!in_array($match['status'], ['scheduled', 'confirmed', 'postponed', 'homologated'], true)) return $this->fail('A partida precisa estar agendada, confirmada, adiada ou aprovada para ser publicada.');
        $this->publications->save((int) $match['id'], 'published', null, (int) $user['id'], $reason);
        $this->audit->record('match_publication.published', (int) $user['id'], 'match', (int) $match['id'], ['reason' => $reason]);
        return ['ok' => true, 'errors' => []];
    }

    public function schedule(array $user, array $match, string $scheduledAt, ?string $reason = null): array
    {
        $timestamp = strtotime($scheduledAt);
        if ($timestamp === false || $timestamp <= time()) return $this->fail('Informe uma data futura para a publicação.');
        if (!in_array($match['status'], ['scheduled', 'confirmed', 'postponed', 'homologated'], true)) return $this->fail('A partida não pode ser agendada para publicação neste estado.');
        $date = date('Y-m-d H:i:s', $timestamp);
        $this->publications->save((int) $match['id'], 'scheduled', $date, null, $reason);
        $this->audit->record('match_publication.scheduled', (int) $user['id'], 'match', (int) $match['id'], ['scheduled_at' => $date, 'reason' => $reason]);
        return ['ok' => true, 'errors' => []];
    }

    public function cancel(array $user, array $match, string $reason): array
    {
        if (mb_strlen(trim($reason)) < 3) return $this->fail('Informe o motivo do cancelamento da publicação.');
        $this->publications->save((int) $match['id'], 'cancelled', null, (int) $user['id'], trim($reason));
        $this->audit->record('match_publication.cancelled', (int) $user['id'], 'match', (int) $match['id'], ['reason' => trim($reason)]);
        return ['ok' => true, 'errors' => []];
    }

    public function publishDue(): array { return $this->publications->publishDue(); }
    private function fail(string $message): array { return ['ok' => false, 'errors' => [$message]]; }
}
