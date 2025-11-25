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
 *	\file       seup/zaprimanja.php
 *	\ingroup    seup
 *	\brief      List of all zaprimanja (incoming documents)
 */

// Učitaj Dolibarr okruženje
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

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Local classes
require_once __DIR__ . '/../class/zaprimanje_helper.class.php';
require_once __DIR__ . '/../class/changelog_sistem.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Security check
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = GETPOST('action', 'alpha');

    if ($action === 'delete_zaprimanje') {
        header('Content-Type: application/json');
        ob_end_clean();

        $zaprimanje_id = GETPOST('zaprimanje_id', 'int');

        if (!$zaprimanje_id) {
            echo json_encode(['success' => false, 'error' => 'Missing zaprimanje ID']);
            exit;
        }

        $result = Zaprimanje_Helper::deleteZaprimanje($db, $zaprimanje_id);
        echo json_encode($result);
        exit;
    }

    if ($action === 'get_zaprimanje') {
        header('Content-Type: application/json');
        ob_end_clean();

        $zaprimanje_id = GETPOST('zaprimanje_id', 'int');

        if (!$zaprimanje_id) {
            echo json_encode(['success' => false, 'error' => 'Missing zaprimanje ID']);
            exit;
        }

        $zaprimanje = Zaprimanje_Helper::getZaprimanjeById($db, $zaprimanje_id);

        if ($zaprimanje) {
            echo json_encode([
                'success' => true,
                'data' => $zaprimanje
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Zaprimanje not found']);
        }
        exit;
    }

    if ($action === 'update_zaprimanje') {
        header('Content-Type: application/json');
        ob_end_clean();

        $zaprimanje_id = GETPOST('zaprimanje_id', 'int');
        $datum_zaprimanja = GETPOST('datum_zaprimanja', 'alpha');
        $nacin_zaprimanja = GETPOST('nacin_zaprimanja', 'alpha');
        $posiljatelj_naziv = GETPOST('posiljatelj_naziv', 'alpha');
        $tip_dokumenta = GETPOST('tip_dokumenta', 'alpha');
        $fk_posiljatelj = GETPOST('fk_posiljatelj', 'int');
        $posiljatelj_broj = GETPOST('posiljatelj_broj', 'alpha');
        $napomena = GETPOST('napomena', 'alpha');

        if (!$zaprimanje_id || !$datum_zaprimanja || !$nacin_zaprimanja || !$posiljatelj_naziv) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        $result = Zaprimanje_Helper::updateZaprimanje(
            $db,
            $zaprimanje_id,
            $datum_zaprimanja,
            $nacin_zaprimanja,
            $posiljatelj_naziv,
            $tip_dokumenta,
            $fk_posiljatelj,
            $posiljatelj_broj,
            null,
            $napomena
        );

        echo json_encode($result);
        exit;
    }

    if ($action === 'export_excel_single') {
        $zaprimanje_id = GETPOST('zaprimanje_id', 'int');

        if (!$zaprimanje_id) {
            echo json_encode(['success' => false, 'error' => 'Missing zaprimanje ID']);
            exit;
        }

        $zaprimanje = Zaprimanje_Helper::getZaprimanjeById($db, $zaprimanje_id);
        if ($zaprimanje) {
            Zaprimanje_Helper::generateExcelOutput([$zaprimanje], 'zaprimanje_' . $zaprimanje_id);
        }
        exit;
    }

    if ($action === 'export_excel_filtered') {
        $filters = [
            'godina' => GETPOST('filter_godina', 'int'),
            'mjesec' => GETPOST('filter_mjesec', 'int'),
            'nacin' => GETPOST('filter_nacin', 'alpha'),
            'tip' => GETPOST('filter_tip', 'alpha'),
            'search' => GETPOST('search', 'alpha')
        ];

        Zaprimanje_Helper::exportExcelFiltered($db, $filters);
        exit;
    }
}

// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'datum_zaprimanja';
$sortOrder = GETPOST('order', 'aZ09') ?: 'DESC';

// Validate sort fields
$allowedSortFields = ['datum_zaprimanja', 'ID_zaprimanja', 'klasa_format', 'posiljatelj_naziv', 'nacin_zaprimanja'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'datum_zaprimanja';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Fetch all zaprimanja
$zaprimanja = Zaprimanje_Helper::getZaprimanjaAll($db);

// Sort zaprimanja
usort($zaprimanja, function($a, $b) use ($sortField, $sortOrder) {
    $valA = $a->$sortField ?? '';
    $valB = $b->$sortField ?? '';

    if ($sortOrder === 'ASC') {
        return $valA <=> $valB;
    } else {
        return $valB <=> $valA;
    }
});

$form = new Form($db);
llxHeader("", "Zaprimanja", '', '', 0, 0, '', '', '', 'mod-seup page-zaprimanja');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Main hero section
print '<main class="seup-settings-hero">';

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

print '<div class="seup-settings-content">';

// Header section
print '<div class="seup-settings-header">';
print '<h1 class="seup-settings-title">Zaprimanja Dokumenata</h1>';
print '<p class="seup-settings-subtitle">Pregled i upravljanje svim zaprimanjima dokumenata u sustavu</p>';
print '</div>';

// Main content card
print '<div class="seup-zaprimanja-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-inbox"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Sva Zaprimanja</h3>';
print '<p class="seup-card-description">Pregled svih zaprimanja s naprednim filterima i mogućnostima izvoza</p>';
print '</div>';
print '</div>';

// Search and filter section
print '<div class="seup-table-controls">';
print '<div class="seup-search-container">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-search seup-search-icon"></i>';
print '<input type="text" id="searchInput" class="seup-search-input" placeholder="Pretraži zaprimanja...">';
print '</div>';
print '</div>';
print '<div class="seup-filter-controls">';

// Filter: Godina
print '<select id="filterGodina" class="seup-filter-select">';
print '<option value="">Sve godine</option>';
$godine = array_unique(array_filter(array_map(function($z) {
    return isset($z->godina) ? $z->godina : null;
}, $zaprimanja)));
rsort($godine);
foreach ($godine as $godina) {
    print '<option value="' . htmlspecialchars($godina) . '">' . htmlspecialchars($godina) . '</option>';
}
print '</select>';

// Filter: Mjesec
print '<select id="filterMjesec" class="seup-filter-select">';
print '<option value="">Svi mjeseci</option>';
$mjeseci = [
    1 => 'Siječanj', 2 => 'Veljača', 3 => 'Ožujak', 4 => 'Travanj',
    5 => 'Svibanj', 6 => 'Lipanj', 7 => 'Srpanj', 8 => 'Kolovoz',
    9 => 'Rujan', 10 => 'Listopad', 11 => 'Studeni', 12 => 'Prosinac'
];
foreach ($mjeseci as $mj_num => $mj_naziv) {
    print '<option value="' . $mj_num . '">' . $mj_naziv . '</option>';
}
print '</select>';

// Filter: Način zaprimanja
print '<select id="filterNacin" class="seup-filter-select">';
print '<option value="">Svi načini</option>';
print '<option value="posta">Pošta</option>';
print '<option value="email">E-mail</option>';
print '<option value="rucno">Na ruke</option>';
print '<option value="courier">Kurirska služba</option>';
print '</select>';

// Filter: Tip dokumenta
print '<select id="filterTip" class="seup-filter-select">';
print '<option value="">Svi tipovi</option>';
print '<option value="novi_akt">Novi akt</option>';
print '<option value="prilog_postojecem">Prilog postojećem</option>';
print '<option value="nerazvrstan">Nerazvrstan</option>';
print '</select>';

print '</div>';
print '</div>';

// Enhanced table with modern styling
print '<div class="seup-table-container">';
print '<table class="seup-table">';
print '<thead class="seup-table-header">';
print '<tr>';

// Function to generate sortable header
function sortableHeader($field, $label, $currentSort, $currentOrder, $icon = '')
{
    $newOrder = ($currentSort === $field && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    $sortIcon = '';

    if ($currentSort === $field) {
        $sortIcon = ($currentOrder === 'ASC')
            ? ' <i class="fas fa-arrow-up seup-sort-icon"></i>'
            : ' <i class="fas fa-arrow-down seup-sort-icon"></i>';
    }

    return '<th class="seup-table-th sortable-header">' .
        '<a href="?sort=' . $field . '&order=' . $newOrder . '" class="seup-sort-link">' .
        ($icon ? '<i class="' . $icon . ' me-2"></i>' : '') .
        $label . $sortIcon .
        '</a></th>';
}

// Generate sortable headers with icons
print sortableHeader('datum_zaprimanja', 'Datum', $sortField, $sortOrder, 'fas fa-calendar');
print sortableHeader('klasa_format', 'Klasa', $sortField, $sortOrder, 'fas fa-layer-group');
print sortableHeader('posiljatelj_naziv', 'Pošiljatelj', $sortField, $sortOrder, 'fas fa-user');
print '<th class="seup-table-th"><i class="fas fa-file me-2"></i>Dokument</th>';
print sortableHeader('nacin_zaprimanja', 'Način', $sortField, $sortOrder, 'fas fa-inbox');
print '<th class="seup-table-th"><i class="fas fa-tag me-2"></i>Tip</th>';
print '<th class="seup-table-th"><i class="fas fa-cogs me-2"></i>Akcije</th>';
print '</tr>';
print '</thead>';
print '<tbody class="seup-table-body">';

if (count($zaprimanja)) {
    foreach ($zaprimanja as $index => $zaprimanje) {
        $rowClass = ($index % 2 === 0) ? 'seup-table-row-even' : 'seup-table-row-odd';

        $datum_obj = new DateTime($zaprimanje->datum_zaprimanja);
        $datum_mjesec = $datum_obj->format('n');

        print '<tr class="seup-table-row ' . $rowClass . '"
            data-id="' . $zaprimanje->ID_zaprimanja . '"
            data-godina="' . htmlspecialchars($zaprimanje->godina ?? '') . '"
            data-mjesec="' . $datum_mjesec . '"
            data-nacin="' . htmlspecialchars($zaprimanje->nacin_zaprimanja) . '"
            data-tip="' . htmlspecialchars($zaprimanje->tip_dokumenta) . '">';

        // 1. Datum
        print '<td class="seup-table-td">';
        print '<div class="seup-date-info">';
        print '<i class="fas fa-calendar me-2"></i>';
        print $datum_obj->format('d.m.Y');
        print '</div>';
        print '</td>';

        // 2. Klasa (clickable link to predmet)
        print '<td class="seup-table-td">';
        if ($zaprimanje->klasa_format) {
            $url = dol_buildpath('/custom/seup/pages/predmet.php', 1) . '?id=' . $zaprimanje->ID_predmeta;
            print '<a href="' . $url . '" class="seup-badge seup-badge-primary seup-klasa-link">' . htmlspecialchars($zaprimanje->klasa_format) . '</a>';
        } else {
            print '<span class="seup-badge seup-badge-neutral">—</span>';
        }
        print '</td>';

        // 3. Pošiljatelj
        print '<td class="seup-table-td">';
        print '<div class="seup-primatelj-info">';
        print '<i class="fas fa-user me-2"></i>';
        print '<span class="seup-primatelj-name">' . htmlspecialchars($zaprimanje->posiljatelj_naziv) . '</span>';
        if (!empty($zaprimanje->posiljatelj_broj)) {
            print '<br><small class="seup-primatelj-email"><i class="fas fa-hashtag me-1"></i>' . htmlspecialchars($zaprimanje->posiljatelj_broj) . '</small>';
        }
        print '</div>';
        print '</td>';

        // 4. Dokument
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

        // 5. Način zaprimanja
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
        print '<i class="' . ($nacinIcons[$nacin] ?? 'fas fa-question-circle') . ' me-1"></i>' . ($nacinLabels[$nacin] ?? $nacin);
        print '</span>';
        print '</td>';

        // 6. Tip dokumenta
        print '<td class="seup-table-td">';
        $tipLabels = [
            'novi_akt' => 'Novi akt',
            'prilog_postojecem' => 'Prilog postojećem',
            'nerazvrstan' => 'Nerazvrstan'
        ];
        $tipColors = [
            'novi_akt' => 'seup-badge-success',
            'prilog_postojecem' => 'seup-badge-warning',
            'nerazvrstan' => 'seup-badge-neutral'
        ];

        $tip = $zaprimanje->tip_dokumenta;
        print '<span class="seup-badge ' . ($tipColors[$tip] ?? 'seup-badge-neutral') . '">';
        print ($tipLabels[$tip] ?? $tip);
        print '</span>';
        print '</td>';

        // 7. Akcije
        print '<td class="seup-table-td">';
        print '<div class="seup-action-buttons">';
        print '<button type="button" class="seup-action-btn seup-btn-edit" title="Uredi" data-id="' . $zaprimanje->ID_zaprimanja . '" onclick="event.preventDefault(); event.stopPropagation(); window.openEditModal(' . $zaprimanje->ID_zaprimanja . '); return false;">';
        print '<i class="fas fa-edit"></i>';
        print '</button>';
        print '<button type="button" class="seup-action-btn seup-btn-delete" title="Obriši" data-id="' . $zaprimanje->ID_zaprimanja . '" onclick="event.preventDefault(); event.stopPropagation(); window.openDeleteModal(' . $zaprimanje->ID_zaprimanja . '); return false;">';
        print '<i class="fas fa-trash"></i>';
        print '</button>';
        print '<button type="button" class="seup-action-btn seup-btn-excel" title="Export Excel" data-id="' . $zaprimanje->ID_zaprimanja . '" onclick="event.preventDefault(); event.stopPropagation(); window.exportSingleZaprimanje(' . $zaprimanje->ID_zaprimanja . '); return false;">';
        print '<i class="fas fa-file-excel"></i>';
        print '</button>';
        print '</div>';
        print '</td>';

        print '</tr>';
    }
} else {
    print '<tr class="seup-table-row">';
    print '<td colspan="7" class="seup-table-empty">';
    print '<div class="seup-empty-state">';
    print '<i class="fas fa-inbox seup-empty-icon"></i>';
    print '<h4 class="seup-empty-title">Nema zaprimanja</h4>';
    print '<p class="seup-empty-description">Zaprimanja dokumenata će se prikazati ovdje</p>';
    print '</div>';
    print '</td>';
    print '</tr>';
}

print '</tbody>';
print '</table>';
print '</div>';

// Table footer with stats and actions
print '<div class="seup-table-footer">';
print '<div class="seup-table-stats">';
print '<i class="fas fa-info-circle me-2"></i>';
print '<span>Prikazano <strong id="visibleCount">' . count($zaprimanja) . '</strong> od <strong id="totalCount">' . count($zaprimanja) . '</strong> zaprimanja</span>';
print '</div>';
print '<div class="seup-table-actions">';
print '<button type="button" class="seup-btn seup-btn-primary seup-btn-sm" id="exportAllBtn">';
print '<i class="fas fa-download me-2"></i>Export Sve Filtrirane';
print '</button>';
print '</div>';
print '</div>';

print '</div>';
print '</div>';

print '</div>';
print '</main>';

// Delete Modal
print '<div class="seup-modal" id="deleteModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-trash me-2"></i>Brisanje Zaprimanja</h5>';
print '<button type="button" class="seup-modal-close" id="closeDeleteModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div class="seup-delete-info">';
print '<div class="seup-delete-warning">';
print '<i class="fas fa-exclamation-triangle me-2"></i>';
print '<strong>PAŽNJA:</strong> Jeste li sigurni da želite obrisati ovo zaprimanje?';
print '</div>';
print '</div>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelDeleteBtn">Odustani</button>';
print '<button type="button" class="seup-btn seup-btn-danger" id="confirmDeleteBtn">';
print '<i class="fas fa-trash me-2"></i>Obriši';
print '</button>';
print '</div>';
print '</div>';
print '</div>';

// Edit Modal
print '<div class="seup-modal" id="editModal">';
print '<div class="seup-modal-content seup-modal-lg">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-edit me-2"></i>Uredi Zaprimanje</h5>';
print '<button type="button" class="seup-modal-close" id="closeEditModal">&times;</button>';
print '</div>';
print '<form id="editZaprimanjeForm" enctype="multipart/form-data">';
print '<div class="seup-modal-body">';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_datum_zaprimanja"><i class="fas fa-calendar me-2"></i>Datum zaprimanja *</label>';
print '<input type="date" class="seup-form-input" id="edit_datum_zaprimanja" name="datum_zaprimanja" required>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_nacin_zaprimanja"><i class="fas fa-inbox me-2"></i>Način zaprimanja *</label>';
print '<select class="seup-form-input" id="edit_nacin_zaprimanja" name="nacin_zaprimanja" required>';
print '<option value="posta">Pošta</option>';
print '<option value="email">E-mail</option>';
print '<option value="rucno">Na ruke</option>';
print '<option value="courier">Kurirska služba</option>';
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_posiljatelj_naziv"><i class="fas fa-user me-2"></i>Ime pošiljatelja *</label>';
print '<input type="text" class="seup-form-input" id="edit_posiljatelj_naziv" name="posiljatelj_naziv" required>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_posiljatelj_broj"><i class="fas fa-hashtag me-2"></i>Broj pošiljke</label>';
print '<input type="text" class="seup-form-input" id="edit_posiljatelj_broj" name="posiljatelj_broj">';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_tip_dokumenta"><i class="fas fa-tag me-2"></i>Tip dokumenta *</label>';
print '<select class="seup-form-input" id="edit_tip_dokumenta" name="tip_dokumenta" required>';
print '<option value="novi_akt">Novi akt</option>';
print '<option value="prilog_postojecem">Prilog postojećem</option>';
print '<option value="nerazvrstan">Nerazvrstan</option>';
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_napomena"><i class="fas fa-sticky-note me-2"></i>Napomena</label>';
print '<textarea class="seup-form-input" id="edit_napomena" name="napomena" rows="3"></textarea>';
print '</div>';

print '<input type="hidden" id="edit_zaprimanje_id" name="zaprimanje_id">';
print '<input type="hidden" id="edit_fk_posiljatelj" name="fk_posiljatelj">';

print '</div>';

print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelEditBtn">Odustani</button>';
print '<button type="button" class="seup-btn seup-btn-primary" id="saveEditBtn">';
print '<i class="fas fa-save me-2"></i>Spremi Izmjene';
print '</button>';
print '</div>';

print '</form>';
print '</div>';
print '</div>';

// JavaScript for enhanced functionality

?>

<script>
let currentDeleteId = null;
let currentEditId = null;

window.openEditModal = function(zaprimanjeId) {
    console.log('openEditModal called with ID:', zaprimanjeId);
    currentEditId = zaprimanjeId;

    const formData = new FormData();
    formData.append('action', 'get_zaprimanje');
    formData.append('zaprimanje_id', zaprimanjeId);

    fetch(window.location.pathname, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const zaprimanje = data.data;

            document.getElementById('edit_zaprimanje_id').value = zaprimanje.ID_zaprimanja;
            document.getElementById('edit_datum_zaprimanja').value = zaprimanje.datum_zaprimanja;
            document.getElementById('edit_nacin_zaprimanja').value = zaprimanje.nacin_zaprimanja;
            document.getElementById('edit_posiljatelj_naziv').value = zaprimanje.posiljatelj_naziv || '';
            document.getElementById('edit_posiljatelj_broj').value = zaprimanje.posiljatelj_broj || '';
            document.getElementById('edit_tip_dokumenta').value = zaprimanje.tip_dokumenta || 'nerazvrstan';
            document.getElementById('edit_napomena').value = zaprimanje.napomena || '';
            document.getElementById('edit_fk_posiljatelj').value = zaprimanje.fk_posiljatelj || '';

            document.getElementById('editModal').classList.add('show');
        } else {
            window.showMessage('Greška pri učitavanju zaprimanja: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Load error:', error);
        window.showMessage('Došlo je do greške pri učitavanju zaprimanja', 'error');
    });
};

window.closeEditModal = function() {
    document.getElementById('editModal').classList.remove('show');
    document.getElementById('editZaprimanjeForm').reset();
    currentEditId = null;
};

window.openDeleteModal = function(zaprimanjeId) {
    currentDeleteId = zaprimanjeId;
    document.getElementById('deleteModal').classList.add('show');
};

window.closeDeleteModal = function() {
    document.getElementById('deleteModal').classList.remove('show');
    currentDeleteId = null;
};

window.exportSingleZaprimanje = function(zaprimanjeId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.pathname;

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'export_excel_single';

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'zaprimanje_id';
    idInput.value = zaprimanjeId;

    form.appendChild(actionInput);
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();

    window.showMessage('Export Excel pokrenut...', 'success');
};

window.showMessage = function(message, type = 'success', duration = 5000) {
    let messageEl = document.querySelector('.seup-message-toast');
    if (!messageEl) {
        messageEl = document.createElement('div');
        messageEl.className = 'seup-message-toast';
        document.body.appendChild(messageEl);
    }

    messageEl.className = `seup-message-toast seup-message-${type} show`;
    messageEl.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
    `;

    setTimeout(() => {
        messageEl.classList.remove('show');
    }, duration);
};

document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('searchInput');
    const filterGodina = document.getElementById('filterGodina');
    const filterMjesec = document.getElementById('filterMjesec');
    const filterNacin = document.getElementById('filterNacin');
    const filterTip = document.getElementById('filterTip');
    const tableRows = document.querySelectorAll('.seup-table-row[data-id]');
    const visibleCountSpan = document.getElementById('visibleCount');
    const exportAllBtn = document.getElementById('exportAllBtn');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedGodina = filterGodina.value;
        const selectedMjesec = filterMjesec.value;
        const selectedNacin = filterNacin.value;
        const selectedTip = filterTip.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.seup-table-td');
            const rowText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(' ');

            const matchesSearch = !searchTerm || rowText.includes(searchTerm);
            const matchesGodina = !selectedGodina || row.dataset.godina === selectedGodina;
            const matchesMjesec = !selectedMjesec || row.dataset.mjesec === selectedMjesec;
            const matchesNacin = !selectedNacin || row.dataset.nacin === selectedNacin;
            const matchesTip = !selectedTip || row.dataset.tip === selectedTip;

            if (matchesSearch && matchesGodina && matchesMjesec && matchesNacin && matchesTip) {
                row.style.display = '';
                visibleCount++;
                row.style.animationDelay = `${visibleCount * 50}ms`;
                row.classList.add('animate-fade-in-up');
            } else {
                row.style.display = 'none';
                row.classList.remove('animate-fade-in-up');
            }
        });

        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleCount;
        }
    }

    if (searchInput) searchInput.addEventListener('input', debounce(filterTable, 300));
    if (filterGodina) filterGodina.addEventListener('change', filterTable);
    if (filterMjesec) filterMjesec.addEventListener('change', filterTable);
    if (filterNacin) filterNacin.addEventListener('change', filterTable);
    if (filterTip) filterTip.addEventListener('change', filterTable);

    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    exportAllBtn.addEventListener('click', function() {
        exportFilteredZaprimanja();
    });

    function confirmDelete() {
        if (!currentDeleteId) return;

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.classList.add('seup-loading');

        const formData = new FormData();
        formData.append('action', 'delete_zaprimanje');
        formData.append('zaprimanje_id', currentDeleteId);

        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`[data-id="${currentDeleteId}"]`);
                if (row) {
                    row.style.animation = 'fadeOut 0.5s ease-out';
                    setTimeout(() => {
                        row.remove();
                        updateVisibleCount();
                    }, 500);
                }

                window.showMessage('Zaprimanje uspješno obrisano!', 'success');
                window.closeDeleteModal();
            } else {
                window.showMessage('Greška pri brisanju: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            window.showMessage('Došlo je do greške pri brisanju', 'error');
        })
        .finally(() => {
            confirmBtn.classList.remove('seup-loading');
        });
    }

    function exportFilteredZaprimanja() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.pathname;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'export_excel_filtered';

        const godinaInput = document.createElement('input');
        godinaInput.type = 'hidden';
        godinaInput.name = 'filter_godina';
        godinaInput.value = filterGodina.value;

        const mjesecInput = document.createElement('input');
        mjesecInput.type = 'hidden';
        mjesecInput.name = 'filter_mjesec';
        mjesecInput.value = filterMjesec.value;

        const nacinInput = document.createElement('input');
        nacinInput.type = 'hidden';
        nacinInput.name = 'filter_nacin';
        nacinInput.value = filterNacin.value;

        const tipInput = document.createElement('input');
        tipInput.type = 'hidden';
        tipInput.name = 'filter_tip';
        tipInput.value = filterTip.value;

        const searchInputHidden = document.createElement('input');
        searchInputHidden.type = 'hidden';
        searchInputHidden.name = 'search';
        searchInputHidden.value = searchInput.value;

        form.appendChild(actionInput);
        form.appendChild(godinaInput);
        form.appendChild(mjesecInput);
        form.appendChild(nacinInput);
        form.appendChild(tipInput);
        form.appendChild(searchInputHidden);
        document.body.appendChild(form);
        form.submit();

        showMessage('Export svih filtriranih zaprimanja pokrenut...', 'success');
    }

    function updateVisibleCount() {
        const visibleRows = document.querySelectorAll('.seup-table-row[data-id]:not([style*="display: none"])');
        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleRows.length;
        }
        const totalCountSpan = document.getElementById('totalCount');
        if (totalCountSpan) {
            totalCountSpan.textContent = document.querySelectorAll('.seup-table-row[data-id]').length;
        }
    }

    document.getElementById('closeDeleteModal').addEventListener('click', window.closeDeleteModal);
    document.getElementById('cancelDeleteBtn').addEventListener('click', window.closeDeleteModal);
    document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            window.closeDeleteModal();
        }
    });

    document.getElementById('closeEditModal').addEventListener('click', window.closeEditModal);
    document.getElementById('cancelEditBtn').addEventListener('click', window.closeEditModal);

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            window.closeEditModal();
        }
    });

    document.getElementById('saveEditBtn').addEventListener('click', function(e) {
        e.preventDefault();

        const form = document.getElementById('editZaprimanjeForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const saveBtn = this;
        saveBtn.classList.add('seup-loading');
        saveBtn.disabled = true;

        const formData = new FormData(form);
        formData.append('action', 'update_zaprimanje');

        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showMessage('Zaprimanje uspješno ažurirano!', 'success');
                window.closeEditModal();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                window.showMessage('Greška pri ažuriranju: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            window.showMessage('Došlo je do greške pri ažuriranju', 'error');
        })
        .finally(() => {
            saveBtn.classList.remove('seup-loading');
            saveBtn.disabled = false;
        });
    });

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 100}ms`;
        row.classList.add('animate-fade-in-up');
    });
});
</script>

