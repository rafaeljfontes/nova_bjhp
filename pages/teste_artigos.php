<?php

declare(strict_types=1);

// No localhost, a chave fica apenas na sessão PHP. Fora dele, exige senha do servidor.
$isLocal = in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
if ($isLocal) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ]);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['api_token']) && is_string($_POST['api_token'])) {
            session_regenerate_id(true);
            $_SESSION['bjhp_api_token'] = trim($_POST['api_token']);
        } elseif (isset($_POST['forget_token'])) {
            unset($_SESSION['bjhp_api_token']);
        }
        header('Location: /bjhp/teste_artigos.php', true, 303);
        exit;
    }
} else {
    $dashboardPassword = (string) (getenv('BJHP_DASHBOARD_PASSWORD') ?: '');
    if ($dashboardPassword === '') {
        http_response_code(503);
        exit('Página de verificação disponível apenas no localhost.');
    }
    if (!hash_equals($dashboardPassword, (string) ($_SERVER['PHP_AUTH_PW'] ?? ''))) {
        header('WWW-Authenticate: Basic realm="BJHP - Submissoes"');
        http_response_code(401);
        exit('Acesso restrito.');
    }
}
header('Cache-Control: no-store, private');

const OJS_BASE_URL = 'https://bjhp.crfmg.org.br/crfmg';
const OJS_API_BASE_URL = OJS_BASE_URL . '/api/v1';
define('OJS_API_TOKEN', $isLocal
    ? (string) ($_SESSION['bjhp_api_token'] ?? '')
    : (string) (getenv('BJHP_OJS_API_TOKEN') ?: ''));

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function requestJson(string $url): array
{
    $headers = [
        'Accept: application/json',
        'User-Agent: BJHP-Portal/1.0',
    ];

    if (OJS_API_TOKEN !== '') {
        $headers[] = 'Authorization: Bearer ' . OJS_API_TOKEN;
    }

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'BJHP-Portal/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status >= 400) {
            throw new RuntimeException($error ?: 'Erro ao consultar a API do OJS.');
        }

        return json_decode((string) $body, true) ?: [];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 20,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);

    if ($body === false) {
        throw new RuntimeException('Erro ao consultar a API do OJS.');
    }

    return json_decode($body, true) ?: [];
}

function tryJson(string $url): array
{
    try {
        return requestJson($url);
    } catch (Throwable $error) {
        return [];
    }
}

function localizedValue($value, array $preferredLocales = ['pt_BR', 'pt', 'en_US', 'en']): string
{
    if (is_string($value)) {
        return trim($value);
    }

    if (!is_array($value)) {
        return '';
    }

    foreach ($preferredLocales as $locale) {
        if (isset($value[$locale]) && is_string($value[$locale])) {
            return trim($value[$locale]);
        }
    }

    foreach ($value as $item) {
        if (is_string($item)) {
            return trim($item);
        }
    }

    return '';
}

function currentPublication(array $submission): array
{
    if (!empty($submission['currentPublication']) && is_array($submission['currentPublication'])) {
        return $submission['currentPublication'];
    }

    if (!empty($submission['publication']) && is_array($submission['publication'])) {
        return $submission['publication'];
    }

    if (!empty($submission['publications']) && is_array($submission['publications'])) {
        $currentPublicationId = (int) ($submission['currentPublicationId'] ?? 0);

        foreach ($submission['publications'] as $publication) {
            if (is_array($publication) && (int) ($publication['id'] ?? 0) === $currentPublicationId) {
                return $publication;
            }
        }

        foreach ($submission['publications'] as $publication) {
            if (is_array($publication)) {
                return $publication;
            }
        }
    }

    return $submission;
}

function formatDate(?string $date): string
{
    if (!$date) {
        return 'Não informado';
    }

    try {
        return (new DateTimeImmutable($date))->format('d/m/Y');
    } catch (Throwable $error) {
        return $date;
    }
}

function fullNameFromArray(array $item): string
{
    $name = localizedValue($item['fullName'] ?? $item['preferredPublicName'] ?? $item['name'] ?? '');

    if ($name) {
        return $name;
    }

    return trim((string) ($item['givenName'] ?? '') . ' ' . (string) ($item['familyName'] ?? ''));
}

