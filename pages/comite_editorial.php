<?php

declare(strict_types=1);

function bjhpEditorialEscape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function bjhpEditorialImage(?string $photo): string
{
    $photo = trim((string) $photo);
    if ($photo === '') {
        return '';
    }
    if (filter_var($photo, FILTER_VALIDATE_URL)) {
        return in_array(parse_url($photo, PHP_URL_SCHEME), ['https', 'http'], true) ? $photo : '';
    }
    $photo = str_replace('\\', '/', $photo);
    if (strpos($photo, '..') !== false) {
        return '';
    }
    $filename = basename($photo);
    return 'https://crfmg.org.br/portalfarma/uploads/bjhp/corpo-editorial/' . rawurlencode($filename);
}

function bjhpEditorialMembers(): array
{
    $host = getenv('BJHP_DB_HOST');
    $user = getenv('BJHP_DB_USER');
    $password = getenv('BJHP_DB_PASSWORD');
    $database = getenv('BJHP_DB_NAME') ?: 'web_site';

    if ($host === false || $user === false) {
        // Usa a mesma conexão configurada pelo portal, quando os projetos estão lado a lado.
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
    try {
        $result = $connection->query(
            "SELECT id, categoria, nome, titulacao, instituicao, cidade, estado, pais, orcid_url, foto, mini_curriculo
             FROM `bjhp_corpo-editorial`
             WHERE status = 'ativo' AND nome IS NOT NULL AND TRIM(nome) <> ''
             ORDER BY CASE categoria
                 WHEN 'editor_chefe' THEN 1
                 WHEN 'editor_associado' THEN 2
                 WHEN 'conselho_consultivo_editorial' THEN 3
                 ELSE 4
             END ASC, ordem_exibicao ASC, nome ASC, id ASC"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    } finally {
        $connection->close();
    }
}

$categoryLabels = [
    'editor_chefe' => 'Editor-chefe',
    'editor_associado' => 'Editor associado',
    'editor_convidado' => 'Editor convidado',
    'conselho_consultivo_editorial' => 'Conselho consultivo editorial',
    'tecnologia_da_informacao' => 'Tecnologia da informação',
    'design' => 'Design',
    'comunicacao' => 'Comunicação',
    'secretaria_administrativa' => 'Secretaria administrativa',
];

$categoryFilters = [
    'editor_chefe' => 'Editor-chefe',
    'editor_associado' => 'Editores Associados',
    'conselho_consultivo_editorial' => 'Conselho Consultivo Editorial',
    'editor_convidado' => 'Editores Convidados',
    'secretaria_administrativa' => 'SECRETARIA ADMINISTRATIVA / CRFMG',
];

$editorialError = false;
try {
    $members = bjhpEditorialMembers();
} catch (Throwable $error) {
    error_log('BJHP editorial: ' . $error->getMessage());
    $members = [];
    $editorialError = true;
}
?>
<section class="comite" data-comite-filter>
    <h2>Comitê Editorial</h2>
    <p class="comite-description">O Corpo Editorial da BJHP é composto por Editor-chefe, Editores Associados, Conselho Consultivo Editorial e Editores Convidados, observados os critérios de qualificação técnico-científica, experiência acadêmica e alinhamento ao escopo da revista. Essa composição segue a <a href="https://documentos.cff.org.br/sei/publicacoes/controlador_publicacoes.php?acao=publicacao_visualizar&amp;id_documento=1651622&amp;id_orgao_publicacao=0" target="_blank" rel="noopener noreferrer">Deliberação CRF-MG nº 35/2026</a>, que altera o Regimento Interno da Revista Científica BJHP.</p>
    <div class="comite-filters" role="group" aria-label="Categorias do comitê editorial" hidden>
        <?php foreach ($categoryFilters as $category => $label): ?>
            <button type="button" data-category="<?= bjhpEditorialEscape($category) ?>" aria-pressed="false" aria-controls="comite-members"><?= bjhpEditorialEscape($label) ?></button>
        <?php endforeach; ?>
    </div>
    <?php if (!$members): ?>
        <p><?= $editorialError ? 'Não foi possível carregar o comitê editorial.' : 'Nenhum membro ativo cadastrado.' ?></p>
    <?php else: ?>
        <div class="comite-grid" id="comite-members">
                    <?php foreach ($members as $index => $member): ?>
                        <?php $photo = bjhpEditorialImage($member['foto']); ?>
                        <div class="editor" data-member-category="<?= bjhpEditorialEscape($member['categoria']) ?>">
                            <button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editor-<?= (int) $member['id'] ?>">
                                <?php if ($photo): ?>
                                    <img src="<?= bjhpEditorialEscape($photo) ?>" alt="<?= bjhpEditorialEscape($member['nome']) ?>">
                                <?php else: ?>
                                    <span class="editor-photo-placeholder" aria-hidden="true"><?= bjhpEditorialEscape(mb_substr($member['nome'], 0, 1)) ?></span>
                                <?php endif; ?>
                                <strong><?= bjhpEditorialEscape($member['nome']) ?></strong>
                            </button>
                        </div>
                    <?php endforeach; ?>
        </div>
        <?php foreach ($members as $member): ?>
            <?php
                $photo = bjhpEditorialImage($member['foto']);
                $orcidUrl = trim((string) ($member['orcid_url'] ?? ''));
                $validOrcid = filter_var($orcidUrl, FILTER_VALIDATE_URL)
                    && parse_url($orcidUrl, PHP_URL_SCHEME) === 'https'
                    && parse_url($orcidUrl, PHP_URL_HOST) === 'orcid.org';
                $affiliation = implode(', ', array_filter([
                    $member['titulacao'] ?? '', $member['instituicao'] ?? '',
                    $member['cidade'] ?? '', $member['estado'] ?? '', $member['pais'] ?? '',
                ], static fn ($value) => trim((string) $value) !== ''));
            ?>
            <div class="modal fade" id="editor-<?= (int) $member['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editor-<?= (int) $member['id'] ?>-label" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editor-<?= (int) $member['id'] ?>-label"><?= bjhpEditorialEscape($member['nome']) ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body text-center">
                            <?php if ($photo): ?>
                                <img class="editor-modal-photo" src="<?= bjhpEditorialEscape($photo) ?>" alt="<?= bjhpEditorialEscape($member['nome']) ?>">
                            <?php endif; ?>
                            <?php if ($affiliation): ?><p class="text-left"><?= bjhpEditorialEscape($affiliation) ?></p><?php endif; ?>
                            <?php if (trim((string) $member['mini_curriculo']) !== ''): ?>
                                <p class="text-left"><?= nl2br(bjhpEditorialEscape($member['mini_curriculo'])) ?></p>
                            <?php endif; ?>
                            <?php if ($validOrcid): ?>
                                <p><a class="button orcid-button" href="<?= bjhpEditorialEscape($orcidUrl) ?>" target="_blank" rel="noopener noreferrer">ORCID</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
