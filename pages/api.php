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

function requestUrl(string $url, bool $decodeJson = false): array
{
    $headers = [
        'Accept: ' . ($decodeJson ? 'application/json' : 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'),
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
            return ['ok' => false, 'status' => $status, 'body' => $body ?: $error, 'data' => null];
        }

        return [
            'ok' => true,
            'status' => $status,
            'body' => (string) $body,
            'data' => $decodeJson ? json_decode((string) $body, true) : null,
        ];
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
    $status = 0;

    if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
        $status = (int) $match[1];
    }

    if ($body === false || $status >= 400) {
        return ['ok' => false, 'status' => $status, 'body' => $body ?: '', 'data' => null];
    }

    return [
        'ok' => true,
        'status' => $status,
        'body' => $body,
        'data' => $decodeJson ? json_decode($body, true) : null,
    ];
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

        if (is_array($item)) {
            foreach (['text', 'value', 'name'] as $key) {
                if (!empty($item[$key]) && is_string($item[$key])) {
                    return trim($item[$key]);
                }
            }
        }
    }

    return '';
}

function metaContent(DOMXPath $xpath, string $selector): string
{
    $nodes = $xpath->query($selector);
    if (!$nodes || $nodes->length === 0) {
        return '';
    }

    return trim((string) $nodes->item(0)->getAttribute('content'));
}

function loadDom(string $html): DOMXPath
{
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);

    libxml_clear_errors();

    return new DOMXPath($dom);
}

function textFromNode(?DOMNode $node): string
{
    if (!$node) {
        return '';
    }

    return trim(preg_replace('/\s+/', ' ', html_entity_decode($node->textContent, ENT_QUOTES, 'UTF-8')));
}

function fetchLatestIssueFromApi(): array
{
    $issuesUrl = OJS_API_BASE_URL . '/issues?count=1&orderBy=datePublished&orderDirection=DESC';
    $issuesResponse = requestUrl($issuesUrl, true);

    if (!$issuesResponse['ok'] || empty($issuesResponse['data']['items'][0])) {
        throw new RuntimeException('API REST do OJS indisponível ou sem autorização.');
    }

    $issue = $issuesResponse['data']['items'][0];
    $issueId = (int) ($issue['id'] ?? 0);

    if ($issueId <= 0) {
        throw new RuntimeException('A API retornou uma edição sem ID.');
    }

    $articles = [];
    $articleEndpointCandidates = [
        OJS_API_BASE_URL . '/issues/' . $issueId . '/articles',
        OJS_API_BASE_URL . '/issues/' . $issueId . '/submissions',
        OJS_API_BASE_URL . '/submissions?issueIds[]=' . $issueId,
    ];

    foreach ($articleEndpointCandidates as $url) {
        $response = requestUrl($url, true);
        if ($response['ok'] && !empty($response['data']['items']) && is_array($response['data']['items'])) {
            $articles = normalizeApiArticles($response['data']['items'], $issue);
            break;
        }
    }

    return [
        'source' => 'API REST PKP/OJS',
        'issue' => [
            'id' => $issueId,
            'title' => localizedValue($issue['title'] ?? ''),
            'url' => absoluteUrl($issue['publishedUrl'] ?? $issue['urlPublished'] ?? $issue['url'] ?? (OJS_BASE_URL . '/issue/view/' . $issueId)),
            'cover' => issueCoverFromApi($issue),
        ],
        'articles' => $articles,
    ];
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
                if (!empty($issue[$key][$locale])) {
                    return absoluteUrl((string) $issue[$key][$locale]);
                }
            }
        }
    }

    return '';
}

