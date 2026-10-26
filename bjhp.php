<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revista Científica - Home</title>
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.css?v=3.3.0.21" type="text/css" />
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/crfmg/$$$call$$$/page/page/css?name=stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/public/journals/1/styleSheet.css?d=2026-02-03+13%3A07%3A57" type="text/css" />
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bjhp-home" dir="ltr">

<?php include __DIR__ . '/pages/header.php'; ?>
    <!-- BANNER ROTATIVO -->
    <!--
	<section class="banner">
        <div class="slider">
            <div class="slides">
                <img src="https://revistacientifica.crfmg.emnuvens.com.br/public/journals/1/homepageImage_pt_BR.png" alt="">
                <img src="https://revistacientifica.crfmg.emnuvens.com.br/public/journals/1/homepageImage_pt_BR.png" alt="">
                <img src="https://revistacientifica.crfmg.emnuvens.com.br/public/journals/1/homepageImage_pt_BR.png" alt="">
            </div>
        </div>
    </section>
	-->
	
	<div id="bannerCarousel" class="carousel slide banner" data-ride="carousel" data-interval="6000">

        <!-- INDICADORES -->
        <ol class="carousel-indicators">
            <li data-target="#bannerCarousel" data-slide-to="0" class="active"></li>
            <li data-target="#bannerCarousel" data-slide-to="1"></li>
        </ol>

        <!-- SLIDES -->
        <div class="carousel-inner">

            <!-- BANNER 1 -->
            <div class="carousel-item active">
                <picture>
                    <source media="(max-width: 768px)" srcset="img/PortalFarma-banner.png">

                    <img src="img/PortalFarma-banner.png" class="d-block w-100" width="3103" height="936" alt="Portal Farma: conhecimento que faz a sua carreira evoluir">
                </picture>
            </div>

            <!-- BANNER 2 -->
            <div class="carousel-item">
                <a href="https://revistacientifica.crfmg.emnuvens.com.br/crfmg/edicao_especial" target="_blank" rel="noopener noreferrer" class="d-block w-100">

                    <picture>
                        <source media="(max-width: 768px)" srcset="img/homepageImage_pt_BR.png">

                        <img src="img/homepageImage_pt_BR.png" class="d-block w-100" width="9245" height="2317" alt="A BJHP agora tem publicações de fluxo contínuo! Submeta seu artigo científico e publique seu trabalho.">
                    </picture>
                </a>
            </div>
        </div>

        <!-- SETAS -->
        <button class="carousel-control-prev" type="button" data-target="#bannerCarousel" data-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>

        <button class="carousel-control-next" type="button" data-target="#bannerCarousel" data-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <?php
    require_once __DIR__ . '/pages/home_current_issue.php';
    try {
        $homeCurrentIssue = bjhpHomeCurrentIssue();
    } catch (Throwable $error) {
        error_log('BJHP edição atual: ' . $error->getMessage());
        $homeCurrentIssue = null;
    }
    $homeCurrentCover = bjhpHomeHttpUrl($homeCurrentIssue['capa_url'] ?? null);
    $homeCurrentIssueId = (int) ($homeCurrentIssue['ojs_issue_id'] ?? 0);
    $homeCurrentArticles = [];
    $homeCurrentArticlesError = false;
    if ($homeCurrentIssueId > 0) {
        try {
            $homeCurrentArticles = bjhpHomeLatestArticles($homeCurrentIssueId);
        } catch (Throwable $error) {
            error_log('BJHP artigos da edição atual: ' . $error->getMessage());
            $homeCurrentArticlesError = true;
        }
    }
    $homeCurrentUrl = bjhpHomeHttpUrl($homeCurrentIssue['edicao_url'] ?? null);
    if ($homeCurrentUrl === '' && !empty($homeCurrentIssue['ojs_issue_id'])) {
        $homeCurrentUrl = 'https://bjhp.crfmg.org.br/crfmg/issue/view/' . (int) $homeCurrentIssue['ojs_issue_id'];
    }
    ?>
    <!-- EDIÇÃO ATUAL -->
    <section class="edicao_atual">
        <h2>Edição Atual</h2>
        <div class="edicao-atual">
            <?php if ($homeCurrentIssue): ?>
                <a class="edicao-atual-cover-link" href="<?= htmlspecialchars($homeCurrentUrl ?: 'edicao-atual', ENT_QUOTES, 'UTF-8') ?>" aria-label="Acessar a edição atual">
                    <?php if ($homeCurrentCover !== ''): ?>
                        <img src="<?= htmlspecialchars($homeCurrentCover, ENT_QUOTES, 'UTF-8') ?>" alt="Capa da edição atual">
                    <?php else: ?>
                        <span>Acessar a edição atual</span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <p>Edição atual não identificada no cadastro.</p>
            <?php endif; ?>

            <div>
                <?php if ($homeCurrentArticles): ?>
                    <div class="edicao-atual-artigos">
                        <?php foreach ($homeCurrentArticles as $article): ?>
                            <div class="edicao-atual-artigo-card">
                                <a href="/bjhp/artigo?id=<?= (int) $article['ojs_article_id'] ?>&amp;issue=<?= $homeCurrentIssueId ?>"><?= htmlspecialchars($article['titulo'] ?: 'Título não informado', ENT_QUOTES, 'UTF-8') ?></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($homeCurrentIssueId > 0): ?>
                    <p><?= $homeCurrentArticlesError ? 'Não foi possível carregar os artigos da edição atual.' : 'Nenhum artigo disponível nesta edição.' ?></p>
                <?php endif; ?>
                
            </div>
            
        </div>
        <?php if ($homeCurrentIssueId > 0): ?>
                    <p class="edicao-atual-actions">
                        <a class="button" href="/bjhp/edicao?id=<?= $homeCurrentIssueId ?>"> &nbsp;&nbsp; Ver todos artigos &nbsp;&nbsp;</a>
                    </p>
                <?php endif; ?>
    </section>

    <?php include __DIR__ . '/pages/home_previous_issues.php'; ?>

    <!-- COMO PUBLICAR -->
    <section class="como-publicar">
        <h2 class="text-white">Como Publicar</h2>
        <p>
            Autores interessados em submeter seus trabalhos devem seguir
            rigorosamente as diretrizes editoriais da revista.
        </p>
        <p>
            <a class="button" href="https://bjhp.crfmg.org.br/crfmg/submissao">Acesse as diretrizes de publicação</a>
        </p>
    </section>

    <?php include __DIR__ . '/pages/comite_editorial.php'; ?>

    <!-- INDEXAÇÕES -->
    <section id="indexação" class="section-indexacoes">
        <h2>Indexações</h2>
        <div class="indexacoes">
            <div class="index-card">
                <img src="https://www.latindex.org/latindex/images/logo_latindex.png" alt="Latindex">
            </div>

            <div class="index-card">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Google_Scholar_logo_2015.PNG?20150923212704" alt="Google Scholar">
            </div>

            <div class="index-card">
                <img src="https://s3.masto.ai/media_attachments/files/114/340/466/560/505/678/original/b7bb90abf7e3beb9.jpg" alt="DOAJ">
            </div>
        </div>
        <div class='abec'>
            <div class="index-card">
                <img src="http://crfmg.org.br/bjhp/img/ABEC.png" alt="ABEC">
                BJHP está associada à Associação Brasileira de Editores Científicos (ABEC)
            </div>
        </div>
    </section>

<?php include __DIR__ . '/pages/footer.php'; ?>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/jquery.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/popper.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/js/main.js?v=3.3.0.21" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var section = document.querySelector('[data-comite-filter]');
    if (!section) return;

    var grid = section.querySelector('.comite-grid');
    if (!grid) return;

    var filters = section.querySelector('.comite-filters');
    var buttons = Array.prototype.slice.call(filters.querySelectorAll('[data-category]'));
    var members = Array.prototype.slice.call(grid.querySelectorAll('[data-member-category]'));

    function selectCategory(button) {
        buttons.forEach(function (item) {
            item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
        });
        members.forEach(function (member) {
            member.hidden = member.dataset.memberCategory !== button.dataset.category;
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () { selectCategory(button); });
    });
    filters.hidden = false;
    selectCategory(buttons[0]);
});
</script>
</body>
</html>
