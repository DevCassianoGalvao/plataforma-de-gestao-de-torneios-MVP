<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\AthleteDocumentTypeSeed;
use App\Database\AthleteSeed;
use App\Database\PositionSeed;
use App\Database\AuthSeed;
use App\Database\ChampionshipSeed;
use App\Database\TacticalFormationSeed;
use App\Database\TeamSeed;
use App\Services\StorageService;
use function Tests\assert_same;
use function Tests\assert_true;

final class AthleteHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $password = getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123';
        AuthSeed::run($pdo, $password);
        ChampionshipSeed::run($pdo);
        TacticalFormationSeed::run($pdo);
        TeamSeed::run($pdo);
        PositionSeed::run($pdo);
        AthleteDocumentTypeSeed::run($pdo);
        AthleteSeed::run($pdo);
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $teamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'estrela-norte-fc'")->fetchColumn();
        $foreignTeamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'serra-azul-futebol'")->fetchColumn();
        $positionId = (int) $pdo->query("SELECT id FROM positions WHERE code = 'center_forward'")->fetchColumn();
        $documentTypeId = (int) $pdo->query("SELECT id FROM athlete_document_types WHERE `key` = 'proof'")->fetchColumn();
        $firstAthleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$teamId} AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        $foreignAthleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$foreignTeamId} AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        Session::destroy();

        self::login($router, 'admin@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas'))->status, 'Administrador nao abriu atletas');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/posicoes'))->status, 'Catalogo de posicoes nao abriu');
        assert_same(422, $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas', ['_csrf' => Security::csrfToken(), 'team_id' => $teamId, 'full_name' => 'Incompleto']))->status, 'Atleta invalido foi aceito');
        $athleteCreatePhoto = tempnam(sys_get_temp_dir(), 'mvp-athlete-create-photo-');
        $athleteCreateDocument = tempnam(sys_get_temp_dir(), 'mvp-athlete-create-document-');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        file_put_contents($athleteCreatePhoto, $png);
        file_put_contents($athleteCreateDocument, $png);
        $created = $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas', ['_csrf' => Security::csrfToken(), 'registration_action' => 'create', 'team_id' => $teamId, 'full_name' => 'Atleta HTTP Completo', 'sporting_name' => 'HTTP', 'birth_date' => '2012-06-15', 'gender' => 'male', 'primary_position_id' => $positionId, 'preferred_number' => '17', 'dominant_foot' => 'right', 'guardian_full_name' => 'Responsavel HTTP', 'guardian_relationship' => 'Mae', 'guardian_phone' => '11999990000', 'guardian_email' => 'responsavel-http@example.test', 'guardian_document' => 'DOC-HTTP', 'guardian_authorization_note' => 'Autorizado', 'private_notes' => 'Nota privada'], ['photo' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($athleteCreatePhoto), 'tmp_name' => $athleteCreatePhoto, 'name' => 'atleta.png'], 'identity_document' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($athleteCreateDocument), 'tmp_name' => $athleteCreateDocument, 'name' => 'documento.png']]));
        assert_same(302, $created->status, 'Administrador nao criou atleta');
        @unlink($athleteCreatePhoto);
        @unlink($athleteCreateDocument);
        $athleteId = (int) $pdo->query("SELECT id FROM athletes WHERE full_name = 'Atleta HTTP Completo' LIMIT 1")->fetchColumn();
        assert_true($athleteId > 0, 'Atleta HTTP nao foi persistido');
        assert_same(1, (int) $pdo->query("SELECT COUNT(*) FROM athlete_registrations WHERE athlete_id = {$athleteId} AND team_id = {$teamId}")->fetchColumn(), 'Inscricao automatica nao foi criada');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas/' . $athleteId))->status, 'Detalhe do atleta nao abriu');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas/' . $athleteId . '/responsaveis'))->status, 'Responsaveis nao abriram');

        $athletePhoto = tempnam(sys_get_temp_dir(), 'mvp-athlete-photo-');
        file_put_contents($athletePhoto, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $photoUpdate = $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas/' . $athleteId, ['_csrf' => Security::csrfToken(), 'full_name' => 'Atleta HTTP Completo', 'sporting_name' => 'HTTP', 'birth_date' => '2012-06-15', 'gender' => 'male', 'primary_position_id' => $positionId, 'preferred_number' => '17', 'dominant_foot' => 'right', 'private_notes' => 'Nota privada'], ['photo' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($athletePhoto), 'tmp_name' => $athletePhoto, 'name' => 'atleta-atualizado.png']]));
        assert_same(302, $photoUpdate->status, 'Foto do atleta nao foi atualizada');
        $athletePhotoPath = (string) $pdo->query("SELECT photo_path FROM athletes WHERE id = {$athleteId}")->fetchColumn();
        assert_true($athletePhotoPath !== '', 'Caminho da foto do atleta nao foi persistido');
        $photoResponse = $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas/' . $athleteId . '/assets/photo'));
        assert_same(200, $photoResponse->status, 'Foto atualizada nao foi servida');
        (new StorageService())->delete($athletePhotoPath);
        @unlink($athletePhoto);

        $guardianId = (int) $pdo->query("SELECT guardian_id FROM athlete_guardians WHERE athlete_id = {$athleteId} LIMIT 1")->fetchColumn();
        $pdo->prepare('DELETE FROM athlete_guardians WHERE athlete_id = ?')->execute([$athleteId]);
        if ($guardianId > 0) $pdo->prepare('DELETE FROM legal_guardians WHERE id = ?')->execute([$guardianId]);
        $photoWithoutGuardian = tempnam(sys_get_temp_dir(), 'mvp-athlete-photo-no-guardian-');
        file_put_contents($photoWithoutGuardian, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $photoOnly = $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas/' . $athleteId, ['_csrf' => Security::csrfToken(), 'full_name' => 'Atleta HTTP Completo', 'sporting_name' => 'HTTP', 'birth_date' => '2012-06-15', 'gender' => 'male', 'primary_position_id' => $positionId, 'preferred_number' => '17', 'dominant_foot' => 'right', 'private_notes' => 'Nota privada'], ['photo' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($photoWithoutGuardian), 'tmp_name' => $photoWithoutGuardian, 'name' => 'atleta-sem-responsavel.png']]));
        assert_same(302, $photoOnly->status, 'Troca de foto foi bloqueada pela ausencia de responsavel');
        $photoOnlyPath = (string) $pdo->query("SELECT photo_path FROM athletes WHERE id = {$athleteId}")->fetchColumn();
        (new StorageService())->delete($photoOnlyPath);
        @unlink($photoWithoutGuardian);

        $temporary = tempnam(sys_get_temp_dir(), 'mvp-athlete-http-');
        file_put_contents($temporary, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $document = $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas/' . $athleteId . '/documentos', ['_csrf' => Security::csrfToken(), 'document_type_id' => $documentTypeId, 'observation' => 'Comprovante HTTP'], ['document' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($temporary), 'tmp_name' => $temporary, 'name' => 'comprovante.png']]));
        assert_same(302, $document->status, 'Documento valido foi rejeitado');
        $documentId = (int) $pdo->query("SELECT id FROM athlete_documents WHERE athlete_id = {$athleteId} ORDER BY id DESC LIMIT 1")->fetchColumn();
        $storedDocumentPath = (string) $pdo->query("SELECT storage_path FROM athlete_documents WHERE id = {$documentId}")->fetchColumn();
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas/' . $athleteId . '/documentos'))->status, 'Documentos nao abriram');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas/' . $athleteId . '/documentos/' . $documentId))->status, 'Documento privado nao foi servido');
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas/' . $athleteId . '/documentos/' . $documentId . '/status', ['_csrf' => Security::csrfToken(), 'status' => 'approved']))->status, 'Revisao de documento falhou');
        assert_same('approved', (string) $pdo->query("SELECT status FROM athlete_documents WHERE id = {$documentId}")->fetchColumn(), 'A aprovacao do documento nao foi persistida');
        (new StorageService())->delete($storedDocumentPath);
        @unlink($temporary);
        $invalidFile = tempnam(sys_get_temp_dir(), 'mvp-athlete-invalid-');
        file_put_contents($invalidFile, 'MZ executable');
        $invalid = $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas/' . $athleteId . '/documentos', ['_csrf' => Security::csrfToken(), 'document_type_id' => $documentTypeId], ['document' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($invalidFile), 'tmp_name' => $invalidFile, 'name' => 'arquivo.png']]));
        assert_same(422, $invalid->status, 'Arquivo executavel foi aceito');
        @unlink($invalidFile);
        assert_same(419, $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas/' . $athleteId . '/documentos', ['_csrf' => 'invalid', 'document_type_id' => $documentTypeId]))->status, 'CSRF de documento foi aceito');
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas/' . $athleteId . '/status', ['_csrf' => Security::csrfToken(), 'status' => 'active']))->status, 'Status do atleta nao foi alterado');
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/admin/atletas/' . $athleteId . '/excluir', ['_csrf' => Security::csrfToken()]))->status, 'Exclusao logica falhou');
        assert_true((int) $pdo->query("SELECT COUNT(*) FROM athletes WHERE id = {$athleteId} AND deleted_at IS NOT NULL")->fetchColumn() === 1, 'Exclusao logica nao persistiu');
        self::logout($router);

        self::login($router, 'treinador@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas/' . $firstAthleteId))->status, 'Treinador nao acessou atleta da propria equipe');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas/' . $foreignAthleteId))->status, 'Treinador acessou atleta de outra equipe');
        self::logout($router);
        self::login($router, 'operador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas'))->status, 'Operador acessou atletas');
        self::logout($router);
        self::login($router, 'prestacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas'))->status, 'Prestacao de contas acessou atletas');
        self::logout($router);
        assert_same(302, $router->dispatch(Request::fake('GET', '/torneio-online/admin/atletas'))->status, 'Usuario sem login nao foi redirecionado');
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login de teste falhou para ' . $email);
        assert_true(Auth::authenticated(), 'Sessao nao criada para ' . $email);
    }

    private static function logout(Router $router): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $response->status, 'Logout de teste falhou');
    }
}
