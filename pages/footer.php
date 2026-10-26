<?php
$footerScriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$footerIsInsidePages = basename($footerScriptDirectory) === 'pages';
$footerBaseUrl = $footerIsInsidePages ? dirname($footerScriptDirectory) : $footerScriptDirectory;
$footerBaseUrl = rtrim(str_replace('\\', '/', $footerBaseUrl), '/');
$footerIssuesUrl = ($footerBaseUrl ?: '') . '/edicoes';
?>
<footer class="site-footer bjhp-footer">
    <div class="bjhp-footer-container">
        <div class="bjhp-footer-grid">
            <div class="bjhp-footer-about">
                <p class="bjhp-footer-eyebrow">Ciência e conhecimento em saúde</p>
                    <img src="http://crfmg.org.br/bjhp/img/bjhp_negativa.png" style="width:225px; padding:10px;">
                    <img src="http://crfmg.org.br/bjhp/img/marca-horizontal-negativo.png" style="width:150px; padding:10px;">
                
                <p>Publicação científica de acesso aberto dedicada à saúde humana e às ciências farmacêuticas.</p>
                <p class="bjhp-footer-institution">Conselho Regional de Farmácia de Minas Gerais</p>
                <address>
                    R. Rodrigues Caldas, 493 — Santo Agostinho<br>
                    Belo Horizonte/MG · Brasil<br>
                    CEP 30190-120
                </address>
                <a class="bjhp-footer-contact" href="https://bjhp.crfmg.org.br/crfmg/contatos">Fale com a revista <span aria-hidden="true">↗</span></a>
            </div>

            <nav aria-labelledby="footer-navigation-title">
                <h2 id="footer-navigation-title">Links rápidos</h2>
                <ul class="bjhp-footer-links">
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/missao_escopo">Sobre a Revista</a></li>
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/equipe_editorial">Comitê Editorial</a></li>
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/submissao">Submissão</a></li>
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/politica">Políticas</a></li>
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/processo_avaliacao">Processo de avaliação</a></li>
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/revisao_pares">Revisão por pares</a></li>
                    <li><a href="<?= htmlspecialchars($footerIssuesUrl, ENT_QUOTES, 'UTF-8') ?>">Edições anteriores</a></li>
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/contatos">Contato</a></li>
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/links_uteis">Links úteis</a></li>
                </ul>
            </nav>

            <nav aria-labelledby="footer-index-title">
                <h2 id="footer-index-title">Indexadores</h2>
                <p>Encontre a produção científica da revista.</p>
                <ul class="bjhp-footer-indexers">
                    <li><a href="https://latindex.org/">Latindex </a></li>
                    <li><a href="https://scholar.google.com/">Google Scholar </a></li>
                    <li><a href="https://doaj.org/">DOAJ </a></li>
                </ul>
                <a class="bjhp-footer-submit" href="https://bjhp.crfmg.org.br/crfmg/about/submissions">Enviar submissão <span aria-hidden="true">→</span></a>
            </nav>

            <div class="bjhp-footer-location">
                <h2>Localização</h2>
                <iframe title="Localização do Conselho Regional de Farmácia de Minas Gerais" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3750.8389297024532!2d-43.956420989306025!3d-19.93119123826806!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xa699e614898a07%3A0x2992c6edb861c1e2!2sConselho%20Regional%20de%20Farm%C3%A1cia%20de%20Minas%20Gerais!5e0!3m2!1spt-BR!2sbr!4v1737036039201!5m2!1spt-BR!2sbr" width="280" height="180" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                <p>Santo Agostinho<br>Belo Horizonte · Minas Gerais</p>
                <a href="https://www.google.com/maps/search/?api=1&amp;query=Conselho+Regional+de+Farmacia+de+Minas+Gerais+Rodrigues+Caldas+493">Ver no Google Maps <span aria-hidden="true">↗</span></a>
            </div>
        </div>

        <div class="bjhp-footer-license">
            <a rel="license" href="https://creativecommons.org/licenses/by/4.0/" target="_blank" aria-label="Licença Creative Commons Atribuição 4.0 Internacional">
                <img src="https://licensebuttons.net/l/by/4.0/88x31.png" alt="Creative Commons CC BY" width="88" height="31" loading="lazy">
            </a>
            <p>A Revista BJHP está sob Licença <a rel="license noopener noreferrer" href="https://creativecommons.org/licenses/by/4.0/" target="_blank">Creative Commons Attribution 4.0 International License</a>.</p>
        </div>
        <div class="bjhp-footer-bottom">
            <p>© <?= date('Y') ?> CRF-MG · Brazilian Journal of Health and Pharmacy.</p>
            <nav aria-label="Informações legais">
                <ul class="bjhp-footer-legal">
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/about/submissions#copyrightNotice">Termos e direitos autorais</a></li>
                    <li><a href="https://bjhp.crfmg.org.br/crfmg/about/privacy">Privacidade</a></li>
                </ul>
            </nav>
            <a class="bjhp-footer-credit" href="https://bjhp.crfmg.org.br/crfmg/about/aboutThisPublishingSystem">Publicado com OJS / PKP</a>
        </div>
    </div>
</footer>
