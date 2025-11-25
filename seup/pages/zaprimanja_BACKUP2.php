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
 *	\file       seup/zaprimanja.php
 *	\ingroup    seup
 *	\brief      List of all zaprimanja (inbound documents)
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
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
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
print '<link href="/custom/seup/css/zaprimanja.css" rel="stylesheet">';

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
print '<div class="seup-card-body">';

print '<div class="seup-zaprimanja-page">';
print '<div class="seup-page-header">';
print '<div class="seup-header-left">';
print '<h2 class="seup-page-title"><i class="fas fa-inbox"></i> Zaprimanja</h2>';
print '<p class="seup-page-subtitle">Pregled svih zaprimljenih dokumenata</p>';
print '</div>';
print '<div class="seup-header-right">';
print '<form method="POST" class="seup-inline-form">';
print '<input type="hidden" name="action" value="export_excel">';
print '<input type="hidden" name="filter_godina" value="' . htmlspecialchars($filters['godina']) . '">';
print '<input type="hidden" name="filter_mjesec" value="' . htmlspecialchars($filters['mjesec']) . '">';
print '<input type="hidden" name="filter_nacin" value="' . htmlspecialchars($filters['nacin']) . '">';
print '<input type="hidden" name="filter_tip" value="' . htmlspecialchars($filters['tip']) . '">';
print '<input type="hidden" name="search" value="' . htmlspecialchars($filters['search']) . '">';
print '<button type="submit" class="seup-btn seup-btn-success">';
print '<i class="fas fa-file-excel me-2"></i>Izvoz u Excel';
print '</button>';
print '</form>';
print '</div>';
print '</div>';

print '<div class="seup-filters-section">';
print '<form method="GET" class="seup-filters-form" id="filtersForm">';

print '<div class="seup-filter-group">';
print '<label for="search" class="seup-filter-label"><i class="fas fa-search"></i> Pretraga</label>';
print '<input type="text" id="search" name="search" class="seup-filter-input" placeholder="Pošiljatelj, predmet, dokument..." value="' . htmlspecialchars($filters['search']) . '">';
print '</div>';

print '<div class="seup-filter-group">';
print '<label for="filter_godina" class="seup-filter-label"><i class="fas fa-calendar-alt"></i> Godina</label>';
print '<select id="filter_godina" name="filter_godina" class="seup-filter-select">';
print '<option value="">Sve godine</option>';
$currentYear = date('Y');
for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
    $selected = ($filters['godina'] == $y) ? 'selected' : '';
    print '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
}
print '</select>';
print '</div>';

print '<div class="seup-filter-group">';
print '<label for="filter_mjesec" class="seup-filter-label"><i class="fas fa-calendar"></i> Mjesec</label>';
print '<select id="filter_mjesec" name="filter_mjesec" class="seup-filter-select">';
print '<option value="">Svi mjeseci</option>';
$mjeseci = [
    1 => 'Siječanj', 2 => 'Veljača', 3 => 'Ožujak', 4 => 'Travanj',
    5 => 'Svibanj', 6 => 'Lipanj', 7 => 'Srpanj', 8 => 'Kolovoz',
    9 => 'Rujan', 10 => 'Listopad', 11 => 'Studeni', 12 => 'Prosinac'
];
foreach ($mjeseci as $num => $naziv) {
    $selected = ($filters['mjesec'] == $num) ? 'selected' : '';
    print '<option value="' . $num . '" ' . $selected . '>' . $naziv . '</option>';
}
print '</select>';
print '</div>';

print '<div class="seup-filter-group">';
print '<label for="filter_nacin" class="seup-filter-label"><i class="fas fa-shipping-fast"></i> Način</label>';
print '<select id="filter_nacin" name="filter_nacin" class="seup-filter-select">';
print '<option value="">Svi načini</option>';
$nacini = [
    'posta' => 'Pošta',
    'email' => 'E-mail',
    'rucno' => 'Na ruke',
    'courier' => 'Kurirska služba'
];
foreach ($nacini as $value => $label) {
    $selected = ($filters['nacin'] == $value) ? 'selected' : '';
    print '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
}
print '</select>';
print '</div>';

print '<div class="seup-filter-group">';
print '<label for="filter_tip" class="seup-filter-label"><i class="fas fa-tags"></i> Tip</label>';
print '<select id="filter_tip" name="filter_tip" class="seup-filter-select">';
print '<option value="">Svi tipovi</option>';
$tipovi = [
    'novi_akt' => 'Novi akt',
    'prilog_postojecem' => 'Prilog postojećem',
    'nerazvrstan' => 'Nerazvrstan'
];
foreach ($tipovi as $value => $label) {
    $selected = ($filters['tip'] == $value) ? 'selected' : '';
    print '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
}
print '</select>';
print '</div>';

print '<div class="seup-filter-actions">';
print '<button type="submit" class="seup-btn seup-btn-primary"><i class="fas fa-filter"></i> Filtriraj</button>';
print '<a href="zaprimanja.php" class="seup-btn seup-btn-secondary"><i class="fas fa-redo"></i> Reset</a>';
print '</div>';

