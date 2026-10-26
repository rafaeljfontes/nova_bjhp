<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revista Científica - Home</title>
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.css?v=3.3.0.21" type="text/css" />
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/crfmg/$$$call$$$/page/page/css?name=stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://bjhp.crfmg.org.br/public/journals/1/styleSheet.css?d=2026-02-03+13%3A07%3A57" type="text/css" />
    <link rel="stylesheet" href="/bjhp/css/styles.css">
</head>
<body dir="ltr">

<?php include __DIR__ . '/header.php'; ?>
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
                    <source media="(max-width: 768px)" srcset="https://www.crfmg.org.br/portalfarma/assets/img/PortalFarma-banner.png">

                    <img src="https://www.crfmg.org.br/portalfarma/assets/img/PortalFarma-banner.png" class="d-block w-100" alt="Banner Portal Farma">
                </picture>
            </div>

            <!-- BANNER 2 -->
            <div class="carousel-item">
                <a href="https://revistacientifica.crfmg.emnuvens.com.br/crfmg/edicao_especial" target="_blank" rel="noopener noreferrer" class="d-block w-100">

                    <picture>
                        <source media="(max-width: 768px)" srcset="https://www.crfmg.org.br/portalfarma/assets/img/BJHP-banner_desktop%20portal_farma.png">

                        <img src="https://www.crfmg.org.br/portalfarma/assets/img/BJHP-banner_desktop%20portal_farma.png" class="d-block w-100" alt="BJHP Edição Especial 2026">
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

    <!-- EDIÇÃO ATUAL -->
    <section class="edicao_atual">
        <h2>Edição Atual</h2>
        <div class="edicao-atual">
            <img src="https://revistacientifica.crfmg.emnuvens.com.br/public/journals/1/cover_issue_53_pt_BR.jpg" alt="Capa da Revista">

            <div>
                <p>
                    A revista Brazilian Journal of Health and Pharmacy tem como missão incentivar a divulgação e publicação de trabalhos científicos, desenvolvidos em instituições de ensino e de pesquisa nacionais e internacionais, relacionados à saúde humana ou às ciências farmacêuticas. 
                </p>
                <p>
                A periodicidade da revista é trimestral e contempla a publicação de artigos originais, de revisão bibliográfica e similares. As publicações não são restritas aos Autores farmacêuticos, sendo também bem-vindos os autores de todas as subáreas da saúde e das ciências biológicas.  
                </p>
                <p>Brazilian Journal of Health and Pharmacy contempla seis seções: (1) Ciências Farmacêuticas, (2) Gestão em Saúde, (3) Ciências Básicas, (4) Práticas Complementares, (5) Educação em Saúde e (6) Outros.
                </p>
                <p>A responsabilidade do periódico é do Conselho Regional de Farmácia de Minas Gerais (CRF/MG), sendo veiculada por meio eletrônico com acesso aberto e gratuito.
                </p>
                <p>A Brazilian Journal of Health and Pharmacy está em processo de indexação.
                </p>
                <p>
                    <a class="button" href="todas_edicoes.php">Acessar todas as edições</a>
                </p>
            </div>
        </div>
    </section>

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

    <!-- CORPO EDITORIAL -->
    <section class="comite">
      <h2>Comitê Editorial</h2>
      <div class="comite-carousel-shell" data-comite-carousel>
          <button type="button" class="comite-carousel-control" data-comite-prev aria-label="Editores anteriores">&#8249;</button>
          <div class="carousel">
    		  <div class="carousel-track">
    			<div class="editor is-visible">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorTiago">
    					<img src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/sodapdf-converted-servletrecuperafoto-1.jpg" alt="Tiago Marques dos Reis" />
    					<strong>Tiago Marques dos Reis</strong>
    				</button>
    				<span class="editor-role">Editor-chefe</span>
    			</div>
    			<div class="editor is-visible">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorFarah">
    					<img src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/maria-farah.jpg" alt="Farah Maria Drumond Chequer Baldoni" />
    					<strong>Farah Maria Drumond Chequer Baldoni</strong>
    				</button>
    				<span class="editor-role">Comitê Editorial</span>
    			</div>
    			<div class="editor is-visible">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorCristiane">
    					<img src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/foto-profissional.jpg" alt="Cristiane de Paula Rezende" />
    					<strong>Cristiane de Paula Rezende</strong>
    				</button>
    				<span class="editor-role">Comitê Editorial</span>
    			</div>
    			<div class="editor is-visible">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorMaurilio">
    					<img src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/maurilio-hehta3.jpg" alt="Maurilio de Souza Cazarim" />
    					<strong>Maurilio de Souza Cazarim</strong>
    				</button>
    				<span class="editor-role">Comitê Editorial</span>
    			</div>
    			<div class="editor">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorSandra">
    					<img src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/img-20221011-181717-1-29455832a3d83176949f7bed67358ada.jpg" alt="Sandra Bertelli Ribeiro de Castro" />
    					<strong>Sandra Bertelli Ribeiro de Castro</strong>
    				</button>
    				<span class="editor-role">Comitê Editorial</span>
    			</div>
    			<div class="editor">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorWander">
    					<img src="https://bjhp.crfmg.org.br/public/site/images/adminbjhp/wander-bjhp.jpg" alt="Wander de Jesus Jeremias" />
    					<strong>Wander de Jesus Jeremias</strong>
    				</button>
    				<span class="editor-role">Comitê Editorial</span>
    			</div>
    			<div class="editor">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorMariaClaudia">
    					<img src="https://via.placeholder.com/200?text=7" alt="Maria Claudia Faria" />
    					<strong>Maria Claudia Faria</strong>
    				</button>
    				<span class="editor-role">Secretaria Administrativa</span>
    			</div>
                <div class="editor">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorWaltovanioVasconcelos">
    					<img src="https://via.placeholder.com/200?text=7" alt="Waltovânio Vasconcelos" />
    					<strong>Waltovânio Vasconcelos</strong>
    				</button>
    				<span class="editor-role">Secretaria Administrativa</span>
    			</div>
                <div class="editor">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorLuanaRamos">
    					<img src="https://via.placeholder.com/200?text=7" alt="Luana Ramos" />
    					<strong>Luana Ramos</strong>
    				</button>
    				<span class="editor-role">Secretaria Administrativa</span>
    			</div>
                <div class="editor">
    				<button type="button" class="editor-profile-button" data-toggle="modal" data-target="#editorRafaelFontes">
    					<img src="https://via.placeholder.com/200?text=7" alt="Rafael Fontes" />
    					<strong>Rafael Fontes</strong>
    				</button>
    				<span class="editor-role">Tecnologia da Informação</span>
    			</div>
    		  </div>
    	  </div>
          <button type="button" class="comite-carousel-control" data-comite-next aria-label="Próximos editores">&#8250;</button>
      </div>
    </section>

    <div class="modal fade" id="editorTiago" tabindex="-1" role="dialog" aria-labelledby="editorTiagoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editorTiagoLabel">Tiago Marques dos Reis</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <img class="editor-modal-photo" src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/sodapdf-converted-servletrecuperafoto-1.jpg" alt="Tiago Marques dos Reis">
                    <p class="text-left">Possui graduação em Farmácia pela Universidade Federal de Alfenas, mestrado em Assistência Farmacêutica pela Universidade Federal do Rio Grande do Sul e doutorado em Ciências pela Faculdade de Ciências Farmacêuticas de Ribeirão Preto da Universidade de São Paulo. Atualmente é professor adjunto na Faculdade de Ciências Farmacêuticas da Universidade Federal de Alfenas, com experiência em Farmácia Clínica, Assistência Farmacêutica e Saúde Baseada em Evidências.</p>
                    <p><a class="button orcid-button" href="https://orcid.org/0000-0002-0789-0187" target="_blank" rel="noopener">ORCID</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editorFarah" tabindex="-1" role="dialog" aria-labelledby="editorFarahLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editorFarahLabel">Farah Maria Drumond Chequer Baldoni</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <img class="editor-modal-photo" src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/maria-farah.jpg" alt="Farah Maria Drumond Chequer Baldoni">
                    <p class="text-left">Possui graduação em Farmácia/Bioquímica pela Universidade Federal de Alfenas, mestrado em Toxicologia e doutorado em Ciências pela Faculdade de Ciências Farmacêuticas de Ribeirão Preto da Universidade de São Paulo. Atua como professora adjunta na Universidade Federal de São João Del-Rei, com experiência em Toxicologia Ambiental, Mutagênese, Nutrigenômica e temas relacionados à Farmácia.</p>
                    <p><a class="button orcid-button" href="https://orcid.org/0000-0003-3514-2132" target="_blank" rel="noopener">ORCID</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editorCristiane" tabindex="-1" role="dialog" aria-labelledby="editorCristianeLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editorCristianeLabel">Cristiane de Paula Rezende</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <img class="editor-modal-photo" src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/foto-profissional.jpg" alt="Cristiane de Paula Rezende">
                    <p class="text-left">Doutora em Medicamentos e Assistência Farmacêutica pela Faculdade de Farmácia da Universidade Federal de Minas Gerais. Farmacêutica e mestra pela Universidade Federal de Ouro Preto, especialista em Preceptoria no Sistema Único de Saúde e pesquisadora colaboradora do Centro de Estudos em Atenção Farmacêutica da UFMG. Atua também em iniciativas científicas relacionadas à segurança no uso de medicamentos.</p>
                    <p><a class="button orcid-button" href="https://orcid.org/0000-0001-7457-4187" target="_blank" rel="noopener">ORCID</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editorMaurilio" tabindex="-1" role="dialog" aria-labelledby="editorMaurilioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editorMaurilioLabel">Maurilio de Souza Cazarim</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <img class="editor-modal-photo" src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/maurilio-hehta3.jpg" alt="Maurilio de Souza Cazarim">
                    <p class="text-left">Farmacêutico e professor adjunto da Universidade Federal de Juiz de Fora. É docente permanente do Programa de Pós-Graduação em Enfermagem da UFJF e atua em núcleos e comitês ligados à avaliação de tecnologias em saúde e pesquisa. Possui trajetória em Assistência Farmacêutica, Farmácia Clínica, farmacoeconomia, estudos epidemiológicos e Saúde Baseada em Evidências.</p>
                    <p><a class="button orcid-button" href="https://orcid.org/0000-0002-2826-5903" target="_blank" rel="noopener">ORCID</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editorSandra" tabindex="-1" role="dialog" aria-labelledby="editorSandraLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editorSandraLabel">Sandra Bertelli Ribeiro de Castro</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <img class="editor-modal-photo" src="https://bjhp.crfmg.org.br/public/site/images/rafaelfontes/img-20221011-181717-1-29455832a3d83176949f7bed67358ada.jpg" alt="Sandra Bertelli Ribeiro de Castro">
                    <p class="text-left">Graduada em Farmácia e Bioquímica pela Universidade Federal de Juiz de Fora, mestre em Microbiologia Agrícola pela Universidade Federal de Lavras e doutora em Saúde pela UFJF. Atua como professora associada na Faculdade de Medicina do Mucuri da Universidade Federal dos Vales do Jequitinhonha e Mucuri, com projetos relacionados a moléculas bioativas, reposicionamento de fármacos, produtos naturais e modelos experimentais em saúde.</p>
                    <p><a class="button orcid-button" href="https://orcid.org/0000-0002-5535-0919" target="_blank" rel="noopener">ORCID</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editorWander" tabindex="-1" role="dialog" aria-labelledby="editorWanderLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editorWanderLabel">Wander de Jesus Jeremias</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <img class="editor-modal-photo" src="https://bjhp.crfmg.org.br/public/site/images/adminbjhp/wander-bjhp.jpg" alt="Wander de Jesus Jeremias">
                    <p class="text-left">Graduado em Farmácia pela Universidade Federal de Ouro Preto, mestre em Ciências Biológicas e doutor em Ciências da Saúde. É professor adjunto do Departamento de Farmácia da UFOP e orientador no Programa de Pós-Graduação em Ciências Farmacêuticas, com experiência em Bioquímica, Biologia Molecular, Farmacologia Molecular, diagnóstico e pesquisa translacional.</p>
                    <p><a class="button orcid-button" href="https://orcid.org/0000-0001-8204-6712" target="_blank" rel="noopener">ORCID</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editorMariaClaudia" tabindex="-1" role="dialog" aria-labelledby="editorMariaClaudiaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editorMariaClaudiaLabel">Maria Claudia Faria</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <img class="editor-modal-photo" src="https://via.placeholder.com/200?text=7" alt="Maria Claudia Faria">
                    <p class="text-left">Mini-currículo não informado no cadastro atual.</p>
                    <p><strong>ORCID:</strong> não informado</p>
                </div>
            </div>
        </div>
    </div>

    <!-- INDEXAÇÕES -->
    <section>
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
    </section>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/jquery.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/popper.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/libs/bootstrap.min.js?v=3.3.0.21" type="text/javascript"></script>
<script src="https://bjhp.crfmg.org.br/plugins/themes/healthSciences/js/main.js?v=3.3.0.21" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var carousel = document.querySelector('[data-comite-carousel]');

    if (!carousel) {
        return;
    }

    var editors = Array.prototype.slice.call(carousel.querySelectorAll('.editor'));
    var previous = carousel.querySelector('[data-comite-prev]');
    var next = carousel.querySelector('[data-comite-next]');
    var visibleCount = 4;
    var currentIndex = 0;

    function getLastStartIndex() {
        return Math.max(0, editors.length - visibleCount);
    }

    function updateEditors() {
        editors.forEach(function (editor, index) {
            var isVisible = index >= currentIndex && index < currentIndex + visibleCount;
            editor.classList.toggle('is-visible', isVisible);
        });

        previous.disabled = currentIndex === 0;
        next.disabled = currentIndex >= getLastStartIndex();
    }

    previous.addEventListener('click', function () {
        currentIndex = Math.max(0, currentIndex - visibleCount);
        updateEditors();
    });

    next.addEventListener('click', function () {
        currentIndex = Math.min(getLastStartIndex(), currentIndex + visibleCount);
        updateEditors();
    });

    updateEditors();
});
</script>
</body>
</html>
