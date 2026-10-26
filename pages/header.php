<?php
$currentHeaderPage = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
$headerScriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$headerIsInsidePages = basename($headerScriptDirectory) === 'pages';
$headerBaseUrl = $headerIsInsidePages ? dirname($headerScriptDirectory) : $headerScriptDirectory;
$headerBaseUrl = rtrim(str_replace('\\', '/', $headerBaseUrl), '/');
$headerHomeUrl = ($headerBaseUrl ?: '') . '/';
$isHomePage = in_array($currentHeaderPage, ['bjhp.php', 'index.php', ''], true);
$isCurrentIssuePage = $currentHeaderPage === 'edicao_atual.php';
$isIssuesPage = in_array($currentHeaderPage, ['todas_edicoes.php', 'issue.php', 'view_artigo.php', 'api.php'], true);
?>
<header class="bjhp-header" id="headerNavigationContainer" role="banner">
	<h1 class="sr-only">Brazilian Journal of Health and Pharmacy</h1>

	<div class="bjhp-header-top">
		<div class="bjhp-header-container">
			<ul class="bjhp-utility-menu" aria-label="Links do usuário">
				<li><a href="https://www.crfmg.org.br/" target="_blank" rel="noopener noreferrer"><span class="bjhp-menu-icon" aria-hidden="true">◆</span> Portal CRF-MG</a></li>
				<li><a href="https://bjhp.crfmg.org.br/crfmg/user/register"><span class="bjhp-menu-icon" aria-hidden="true">✦</span> Cadastro</a></li>
				<li><a href="https://bjhp.crfmg.org.br/crfmg/login"><span class="bjhp-menu-icon" aria-hidden="true">●</span> Login</a></li>
			</ul>
		</div>
	</div>

	<div class="bjhp-brand-area">
		<div class="bjhp-header-container">
			<a class="bjhp-brand" href="<?= htmlspecialchars($headerHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Ir para a página inicial do BJHP">
				<img src="<?= htmlspecialchars(($headerBaseUrl ?: '') . '/img/BJHP_logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="Revista Científica Brazilian Journal of Health and Pharmacy">
			</a>
			<a class="bjhp-institution-brand" href="https://www.crfmg.org.br/" target="_blank" rel="noopener noreferrer" aria-label="Acessar o portal do CRF-MG">
				<img src="<?= htmlspecialchars(($headerBaseUrl ?: '') . '/img/marca-horizontal-extenso.png', ENT_QUOTES, 'UTF-8') ?>" alt="Conselho Regional de Farmácia do Estado de Minas Gerais">
			</a>
		</div>
	</div>

	<nav class="bjhp-primary-navigation navbar navbar-expand-lg" aria-label="Navegação principal">
		<div class="bjhp-header-container bjhp-navigation-inner">
			<button class="navbar-toggler bjhp-menu-toggle" type="button" data-toggle="collapse" data-target="#bjhpMainNavbar" aria-controls="bjhpMainNavbar" aria-expanded="false" aria-label="Abrir ou fechar menu">
				<span class="bjhp-menu-toggle-bars" aria-hidden="true"></span><span>Menu</span>
			</button>

			<div class="collapse navbar-collapse" id="bjhpMainNavbar">
				<ul class="navbar-nav bjhp-main-menu" id="main-navigation">
					<li class="nav-item<?= $isHomePage ? ' active' : '' ?>"><a class="nav-link" href="<?= htmlspecialchars($headerHomeUrl, ENT_QUOTES, 'UTF-8') ?>"<?= $isHomePage ? ' aria-current="page"' : '' ?>>Início</a></li>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="https://bjhp.crfmg.org.br/crfmg/apresentacao" id="bjhpAboutMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Apresentação</a>
						<div class="dropdown-menu" aria-labelledby="bjhpAboutMenu">
							<a class="dropdown-item" href="https://bjhp.crfmg.org.br/crfmg/missao_escopo">Missão e Escopo</a>
							<a class="dropdown-item" href="https://bjhp.crfmg.org.br/crfmg/equipe_editorial">Corpo Editorial</a>
							<a class="dropdown-item" href="https://bjhp.crfmg.org.br/crfmg/contatos">Contato</a>
						</div>
					</li>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="https://bjhp.crfmg.org.br/crfmg/politicas" id="bjhpPoliciesMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Políticas</a>
						<div class="dropdown-menu" aria-labelledby="bjhpPoliciesMenu">
							<a class="dropdown-item" href="https://bjhp.crfmg.org.br/crfmg/politica">Sobre a Revista</a>
							<a class="dropdown-item" href="https://bjhp.crfmg.org.br/crfmg/processo_avaliacao">Processo de Avaliação</a>
							<a class="dropdown-item" href="https://bjhp.crfmg.org.br/crfmg/revisao_pares">Revisão por Pares</a>
						</div>
					</li>
					<!--<li class="nav-item<?= $isCurrentIssuePage ? ' active' : '' ?>"><a class="nav-link" href="<?= htmlspecialchars(($headerBaseUrl ?: '') . '/edicao-atual', ENT_QUOTES, 'UTF-8') ?>"<?= $isCurrentIssuePage ? ' aria-current="page"' : '' ?>>Edição Atual</a></li>-->
					<li class="nav-item<?= $isIssuesPage ? ' active' : '' ?>"><a class="nav-link" href="<?= htmlspecialchars(($headerBaseUrl ?: '') . '/edicoes', ENT_QUOTES, 'UTF-8') ?>"<?= $isIssuesPage ? ' aria-current="page"' : '' ?>>Edições</a></li>
					<li class="nav-item"><a class="nav-link" href="https://bjhp.crfmg.org.br/crfmg/submissao">Submissão</a></li>
					<li class="nav-item"><a class="nav-link" href="https://bjhp.crfmg.org.br/crfmg/edicao_especial">Edição Especial</a></li>
					<li class="nav-item"><a class="nav-link" href="https://bjhp.crfmg.org.br/crfmg/links_uteis">Links Úteis</a></li>
					<li class="nav-item"><a class="nav-link" href="https://bjhp.crfmg.org.br/crfmg/search/search">Pesquisar</a></li>
				</ul>

				<div class="bjhp-navigation-actions">
					<a href="https://bjhp.crfmg.org.br/crfmg/about/submissions">Enviar submissão</a>
					<a href="https://bjhp.crfmg.org.br/crfmg/contatos">Fale Conosco</a>
				</div>
			</div>
		</div>
	</nav>
</header>
