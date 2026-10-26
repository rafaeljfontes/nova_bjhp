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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status >= 400) {
            throw new RuntimeException($error ?: 'Não foi possível carregar os dados do artigo.');
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
        throw new RuntimeException('Não foi possível carregar os dados do artigo.');
    }

    return json_decode($body, true) ?: [];
}

function requestHtml(string $url): string
{
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'User-Agent: BJHP-Portal/1.0',
    ];

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
        curl_close($curl);

        if ($body === false || $status >= 400) {
            return '';
        }

        return (string) $body;
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

    return (string) @file_get_contents($url, false, $context);
}

function loadDom(string $html): ?DOMXPath
{
    if ($html === '') {
        return null;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();

    return $loaded ? new DOMXPath($dom) : null;
}

function metaContent(?DOMXPath $xpath, string $selector): string
{
    if (!$xpath) {
        return '';
    }

    $nodes = $xpath->query($selector);
    if (!$nodes || $nodes->length === 0 || !$nodes->item(0) instanceof DOMElement) {
        return '';
    }

    return trim((string) $nodes->item(0)->getAttribute('content'));
}

function textFromNode(?DOMNode $node): string
{
    if (!$node) {
        return '';
    }

    return trim(preg_replace('/\s+/', ' ', html_entity_decode($node->textContent, ENT_QUOTES, 'UTF-8')));
}

function fetchPublicArticleMetadata(string $articleUrl): array
{
    if ($articleUrl === '') {
        return [];
    }

    $xpath = loadDom(requestHtml($articleUrl));
    if (!$xpath) {
        return [];
    }

    $abstract = metaContent($xpath, "//meta[@name='DC.Description' or @name='citation_abstract' or @property='og:description']");
    $keywords = metaContent($xpath, "//meta[@name='keywords' or @name='citation_keywords' or @name='DC.Subject']");
    $cover = metaContent($xpath, "//meta[@property='og:image' or @name='image']");
    $citation = metaContent($xpath, "//meta[@name='citation_reference']");

    if ($abstract === '') {
        $abstract = textFromNode($xpath->query("//section[contains(@class,'item') and contains(@class,'abstract')]//p | //div[contains(@class,'abstract')]//p")->item(0));
    }

    if ($keywords === '') {
        $keywords = textFromNode($xpath->query("//section[contains(@class,'item') and contains(@class,'keywords')] | //div[contains(@class,'keywords')]")->item(0));
    }

    return [
        'abstract' => $abstract ? '<p>' . e($abstract) . '</p>' : '',
        'keywords' => normalizeKeywords($keywords),
        'cover' => absoluteUrl($cover),
        'citation' => $citation,
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
    }

    return '';
}

function localizedHtml($value): string
{
    $html = localizedValue($value);
    $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

    return strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li>');
}

function plainText(string $html): string
{
    return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));
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

function formatDateValue(string $date): string
{
    if ($date === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($date))->format('Y-m-d');
    } catch (Throwable $error) {
        return $date;
    }
}

function splitIssueIdentification(array $issue): string
{
    $identification = localizedValue($issue['identification'] ?? '');

    if (strpos($identification, ':') !== false) {
        return trim(explode(':', $identification, 2)[0]);
    }

    return $identification ?: localizedValue($issue['title'] ?? '');
}

function normalizeKeywords($keywords): array
{
    if (empty($keywords)) {
        return [];
    }

    if (is_string($keywords)) {
        $keywords = preg_replace('/^\s*(Palavras-chave|Keywords)\s*/iu', '', $keywords);
        return array_filter(array_map('trim', preg_split('/[,;]+/', $keywords)));
    }

    if (!is_array($keywords)) {
        return [];
    }

    $localized = $keywords['pt_BR'] ?? $keywords['pt'] ?? $keywords['en_US'] ?? $keywords['en'] ?? $keywords;
    if (is_string($localized)) {
        $localized = preg_replace('/^\s*(Palavras-chave|Keywords)\s*/iu', '', $localized);
        return array_filter(array_map('trim', preg_split('/[,;]+/', $localized)));
    }

    if (is_array($localized)) {
        $terms = [];
        foreach ($localized as $item) {
            if (is_string($item) && trim($item) !== '') {
                $terms[] = trim($item);
            }
        }
        return $terms;
    }

    return [];
}

