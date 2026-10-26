<?php

declare(strict_types=1);

const OJS_BASE_URL = 'https://bjhp.crfmg.org.br/crfmg';
const OJS_API_BASE_URL = OJS_BASE_URL . '/api/v1';
define('OJS_API_TOKEN', (string) (getenv('BJHP_OJS_API_TOKEN') ?: ''));

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function absoluteUrl(?string $url): string
{
    if (!$url) {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }

    return rtrim(OJS_BASE_URL, '/') . '/' . ltrim($url, '/');
}

function requestUrl(string $url): array
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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status >= 400) {
            throw new RuntimeException($error ?: 'Não foi possível carregar os dados da edição.');
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
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);

    if ($body === false) {
        throw new RuntimeException('Não foi possível carregar os dados da edição.');
    }

    return json_decode($body, true) ?: [];
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

function localizedHtml($value): string
{
    $html = localizedValue($value);
    $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

    return strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li><a>');
}

function formatIssueDate(array $issue): string
{
    $date = (string) ($issue['datePublished'] ?? $issue['published'] ?? $issue['lastModified'] ?? '');

    if ($date === '') {
        return '';
    }

    try {
        $dateTime = new DateTimeImmutable($date);
    } catch (Throwable $error) {
        return $date;
    }

    $months = [
        1 => 'janeiro',
        2 => 'fevereiro',
        3 => 'março',
        4 => 'abril',
        5 => 'maio',
        6 => 'junho',
        7 => 'julho',
        8 => 'agosto',
        9 => 'setembro',
        10 => 'outubro',
        11 => 'novembro',
        12 => 'dezembro',
    ];

    return 'Publicado em ' . $months[(int) $dateTime->format('n')] . ' ' . $dateTime->format('j') . ', ' . $dateTime->format('Y');
}

function issueCoverFromApi(array $issue): string
{
    foreach (['coverImageUrl', 'coverImage', 'cover'] as $key) {
        if (empty($issue[$key])) {
            continue;
        }

        if (is_string($issue[$key])) {
            return absoluteUrl($issue[$key]);
        }

        if (is_array($issue[$key])) {
            foreach (['pt_BR', 'pt', 'en_US', 'en', 'url'] as $locale) {
                if (!empty($issue[$key][$locale]) && is_string($issue[$key][$locale])) {
                    return absoluteUrl($issue[$key][$locale]);
                }
            }
        }
    }

    return '';
}

function splitIssueIdentification(array $issue): array
{
    $identification = localizedValue($issue['identification'] ?? '');
    $title = localizedValue($issue['title'] ?? '');

    if (strpos($identification, ':') !== false) {
        [$issueLabel, $embeddedTitle] = array_map('trim', explode(':', $identification, 2));
        $identification = $issueLabel;

        if ($title === '') {
            $title = $embeddedTitle;
        }
    }

    return [
        'label' => $identification ?: $title,
        'title' => $title,
    ];
}

