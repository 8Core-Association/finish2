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

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
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

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/seup.lib.php';
require_once '../class/changelog_sistem.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("errors", "admin", "seup@seup"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * Actions
 */

// None

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "SEUPSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-seup page-admin_about');

// Load modern CSS
print '<link href="../css/seup-modern.css" rel="stylesheet">';

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

// Configuration header
$head = seupAdminPrepareHead();

print '<div class="seup-settings-hero">';

// Floating elements for visual effect
print '<div class="seup-floating-elements">';
for ($i = 1; $i <= 5; $i++) {
    print '<div class="seup-floating-element"></div>';
}
print '</div>';

print '<div class="seup-settings-content">';

// Header section
print '<div class="seup-settings-header">';
print '<h1 class="seup-settings-title">O SEUP Modulu</h1>';
print '<p class="seup-settings-subtitle">Informacije o modulu, licenci i autorskim pravima</p>';
print '</div>';

// Navigation tabs
print '<div class="seup-admin-tabs">';
// Main content cards
print '<div class="seup-settings-grid">';

// Module Info Card
print '<div class="seup-settings-card">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-cube"></i></div>';
print '<h3 class="seup-card-title">SEUP - Sustav Elektronskog Uredskog Poslovanja</h3>';
print '<p class="seup-card-description">Moderni modul za upravljanje dokumentima i predmetima u javnoj upravi</p>';
print '</div>';
print '<div class="seup-form">';

// Version and basic info
print '<div class="seup-form-grid">';
print '<div class="seup-form-group">';
print '<label class="seup-label"><i class="fas fa-tag me-2"></i>Verzija</label>';
print '<div class="seup-version-display">' . Changelog_Sistem::VERSION . '</div>';
print '</div>';
print '<div class="seup-form-group">';
print '<label class="seup-label"><i class="fas fa-calendar me-2"></i>Datum izdanja</label>';
print '<div class="seup-version-display">' . date('d.m.Y') . '</div>';
print '</div>';
print '<div class="seup-form-group">';
print '<label class="seup-label"><i class="fas fa-code me-2"></i>Kompatibilnost</label>';
print '<div class="seup-version-display">Dolibarr 19.0+</div>';
print '</div>';
print '<div class="seup-form-group">';
print '<label class="seup-label"><i class="fas fa-shield-alt me-2"></i>Licenca</label>';
print '<div class="seup-version-display seup-license-badge">Vlasnička</div>';
print '</div>';
print '</div>';

print '</div>'; // seup-form
print '</div>'; // seup-settings-card

// Features Card
print '<div class="seup-settings-card">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-star"></i></div>';
print '<h3 class="seup-card-title">Značajke Modula</h3>';
print '<p class="seup-card-description">Napredne funkcionalnosti za upravljanje uredskim poslovanjem</p>';
print '</div>';
print '<div class="seup-form">';

$features = [
    ['icon' => 'fas fa-folder-plus', 'title' => 'Upravljanje predmetima', 'desc' => 'Kreiranje i praćenje predmeta s klasifikacijskim oznakama'],
    ['icon' => 'fas fa-file-upload', 'title' => 'Upravljanje dokumentima', 'desc' => 'Upload, pregled i organizacija dokumenata'],
    ['icon' => 'fas fa-building', 'title' => 'Oznake ustanova', 'desc' => 'Konfiguracija osnovnih podataka ustanove'],
    ['icon' => 'fas fa-users', 'title' => 'Interne oznake korisnika', 'desc' => 'Upravljanje korisničkim oznakama i radnim mjestima'],
    ['icon' => 'fas fa-sitemap', 'title' => 'Plan klasifikacijskih oznaka', 'desc' => 'Hijerarhijski sustav klasifikacije'],
    ['icon' => 'fas fa-tags', 'title' => 'Tagovi', 'desc' => 'Fleksibilno označavanje s color pickerom'],
    ['icon' => 'fas fa-chart-bar', 'title' => 'Statistike', 'desc' => 'Pregled aktivnosti i izvještaji'],
    ['icon' => 'fas fa-cloud', 'title' => 'Nextcloud integracija', 'desc' => 'Sinkronizacija dokumenata s vanjskim sustavima']
];