function principalAuthor(array $publication, array $submission): string
{
    $authors = $publication['authors'] ?? $submission['authors'] ?? [];
    if (is_array($authors) && !empty($authors[0]) && is_array($authors[0])) {
        $name = fullNameFromArray($authors[0]);
        if ($name) {
            return $name;
        }
    }

    $authorsString = (string) ($publication['authorsString'] ?? $submission['authorsString'] ?? '');
    if ($authorsString !== '') {
        return trim(explode(',', $authorsString)[0]);
    }

    return 'Não informado';
}

function collectRows($value): array
{
    if (!is_array($value)) {
        return [];
    }

    if (isset($value['items']) && is_array($value['items'])) {
        return $value['items'];
    }

    foreach (['reviewAssignments', 'reviewers', 'reviews', 'participants', 'users', 'editors'] as $key) {
        if (isset($value[$key]) && is_array($value[$key])) {
            return $value[$key];
        }
    }

    return array_is_list($value) ? $value : [];
}

function collectReviewAssignments(array $submission, int $submissionId): array
{
    $assignments = [];
    $candidateKeys = [
        'reviewAssignments',
        'reviewers',
        'reviews',
        'reviewerAssignments',
    ];

    foreach ($candidateKeys as $key) {
        if (!empty($submission[$key]) && is_array($submission[$key])) {
            $assignments = array_merge($assignments, collectRows($submission[$key]));
        }
    }

    foreach (($submission['reviewRounds'] ?? []) as $round) {
        if (is_array($round)) {
            foreach ($candidateKeys as $key) {
                if (!empty($round[$key]) && is_array($round[$key])) {
                    $assignments = array_merge($assignments, collectRows($round[$key]));
                }
            }
        }
    }

    return array_values(array_filter($assignments, 'is_array'));
}

function reviewerName(array $assignment): string
{
    foreach (['reviewerFullName', 'reviewerName', 'fullName', 'name'] as $key) {
        if (!empty($assignment[$key]) && is_string($assignment[$key])) {
            return trim($assignment[$key]);
        }
    }

    foreach (['reviewer', 'user', 'reviewerUser'] as $key) {
        if (!empty($assignment[$key]) && is_array($assignment[$key])) {
            $name = fullNameFromArray($assignment[$key]);
            if ($name) {
                return $name;
            }
        }
    }

    $reviewerId = (int) ($assignment['reviewerId'] ?? 0);
    return $reviewerId > 0 ? 'Avaliador #' . $reviewerId : 'Avaliador (nome não disponível)';
}

function reviewStatus(array $assignment): array
{
    if (!empty($assignment['declined']) || !empty($assignment['dateDeclined'])) {
        return ['class' => 'no', 'icon' => '-', 'label' => 'Convite recusado'];
    }
    if (!empty($assignment['cancelled'])) {
        return ['class' => 'no', 'icon' => '-', 'label' => 'Convite cancelado'];
    }
    $completed = !empty($assignment['dateCompleted'])
        || !empty($assignment['dateRecommendationSubmitted'])
        || !empty($assignment['recommendation'])
        || !empty($assignment['reviewerRecommendation'])
        || (!empty($assignment['status']) && preg_match('/complete|completed|conclu|submet|done|accepted/i', (string) $assignment['status']));

    $dueDate = (string) ($assignment['dateDue'] ?? $assignment['dueDate'] ?? $assignment['responseDueDate'] ?? '');
    $overdue = false;

    if (!$completed && $dueDate !== '') {
        try {
            $overdue = new DateTimeImmutable($dueDate) < new DateTimeImmutable('today');
        } catch (Throwable $error) {
            $overdue = false;
        }
    }

    if (!$completed && !empty($assignment['overdue'])) {
        $overdue = true;
    }

    if ($overdue) {
        return ['class' => 'late', 'icon' => '!', 'label' => 'Em atraso'];
    }

    if ($completed) {
        return ['class' => 'ok', 'icon' => '+', 'label' => 'Avaliado'];
    }

    return ['class' => 'no', 'icon' => '-', 'label' => 'Pendente'];
}

