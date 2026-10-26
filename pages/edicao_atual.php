<?php

declare(strict_types=1);

const CURRENT_OJS_BASE_URL = 'https://bjhp.crfmg.org.br/crfmg';
const CURRENT_OJS_API_URL = CURRENT_OJS_BASE_URL . '/api/v1';
define('CURRENT_OJS_API_TOKEN', (string) (getenv('BJHP_OJS_API_TOKEN') ?: ''));

function currentE(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function currentArrayIsList(array $value): bool
{
    $expectedKey = 0;
    foreach ($value as $key => $_item) {
        if ($key !== $expectedKey) {
            return false;
        }
        $expectedKey++;
    }
    return true;
}

function currentAbsoluteUrl(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    return rtrim(CURRENT_OJS_BASE_URL, '/') . '/' . ltrim($url, '/');
}

function currentRequestJson(string $url): array
{
    $headers = ['Accept: application/json'];
    if (CURRENT_OJS_API_TOKEN !== '') {
        $headers[] = 'Authorization: Bearer ' . CURRENT_OJS_API_TOKEN;
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'BJHP-Portal/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException($error ?: 'A API do OJS não respondeu à consulta.');
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('A API do OJS retornou dados inválidos.');
    }
    return $data;
}

function currentLocalized($value, array $locales = ['pt_BR', 'pt', 'en_US', 'en']): string
{
    if (is_string($value)) {
        return trim($value);
    }
    if (!is_array($value)) {
        return '';
    }
    foreach ($locales as $locale) {
        if (!empty($value[$locale]) && is_string($value[$locale])) {
            return trim($value[$locale]);
        }
    }
    foreach ($value as $item) {
        if (is_string($item) && trim($item) !== '') {
            return trim($item);
        }
    }
    return '';
}

function currentLocalizedHtml($value): string
{
    return currentLocalized($value);
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
        $currentId = (int) ($submission['currentPublicationId'] ?? 0);
        foreach ($submission['publications'] as $publication) {
            if (is_array($publication) && (int) ($publication['id'] ?? 0) === $currentId) {
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

function currentFirstGalleyUrl(array $galleys): string
{
    foreach ($galleys as $galley) {
        if (!is_array($galley)) {
            continue;
        }
        foreach (['urlPublished', 'urlRemote', 'url'] as $key) {
            if (!empty($galley[$key]) && is_string($galley[$key])) {
                return currentAbsoluteUrl($galley[$key]);
            }
        }
    }
    return '';
}

function currentCover(array $data): string
{
    foreach (['coverImageUrl', 'coverImage', 'cover'] as $key) {
        $cover = $data[$key] ?? null;
        if (is_string($cover) && $cover !== '') {
            return currentAbsoluteUrl($cover);
        }
        if (is_array($cover)) {
            foreach (['pt_BR', 'pt', 'en_US', 'en', 'url'] as $locale) {
                $item = $cover[$locale] ?? null;
                if (is_string($item) && $item !== '') {
                    return currentAbsoluteUrl($item);
                }
                if (is_array($item) && !empty($item['url'])) {
                    return currentAbsoluteUrl((string) $item['url']);
                }
                if (is_array($item) && !empty($item['uploadName'])) {
                    return 'https://bjhp.crfmg.org.br/public/journals/1/' . rawurlencode((string) $item['uploadName']);
                }
            }
        }
    }
    return '';
}

function currentFormatDate(string $date): string
{
    if ($date === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($date))->format('d/m/Y');
    } catch (Throwable $error) {
        return $date;
    }
}

function currentNormalizeArticle(array $submission, array $issue): array
{
    $publication = currentPublication($submission);
    $id = (int) ($submission['id'] ?? $submission['submissionId'] ?? 0);
    $authors = [];
    foreach (($publication['authors'] ?? $submission['authors'] ?? []) as $author) {
        if (!is_array($author)) {
            continue;
        }
        $name = currentLocalized($author['fullName'] ?? $author['preferredPublicName'] ?? '');
        if ($name === '') {
            $name = trim(currentLocalized($author['givenName'] ?? '') . ' ' . currentLocalized($author['familyName'] ?? ''));
        }
        if ($name !== '') {
            $authors[] = $name;
        }
    }
    if (!$authors && !empty($publication['authorsString'])) {
        $authors[] = trim((string) $publication['authorsString']);
    }

    $remoteUrl = (string) ($publication['urlPublished'] ?? $publication['publishedUrl'] ?? $submission['urlPublished'] ?? $submission['url'] ?? '');
    $pdfUrl = currentFirstGalleyUrl($publication['galleys'] ?? $submission['galleys'] ?? []);

    return [
        'id' => $id,
        'title' => currentLocalized($publication['fullTitle'] ?? $publication['title'] ?? $submission['title'] ?? ''),
        'authors' => implode(', ', $authors),
        'section' => currentLocalized($publication['sectionTitle'] ?? $submission['sectionTitle'] ?? '') ?: 'Artigos',
        'abstract' => currentLocalizedHtml($publication['abstract'] ?? $submission['abstract'] ?? ''),
        'cover' => currentCover($publication) ?: currentCover($submission) ?: currentCover($issue),
        'url' => $id > 0 ? '/bjhp/artigo?id=' . $id . '&issue=' . (int) ($issue['id'] ?? 0) : currentAbsoluteUrl($remoteUrl),
        'pdf_url' => $pdfUrl ?: currentAbsoluteUrl($remoteUrl),
        'pages' => (string) ($publication['pages'] ?? $submission['pages'] ?? ''),
    ];
}

function currentResolvePublicIssueId(): int
{
    $curl = curl_init(CURRENT_OJS_BASE_URL . '/issue/current');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'BJHP-Portal/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $body = (string) curl_exec($curl);
    $effectiveUrl = (string) curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
    curl_close($curl);

    foreach ([$effectiveUrl, $body] as $value) {
        if (preg_match('#/issue/view/(\d+)#', $value, $matches)) {
            return (int) $matches[1];
        }
    }
    return 0;
}

function currentFetchArticles(array $issue): array
{
    $issueId = (int) ($issue['id'] ?? 0);
    $candidates = [
        CURRENT_OJS_API_URL . '/issues/' . $issueId . '/articles',
        CURRENT_OJS_API_URL . '/issues/' . $issueId . '/submissions',
        CURRENT_OJS_API_URL . '/submissions?issueIds[]=' . $issueId . '&count=100',
    ];
    foreach ($candidates as $url) {
        try {
            $response = currentRequestJson($url);
            $items = $response['items'] ?? (currentArrayIsList($response) ? $response : []);
            if ($items) {
                return array_map(fn(array $item): array => currentNormalizeArticle($item, $issue), array_filter($items, 'is_array'));
            }
        } catch (Throwable $error) {
            continue;
        }
    }
    return [];
}

function currentFetchIssue(): array
{
    $issue = null;

    // O contexto da revista informa qual edição foi marcada como "atual" no OJS.
    // Se esse dado não estiver acessível nesta versão, usamos a última publicada.
    try {
        $contextsResponse = currentRequestJson(CURRENT_OJS_API_URL . '/contexts?count=100');
        $contexts = $contextsResponse['items'] ?? (currentArrayIsList($contextsResponse) ? $contextsResponse : [$contextsResponse]);
        foreach ($contexts as $context) {
            if (!is_array($context)) {
                continue;
            }
            $path = (string) ($context['urlPath'] ?? $context['path'] ?? '');
            if (($path === 'crfmg' || count($contexts) === 1) && !empty($context['currentIssueId'])) {
                $issue = currentRequestJson(CURRENT_OJS_API_URL . '/issues/' . (int) $context['currentIssueId']);
                break;
            }
        }
    } catch (Throwable $error) {
        $issue = null;
    }

    if (!is_array($issue) || empty($issue['id'])) {
        $publicCurrentIssueId = currentResolvePublicIssueId();
        if ($publicCurrentIssueId > 0) {
            try {
                $issue = currentRequestJson(CURRENT_OJS_API_URL . '/issues/' . $publicCurrentIssueId);
            } catch (Throwable $error) {
                $issue = null;
            }
        }
    }

    if (!is_array($issue) || empty($issue['id'])) {
        $response = currentRequestJson(CURRENT_OJS_API_URL . '/issues?count=1&orderBy=datePublished&orderDirection=DESC');
        $issue = $response['items'][0] ?? null;
    }

    if (!is_array($issue) || empty($issue['id'])) {
        throw new RuntimeException('Nenhuma edição publicada foi retornada pela API.');
    }

    $identification = currentLocalized($issue['identification'] ?? '');
    $title = currentLocalized($issue['title'] ?? '');
    if (strpos($identification, ':') !== false) {
        [$label, $embeddedTitle] = array_map('trim', explode(':', $identification, 2));
        $identification = $label;
        $title = $title ?: $embeddedTitle;
    }

    return [
        'id' => (int) $issue['id'],
        'identification' => $identification,
        'title' => $title,
        'date' => currentFormatDate((string) ($issue['datePublished'] ?? $issue['published'] ?? '')),
        'description' => currentLocalizedHtml($issue['description'] ?? $issue['descriptionFull'] ?? $issue['summary'] ?? ''),
        'cover' => currentCover($issue),
        'url' => currentAbsoluteUrl((string) ($issue['publishedUrl'] ?? $issue['urlPublished'] ?? $issue['url'] ?? (CURRENT_OJS_BASE_URL . '/issue/view/' . $issue['id']))),
        'articles' => currentFetchArticles($issue),
    ];
}

$currentIssue = null;
$currentIssueError = '';
try {
    $currentIssue = currentFetchIssue();
} catch (Throwable $error) {
    $currentIssueError = $error->getMessage();
}

$articleSections = [];
if ($currentIssue) {
    foreach ($currentIssue['articles'] as $article) {
        $articleSections[$article['section']][] = $article;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edição Atual - BJHP</title>
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.css?v=3.3.0.21" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/crfmg/$$$call$$$/page/page/css?name=stylesheet" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/public/journals/1/styleSheet.css?d=2026-02-03+13%3A07%3A57" type="text/css">
    <link rel="stylesheet" href="/bjhp/css/styles.css">
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>

<main class="current-issue-page">
    <section class="current_issue">
        <h2 class="headings">Edição Atual</h2>

        <?php if ($currentIssueError !== ''): ?>
            <div class="current-issue-message" role="alert"><?= currentE($currentIssueError) ?></div>
        <?php elseif ($currentIssue): ?>
            <p class="current_issue_title lead">
                <?= currentE(trim($currentIssue['identification'] . ($currentIssue['title'] ? ': ' . $currentIssue['title'] : ''))) ?>
            </p>

            <div class="issue-toc">
                <div class="current-issue-heading">
                    <?php if ($currentIssue['cover']): ?>
                        <div class="current-issue-thumbnail">
                            <a href="<?= currentE($currentIssue['url']) ?>" target="_blank" rel="noopener">
                                <img src="<?= currentE($currentIssue['cover']) ?>" alt="Capa da edição atual">
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="current-issue-description">
                        <?php if ($currentIssue['date']): ?>
                            <p class="published"><strong>Publicado em:</strong> <?= currentE($currentIssue['date']) ?></p>
                        <?php endif; ?>
                        <?= $currentIssue['description'] ?: '<p>Descrição da edição não informada.</p>' ?>
                    </div>
                </div>

                <div class="current-issue-sections">
                    <?php if (!$articleSections): ?>
                        <div class="current-issue-message">Nenhum artigo foi retornado pela API para esta edição.</div>
                    <?php endif; ?>

                    <?php foreach ($articleSections as $sectionTitle => $articles): ?>
                        <section class="current-issue-article-section">
                            <h2 class="current_issue_title lead"><?= currentE($sectionTitle) ?></h2>
                            <div class="current-issue-media-list">
                                <?php foreach ($articles as $article): ?>
                                    <article class="current-article-summary">
                                        <?php if ($article['cover']): ?>
                                            <a class="current-article-cover" href="<?= currentE($article['url']) ?>">
                                                <img src="<?= currentE($article['cover']) ?>" alt="">
                                            </a>
                                        <?php endif; ?>
                                        <div class="current-article-body">
                                            <h3><a href="<?= currentE($article['url']) ?>"><?= currentE($article['title'] ?: 'Título não informado') ?></a></h3>
                                            <p class="current-article-authors"><?= currentE($article['authors'] ?: 'Autoria não informada') ?></p>
                                            <p class="current-article-meta">
                                                <a href="<?= currentE($article['url']) ?>">Resumo</a>
                                                <?php if ($article['pages']): ?><span aria-hidden="true">|</span> <span>Páginas <?= currentE($article['pages']) ?></span><?php endif; ?>
                                            </p>
                                        </div>
                                        <?php if ($article['pdf_url']): ?>
                                            <a class="current-galley-link" href="<?= currentE($article['pdf_url']) ?>" target="_blank" rel="noopener">PDF</a>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="/bjhp/edicoes" class="current-all-issues">Ver todas as edições <span aria-hidden="true">›</span></a>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/jquery.min.js?v=3.3.0.21"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/popper.min.js?v=3.3.0.21"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.js?v=3.3.0.21"></script>
</body>
</html>