print '<div class="seup-features-showcase">';
foreach ($features as $feature) {
    print '<div class="seup-feature-item">';
    print '<div class="seup-feature-icon-small"><i class="' . $feature['icon'] . '"></i></div>';
    print '<div class="seup-feature-text">';
    print '<h4>' . $feature['title'] . '</h4>';
    print '<p>' . $feature['desc'] . '</p>';
    print '</div>';
    print '</div>';
}
print '</div>';

print '</div>'; // seup-form
print '</div>'; // seup-settings-card

print '</div>'; // seup-settings-grid

// License Section - Full Width
print '<div class="seup-settings-card seup-card-wide seup-license-card">';
print '<div class="seup-card-header seup-license-header">';
print '<div class="seup-card-icon"><i class="fas fa-certificate"></i></div>';
print '<h3 class="seup-card-title">Licenca i Autorska Prava</h3>';
print '<p class="seup-card-description">Informacije o vlasništvu i uvjetima korištenja</p>';
print '</div>';
print '<div class="seup-form">';

// License warning
print '<div class="seup-license-warning">';
print '<div class="seup-warning-icon"><i class="fas fa-exclamation-triangle"></i></div>';
print '<div class="seup-warning-content">';
print '<h4>Plaćena Licenca - Sva Prava Pridržana</h4>';
print '<p>Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima.</p>';
print '</div>';
print '</div>';

// Authors section
print '<div class="seup-form-grid">';
print '<div class="seup-form-group">';
print '<label class="seup-label"><i class="fas fa-copyright me-2"></i>Autorska Prava</label>';
print '<div class="seup-authors-grid">';
print '<div class="seup-author-card">';
print '<div class="seup-author-avatar"><i class="fas fa-user"></i></div>';
print '<div class="seup-author-details">';
print '<h5>Tomislav Galić</h5>';
print '<p>Glavni developer</p>';
print '<a href="mailto:tomislav@8core.hr"><i class="fas fa-envelope me-1"></i>tomislav@8core.hr</a>';
print '</div>';
print '</div>';
print '<div class="seup-author-card">';
print '<div class="seup-author-avatar"><i class="fas fa-user"></i></div>';
print '<div class="seup-author-details">';
print '<h5>Marko Šimunović</h5>';
print '<p>Suradnik</p>';
print '<a href="mailto:marko@8core.hr"><i class="fas fa-envelope me-1"></i>marko@8core.hr</a>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Company info
print '<div class="seup-form-group">';
print '<label class="seup-label"><i class="fas fa-building me-2"></i>Tvrtka</label>';
print '<div class="seup-company-details">';
print '<div class="seup-company-header">';
print '<h5>8Core Association</h5>';
print '<p class="seup-company-location">Požega - Hrvatska EU</p>';
print '<div class="seup-company-ids">';
print '<span class="seup-company-id">OIB: 14484857067</span>';
print '<span class="seup-company-id">MB: 04611799</span>';
print '</div>';
print '</div>';
print '<div class="seup-contact-item">';
print '<i class="fas fa-globe"></i>';
print '<a href="https://8core.hr" target="_blank">https://8core.hr</a>';
print '</div>';
print '<div class="seup-contact-item">';
print '<i class="fas fa-envelope"></i>';
print '<a href="mailto:info@8core.hr">info@8core.hr</a>';
print '</div>';
print '<div class="seup-contact-item">';
print '<i class="fas fa-phone"></i>';
print '<a href="tel:+385099851071">+385 099 851 0717</a>';
print '</div>';
print '<div class="seup-contact-item">';
print '<i class="fas fa-calendar"></i>';
print '<span>2014 - ' . date('Y') . '</span>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Legal notice
print '<div class="seup-legal-notice">';
print '<h4><i class="fas fa-gavel me-2"></i>Pravne Napomene</h4>';
print '<div class="seup-legal-grid">';
print '<div class="seup-legal-section">';
print '<h5>Zabranjeno je:</h5>';
print '<ul>';
print '<li>Umnožavanje bez pismenog odobrenja</li>';
print '<li>Distribucija ili dijeljenje koda</li>';
print '<li>Mijenjanje ili prerada softvera</li>';
print '<li>Objavljivanje ili komercijalna eksploatacija</li>';
print '</ul>';
print '</div>';
print '<div class="seup-legal-section">';
print '<h5>Pravni okvir:</h5>';
print '<p>Zakon o autorskom pravu i srodnim pravima (NN 167/03, 79/07, 80/11, 125/17) i Kazneni zakon (NN 125/11, 144/12, 56/15), članak 228.</p>';
print '<p class="seup-penalty"><strong>Kazne:</strong> Prekršitelji se mogu kazniti novčanom kaznom ili zatvorom do jedne godine, uz mogućnost oduzimanja protivpravne imovinske koristi.</p>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // seup-form
print '</div>'; // seup-license-card

