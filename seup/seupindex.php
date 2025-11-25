<?php
/**
 * Plaćena licenca
 * (c) 2025 Tomislav Galić <tomislav@8core.hr>
 * Suradnik: Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zabranjeno ga je
 * distribuirati ili mijenjati bez izričitog dopuštenja autora.
 */
/**
 *	\file       seup2/seup2index.php
 *	\ingroup    seup2
 *	\brief      Home page of seup2 top menu
 */


// Učitaj Dolibarr okruženje
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Pokušaj učitati main.inc.php iz korijenskog direktorija weba, koji je određen na temelju vrijednosti SCRIPT_FILENAME.
$tmp = empty($_SERVER['SCRIPT_FILENAME']) 
? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Pokušaj učitati main.inc.php koristeći relativnu putanju

if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once __DIR__ . '/../seup/class/changelog_sistem.class.php';

// Učitaj datoteke prijevoda potrebne za stranicu
$langs->loadLangs(array("seup@seup"));

$action = GETPOST('action', 'aZ09');

$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Sigurnosna provjera – zaštita ako je korisnik eksterni
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}


require_once __DIR__ . '/class/predmet_helper.class.php';


// Provjeri da li postoje potrebne tablice u bazi - ako ne postoje, kreiraj ih
Predmet_helper::createSeupDatabaseTables($db);

// Fetch real statistics from database
$stats = [
    'predmeti' => 0,
    'dokumenti' => 0,
    'korisnici' => 0,
    'ustanove' => 0
];

// Setup check - provjeri konfiguraciju sustava
$setup_checks = [
    'company' => false,
    'users' => false,
    'oznaka_ustanove' => false,
    'interna_oznaka' => false,
    'klasifikacijska_oznaka' => false
];

// Check company data using global settings (consistent approach)
$g = function($k) {
    global $conf;
    if (function_exists('getDolGlobalString')) return (string) getDolGlobalString($k, '');
    return isset($conf->global->$k) ? (string) $conf->global->$k : '';
};
$setup_checks['company'] = 
    trim($g('MAIN_INFO_SOCIETE_NOM'))     !== '' &&
    trim($g('MAIN_INFO_SOCIETE_ADDRESS')) !== '' &&
    trim($g('MAIN_INFO_SOCIETE_ZIP'))     !== '' &&
    trim($g('MAIN_INFO_SOCIETE_TOWN'))    !== '' &&
    trim($g('MAIN_INFO_SOCIETE_COUNTRY')) !== '' &&
    trim($g('MAIN_INFO_SOCIETE_MAIL'))    !== '';

// Check users
$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1";
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $setup_checks['users'] = (int)$obj->count > 0;
}

// Check oznaka ustanove
$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_oznaka_ustanove";
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $setup_checks['oznaka_ustanove'] = (int)$obj->count > 0;
}

// Check interna oznaka korisnika
$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika";
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $setup_checks['interna_oznaka'] = (int)$obj->count > 0;
}

// Check klasifikacijska oznaka
$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka";
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $setup_checks['klasifikacijska_oznaka'] = (int)$obj->count > 0;
}

$all_configured = array_reduce($setup_checks, function($carry, $item) { return $carry && $item; }, true);

// Count predmeti
$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_predmet";
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $stats['predmeti'] = (int)$obj->count;
}

// Count documents
$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files WHERE filepath LIKE 'SEUP%'";
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $stats['dokumenti'] = (int)$obj->count;
}

// Count users
$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika";
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $stats['korisnici'] = (int)$obj->count;
}

// Count ustanove
$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_oznaka_ustanove";
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $stats['ustanove'] = (int)$obj->count;
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", "SEUP - Elektronski sustav uredskog poslovanja", '', '', 0, 0, '', '', '', 'mod-seup page-index');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
print '<link href="css/setup-modal.css" rel="stylesheet">';

// Main hero section
print '<main class="seup-hero">';

// Copyright footer
print '<footer class="seup-footer">';
print '<div class="seup-footer-content">';
print '<div class="seup-footer-left">';
print '<p>Sva prava pridržana © <a href="https://8core.hr" target="_blank" rel="noopener">8Core Association</a> 2014 - ' . date('Y') . '</p>';
print '</div>';
print '<div class="seup-footer-right">';
print '<p class="seup-version">' . Changelog_Sistem::getVersion() . '</p>';
print '</div>';
print '</div>';
print '</footer>';

// Floating background elements
print '<div class="seup-floating-elements">';
for ($i = 1; $i <= 5; $i++) {
    print '<div class="seup-floating-element"></div>';
}
print '</div>';

print '<div class="seup-hero-content">';
print '<h1 class="seup-hero-title">Sustav elektronskog uredskog poslovanja</h1>';
print '<p class="seup-hero-subtitle">Moderan i efikasan način upravljanja dokumentima, predmetima i administrativnim procesima u javnoj upravi</p>';

// Action cards
print '<div class="seup-actions">';