function normalizeAuthors(array $publication, array $article): array
{
    $authors = [];

    foreach (($publication['authors'] ?? $article['authors'] ?? []) as $author) {
        if (!is_array($author)) {
            continue;
        }

        $name = localizedValue($author['fullName'] ?? $author['preferredPublicName'] ?? '');
        if (!$name) {
            $name = trim(($author['givenName'] ?? '') . ' ' . ($author['familyName'] ?? ''));
        }

        if ($name) {
            $authors[] = [
                'name' => $name,
                'orcid' => (string) ($author['orcid'] ?? ''),
            ];
        }
    }

    if (empty($authors) && !empty($publication['authorsString'])) {
        foreach (array_filter(array_map('trim', explode(',', (string) $publication['authorsString']))) as $name) {
            $authors[] = ['name' => $name, 'orcid' => ''];
        }
    }

    return $authors;
}

function buildCitation(array $article, array $issue): string
{
    $authorNames = array_map(static fn(array $author): string => $author['name'], $article['authors']);
    $authors = implode(', ', $authorNames);
    $year = substr($article['date'], 0, 4);
    $parts = array_filter([
        $authors,
        $article['title'],
        'Brazilian Journal of Health and Pharmacy',
        $issue['label'],
        $article['pages'],
        $article['doi'] ? 'https://doi.org/' . $article['doi'] : '',
    ]);

    return implode('. ', $parts) . ($parts ? '.' : '');
}

function fetchArticleFromIssueList(int $articleId, int $issueId): array
{
    if ($issueId <= 0) {
        return [];
    }

    $response = requestJson(OJS_API_BASE_URL . '/submissions?issueIds[]=' . $issueId . '&count=100');
    foreach (($response['items'] ?? []) as $item) {
        if (is_array($item) && (int) ($item['id'] ?? 0) === $articleId) {
            return $item;
        }
    }

    return [];
}

function fetchArticleData(int $articleId, int $issueId): array
{
    if ($articleId <= 0) {
        throw new RuntimeException('Artigo não informado.');
    }

    $article = [];
    try {
        $article = requestJson(OJS_API_BASE_URL . '/submissions/' . $articleId);
    } catch (Throwable $error) {
        $article = fetchArticleFromIssueList($articleId, $issueId);
    }

    if (empty($article)) {
        $article = fetchArticleFromIssueList($articleId, $issueId);
    }

    if (empty($article) || !is_array($article)) {
        throw new RuntimeException('Artigo não encontrado.');
    }

    $publication = currentPublication($article);
    $issueId = (int) ($issueId ?: ($publication['issueId'] ?? $article['issueId'] ?? 0));
    $issue = $issueId > 0 ? requestJson(OJS_API_BASE_URL . '/issues/' . $issueId) : [];

    $authors = normalizeAuthors($publication, $article);
    $doi = (string) ($publication['doiObject']['doi'] ?? $publication['pub-id::doi'] ?? $publication['doi'] ?? $article['pub-id::doi'] ?? $article['doi'] ?? '');
    $pdfUrl = firstGalleyUrl($publication['galleys'] ?? $article['galleys'] ?? []);
    $articleUrl = $publication['urlPublished'] ?? $publication['publishedUrl'] ?? $article['urlPublished'] ?? $article['publishedUrl'] ?? $article['url'] ?? '';
    $abstract = localizedHtml($publication['abstract'] ?? $article['abstract'] ?? '');
    $keywords = normalizeKeywords($publication['keywords'] ?? $article['keywords'] ?? []);
    $publicMetadata = fetchPublicArticleMetadata(absoluteUrl($articleUrl));

    if ($abstract === '' && !empty($publicMetadata['abstract'])) {
        $abstract = $publicMetadata['abstract'];
    }

    if (empty($keywords) && !empty($publicMetadata['keywords'])) {
        $keywords = $publicMetadata['keywords'];
    }

    $data = [
        'issue' => [
            'id' => $issueId,
            'label' => splitIssueIdentification($issue),
        ],
        'article' => [
            'id' => $articleId,
            'title' => localizedValue($publication['fullTitle'] ?? $publication['title'] ?? $article['title'] ?? ''),
            'section' => localizedValue($publication['sectionTitle'] ?? $article['sectionTitle'] ?? 'Artigos'),
            'doi' => $doi,
            'date' => formatDateValue((string) ($publication['datePublished'] ?? $article['datePublished'] ?? $article['dateSubmitted'] ?? '')),
            'authors' => $authors,
            'abstract' => $abstract,
            'cover' => articleCoverFromApi($article, $issue) ?: ($publicMetadata['cover'] ?? ''),
            'keywords' => $keywords,
            'pdf_url' => absoluteUrl($pdfUrl ?: $articleUrl),
            'pages' => (string) ($publication['pages'] ?? $article['pages'] ?? ''),
        ],
    ];

    $data['article']['citation'] = buildCitation($data['article'], $data['issue']);
    if (!empty($publicMetadata['citation'])) {
        $data['article']['citation'] = $publicMetadata['citation'];
    }

    return $data;
}