function normalizeApiArticles(array $items, array $issue): array
{
    $articles = [];

    foreach ($items as $item) {
        $publication = currentPublication($item);
        $titles = [
            'pt' => localizedValue($publication['fullTitle'] ?? $publication['title'] ?? $item['title'] ?? '', ['pt_BR', 'pt']),
            'en' => localizedValue($publication['fullTitle'] ?? $publication['title'] ?? $item['title'] ?? '', ['en_US', 'en']),
        ];

        if ($titles['en'] === $titles['pt']) {
            $titles['en'] = localizedValue($publication['subtitle'] ?? '', ['en_US', 'en']);
        }

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

        $doi = $publication['doiObject']['doi'] ?? $publication['pub-id::doi'] ?? $publication['doi'] ?? $item['pub-id::doi'] ?? $item['doi'] ?? '';
        $url = $publication['urlPublished'] ?? $publication['publishedUrl'] ?? $item['urlPublished'] ?? $item['publishedUrl'] ?? $item['url'] ?? '';
        $galleyUrl = firstGalleyUrl($publication['galleys'] ?? $item['galleys'] ?? []);

        $articles[] = [
            'cover' => articleCoverFromApi($item, $issue),
            'title_pt' => $titles['pt'],
            'title_en' => $titles['en'],
            'authors' => implode(', ', $authors),
            'doi' => (string) $doi,
            'manuscript_url' => absoluteUrl($galleyUrl ?: $url),
        ];
    }

    return $articles;
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

function articleCoverFromApi(array $article, array $issue): string
{
    $publication = currentPublication($article);

    foreach (['coverImageUrl', 'coverImage'] as $key) {
        $coverImage = $publication[$key] ?? $article[$key] ?? null;

        if (empty($coverImage)) {
            continue;
        }

        if (is_string($coverImage)) {
            return absoluteUrl($coverImage);
        }

        if (is_array($coverImage)) {
            foreach (['pt_BR', 'pt', 'en_US', 'en'] as $locale) {
                if (empty($coverImage[$locale]) || !is_array($coverImage[$locale])) {
                    continue;
                }

                if (!empty($coverImage[$locale]['url'])) {
                    return absoluteUrl((string) $coverImage[$locale]['url']);
                }

                if (!empty($coverImage[$locale]['uploadName'])) {
                    return 'https://bjhp.crfmg.org.br/public/journals/1/' . rawurlencode((string) $coverImage[$locale]['uploadName']);
                }
            }
        }
    }

    return issueCoverFromApi($issue);
}

function fetchLatestIssueFromPublicPages(): array
{
    $archive = requestUrl(OJS_BASE_URL . '/issue/archive');
    if (!$archive['ok']) {
        throw new RuntimeException('Não foi possível acessar o arquivo público de edições.');
    }

    $archiveXpath = loadDom($archive['body']);
    $issueLinkNode = $archiveXpath->query("//div[contains(@class,'issue-summary')]//h2[contains(@class,'issue-summary-series')]//a")->item(0);
    $issueCoverNode = $archiveXpath->query("//div[contains(@class,'issue-summary')]//img[contains(@class,'issue-summary-cover')]")->item(0);

    if (!$issueLinkNode instanceof DOMElement) {
        throw new RuntimeException('Nenhuma edição foi encontrada no arquivo público.');
    }

    $issueUrl = absoluteUrl($issueLinkNode->getAttribute('href'));
    $issueTitle = textFromNode($issueLinkNode);
    $issueCover = $issueCoverNode instanceof DOMElement ? absoluteUrl($issueCoverNode->getAttribute('src')) : '';

    $issuePage = requestUrl($issueUrl);
    if (!$issuePage['ok']) {
        throw new RuntimeException('Não foi possível acessar a última edição.');
    }

    $issueXpath = loadDom($issuePage['body']);
    $pageCover = $issueXpath->query("//img[contains(@class,'page-issue-cover')]")->item(0);
    if ($pageCover instanceof DOMElement) {
        $issueCover = absoluteUrl($pageCover->getAttribute('src'));
    }

    $articleNodes = $issueXpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' article-summary ')]");
    $articles = [];

    foreach ($articleNodes as $articleNode) {
        $article = parsePublicArticleSummary($issueXpath, $articleNode, $issueCover);
        if (!empty($article['title_pt']) || !empty($article['title_en'])) {
            $articles[] = $article;
        }
    }

    return [
        'source' => 'Páginas públicas OJS (fallback; API REST retornou 403 sem token)',
        'issue' => [
            'id' => '',
            'title' => $issueTitle,
            'url' => $issueUrl,
            'cover' => $issueCover,
        ],
        'articles' => $articles,
    ];
}