// Novi Predmet card
print '<a href="' . dol_buildpath('/custom/seup/pages/novi_predmet.php', 1) . '" class="seup-action-card">';
print '<div class="seup-action-icon"><i class="fas fa-plus"></i></div>';
print '<h3 class="seup-action-title">Novi Predmet</h3>';
print '<p class="seup-action-description">Kreirajte novi predmet s klasifikacijskim oznakama i povezanim dokumentima</p>';
print '</a>';

// Predmeti card
print '<a href="' . dol_buildpath('/custom/seup/pages/predmeti.php', 1) . '" class="seup-action-card">';
print '<div class="seup-action-icon"><i class="fas fa-folder-open"></i></div>';
print '<h3 class="seup-action-title">Predmeti</h3>';
print '<p class="seup-action-description">Pregledajte i upravljajte svim aktivnim predmetima u sustavu</p>';
print '</a>';

// Plan Klasifikacijskih Oznaka card
print '<a href="' . dol_buildpath('/custom/seup/pages/plan_klasifikacijskih_oznaka.php', 1) . '" class="seup-action-card">';
print '<div class="seup-action-icon"><i class="fas fa-sitemap"></i></div>';
print '<h3 class="seup-action-title">Plan Klasifikacijskih Oznaka</h3>';
print '<p class="seup-action-description">Upravljanje hijerarhijskim sustavom klasifikacije dokumenata</p>';
print '</a>';

// Postavke card
print '<a href="' . dol_buildpath('/custom/seup/pages/postavke.php', 1) . '" class="seup-action-card">';
print '<div class="seup-action-icon"><i class="fas fa-cog"></i></div>';
print '<h3 class="seup-action-title">Postavke</h3>';
print '<p class="seup-action-description">Konfigurirajte sustav, korisničke oznake i parametre ustanove</p>';
print '</a>';

print '</div>'; // seup-actions

// Statistics section
print '<div class="seup-stats">';
print '<div class="seup-stats-grid">';

print '<div class="seup-stat-item">';
print '<span class="seup-stat-number stat-predmeti">0</span>';
print '<span class="seup-stat-label">Aktivnih Predmeta</span>';
print '</div>';

print '<div class="seup-stat-item">';
print '<span class="seup-stat-number stat-dokumenti">0</span>';
print '<span class="seup-stat-label">Dokumenata</span>';
print '</div>';

print '<div class="seup-stat-item">';
print '<span class="seup-stat-number stat-korisnici">0</span>';
print '<span class="seup-stat-label">Korisnika</span>';
print '</div>';

print '<div class="seup-stat-item">';
print '<span class="seup-stat-number stat-ustanove">0</span>';
print '<span class="seup-stat-label">Ustanova</span>';
print '</div>';

print '</div>'; // seup-stats-grid
print '</div>'; // seup-stats

print '</div>'; // seup-hero-content
print '</main>';

