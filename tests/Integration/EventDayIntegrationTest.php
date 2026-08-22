<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\EventDayRepository;
use App\Repositories\UserRepository;
use function Tests\assert_same;
use function Tests\assert_true;

final class EventDayIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $admin = (new UserRepository($pdo))->findByEmail('admin@torneios.local');
        $championshipId = (int) $pdo->query('SELECT id FROM championships WHERE deleted_at IS NULL ORDER BY id LIMIT 1')->fetchColumn();
        assert_true((bool) $admin && $championshipId > 0, 'Fixture de dia de evento ausente');

        $repository = new EventDayRepository($pdo);
        $dayId = $repository->create([
            'championship_id' => $championshipId,
            'venue_id' => null,
            'event_date' => '2035-01-15',
            'name' => 'Etapa de teste',
            'notes' => 'Fixture isolada',
        ], (int) $admin['id']);
        try {
            assert_true((bool) $repository->find($dayId, $championshipId), 'Dia de evento não foi criado');
            assert_same([], $repository->checklist($championshipId), 'Checklist de outro escopo vazou para o dia');

            $mediaId = $repository->createMedia([
                'event_day_id' => $dayId,
                'championship_id' => $championshipId,
                'checklist_item_id' => null,
                'title' => 'Foto da equipe de trabalho',
                'caption' => 'Fixture',
                'storage_path' => 'private/test-event-day.webp',
                'original_name' => 'equipe.webp',
                'mime_type' => 'image/webp',
                'file_hash' => hash('sha256', 'fixture'),
                'review_status' => 'submitted',
                'captured_at' => null,
                'uploaded_by' => (int) $admin['id'],
            ]);
            $media = $repository->findMedia($mediaId, $dayId);
            assert_same('private', $media['visibility'] ?? null, 'Mídia de evento não ficou privada');
            assert_same('submitted', $media['review_status'] ?? null, 'Mídia não preservou análise pendente');

            $repository->review($mediaId, 'approved', (int) $admin['id'], null);
            assert_same('approved', $repository->findMedia($mediaId, $dayId)['review_status'] ?? null, 'Análise da mídia não foi registrada');
            assert_true($repository->delete($dayId, $championshipId, (int) $admin['id']), 'Dia de evento não foi arquivado');
            assert_same(null, $repository->find($dayId, $championshipId), 'Dia arquivado ainda aparece como ativo');
            $deletedBy = $pdo->prepare('SELECT deleted_by FROM event_days WHERE id=?');
            $deletedBy->execute([$dayId]);
            assert_same((int) $admin['id'], (int) $deletedBy->fetchColumn(), 'Usuário do arquivamento não foi registrado');
        } finally {
            $pdo->prepare('DELETE FROM event_day_media WHERE event_day_id=?')->execute([$dayId]);
            $pdo->prepare('DELETE FROM event_days WHERE id=?')->execute([$dayId]);
        }
    }
}