function normalizeReviews(array $submission, int $submissionId): array
{
    $assignments = collectReviewAssignments($submission, $submissionId);
    $reviews = [];
    $seen = [];

    foreach ($assignments as $assignment) {
        $name = reviewerName($assignment);
        $status = reviewStatus($assignment);
        $key = $name . '|' . $status['label'];

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $reviews[] = [
            'name' => $name,
            'status' => $status,
        ];
    }

    return $reviews;
}

function normalizeEditors(array $submission, int $submissionId): string
{
    $candidates = [];

    foreach (['sectionEditors', 'assignedEditors', 'editors', 'editorAssignments', 'participants'] as $key) {
        if (!empty($submission[$key]) && is_array($submission[$key])) {
            $candidates = array_merge($candidates, collectRows($submission[$key]));
        }
    }

    $editors = [];

    foreach ($candidates as $candidate) {
        if (!is_array($candidate)) {
            continue;
        }

        $roleText = implode(' ', array_filter([
            localizedValue($candidate['roleName'] ?? ''),
            localizedValue($candidate['userGroupName'] ?? ''),
            localizedValue($candidate['userGroup']['name'] ?? ''),
            localizedValue($candidate['groupName'] ?? ''),
        ]));

        if ($roleText !== '' && !preg_match('/editor/i', $roleText)) {
            continue;
        }

        $user = $candidate['user'] ?? $candidate;
        $name = is_array($user) ? fullNameFromArray($user) : '';

        if ($name && !in_array($name, $editors, true)) {
            $editors[] = $name;
        }
    }

    return $editors ? implode(', ', $editors) : 'Não informado';
}

function fetchAllSubmissions(int $maxRows): array
{
    $submissions = [];
    $count = 20;
    $offset = 0;

    do {
            $url = OJS_API_BASE_URL . '/submissions?count=' . $count . '&offset=' . $offset . '&orderBy=dateSubmitted&orderDirection=DESC';
            $response = requestJson($url);
            $items = $response['items'] ?? [];

            if (!is_array($items) || empty($items)) {
                break;
            }

            foreach ($items as $item) {
                if (is_array($item) && in_array((int) ($item['status'] ?? 0), [1, 5], true)) {
                    $submissions[] = $item;
                    if (count($submissions) >= $maxRows) {
                        break 2;
                    }
                }
            }

            $offset += $count;
            $total = (int) ($response['itemsMax'] ?? $response['total'] ?? count($submissions));
    } while (count($submissions) < $total && count($submissions) < $maxRows && $offset < 1000);

    return $submissions;
}

function fetchPublishedSubmissionsByIssues(int $maxRows): array
{
    $submissions = [];
    $issues = requestJson(OJS_API_BASE_URL . '/issues?count=10&orderBy=datePublished&orderDirection=DESC');

    foreach (($issues['items'] ?? []) as $issue) {
        if (!is_array($issue)) {
            continue;
        }

        $issueId = (int) ($issue['id'] ?? 0);
        if ($issueId <= 0) {
            continue;
        }

        $response = tryJson(OJS_API_BASE_URL . '/submissions?issueIds[]=' . $issueId . '&count=100');
        foreach (($response['items'] ?? []) as $item) {
            if (is_array($item)) {
                $submissions[] = $item;
                if (count($submissions) >= $maxRows) {
                    break 2;
                }
            }
        }
    }

    return $submissions;
}

function normalizeArticleRow(array $submission, int $index): array
{
    $submissionId = (int) ($submission['id'] ?? 0);
    $publication = currentPublication($submission);
    $sectionId = (int) ($publication['sectionId'] ?? $submission['sectionId'] ?? 0);
    global $sectionNames;

    return [
        'id' => $submissionId,
        'number' => (string) ($submissionId ?: $index),
        'title' => localizedValue($publication['fullTitle'] ?? $publication['title'] ?? $submission['title'] ?? '') ?: 'Título não informado',
        'section' => $sectionNames[$sectionId] ?? ($sectionId ? 'Seção #' . $sectionId : 'Não informada'),
        'date_submitted' => formatDate((string) ($submission['dateSubmitted'] ?? $submission['submissionDate'] ?? '')),
        'principal_author' => principalAuthor($publication, $submission),
        'reviews' => normalizeReviews($submission, $submissionId),
        'review_note' => '',
        'section_editor' => normalizeEditors($submission, $submissionId),
    ];
}