function parsePublicArticleSummary(DOMXPath $xpath, DOMNode $articleNode, string $issueCover): array
{
    $titleLink = $xpath->query(".//div[contains(@class,'article-summary-title')]//a", $articleNode)->item(0);
    $articleUrl = $titleLink instanceof DOMElement ? absoluteUrl($titleLink->getAttribute('href')) : '';
    $summaryTitle = textFromNode($titleLink);
    $authors = textFromNode($xpath->query(".//div[contains(@class,'article-summary-authors')]", $articleNode)->item(0));
    $doi = textFromNode($xpath->query(".//div[contains(@class,'article-summary-doi')]//a", $articleNode)->item(0));
    $galley = $xpath->query(".//div[contains(@class,'article-summary-galleys')]//a", $articleNode)->item(0);
    $manuscriptUrl = $galley instanceof DOMElement ? absoluteUrl($galley->getAttribute('href')) : $articleUrl;

    $details = $articleUrl ? fetchPublicArticleDetails($articleUrl) : [];

    return [
        'cover' => $details['cover'] ?? $issueCover,
        'title_pt' => $details['title_pt'] ?? $summaryTitle,
        'title_en' => $details['title_en'] ?? '',
        'authors' => $authors,
        'doi' => $details['doi'] ?? $doi,
        'manuscript_url' => $manuscriptUrl,
    ];
}

function fetchPublicArticleDetails(string $articleUrl): array
{
    $response = requestUrl($articleUrl);
    if (!$response['ok']) {
        return [];
    }

    $xpath = loadDom($response['body']);
    $dcTitle = metaContent($xpath, "//meta[@name='DC.Title']");
    $titlePt = metaContent($xpath, "//meta[@name='DC.Title.Alternative' and (@xml:lang='pt' or @lang='pt')]");
    $titleEn = metaContent($xpath, "//meta[@name='DC.Title.Alternative' and (@xml:lang='en' or @lang='en')]");
    $citationTitle = metaContent($xpath, "//meta[@name='citation_title']");
    $citationLanguage = metaContent($xpath, "//meta[@name='citation_language']");
    $cover = metaContent($xpath, "//meta[@property='og:image' or @name='image']");
    $doi = metaContent($xpath, "//meta[@name='citation_doi']");

    if (!$titlePt && $citationLanguage !== 'en') {
        $titlePt = $dcTitle ?: $citationTitle;
    }

    if (!$titleEn && $citationLanguage === 'en') {
        $titleEn = $citationTitle;
    }

    if (!$titleEn && $dcTitle && $dcTitle !== $titlePt) {
        $titleEn = $dcTitle;
    }

    return [
        'title_pt' => $titlePt ?: $dcTitle ?: $citationTitle,
        'title_en' => $titleEn,
        'cover' => absoluteUrl($cover),
        'doi' => $doi,
    ];
}

function loadLatestIssue(): array
{
    try {
        return fetchLatestIssueFromApi();
    } catch (Throwable $apiError) {
        try {
            $fallback = fetchLatestIssueFromPublicPages();
            $fallback['warning'] = $apiError->getMessage();
            return $fallback;
        } catch (Throwable $fallbackError) {
            return [
                'source' => 'Indisponível',
                'warning' => $apiError->getMessage(),
                'error' => $fallbackError->getMessage(),
                'issue' => [
                    'id' => '',
                    'title' => 'Última edição indisponível',
                    'url' => OJS_BASE_URL . '/issue/archive',
                    'cover' => '',
                ],
                'articles' => [],
            ];
        }
    }
}

