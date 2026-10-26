<?php

declare(strict_types=1);

const OJS_BASE_URL = 'https://bjhp.crfmg.org.br/crfmg';
const OJS_API_BASE_URL = OJS_BASE_URL . '/api/v1';
define('OJS_API_TOKEN', (string) (getenv('BJHP_OJS_API_TOKEN') ?: ''));

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function absoluteIssueUrl(?string $url): string
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
            throw new RuntimeException('Não foi possível carregar as edições do OJS.');
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
        throw new RuntimeException('Não foi possível carregar as edições do OJS.');
    }

    return json_decode($body, true) ?: [];
}

function localizedIssueValue($value, array $preferredLocales = ['pt_BR', 'pt', 'en_US', 'en']): string
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

function issueCoverUrl(array $issue): string
{
    foreach (['coverImageUrl', 'coverImage', 'cover'] as $key) {
        if (empty($issue[$key])) {
            continue;
        }

        if (is_string($issue[$key])) {
            return absoluteIssueUrl($issue[$key]);
        }

        if (is_array($issue[$key])) {
            foreach (['pt_BR', 'pt', 'en_US', 'en', 'url'] as $locale) {
                if (!empty($issue[$key][$locale]) && is_string($issue[$key][$locale])) {
                    return absoluteIssueUrl($issue[$key][$locale]);
                }
            }
        }
    }

    return '';
}

function issuePublicationDate(array $issue): string
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

    return $months[(int) $dateTime->format('n')] . ' ' . $dateTime->format('j') . ', ' . $dateTime->format('Y');
}

function normalizeIssue(array $issue): array
{
    $id = (int) ($issue['id'] ?? 0);
    $identification = localizedIssueValue($issue['identification'] ?? '');
    $title = localizedIssueValue($issue['title'] ?? '');

    if (strpos($identification, ':') !== false) {
        [$issueLabel, $embeddedTitle] = array_map('trim', explode(':', $identification, 2));
        $identification = $issueLabel;

        if ($title === '') {
            $title = $embeddedTitle;
        }
    }

    return [
        'id' => $id,
        'cover' => issueCoverUrl($issue),
        'identification' => $identification ?: $title,
        'title' => $title,
        'date' => issuePublicationDate($issue),
        'url' => '/bjhp/edicao?id=' . $id,
    ];
}

function fetchAllIssues(): array
{
    $issues = [];
    $count = 100;
    $offset = 0;

    do {
        $url = OJS_API_BASE_URL . '/issues?count=' . $count . '&offset=' . $offset . '&orderBy=datePublished&orderDirection=DESC';
        $response = requestJson($url);
        $items = $response['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            break;
        }

        foreach ($items as $item) {
            if (is_array($item)) {
                $issues[] = normalizeIssue($item);
            }
        }

        $offset += $count;
        $total = (int) ($response['itemsMax'] ?? $response['total'] ?? count($issues));
    } while (count($issues) < $total);

    return $issues;
}

$issues = [];
$loadError = '';

try {
    $issues = fetchAllIssues();
} catch (Throwable $error) {
    $loadError = $error->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todas as edições - BJHP</title>
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.css?v=3.3.0.21" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/crfmg/$$$call$$$/page/page/css?name=stylesheet" type="text/css">
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/public/journals/1/styleSheet.css?d=2026-02-03+13%3A07%3A57" type="text/css">
    <link rel="stylesheet" href="/bjhp/css/styles.css">
</head>
<body dir="ltr">
<?php include __DIR__ . '/header.php'; ?>

<main class="edicoes-page">
    <section class="edicoes-section">
        <h2>Edições anteriores</h2>

        <?php if ($loadError): ?>
            <div class="edicoes-empty">Não foi possível carregar as edições: <?= e($loadError) ?></div>
        <?php elseif (empty($issues)): ?>
            <div class="edicoes-empty">Nenhuma edição foi encontrada.</div>
        <?php else: ?>
            <div class="edicoes-grid">
                <?php foreach ($issues as $issue): ?>
                    <article class="edicao-card">
                        <a class="edicao-cover-link" href="<?= e($issue['url']) ?>" aria-label="Acessar <?= e($issue['identification']) ?>">
                            <?php if (!empty($issue['cover'])): ?>
                                <img class="edicao-cover" src="<?= e($issue['cover']) ?>" alt="Capa da edição <?= e($issue['identification']) ?>">
                            <?php else: ?>
                                <span class="edicao-cover-placeholder">BJHP</span>
                            <?php endif; ?>
                        </a>
                        <h3><a href="<?= e($issue['url']) ?>"><?= e($issue['identification']) ?></a></h3>
                        <?php if (!empty($issue['date'])): ?>
                            <p class="edicao-date"><?= e($issue['date']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($issue['title']) && $issue['title'] !== $issue['identification']): ?>
                            <p class="edicao-title"><?= e($issue['title']) ?></p>
                        <?php endif; ?>
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