function firstGalleyUrl(array $galleys): string
{
    foreach ($galleys as $galley) {
        foreach (['urlPublished', 'urlRemote', 'url'] as $key) {
            if (!empty($galley[$key]) && is_string($galley[$key])) {
                return $galley[$key];
            }
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

function normalizeArticle(array $item): array
{
    $id = (int) ($item['id'] ?? $item['submissionId'] ?? 0);
    $publication = currentPublication($item);
    $title = localizedValue($publication['fullTitle'] ?? $publication['title'] ?? $item['title'] ?? '');
    $authors = [];

    foreach (($publication['authors'] ?? $item['authors'] ?? []) as $author) {
        $name = localizedValue($author['fullName'] ?? $author['preferredPublicName'] ?? '');
        if (!$name) {
            $name = trim(($author['givenName'] ?? '') . ' ' . ($author['familyName'] ?? ''));
        }
        if ($name) {
            $authors[] = $name;
        }
    }

    if (empty($authors) && !empty($publication['authorsString'])) {
        $authors[] = trim((string) $publication['authorsString']);
    }

    $doi = (string) ($publication['doiObject']['doi'] ?? $publication['pub-id::doi'] ?? $publication['doi'] ?? $item['pub-id::doi'] ?? $item['doi'] ?? '');
    $articleUrl = $publication['urlPublished'] ?? $publication['publishedUrl'] ?? $item['urlPublished'] ?? $item['publishedUrl'] ?? $item['url'] ?? '';
    $pdfUrl = firstGalleyUrl($publication['galleys'] ?? $item['galleys'] ?? []);
    $pages = (string) ($publication['pages'] ?? $item['pages'] ?? '');

    return [
        'id' => $id,
        'title' => $title,
        'authors' => implode(', ', $authors),
        'doi' => $doi,
        'article_url' => $id > 0 ? '/bjhp/artigo?id=' . $id : absoluteUrl($articleUrl),
        'pdf_url' => absoluteUrl($pdfUrl ?: $articleUrl),
        'pages' => $pages,
    ];
}

function fetchIssueArticles(int $issueId): array
{
    $endpointCandidates = [
        OJS_API_BASE_URL . '/issues/' . $issueId . '/articles',
        OJS_API_BASE_URL . '/issues/' . $issueId . '/submissions',
        OJS_API_BASE_URL . '/submissions?issueIds[]=' . $issueId . '&count=100',
    ];

    foreach ($endpointCandidates as $url) {
        try {
            $response = requestUrl($url);
            if (!empty($response['items']) && is_array($response['items'])) {
                return array_map('normalizeArticle', $response['items']);
            }
        } catch (Throwable $error) {
            continue;
        }
    }

    return [];
}

function fetchIssueData(int $issueId): array
{
    if ($issueId <= 0) {
        throw new RuntimeException('Edição não informada.');
    }

    try {
        $issue = requestUrl(OJS_API_BASE_URL . '/issues/' . $issueId);
    } catch (Throwable $error) {
        $issues = requestUrl(OJS_API_BASE_URL . '/issues?count=100&orderBy=datePublished&orderDirection=DESC');
        foreach (($issues['items'] ?? []) as $item) {
            if (is_array($item) && (int) ($item['id'] ?? 0) === $issueId) {
                $issue = $item;
                break;
            }
        }
    }

    if (empty($issue) || !is_array($issue)) {
        throw new RuntimeException('Edição não encontrada.');
    }

    $heading = splitIssueIdentification($issue);

    return [
        'issue' => [
            'id' => $issueId,
            'label' => $heading['label'],
            'title' => $heading['title'],
            'date' => formatIssueDate($issue),
            'description' => localizedHtml($issue['description'] ?? $issue['descriptionFull'] ?? $issue['summary'] ?? ''),
            'cover' => issueCoverFromApi($issue),
            'url' => absoluteUrl($issue['publishedUrl'] ?? $issue['urlPublished'] ?? $issue['url'] ?? (OJS_BASE_URL . '/issue/view/' . $issueId)),
            'pdf_url' => firstGalleyUrl($issue['galleys'] ?? $issue['issueGalleys'] ?? []),
        ],
        'articles' => fetchIssueArticles($issueId),
    ];
}

$issueId = (int) ($_GET['id'] ?? 0);
$pageData = [];
$loadError = '';

try {
    $pageData = fetchIssueData($issueId);
} catch (Throwable $error) {
    $loadError = $error->getMessage();
}

$issue = $pageData['issue'] ?? [];
$articles = $pageData['articles'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($issue['label'] ?? 'Edição') . ' - BJHP') ?></title>
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.css?v=3.3.0.21" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/crfmg/$$$call$$$/page/page/css?name=stylesheet" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/public/journals/1/styleSheet.css?d=2026-02-03+13%3A07%3A57" type="text/css">
    <link rel="stylesheet" href="/bjhp/css/styles.css">
</head>
<body dir="ltr">
<?php include __DIR__ . '/header.php'; ?>

<main class="issue-page">
    <?php if ($loadError): ?>
        <section class="issue-error">
            <h2>Edição</h2>
            <div class="edicoes-empty"><?= e($loadError) ?></div>
        </section>
    <?php else: ?>
        <section class="issue-hero">
            <h2><?= e($issue['label'] ?? '') ?></h2>
            <?php if (!empty($issue['date'])): ?>
                <p class="issue-date"><?= e($issue['date']) ?></p>
            <?php endif; ?>

            <div class="issue-overview">
                <div class="issue-description">
                    <?php if (!empty($issue['title'])): ?>
                        <h3><?= e($issue['title']) ?></h3>
                    <?php endif; ?>

                    <?php if (!empty($issue['description'])): ?>
                        <div class="issue-description-text"><?= $issue['description'] ?></div>
                    <?php else: ?>
                        <div class="issue-description-text">
                            <p>Consulte abaixo os artigos publicados nesta edição da Brazilian Journal of Health and Pharmacy.</p>
                        </div>
                    <?php endif; ?>

                    <div class="issue-complete">
                        <h3>Edição completa</h3>
                        <a class="button issue-pdf-button" href="<?= e(absoluteUrl($issue['pdf_url'] ?: $issue['url'])) ?>" target="_blank" rel="noopener">PDF</a>
                    </div>
                </div>

                <?php if (!empty($issue['cover'])): ?>
                    <aside class="issue-cover-panel">
                        <img src="<?= e($issue['cover']) ?>" alt="Capa da edição <?= e($issue['label'] ?? '') ?>">
                    </aside>
                <?php endif; ?>
            </div>
        </section>

        <section class="issue-articles-section">
            <h2>Artigos</h2>

            <?php if (empty($articles)): ?>
                <div class="edicoes-empty">Nenhum artigo foi encontrado para esta edição.</div>
            <?php else: ?>
                <div class="issue-articles-list">
                    <?php foreach ($articles as $article): ?>
                        <article class="issue-article">
                            <div class="issue-article-content">
                                <?php if (!empty($article['authors'])): ?>
                                    <p class="issue-article-authors"><?= e($article['authors']) ?></p>
                                <?php endif; ?>
                                <h3>
                                    <a href="<?= e(($article['id'] ?? 0) > 0 ? $article['article_url'] . '&issue=' . (int) ($issue['id'] ?? 0) : ($article['article_url'] ?: $article['pdf_url'])) ?>">
                                        <?= e($article['title'] ?: 'Título não informado') ?>
                                    </a>
                                </h3>
                                <?php if (!empty($article['pdf_url'])): ?>
                                    <a class="button issue-pdf-button" href="<?= e($article['pdf_url']) ?>" target="_blank" rel="noopener">PDF</a>
                                <?php endif; ?>
                                <?php if (!empty($article['doi'])): ?>
                                    <p class="issue-article-doi"><strong>DOI:</strong> <a href="https://doi.org/<?= e($article['doi']) ?>" target="_blank" rel="noopener">https://doi.org/<?= e($article['doi']) ?></a></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($article['pages'])): ?>
                                <p class="issue-article-pages"><?= e($article['pages']) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/jquery.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/popper.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/js/main.js?v=3.3.0.21" type="text/javascript"></script>
</body>
</html>