$pageData = loadLatestIssue();
$issue = $pageData['issue'];
$articles = $pageData['articles'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Última edição - BJHP</title>
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.css?v=3.3.0.21" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/crfmg/$$$call$$$/page/page/css?name=stylesheet" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/public/journals/1/styleSheet.css?d=2026-02-03+13%3A07%3A57" type="text/css">
    <link rel="stylesheet" href="/bjhp/css/styles.css">
    <style>
        .api-page { max-width: 1180px; margin: 40px auto; padding: 0 20px; }
        .api-issue-header { display: grid; grid-template-columns: 220px 1fr; gap: 30px; align-items: center; margin-bottom: 35px; }
        .api-issue-cover { width: 100%; max-width: 220px; border-radius: 2px; box-shadow: 0 8px 24px rgba(0,0,0,.16); }
        .api-source-note { color: #666; font-size: 13px; margin-top: 8px; }
        .api-article-grid { display: grid; gap: 22px; }
        .api-article-card { display: grid; grid-template-columns: 140px 1fr; gap: 22px; padding: 22px; border: 1px solid #e5e5e5; border-radius: 2px; background: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
        .api-article-cover { width: 140px; max-height: 190px; object-fit: cover; border-radius: 2px; }
        .api-article-title { color: #1391b3; font-size: 20px; font-weight: 700; margin: 0 0 8px; }
        .api-article-title-en { color: #333; font-size: 16px; font-weight: 600; margin: 0 0 12px; }
        .api-meta { margin: 0 0 8px; }
        .api-actions { margin-top: 14px; }
        .api-empty { padding: 22px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 2px; }
        @media (max-width: 760px) {
            .api-issue-header,
            .api-article-card { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body dir="ltr">
<?php include __DIR__ . '/header.php'; ?>

<main class="api-page">
    <section>
        <h2>Última edição</h2>
        <div class="api-issue-header">
            <div>
                <?php if (!empty($issue['cover'])): ?>
                    <img class="api-issue-cover" src="<?= e($issue['cover']) ?>" alt="Capa da edição <?= e($issue['title']) ?>">
                <?php endif; ?>
            </div>
            <div>
                <h3><?= e($issue['title']) ?></h3>
                <p><a class="button" href="<?= e($issue['url']) ?>" target="_blank" rel="noopener">Acessar outras edições</a></p>
                <p class="api-source-note">Fonte dos dados: <?= e($pageData['source']) ?>.</p>
                <?php if (!empty($pageData['warning'])): ?>
                    <p class="api-source-note">Observação: <?= e($pageData['warning']) ?> Para usar a API REST diretamente, configure o token em <code>OJS_API_TOKEN</code>.</p>
                <?php endif; ?>
                <?php if (!empty($pageData['error'])): ?>
                    <div class="api-empty">Não foi possível carregar os dados do OJS neste servidor: <?= e($pageData['error']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section>
        <h2>Artigos da edição</h2>

        <?php if (empty($articles)): ?>
            <div class="api-empty">Nenhum artigo foi retornado para a última edição.</div>
        <?php else: ?>
            <div class="api-article-grid">
                <?php foreach ($articles as $article): ?>
                    <article class="api-article-card">
                        <div>
                            <?php if (!empty($article['cover'])): ?>
                                <img class="api-article-cover" src="<?= e($article['cover']) ?>" alt="Imagem do artigo">
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 class="api-article-title"><?= e($article['title_pt'] ?: 'Título não informado') ?></h3>
                            <?php if (!empty($article['title_en'])): ?>
                                <p class="api-article-title-en"><?= e($article['title_en']) ?></p>
                            <?php endif; ?>
                            <p class="api-meta"><strong>Autores:</strong> <?= e($article['authors'] ?: 'Não informado') ?></p>
                            <p class="api-meta"><strong>DOI:</strong> <?= e($article['doi'] ?: 'Não informado') ?></p>
                            <?php if (!empty($article['manuscript_url'])): ?>
                                <p class="api-actions"><a class="button" href="<?= e($article['manuscript_url']) ?>" target="_blank" rel="noopener">Acessar manuscrito</a></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/jquery.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/popper.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/js/main.js?v=3.3.0.21" type="text/javascript"></script>
</body>
</html>