// Support Section
print '<div class="seup-settings-cards-grid">';

print '<div class="seup-settings-card-trigger" onclick="window.location.href=\'https://8core.hr/store/index.php\'">';
print '<div class="seup-card-icon"><i class="fas fa-question-circle"></i></div>';
print '<div class="seup-card-content">';
print '<h4 class="seup-card-title">Tehnička Podrška</h4>';
print '<p class="seup-card-description">Za tehnička pitanja i probleme s modulom</p>';
print '</div>';
print '<div class="seup-card-arrow"><i class="fas fa-arrow-right"></i></div>';
print '</div>';

print '<div class="seup-settings-card-trigger" onclick="window.location.href=\'https://8core.hr/store/index.php\'">';
print '<div class="seup-card-icon"><i class="fas fa-key"></i></div>';
print '<div class="seup-card-content">';
print '<h4 class="seup-card-title">Licenciranje</h4>';
print '<p class="seup-card-description">Za zahtjeve za dodatnim licencama</p>';
print '</div>';
print '<div class="seup-card-arrow"><i class="fas fa-arrow-right"></i></div>';
print '</div>';

print '<div class="seup-settings-card-trigger" onclick="window.location.href=\'https://8core.hr/store/index.php\'">';
print '<div class="seup-card-icon"><i class="fas fa-cogs"></i></div>';
print '<div class="seup-card-content">';
print '<h4 class="seup-card-title">Prilagodbe</h4>';
print '<p class="seup-card-description">Za custom razvoj i prilagodbe</p>';
print '</div>';
print '<div class="seup-card-arrow"><i class="fas fa-arrow-right"></i></div>';
print '</div>';

print '<div class="seup-settings-card-trigger" onclick="window.location.href=\'https://8core.hr/store/index.php\'">';
print '<div class="seup-card-icon"><i class="fas fa-life-ring"></i></div>';
print '<div class="seup-card-content">';
print '<h4 class="seup-card-title">Općenita Podrška</h4>';
print '<p class="seup-card-description">Za sva ostala pitanja i informacije</p>';
print '</div>';
print '<div class="seup-card-arrow"><i class="fas fa-arrow-right"></i></div>';
print '</div>';

print '</div>'; // seup-settings-cards-grid

print '</div>'; // seup-settings-content

print '</div>'; // seup-settings-hero

// Footer
print '<div class="seup-footer">';
print '<div class="seup-footer-content">';
print '<div class="seup-footer-left">';
print '<p>&copy; 2014-' . date('Y') . ' <a href="https://8core.hr" target="_blank">8Core Association</a></p>';
print '</div>';
print '<div class="seup-footer-right">';
print '<p class="seup-version">' . Changelog_Sistem::getVersion() . '</p>';
print '</div>';
print '</div>';
print '</div>';

// Additional CSS for about page specific styling
print '<style>
.seup-version-display {
    background: var(--primary-50);
    border: 1px solid var(--primary-200);
    border-radius: var(--radius-lg);
    padding: var(--space-3);
    font-weight: var(--font-semibold);
    color: var(--primary-800);
}

