<?php

declare(strict_types=1);

require_once __DIR__ . '/home_current_issue.php';

$previousIssuesError = false;
try {
    $previousIssues = bjhpHomePreviousIssues();
} catch (Throwable $error) {
    error_log('BJHP edições anteriores: ' . $error->getMessage());
    $previousIssues = [];
    $previousIssuesError = true;
}
?>
<section class="edicoes_anteriores" aria-labelledby="previous-issues-title">
    <h2 id="previous-issues-title">Edições Anteriores</h2>
    <?php if (!$previousIssues): ?>
        <p class="previous-issues-message"><?= $previousIssuesError ? 'Não foi possível carregar as edições anteriores.' : 'Nenhuma edição anterior disponível.' ?></p>
    <?php else: ?>
        <div class="previous-issues-grid">
            <?php foreach ($previousIssues as $previousIssue): ?>
                <?php
                $previousCover = bjhpHomeHttpUrl($previousIssue['capa_url'] ?? null);
                $previousLabel = trim((string) ($previousIssue['identificacao'] ?? '')) ?: 'Edição ' . (int) $previousIssue['ojs_issue_id'];
                ?>
                <a class="previous-issue-cover" href="edicao?id=<?= (int) $previousIssue['ojs_issue_id'] ?>" aria-label="<?= htmlspecialchars('Acessar ' . $previousLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    <?php if ($previousCover !== ''): ?>
                        <img src="<?= htmlspecialchars($previousCover, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars('Capa — ' . $previousLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span class="previous-issue-placeholder"><?= htmlspecialchars($previousLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?><br>Capa indisponível</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="previous-issues-actions">
        <a class="button" href="/bjhp/edicoes">Ver todas as edições</a>
    </div>
</section>
