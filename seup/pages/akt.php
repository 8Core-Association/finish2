<?php

/**
 * Plaćena licenca
 * (c) 2025 8Core Association
 * Tomislav Galić <tomislav@8core.hr>
 * Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima 
 * te ga je izričito zabranjeno umnožavati, distribuirati, mijenjati, objavljivati ili 
 * na drugi način eksploatirati bez pismenog odobrenja autora.
 * U skladu sa Zakonom o autorskom pravu i srodnim pravima 
 * (NN 167/03, 79/07, 80/11, 125/17), a osobito člancima 32. (pravo na umnožavanje), 35. 
 * (pravo na preradu i distribuciju) i 76. (kaznene odredbe), 
 * svako neovlašteno umnožavanje ili prerada ovog softvera smatra se prekršajem. 
 * Prema Kaznenom zakonu (NN 125/11, 144/12, 56/15), članak 228., stavak 1., 
 * prekršitelj se može kazniti novčanom kaznom ili zatvorom do jedne godine, 
 * a sud može izreći i dodatne mjere oduzimanja protivpravne imovinske koristi.
 * Bilo kakve izmjene, prijevodi, integracije ili dijeljenje koda bez izričitog pismenog 
 * odobrenja autora smatraju se kršenjem ugovora i zakona te će se pravno sankcionirati. 
 * Za sva pitanja, zahtjeve za licenciranjem ili dodatne informacije obratite se na info@8core.hr.
 */
/**
 * 	\defgroup   seup     Module SEUP
 *  \brief      SEUP module descriptor.
 *
 *  \file       htdocs/seup/core/modules/modSEUP.class.php
 *  \ingroup    seup
 *  \brief      Description and activation file for module SEUP
 */


// Onemogući CSRF za ovu stranicu

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);



// Učitaj Dolibarr

require_once __DIR__ . '/../../main.inc.php';

// Učitaj prijevode

$langs->loadLangs(['seup@seup']);






// Prikaz headera

llxHeader('', $langs->trans('NovaStranica'));

?>



<div class="container py-5">

  <div class="row g-4 mb-4">

    <!-- Prvi stupac: OnlyOffice editor -->

    <div class="col-12 col-md-6">

      <div class="custom-container bg-white shadow rounded-3 p-4">

        <h5 class="mb-3"><?php echo $langs->trans('Kontejner1Naslov'); ?></h5>

        <p class="mb-3"><?php echo $langs->trans('Kontejner1Sadrzaj'); ?></p>

        
        <div id="onlyoffice-editor" style="width:100%; height:600px;"></div>

        <script>

          var config = <?php echo json_encode($onlyOfficeConfig); ?>;

          new DocsAPI.DocEditor('onlyoffice-editor', Object.assign({

            width: '100%', height: '600px'

          }, config));

        </script>

      </div>

    </div>



    <!-- Drugi stupac -->

    <div class="col-12 col-md-6">

      <div class="custom-container bg-white shadow rounded-3 p-4">

        <h5 class="mb-3"><?php echo $langs->trans('Kontejner2Naslov'); ?></h5>

        <p class="mb-0"><?php echo $langs->trans('Kontejner2Sadrzaj'); ?></p>

      </div>

    </div>

  </div>



  <!-- Full-width red -->

  <div class="row">

    <div class="col-12">

      <div class="custom-container bg-light shadow rounded-3 p-4">

        <h5 class="text-center mb-3"><?php echo $langs->trans('FullWidthNaslov'); ?></h5>

        <p class="text-center mb-0"><?php echo $langs->trans('FullWidthSadrzaj'); ?></p>

      </div>

    </div>

  </div>

</div>



<?php

// Footer & JS

print '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>';

llxFooter();

$db->close();

?>

