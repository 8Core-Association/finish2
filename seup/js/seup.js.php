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


if (!defined('NOREQUIREUSER')) {

	define('NOREQUIREUSER', '1');

}

if (!defined('NOREQUIREDB')) {

	define('NOREQUIREDB', '1');

}

if (!defined('NOREQUIRESOC')) {

	define('NOREQUIRESOC', '1');

}

if (!defined('NOREQUIRETRAN')) {

	define('NOREQUIRETRAN', '1');

}

if (!defined('NOCSRFCHECK')) {

	define('NOCSRFCHECK', 1);

}

if (!defined('NOTOKENRENEWAL')) {

	define('NOTOKENRENEWAL', 1);

}

if (!defined('NOLOGIN')) {

	define('NOLOGIN', 1);

}

if (!defined('NOREQUIREMENU')) {

	define('NOREQUIREMENU', 1);

}

if (!defined('NOREQUIREHTML')) {

	define('NOREQUIREHTML', 1);

}

if (!defined('NOREQUIREAJAX')) {

	define('NOREQUIREAJAX', '1');

}





/**

 * \file    seup/js/seup.js.php

 * \ingroup seup

 * \brief   JavaScript file for module SEUP.

 */



// Load Dolibarr environment

$res = 0;

// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)

if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {

	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";

}

// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME

$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;

while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {

	$i--;

	$j--;

}

if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {

	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";

}

if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {

	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";

}

// Try main.inc.php using relative path

if (!$res && file_exists("../../main.inc.php")) {

	$res = @include "../../main.inc.php";

}

if (!$res && file_exists("../../../main.inc.php")) {

	$res = @include "../../../main.inc.php";

}

if (!$res) {

	die("Include of main fails");

}



// Define js type

header('Content-Type: application/javascript');

// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.

// You can use CTRL+F5 to refresh your browser cache.

if (empty($dolibarr_nocache)) {

	header('Cache-Control: max-age=3600, public, must-revalidate');

} else {

	header('Cache-Control: no-cache');

}

?>



/* Javascript library of module SEUP */