// Setup Check Modal
if (!$all_configured) {
    print '<div class="seup-modal show" id="setupCheckModal">';
    print '<div class="seup-modal-content">';
    print '<div class="seup-modal-header">';
    print '<h5 class="seup-modal-title"><i class="fas fa-cogs"></i>Provjera Konfiguracije Sustava</h5>';
    print '<button type="button" class="seup-modal-close-btn"><i class="fas fa-times"></i></button>';
    print '</div>';
    print '<div class="seup-modal-body">';
    print '<p class="setup-intro-text">Prije korištenja sustava potrebno je konfigurirati sljedeće postavke<br>molimo da se obratite administratoru sustava:</p>';

    print '<div class="setup-checklist">';

    // Podaci o pravnoj osobi
    print '<div class="setup-check-item ' . ($setup_checks['company'] ? 'ok' : 'fail') . '">';
    print '  <div class="setup-check-icon">' . ($setup_checks['company'] ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-triangle"></i>') . '</div>';
    print '  <div class="setup-check-content">';
    print '    <h4 class="setup-check-title">Podaci o pravnoj osobi</h4>';
    print '    <p class="setup-check-description">' . ($setup_checks['company'] ? 'Konfiguracija je završena' : 'Potrebna je konfiguracija') . '</p>';
    print '  </div>';
    if (!$setup_checks['company']) {
        print '  <a href="' . DOL_URL_ROOT . '/admin/company.php" class="seup-btn seup-btn-sm seup-btn-primary">Uredi</a>';
    }
    print '</div>';

    // Users check
    print '<div class="setup-check-item">';
    if ($setup_checks['users']) {
        print '<div class="setup-check-icon"><i class="fas fa-check-circle"></i></div>';
        print '<div class="setup-check-content">';
        print '<h4 class="setup-check-title">Barem jedan zaposlenik</h4>';
        print '<p class="setup-check-description">Konfiguracija je završena</p>';
        print '</div>';
    } else {
        print '<div class="setup-check-icon"><i class="fas fa-exclamation-triangle"></i></div>';
        print '<div class="setup-check-content">';
        print '<h4 class="setup-check-title">Barem jedan zaposlenik</h4>';
        print '<p class="setup-check-description">Potrebna je konfiguracija</p>';
        print '</div>';
        print '<a href="' . DOL_URL_ROOT . '/user/card.php" class="seup-btn seup-btn-sm seup-btn-primary">Dodaj</a>';
    }
    print '</div>';
    
    // Oznaka ustanove check
    print '<div class="setup-check-item">';
    if ($setup_checks['oznaka_ustanove']) {
        print '<div class="setup-check-icon"><i class="fas fa-check-circle"></i></div>';
        print '<div class="setup-check-content">';
        print '<h4 class="setup-check-title">Interna oznaka ustanove</h4>';
        print '<p class="setup-check-description">Konfiguracija je završena</p>';
        print '</div>';
    } else {
        print '<div class="setup-check-icon"><i class="fas fa-exclamation-triangle"></i></div>';
        print '<div class="setup-check-content">';
        print '<h4 class="setup-check-title">Interna oznaka ustanove</h4>';
        print '<p class="setup-check-description">Potrebna je konfiguracija</p>';
        print '</div>';
        print '<a href="' . dol_buildpath('/custom/seup/pages/postavke.php', 1) . '" class="seup-btn seup-btn-sm seup-btn-primary">Dodaj</a>';
    }
    print '</div>';
    
    // Interna oznaka korisnika check
    print '<div class="setup-check-item">';
    if ($setup_checks['interna_oznaka']) {
        print '<div class="setup-check-icon"><i class="fas fa-check-circle"></i></div>';
        print '<div class="setup-check-content">';
        print '<h4 class="setup-check-title">Interna oznaka korisnika</h4>';
        print '<p class="setup-check-description">Konfiguracija je završena</p>';
        print '</div>';
    } else {
        print '<div class="setup-check-icon"><i class="fas fa-exclamation-triangle"></i></div>';
        print '<div class="setup-check-content">';
        print '<h4 class="setup-check-title">Interna oznaka korisnika</h4>';
        print '<p class="setup-check-description">Potrebna je konfiguracija</p>';
        print '</div>';
        print '<a href="' . dol_buildpath('/custom/seup/pages/postavke.php', 1) . '" class="seup-btn seup-btn-sm seup-btn-primary">Dodaj</a>';
    }
    print '</div>';
    
    // Klasifikacijska oznaka check
    print '<div class="setup-check-item">';
    if ($setup_checks['klasifikacijska_oznaka']) {
        print '<div class="setup-check-icon"><i class="fas fa-check-circle"></i></div>';
        print '<div class="setup-check-content">';
        print '<h4 class="setup-check-title">Barem jedna klasifikacijska oznaka</h4>';
        print '<p class="setup-check-description">Konfiguracija je završena</p>';
        print '</div>';
    } else {
        print '<div class="setup-check-icon"><i class="fas fa-exclamation-triangle"></i></div>';
        print '<div class="setup-check-content">';
        print '<h4 class="setup-check-title">Barem jedna klasifikacijska oznaka</h4>';
        print '<p class="setup-check-description">Potrebna je konfiguracija</p>';
        print '</div>';
        print '<a href="' . dol_buildpath('/custom/seup/pages/postavke.php', 1) . '" class="seup-btn seup-btn-sm seup-btn-primary">Dodaj</a>';
    }
    print '</div>';
    
    print '</div>'; // setup-checklist
    print '</div>';
    print '<div class="seup-modal-footer">';
    print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
    print '</div>';
    print '</div>';
    print '</div>';
} else {
    // Sve je konfigurirano - prikaži success modal samo ako nije već prikazan
    $show_success_modal = empty($_SESSION['setup_success_shown']);
    if ($show_success_modal) {
        print '<div class="seup-modal show" id="setupSuccessModal">';
        print '<div class="seup-modal-content setup-success-modal">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-check-circle text-success"></i> Sustav Konfiguriran</h5>';
        print '</div>';
        print '<div class="seup-modal-body text-center">';
        print '<div class="setup-success-icon">';
        print '<i class="fas fa-check-circle"></i>';
        print '</div>';
        print '<p class="setup-success-message">Vaš je sustav u potpunosti konfiguriran, možete poćeti sa radom</p>';
        print '</div>';
        print '</div>';
        print '</div>';
        $_SESSION['setup_success_shown'] = true;
    }
}

// Pass stats to JavaScript
print '<script>';
print 'window.seupStats = ' . json_encode($stats) . ';';
print 'if (document.getElementById("setupSuccessModal")) {';
print '  setTimeout(function() {';
print '    var modal = document.getElementById("setupSuccessModal");';
print '    if (modal) {';
print '      modal.classList.remove("show");';
print '      setTimeout(function() { modal.remove(); }, 300);';
print '    }';
print '  }, 2000);';
print '}';
print '</script>';
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="js/setup-modal.js"></script>';

// End of page
llxFooter();
$db->close();