.seup-license-badge {
    background: var(--error-50);
    border-color: var(--error-200);
    color: var(--error-800);
}

.seup-features-showcase {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-4);
}

.seup-feature-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-3);
    background: var(--neutral-50);
    border-radius: var(--radius-lg);
    border: 1px solid var(--neutral-200);
    transition: all var(--transition-fast);
}

.seup-feature-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary-200);
}

.seup-feature-icon-small {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--accent-500), var(--accent-600));
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    flex-shrink: 0;
}

.seup-feature-text h4 {
    margin: 0 0 var(--space-1) 0;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--secondary-900);
}

.seup-feature-text p {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--secondary-600);
    line-height: var(--leading-relaxed);
}

.seup-license-card .seup-card-header {
    background: linear-gradient(135deg, var(--error-50), var(--error-100));
    border-bottom-color: var(--error-200);
}

.seup-license-header .seup-card-icon {
    background: linear-gradient(135deg, var(--error-500), var(--error-600));
}

.seup-authors-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-3);
}

.seup-author-card {
    background: white;
    border: 1px solid var(--neutral-200);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
    transition: all var(--transition-fast);
}

.seup-author-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary-200);
}

.seup-author-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.seup-author-details h5 {
    margin: 0 0 var(--space-1) 0;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--secondary-900);
}

.seup-author-details p {
    margin: 0 0 var(--space-1) 0;
    font-size: var(--text-xs);
    color: var(--secondary-600);
}

.seup-author-details a {
    color: var(--primary-600);
    text-decoration: none;
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.seup-author-details a:hover {
    color: var(--primary-700);
    text-decoration: underline;
}

.seup-company-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-2);
}

.seup-company-header {
    grid-column: 1 / -1;
    background: var(--primary-50);
    border: 1px solid var(--primary-200);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    margin-bottom: var(--space-3);
    text-align: center;
}

.seup-company-header h5 {
    margin: 0 0 var(--space-2) 0;
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--primary-800);
}

.seup-company-location {
    margin: 0 0 var(--space-2) 0;
    font-size: var(--text-sm);
    color: var(--primary-700);
    font-weight: var(--font-medium);
}

.seup-company-ids {
    display: flex;
    justify-content: center;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.seup-company-id {
    background: var(--primary-100);
    color: var(--primary-800);
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-md);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    font-family: var(--font-family-mono);
}

.seup-contact-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2);
    background: var(--primary-50);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
}

.seup-contact-item i {
    width: 16px;
    text-align: center;
    color: var(--primary-600);
}

.seup-contact-item a {
    color: var(--primary-700);
    text-decoration: none;
    font-weight: var(--font-medium);
}

.seup-contact-item a:hover {
    color: var(--primary-800);
    text-decoration: underline;
}

.seup-legal-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-4);
}

.seup-legal-section h5 {
    margin: 0 0 var(--space-2) 0;
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--warning-800);
}

.seup-legal-section ul {
    margin: 0;
    padding-left: var(--space-4);
    color: var(--warning-700);
}

.seup-legal-section li {
    margin-bottom: var(--space-1);
    font-size: var(--text-sm);
}

.seup-legal-section p {
    margin: 0 0 var(--space-2) 0;
    color: var(--warning-700);
    font-size: var(--text-sm);
    line-height: var(--leading-relaxed);
}

.seup-penalty {
    background: var(--error-100);
    padding: var(--space-2);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--error-500);
    color: var(--error-800);
    font-weight: var(--font-medium);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .seup-features-showcase {
        grid-template-columns: 1fr;
    }
    
    .seup-authors-grid {
        grid-template-columns: 1fr;
    }
    
    .seup-company-details {
        grid-template-columns: 1fr;
    }
    
    .seup-legal-grid {
        grid-template-columns: 1fr;
    }
    
    .seup-settings-cards-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .seup-settings-cards-grid {
        grid-template-columns: 1fr;
    }
}
</style>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();