<style>
.seup-zaprimanja-container {
  max-width: 1400px;
  margin: 0 auto;
}

.seup-primatelj-info {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.seup-primatelj-name {
  font-weight: var(--font-medium);
  color: var(--secondary-800);
}

.seup-primatelj-email {
  color: var(--secondary-600);
  font-size: var(--text-xs);
}

.seup-dokument-info {
  display: flex;
  align-items: center;
  font-size: var(--text-sm);
  color: var(--secondary-700);
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.seup-btn-excel {
  background: var(--success-100);
  color: var(--success-600);
}

.seup-btn-excel:hover {
  background: var(--success-200);
  color: var(--success-700);
  transform: scale(1.1);
}

.seup-action-btn i {
  pointer-events: none;
}

.seup-action-buttons {
  pointer-events: auto;
}

.seup-action-btn {
  pointer-events: auto !important;
  cursor: pointer !important;
}

.seup-badge-info {
  background: var(--primary-100);
  color: var(--primary-800);
}

@keyframes fadeOut {
  from {
    opacity: 1;
    transform: translateX(0);
  }
  to {
    opacity: 0;
    transform: translateX(-100px);
  }
}

.seup-delete-info {
  background: var(--error-50);
  border: 1px solid var(--error-200);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
  margin-bottom: var(--space-4);
}

.seup-delete-warning {
  font-size: var(--text-sm);
  color: var(--error-700);
  display: flex;
  align-items: flex-start;
  gap: var(--space-2);
}

.seup-modal-lg .seup-modal-content {
  max-width: 700px;
}

.seup-form-group {
  margin-bottom: var(--space-4);
}

.seup-form-label {
  display: block;
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--secondary-700);
  margin-bottom: var(--space-2);
}

.seup-form-input {
  width: 100%;
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--secondary-300);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  color: var(--secondary-800);
  transition: all 0.2s ease;
  background: white;
}

.seup-form-input:focus {
  outline: none;
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.seup-form-input[type="file"] {
  padding: var(--space-2);
}

.seup-form-help {
  display: block;
  font-size: var(--text-xs);
  color: var(--secondary-500);
  margin-top: var(--space-1);
}

textarea.seup-form-input {
  resize: vertical;
  min-height: 80px;
  font-family: inherit;
}

select.seup-form-input {
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
  appearance: none;
}

.seup-modal-body {
  max-height: 60vh;
  overflow-y: auto;
  padding: var(--space-5, 20px);
}

.seup-modal-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: var(--space-3, 12px);
  padding: var(--space-4, 16px) var(--space-5, 20px);
  border-top: 1px solid var(--secondary-200, #e5e7eb);
  background: var(--secondary-50, #f9fafb);
  border-radius: 0 0 var(--radius-xl, 16px) var(--radius-xl, 16px);
}

.seup-modal-footer .seup-btn {
  min-width: 120px;
}
</style>

<?php
llxFooter();
$db->close();
?>
