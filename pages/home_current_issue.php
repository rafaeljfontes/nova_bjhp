<?php

declare(strict_types=1);

function bjhpHomeIssueConnection(): mysqli
{
    $host = getenv('BJHP_DB_HOST');
    $user = getenv('BJHP_DB_USER');
    $password = getenv('BJHP_DB_PASSWORD');
    $database = getenv('BJHP_DB_NAME') ?: 'web_site';

    if ($host === false || $user === false) {
        $portalConfig = dirname(__DIR__, 2) . '/portalfarma/application/config/database.php';
        if (!is_file($portalConfig)) {
            throw new RuntimeException('Conexão do banco não configurada.');
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', true);
        }
        if (!defined('ENVIRONMENT')) {
            define('ENVIRONMENT', 'production');
        }
        include $portalConfig;
        $config = $db['default'] ?? [];
        $host = $config['hostname'] ?? '';
        $user = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $database = $config['database'] ?? $database;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli((string) $host, (string) $user, (string) $password, (string) $database);
    $connection->set_charset('utf8mb4');
    return $connection;
}

function bjhpHomeCurrentIssue(): ?array
{
    $connection = bjhpHomeIssueConnection();
    try {
        return $connection->query(
            'SELECT ojs_issue_id, identificacao, capa_url, edicao_url FROM bjhp_edicoes
             WHERE currentIssueId = 1
             ORDER BY data_publicacao DESC, id DESC LIMIT 1'
        )->fetch_assoc() ?: null;
    } finally {
        $connection->close();
    }
}

function bjhpHomePreviousIssues(): array
{
    $connection = bjhpHomeIssueConnection();
    try {
        return $connection->query(
            'SELECT ojs_issue_id, identificacao, capa_url FROM bjhp_edicoes
             WHERE (currentIssueId IS NULL OR currentIssueId <> 1)
             ORDER BY data_publicacao DESC, id DESC LIMIT 4'
        )->fetch_all(MYSQLI_ASSOC);
    } finally {
        $connection->close();
    }
}

function bjhpHomeLatestArticles(int $issueId): array
{
    $connection = bjhpHomeIssueConnection();
    try {
        // O cadastro local não possui a data de publicação dos artigos.
        $statement = $connection->prepare(
            'SELECT a.ojs_article_id, a.titulo FROM bjhp_artigos a
             INNER JOIN bjhp_edicoes e ON e.id = a.edicao_id
             WHERE e.ojs_issue_id = ?
             ORDER BY a.ojs_article_id DESC, a.id DESC LIMIT 3'
        );
        $statement->bind_param('i', $issueId);
        $statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    } finally {
        $connection->close();
    }
}

function bjhpHomeHttpUrl(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    return in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true) ? $url : '';
}