$articleId = (int) ($_GET['id'] ?? 0);
$issueId = (int) ($_GET['issue'] ?? 0);
$pageData = [];
$loadError = '';

try {
    $pageData = fetchArticleData($articleId, $issueId);
} catch (Throwable $error) {
    $loadError = $error->getMessage();
}

$issue = $pageData['issue'] ?? [];
$article = $pageData['article'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($article['title'] ?? 'Artigo') . ' - BJHP') ?></title>
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.css?v=3.3.0.21" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/crfmg/$$$call$$$/page/page/css?name=stylesheet" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/public/journals/1/styleSheet.css?d=2026-02-03+13%3A07%3A57" type="text/css">
    <link rel="stylesheet" href="/bjhp/css/styles.css">
</head>
<body dir="ltr">
<?php include __DIR__ . '/header.php'; ?>

<main class="article-view-page">
    <?php if ($loadError): ?>
        <section class="article-view-error">
            <h2>Artigo</h2>
            <div class="edicoes-empty"><?= e($loadError) ?></div>
        </section>
    <?php else: ?>
        <section class="article-view-header">
            <p class="article-view-issue"><?= e($issue['label'] ?? '') ?></p>
            <h2><?= e($article['title'] ?? '') ?></h2>
            <p class="article-view-section"><?= e($article['section'] ?: 'Artigos') ?></p>
            <?php if (!empty($article['doi'])): ?>
                <p class="article-view-doi"><a href="https://doi.org/<?= e($article['doi']) ?>" target="_blank" rel="noopener">https://doi.org/<?= e($article['doi']) ?></a></p>
            <?php endif; ?>
            <?php if (!empty($article['date'])): ?>
                <p class="article-view-date">Publicado <?= e($article['date']) ?></p>
            <?php endif; ?>
            <?php if (!empty($article['authors'])): ?>
                <p class="article-view-authors">
                    <?php foreach ($article['authors'] as $index => $author): ?>
                        <?php if ($index > 0): ?>, <?php endif; ?>
                        <?= e($author['name']) ?><?php if (!empty($author['orcid'])): ?><a class="author-orcid" href="<?= e($author['orcid']) ?>" target="_blank" rel="noopener">iD</a><?php endif; ?>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </section>

        <section class="article-view-body">
            <article class="article-view-main">
                <h3>Resumo</h3>
                <div class="article-view-abstract">
                    <?= $article['abstract'] ?: '<p>Resumo não informado.</p>' ?>
                </div>
                <?php if (!empty($article['pdf_url'])): ?>
                    <a class="button article-view-pdf" href="<?= e($article['pdf_url']) ?>" target="_blank" rel="noopener">PDF</a>
                <?php endif; ?>
            </article>

            <aside class="article-view-sidebar">
                <?php if (!empty($article['cover'])): ?>
                    <img class="article-view-cover" src="<?= e($article['cover']) ?>" alt="Capa do artigo">
                <?php endif; ?>

                <div class="article-view-side-block">
                    <h3>Palavras-chave</h3>
                    <?php if (!empty($article['keywords'])): ?>
                        <ul>
                            <?php foreach ($article['keywords'] as $keyword): ?>
                                <li><?= e($keyword) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Não informado.</p>
                    <?php endif; ?>
                </div>

                <div class="article-view-side-block">
                    <h3>Como Citar</h3>
                    <p><?= e($article['citation'] ?? '') ?></p>
                    <button class="citation-format-button" type="button">Formatos de Citação</button>
                </div>
            </aside>
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