$rows = [];
$allRows = [];
$loadError = '';
$perPage = 10;
$currentPage = max(1, (int) ($_GET['pagina'] ?? 1));
$fetchLimit = ($currentPage * $perPage) + 1;
$offset = ($currentPage - 1) * $perPage;
$hasNextPage = false;
$sectionNames = [];

try {
    if (OJS_API_TOKEN !== '') {
    $sectionsResponse = requestJson(OJS_API_BASE_URL . '/sections?count=100');
    foreach (($sectionsResponse['items'] ?? []) as $section) {
        if (is_array($section) && isset($section['id'])) {
            $sectionNames[(int) $section['id']] = localizedValue($section['title'] ?? $section['name'] ?? '');
        }
    }
    foreach (fetchAllSubmissions($fetchLimit) as $index => $submission) {
        $allRows[] = normalizeArticleRow($submission, $index + 1);
    }

    $rows = array_slice($allRows, $offset, $perPage);
    foreach ($rows as &$row) {
        if ($row['id'] <= 0) {
            continue;
        }
        try {
            $details = requestJson(OJS_API_BASE_URL . '/submissions/' . $row['id']);
            $row['reviews'] = normalizeReviews($details, $row['id']);
            if (!array_key_exists('reviewAssignments', $details)
                && !array_key_exists('reviewRounds', $details)) {
                $row['review_note'] = 'Dados de avaliação não disponíveis para esta conta';
            }
            $detailEditor = normalizeEditors($details, $row['id']);
            if ($detailEditor !== 'Não informado') {
                $row['section_editor'] = $detailEditor;
            }
        } catch (Throwable $error) {
            $row['review_note'] = 'Não foi possível consultar os avaliadores';
        }
    }
    unset($row);
    $hasNextPage = count($allRows) > ($offset + $perPage);
    }
} catch (Throwable $error) {
    $loadError = $error->getMessage();
}

