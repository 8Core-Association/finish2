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

$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once __DIR__ . '/../class/zaprimanje_helper.class.php';
require_once __DIR__ . '/../class/changelog_sistem.class.php';

$langs->loadLangs(array("seup@seup"));

$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = GETPOST('action', 'alpha');
    if ($action === 'export_excel') {
        $filters = [
            'godina' => GETPOST('filter_godina', 'alpha'),
            'mjesec' => GETPOST('filter_mjesec', 'int'),
            'nacin' => GETPOST('filter_nacin', 'alpha'),
            'tip' => GETPOST('filter_tip', 'alpha'),
            'search' => GETPOST('search', 'alpha')
        ];
        Zaprimanje_Helper::exportExcelFiltered($db, $filters);
        exit;
    }
}

$filters = [
    'godina' => GETPOST('filter_godina', 'alpha'),
    'mjesec' => GETPOST('filter_mjesec', 'int'),
    'nacin' => GETPOST('filter_nacin', 'alpha'),
    'tip' => GETPOST('filter_tip', 'alpha'),
    'search' => GETPOST('search', 'alpha')
];

$zaprimanja = Zaprimanje_Helper::getZaprimanjaAll($db, $filters);

$form = new Form($db);
llxHeader("", "Zaprimanja", '', '', 0, 0, '', '', '', 'mod-seup page-zaprimanja');

print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

print '<main class="seup-settings-hero">';

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

print '<div class="seup-floating-elements">';
for ($i = 1; $i <= 5; $i++) {
    print '<div class="seup-floating-element"></div>';
}
print '</div>';

print '<div class="seup-settings-content">';
print '<div class="seup-settings-header">';
print '<h1 class="seup-settings-title">Zaprimanja Dokumenata</h1>';
print '<p class="seup-settings-subtitle">Pregled i upravljanje svim zaprimanjima dokumenata iz cijelog sustava</p>';
print '</div>';

print '<div class="seup-zaprimanja-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-inbox"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Sva Zaprimanja</h3>';
print '<p class="seup-card-description">Pregled svih zaprimanja s naprednim filterima i mogućnostima izvoza</p>';
print '</div>';
print '</div>';

print '<div class="seup-table-controls">';
print '<div class="seup-search-container">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-search seup-search-icon"></i>';
print '<input type="text" id="searchInput" class="seup-search-input" placeholder="Pretraži zaprimanja...">';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-table-container">';
print '<table class="seup-table">';
print '<thead class="seup-table-header">';
print '<tr>';
print '<th class="seup-table-th"><i class="fas fa-calendar me-2"></i>Datum</th>';
print '<th class="seup-table-th"><i class="fas fa-layer-group me-2"></i>Klasa</th>';
print '<th class="seup-table-th"><i class="fas fa-user me-2"></i>Pošiljatelj</th>';
print '<th class="seup-table-th"><i class="fas fa-hashtag me-2"></i>Broj</th>';
print '<th class="seup-table-th"><i class="fas fa-file me-2"></i>Dokument</th>';
print '<th class="seup-table-th"><i class="fas fa-tags me-2"></i>Tip</th>';
print '<th class="seup-table-th"><i class="fas fa-shipping-fast me-2"></i>Način</th>';
print '</tr>';
print '</thead>';
print '<tbody class="seup-table-body">';

if (count($zaprimanja) > 0) {
    foreach ($zaprimanja as $index => $zaprimanje) {
        $rowClass = ($index % 2 === 0) ? 'seup-table-row-even' : 'seup-table-row-odd';
        print '<tr class="seup-table-row ' . $rowClass . '">';

        print '<td class="seup-table-td">';
        print '<div class="seup-date-info">';
        print '<i class="fas fa-calendar me-2"></i>';
        print date('d.m.Y', strtotime($zaprimanje->datum_zaprimanja));
        print '</div>';
        print '</td>';

        print '<td class="seup-table-td">';
        if ($zaprimanje->klasa_format) {
            $url = dol_buildpath('/custom/seup/pages/predmet.php', 1) . '?id=' . $zaprimanje->ID_predmeta;
            print '<a href="' . $url . '" class="seup-badge seup-badge-primary seup-klasa-link">' . htmlspecialchars($zaprimanje->klasa_format) . '</a>';
        } else {
            print '<span class="seup-badge seup-badge-neutral">—</span>';
        }
        print '</td>';

        print '<td class="seup-table-td">';
        print '<div class="seup-primatelj-info">';
        print '<i class="fas fa-user me-2"></i>';
        print '<span class="seup-primatelj-name">' . htmlspecialchars($zaprimanje->posiljatelj_naziv) . '</span>';
        print '</div>';
        print '</td>';

        print '<td class="seup-table-td">';
        if ($zaprimanje->posiljatelj_broj) {
            print '<span class="seup-badge seup-badge-info">' . htmlspecialchars($zaprimanje->posiljatelj_broj) . '</span>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';

        print '<td class="seup-table-td">';
        if ($zaprimanje->doc_filename) {
            print '<div class="seup-dokument-info" title="' . htmlspecialchars($zaprimanje->doc_filename) . '">';
            print '<i class="fas fa-file-pdf me-2"></i>';
            print dol_trunc($zaprimanje->doc_filename, 30);
            print '</div>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';

        print '<td class="seup-table-td">';
        $tipColors = [
            'novi_akt' => 'seup-badge-success',
            'prilog_postojecem' => 'seup-badge-info',
            'nerazvrstan' => 'seup-badge-warning'
        ];
        $tipLabels = [
            'novi_akt' => 'Novi akt',
            'prilog_postojecem' => 'Prilog',
            'nerazvrstan' => 'Nerazvrstan'
        ];
        $tip = $zaprimanje->tip_dokumenta;
        print '<span class="seup-badge ' . ($tipColors[$tip] ?? 'seup-badge-neutral') . '">';
        print $tipLabels[$tip] ?? ucfirst($tip);
        print '</span>';
        print '</td>';

        print '<td class="seup-table-td">';
        $nacinIcons = [
            'posta' => 'fas fa-mail-bulk',
            'email' => 'fas fa-at',
            'rucno' => 'fas fa-hand-holding',
            'courier' => 'fas fa-truck'
        ];
        $nacinLabels = [
            'posta' => 'Pošta',
            'email' => 'E-mail',
            'rucno' => 'Na ruke',
            'courier' => 'Kurirska služba'
        ];
        $nacinColors = [
            'posta' => 'seup-badge-info',
            'email' => 'seup-badge-success',
            'rucno' => 'seup-badge-warning',
            'courier' => 'seup-badge-neutral'
        ];
        $nacin = $zaprimanje->nacin_zaprimanja;
        print '<span class="seup-badge ' . ($nacinColors[$nacin] ?? 'seup-badge-neutral') . '">';
        print '<i class="' . ($nacinIcons[$nacin] ?? 'fas fa-inbox') . ' me-1"></i>' . ($nacinLabels[$nacin] ?? ucfirst($nacin));
        print '</span>';
        print '</td>';

        print '</tr>';
    }
} else {
    print '<tr><td colspan="7" class="seup-table-td">';
    print '<div class="seup-empty-state">';
    print '<i class="fas fa-inbox"></i>';
    print '<h4>Nema zaprimljenih dokumenata</h4>';
    print '<p>Zaprimanja se registriraju iz predmeta putem taba "Zaprimanja"</p>';
    print '</div>';
    print '</td></tr>';
}

print '</tbody>';
print '</table>';
print '</div>';

print '</div>';
print '</div>';
print '</div>';
print '</main>';

llxFooter();
$db->close();