print '</form>';
print '</div>';

if (empty($zaprimanja)) {
    print '<div class="seup-empty-state">';
    print '<i class="fas fa-inbox"></i>';
    print '<h4>Nema zaprimljenih dokumenata</h4>';
    print '<p>Zaprimanja se registriraju iz predmeta putem taba "Zaprimanja"</p>';
    print '</div>';
} else {
    print '<div class="seup-zaprimanja-stats">';
    print '<div class="seup-stat-card">';
    print '<div class="seup-stat-icon"><i class="fas fa-inbox"></i></div>';
    print '<div class="seup-stat-content">';
    print '<div class="seup-stat-value">' . count($zaprimanja) . '</div>';
    print '<div class="seup-stat-label">Zaprimanja</div>';
    print '</div>';
    print '</div>';
    print '</div>';

    print '<div class="seup-table-container">';
    print '<table class="seup-table seup-zaprimanja-table">';
    print '<thead>';
    print '<tr>';
    print '<th>Datum</th>';
    print '<th>Klasa</th>';
    print '<th>Naziv predmeta</th>';
    print '<th>Pošiljatelj</th>';
    print '<th>Broj pošiljatelja</th>';
    print '<th>Dokument</th>';
    print '<th>Tip</th>';
    print '<th>Način</th>';
    print '<th>Napomena</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody>';

    foreach ($zaprimanja as $zaprimanje) {
        $datum_formatted = date('d.m.Y', strtotime($zaprimanje->datum_zaprimanja));

        $tip_badges = [
            'novi_akt' => 'success',
            'prilog_postojecem' => 'info',
            'nerazvrstan' => 'warning'
        ];
        $tip_labels = [
            'novi_akt' => 'Novi akt',
            'prilog_postojecem' => 'Prilog',
            'nerazvrstan' => 'Nerazvrstan'
        ];

        $nacin_icons = [
            'posta' => 'fa-envelope',
            'email' => 'fa-at',
            'rucno' => 'fa-hand-holding',
            'courier' => 'fa-truck'
        ];
        $nacin_labels = [
            'posta' => 'Pošta',
            'email' => 'E-mail',
            'rucno' => 'Na ruke',
            'courier' => 'Kurirska služba'
        ];

        print '<tr>';

        print '<td><strong>' . $datum_formatted . '</strong></td>';

        print '<td>';
        if ($zaprimanje->klasa_format) {
            print '<a href="predmet.php?id=' . $zaprimanje->ID_predmeta . '" class="seup-link">';
            print htmlspecialchars($zaprimanje->klasa_format);
            print '</a>';
        } else {
            print '—';
        }
        print '</td>';

        print '<td>' . htmlspecialchars($zaprimanje->naziv_predmeta ?: '—') . '</td>';

        print '<td><strong>' . htmlspecialchars($zaprimanje->posiljatelj_naziv) . '</strong></td>';

        print '<td>' . htmlspecialchars($zaprimanje->posiljatelj_broj ?: '—') . '</td>';

        print '<td>';
        if ($zaprimanje->doc_filename) {
            $doc_path = rtrim($zaprimanje->doc_filepath, '/') . '/' . $zaprimanje->doc_filename;
            $download_url = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($doc_path);
            print '<a href="' . $download_url . '" target="_blank" class="seup-doc-link">';
            print '<i class="fas fa-file-alt"></i> ' . htmlspecialchars($zaprimanje->doc_filename);
            print '</a>';
        } else {
            print '—';
        }
        print '</td>';

        print '<td>';
        $badge_class = $tip_badges[$zaprimanje->tip_dokumenta] ?? 'secondary';
        $tip_label = $tip_labels[$zaprimanje->tip_dokumenta] ?? ucfirst($zaprimanje->tip_dokumenta);
        print '<span class="seup-badge seup-badge-' . $badge_class . '">' . $tip_label . '</span>';
        print '</td>';

        print '<td>';
        $nacin_icon = $nacin_icons[$zaprimanje->nacin_zaprimanja] ?? 'fa-inbox';
        $nacin_label = $nacin_labels[$zaprimanje->nacin_zaprimanja] ?? ucfirst($zaprimanje->nacin_zaprimanja);
        print '<i class="fas ' . $nacin_icon . '"></i> ' . $nacin_label;
        print '</td>';

        print '<td>';
        if (!empty($zaprimanje->napomena)) {
            print '<span class="seup-napomena-preview" title="' . htmlspecialchars($zaprimanje->napomena) . '">';
            print htmlspecialchars(mb_substr($zaprimanje->napomena, 0, 50)) . (mb_strlen($zaprimanje->napomena) > 50 ? '...' : '');
            print '</span>';
        } else {
            print '—';
        }
        print '</td>';

        print '</tr>';
    }

    print '</tbody>';
    print '</table>';
    print '</div>';
}

print '</div>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';
print '</main>';

llxFooter();

$db->close();