function pageUrl(int $page): string
{
    $query = $_GET;
    $query['pagina'] = max(1, $page);
    unset($query['limite']);

    return 'teste_artigos.php?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissões ativas - BJHP</title>
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.css?v=3.3.0.21" type="text/css">
    <link rel="stylesheet" href="/bjhp/css/styles.css">
    <style>
        .teste-artigos-page { max-width: 1280px; margin: 36px auto; padding: 0 20px; }
        .teste-artigos-page h1 { color: #1391b3; font-size: 26px; font-weight: 800; margin: 0 0 18px; text-align: center; text-transform: uppercase; }
        .teste-artigos-note { color: #666; font-size: 13px; margin: 0 0 18px; text-align: center; }
        .teste-artigos-table-wrap { overflow-x: auto; background: #fff; border: 1px solid #dce8ec; }
        .teste-artigos-table { width: 100%; border-collapse: collapse; min-width: 1040px; }
        .teste-artigos-table th,
        .teste-artigos-table td { padding: 12px 14px; border-bottom: 1px solid #e8eef1; vertical-align: top; font-size: 14px; }
        .teste-artigos-table th { background: #1391b3; color: #fff; font-weight: 700; text-align: left; white-space: nowrap; }
        .teste-artigos-table tr:nth-child(even) td { background: #f8fbfc; }
        .teste-artigos-number { width: 80px; font-weight: 700; color: #1391b3; }
        .teste-artigos-title { min-width: 320px; font-weight: 700; color: #111; }
        .review-list { display: grid; gap: 6px; }
        .review-badge { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .review-icon { width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 800; line-height: 1; }
        .review-icon.ok { background: #238636; }
        .review-icon.no { background: #b42318; }
        .review-icon.late { background: #d97706; }
        .teste-artigos-empty { padding: 22px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 2px; color: #664d03; }
        .teste-artigos-pagination { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 18px; }
        .teste-artigos-pagination a,
        .teste-artigos-pagination span { min-width: 38px; padding: 8px 12px; border: 1px solid #cfdde2; border-radius: 2px; background: #fff; color: #1391b3; font-size: 14px; font-weight: 700; text-align: center; }
        .teste-artigos-pagination span.current { background: #1391b3; border-color: #1391b3; color: #fff; }
        .teste-artigos-pagination span.disabled { color: #9aa8ad; background: #f5f7f8; }
    </style>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>

<main class="teste-artigos-page">
    <h1>Submissões ativas</h1>
    <p class="teste-artigos-note">Submissões em andamento, com a seção escolhida pelo autor. Paginação de 10 em 10 registros.</p>

    <?php if ($isLocal): ?>
        <form method="post" action="/bjhp/teste_artigos.php" autocomplete="off" style="margin-bottom:20px">
            <label for="api_token">Chave de API do OJS (guardada apenas nesta sessão local)</label><br>
            <input id="api_token" name="api_token" type="password" size="80" required autocomplete="off">
            <button type="submit">Consultar submissões</button>
        </form>
        <?php if (OJS_API_TOKEN !== ''): ?>
            <form method="post" action="/bjhp/teste_artigos.php" style="margin-bottom:20px">
                <button type="submit" name="forget_token" value="1">Esquecer chave</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (OJS_API_TOKEN === ''): ?>
        <div class="teste-artigos-empty">Insira a chave de API para carregar as submissões.</div>
    <?php elseif ($loadError): ?>
        <div class="teste-artigos-empty">Não foi possível carregar os artigos: <?= e($loadError) ?></div>
    <?php elseif (empty($rows)): ?>
        <div class="teste-artigos-empty">Nenhum artigo foi retornado pela API.</div>
    <?php else: ?>
        <div class="teste-artigos-table-wrap">
            <table class="teste-artigos-table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Título</th>
                        <th>Seção</th>
                        <th>Data de submissão</th>
                        <th>Autor Principal</th>
                        <th>Avaliadores</th>
                        <th>Editor de Seção</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="teste-artigos-number"><?= e($row['number']) ?></td>
                            <td class="teste-artigos-title"><?= e($row['title']) ?></td>
                            <td><?= e($row['section']) ?></td>
                            <td><?= e($row['date_submitted']) ?></td>
                            <td><?= e($row['principal_author']) ?></td>
                            <td>
                                <div class="review-list">
                                    <?php if ($row['review_note'] !== ''): ?>
                                        <span><?= e($row['review_note']) ?></span>
                                    <?php elseif (empty($row['reviews'])): ?>
                                        <span class="review-badge"><span class="review-icon no" title="Sem avaliadores">-</span> Sem avaliadores informados</span>
                                    <?php else: ?>
                                        <?php foreach ($row['reviews'] as $review): ?>
                                            <span class="review-badge">
                                                <span class="review-icon <?= e($review['status']['class']) ?>" title="<?= e($review['status']['label']) ?>"><?= e($review['status']['icon']) ?></span>
                                                <?= e($review['name']) ?> - <?= e($review['status']['label']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= e($row['section_editor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="teste-artigos-pagination" aria-label="Paginação dos artigos">
            <?php if ($currentPage > 1): ?>
                <a href="<?= e(pageUrl($currentPage - 1)) ?>">Anterior</a>
            <?php else: ?>
                <span class="disabled">Anterior</span>
            <?php endif; ?>

            <?php if ($currentPage > 2): ?>
                <a href="<?= e(pageUrl(1)) ?>">1</a>
            <?php endif; ?>

            <?php if ($currentPage > 3): ?>
                <span class="disabled">...</span>
            <?php endif; ?>

            <?php if ($currentPage > 1): ?>
                <a href="<?= e(pageUrl($currentPage - 1)) ?>"><?= e((string) ($currentPage - 1)) ?></a>
            <?php endif; ?>

            <span class="current"><?= e((string) $currentPage) ?></span>

            <?php if ($hasNextPage): ?>
                <a href="<?= e(pageUrl($currentPage + 1)) ?>"><?= e((string) ($currentPage + 1)) ?></a>
                <a href="<?= e(pageUrl($currentPage + 1)) ?>">Próxima</a>
            <?php else: ?>
                <span class="disabled">Próxima</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/jquery.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/popper.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.js?v=3.3.0.21" type="text/javascript"></script>
</body>
</html>
