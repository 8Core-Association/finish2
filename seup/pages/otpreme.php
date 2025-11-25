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
 *	\file       seup/otpreme.php
 *	\ingroup    seup
 *	\brief      List of all otpreme (shipments)
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
require_once __DIR__ . '/../class/otprema_helper.class.php';
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

    if ($action === 'delete_otprema') {
        header('Content-Type: application/json');
        ob_end_clean();

        $otprema_id = GETPOST('otprema_id', 'int');

        if (!$otprema_id) {
            echo json_encode(['success' => false, 'error' => 'Missing otprema ID']);
            exit;
        }

        $result = Otprema_helper::deleteOtprema($db, $otprema_id);
        echo json_encode($result);
        exit;
    }

    if ($action === 'get_otprema') {
        header('Content-Type: application/json');
        ob_end_clean();

        $otprema_id = GETPOST('otprema_id', 'int');

        if (!$otprema_id) {
            echo json_encode(['success' => false, 'error' => 'Missing otprema ID']);
            exit;
        }

        $otprema = Otprema_helper::getOtpremaById($db, $otprema_id);

        if ($otprema) {
            echo json_encode([
                'success' => true,
                'data' => $otprema
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Otprema not found']);
        }
        exit;
    }

    if ($action === 'update_otprema') {
        header('Content-Type: application/json');
        ob_end_clean();

        $otprema_id = GETPOST('otprema_id', 'int');
        $datum_otpreme = GETPOST('datum_otpreme', 'alpha');
        $nacin_otpreme = GETPOST('nacin_otpreme', 'alpha');
        $primatelj_naziv = GETPOST('primatelj_naziv', 'alpha');
        $primatelj_adresa = GETPOST('primatelj_adresa', 'alpha');
        $primatelj_email = GETPOST('primatelj_email', 'alpha');
        $primatelj_telefon = GETPOST('primatelj_telefon', 'alpha');
        $napomena = GETPOST('napomena', 'alpha');

        if (!$otprema_id || !$datum_otpreme || !$nacin_otpreme || !$primatelj_naziv) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        $fk_potvrda_ecm_file = null;
        if (isset($_FILES['potvrda_file']) && $_FILES['potvrda_file']['error'] === UPLOAD_ERR_OK) {
            $fk_potvrda_ecm_file = Otprema_helper::uploadPotvrdaOtpreme($db, $conf, $_FILES['potvrda_file'], $datum_otpreme);
        }

        $result = Otprema_helper::updateOtprema(
            $db,
            $otprema_id,
            $datum_otpreme,
            $nacin_otpreme,
            $primatelj_naziv,
            $primatelj_adresa,
            $primatelj_email,
            $primatelj_telefon,
            $napomena,
            $fk_potvrda_ecm_file
        );

        echo json_encode($result);
        exit;
    }

    if ($action === 'export_excel_single') {
        $otprema_id = GETPOST('otprema_id', 'int');

        if (!$otprema_id) {
            echo json_encode(['success' => false, 'error' => 'Missing otprema ID']);
            exit;
        }

        Otprema_helper::exportExcelSingle($db, $otprema_id);
        exit;
    }

    if ($action === 'export_excel_filtered') {
        $filters = [
            'klasa' => GETPOST('filter_klasa', 'alpha'),
            'godina' => GETPOST('filter_godina', 'int'),
            'mjesec' => GETPOST('filter_mjesec', 'int'),
            'primatelj' => GETPOST('filter_primatelj', 'alpha'),
            'nacin' => GETPOST('filter_nacin', 'alpha'),
            'search' => GETPOST('search', 'alpha')
        ];

        Otprema_helper::exportExcelFiltered($db, $filters);
        exit;
    }
}

// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'datum_otpreme';
$sortOrder = GETPOST('order', 'aZ09') ?: 'DESC';

// Validate sort fields
$allowedSortFields = ['datum_otpreme', 'ID_otpreme', 'klasifikacijska_oznaka', 'primatelj_naziv', 'nacin_otpreme'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'datum_otpreme';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Fetch all otpreme
$sql = "SELECT
            o.ID_otpreme,
            o.ID_predmeta,
            o.datum_otpreme,
            o.nacin_otpreme,
            o.primatelj_naziv,
            o.primatelj_adresa,
            o.primatelj_email,
            o.klasifikacijska_oznaka,
            o.naziv_predmeta,
            o.napomena,
            o.tip_dokumenta,
            ef.filename as dokument_naziv,
            p.klasa_br,
            p.sadrzaj,
            p.dosje_broj,
            p.godina,
            p.predmet_rbr,
            CONCAT(p.klasa_br, '-', p.sadrzaj, '/', p.godina, '-', p.dosje_broj, '/', p.predmet_rbr) as klasa_format,
            DATE_FORMAT(o.datum_otpreme, '%d.%m.%Y') as datum_format,
            YEAR(o.datum_otpreme) as otprema_godina,
            MONTH(o.datum_otpreme) as otprema_mjesec
        FROM " . MAIN_DB_PREFIX . "a_otprema o
        LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files ef ON o.fk_ecm_file = ef.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet p ON o.ID_predmeta = p.ID_predmeta
        ORDER BY {$sortField} {$sortOrder}";

$resql = $db->query($sql);
$otpreme = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $otpreme[] = $obj;
    }
}

$form = new Form($db);
llxHeader("", "Otpreme", '', '', 0, 0, '', '', '', 'mod-seup page-otpreme');

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
print '<h1 class="seup-settings-title">Otpreme Dokumenata</h1>';
print '<p class="seup-settings-subtitle">Pregled i upravljanje svim otpremama dokumenata iz cijelog sustava</p>';
print '</div>';

// Main content card
print '<div class="seup-otpreme-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-shipping-fast"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Sve Otpreme</h3>';
print '<p class="seup-card-description">Pregled svih otprema s naprednim filterima i mogućnostima izvoza</p>';
print '</div>';
print '</div>';

// Search and filter section
print '<div class="seup-table-controls">';
print '<div class="seup-search-container">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-search seup-search-icon"></i>';
print '<input type="text" id="searchInput" class="seup-search-input" placeholder="Pretraži otpreme...">';
print '</div>';
print '</div>';
print '<div class="seup-filter-controls">';

// Filter: Klasifikacijska oznaka
print '<select id="filterKlasa" class="seup-filter-select">';
print '<option value="">Sve klase</option>';
$klase = array_unique(array_filter(array_column($otpreme, 'klasifikacijska_oznaka')));
sort($klase);
foreach ($klase as $klasa) {
    print '<option value="' . htmlspecialchars($klasa) . '">' . htmlspecialchars($klasa) . '</option>';
}
print '</select>';

// Filter: Godina
print '<select id="filterGodina" class="seup-filter-select">';
print '<option value="">Sve godine</option>';
$godine = array_unique(array_filter(array_column($otpreme, 'otprema_godina')));
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

// Filter: Način otpreme
print '<select id="filterNacin" class="seup-filter-select">';
print '<option value="">Svi načini</option>';
print '<option value="posta">Pošta</option>';
print '<option value="email">E-mail</option>';
print '<option value="rucno">Na ruke</option>';
print '<option value="ostalo">Ostalo</option>';
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
print sortableHeader('datum_otpreme', 'Datum', $sortField, $sortOrder, 'fas fa-calendar');
print sortableHeader('klasifikacijska_oznaka', 'Klasa', $sortField, $sortOrder, 'fas fa-layer-group');
print sortableHeader('primatelj_naziv', 'Primatelj', $sortField, $sortOrder, 'fas fa-user');
print '<th class="seup-table-th"><i class="fas fa-file me-2"></i>Dokument</th>';
print sortableHeader('nacin_otpreme', 'Način', $sortField, $sortOrder, 'fas fa-shipping-fast');
print '<th class="seup-table-th"><i class="fas fa-cogs me-2"></i>Akcije</th>';
print '</tr>';
print '</thead>';
print '<tbody class="seup-table-body">';

if (count($otpreme)) {
    foreach ($otpreme as $index => $otprema) {
        $rowClass = ($index % 2 === 0) ? 'seup-table-row-even' : 'seup-table-row-odd';
        print '<tr class="seup-table-row ' . $rowClass . '"
            data-id="' . $otprema->ID_otpreme . '"
            data-klasa="' . htmlspecialchars($otprema->klasifikacijska_oznaka ?: '') . '"
            data-godina="' . $otprema->otprema_godina . '"
            data-mjesec="' . $otprema->otprema_mjesec . '"
            data-nacin="' . $otprema->nacin_otpreme . '">';

        // 1. Datum
        print '<td class="seup-table-td">';
        print '<div class="seup-date-info">';
        print '<i class="fas fa-calendar me-2"></i>';
        print $otprema->datum_format;
        print '</div>';
        print '</td>';

        // 2. Klasa (clickable link to predmet)
        print '<td class="seup-table-td">';
        if ($otprema->klasa_format) {
            $url = dol_buildpath('/custom/seup/pages/predmet.php', 1) . '?id=' . $otprema->ID_predmeta;
            print '<a href="' . $url . '" class="seup-badge seup-badge-primary seup-klasa-link">' . htmlspecialchars($otprema->klasa_format) . '</a>';
        } else {
            print '<span class="seup-badge seup-badge-neutral">—</span>';
        }
        print '</td>';

        // 3. Primatelj
        print '<td class="seup-table-td">';
        print '<div class="seup-primatelj-info">';
        print '<i class="fas fa-user me-2"></i>';
        print '<span class="seup-primatelj-name">' . htmlspecialchars($otprema->primatelj_naziv) . '</span>';
        if ($otprema->primatelj_email) {
            print '<br><small class="seup-primatelj-email"><i class="fas fa-envelope me-1"></i>' . htmlspecialchars($otprema->primatelj_email) . '</small>';
        }
        print '</div>';
        print '</td>';

        // 4. Dokument
        print '<td class="seup-table-td">';
        if ($otprema->dokument_naziv) {
            print '<div class="seup-dokument-info" title="' . htmlspecialchars($otprema->dokument_naziv) . '">';
            print '<i class="fas fa-file-pdf me-2"></i>';
            print dol_trunc($otprema->dokument_naziv, 30);
            print '</div>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';

        // 5. Način otpreme
        print '<td class="seup-table-td">';
        $nacinIcons = [
            'posta' => 'fas fa-mail-bulk',
            'email' => 'fas fa-at',
            'rucno' => 'fas fa-hand-holding',
            'ostalo' => 'fas fa-question-circle'
        ];
        $nacinLabels = [
            'posta' => 'Pošta',
            'email' => 'E-mail',
            'rucno' => 'Na ruke',
            'ostalo' => 'Ostalo'
        ];
        $nacinColors = [
            'posta' => 'seup-badge-info',
            'email' => 'seup-badge-success',
            'rucno' => 'seup-badge-warning',
            'ostalo' => 'seup-badge-neutral'
        ];

        $nacin = $otprema->nacin_otpreme;
        print '<span class="seup-badge ' . $nacinColors[$nacin] . '">';
        print '<i class="' . $nacinIcons[$nacin] . ' me-1"></i>' . $nacinLabels[$nacin];
        print '</span>';
        print '</td>';

        // 6. Akcije
        print '<td class="seup-table-td">';
        print '<div class="seup-action-buttons">';
        print '<button type="button" class="seup-action-btn seup-btn-edit" title="Uredi" data-id="' . $otprema->ID_otpreme . '" onclick="event.preventDefault(); event.stopPropagation(); window.openEditModal(' . $otprema->ID_otpreme . '); return false;">';
        print '<i class="fas fa-edit"></i>';
        print '</button>';
        print '<button type="button" class="seup-action-btn seup-btn-delete" title="Obriši" data-id="' . $otprema->ID_otpreme . '" onclick="event.preventDefault(); event.stopPropagation(); window.openDeleteModal(' . $otprema->ID_otpreme . '); return false;">';
        print '<i class="fas fa-trash"></i>';
        print '</button>';
        print '<button type="button" class="seup-action-btn seup-btn-excel" title="Export Excel" data-id="' . $otprema->ID_otpreme . '" onclick="event.preventDefault(); event.stopPropagation(); window.exportSingleOtprema(' . $otprema->ID_otpreme . '); return false;">';
        print '<i class="fas fa-file-excel"></i>';
        print '</button>';
        print '</div>';
        print '</td>';

        print '</tr>';
    }
} else {
    print '<tr class="seup-table-row">';
    print '<td colspan="6" class="seup-table-empty">';
    print '<div class="seup-empty-state">';
    print '<i class="fas fa-shipping-fast seup-empty-icon"></i>';
    print '<h4 class="seup-empty-title">Nema otprema</h4>';
    print '<p class="seup-empty-description">Otpreme dokumenata će se prikazati ovdje</p>';
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
print '<span>Prikazano <strong id="visibleCount">' . count($otpreme) . '</strong> od <strong id="totalCount">' . count($otpreme) . '</strong> otprema</span>';
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
print '<h5 class="seup-modal-title"><i class="fas fa-trash me-2"></i>Brisanje Otpreme</h5>';
print '<button type="button" class="seup-modal-close" id="closeDeleteModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div class="seup-delete-info">';
print '<div class="seup-delete-warning">';
print '<i class="fas fa-exclamation-triangle me-2"></i>';
print '<strong>PAŽNJA:</strong> Jeste li sigurni da želite obrisati ovu otpremu?';
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
print '<h5 class="seup-modal-title"><i class="fas fa-edit me-2"></i>Uredi Otpremu</h5>';
print '<button type="button" class="seup-modal-close" id="closeEditModal">&times;</button>';
print '</div>';
print '<form id="editOtpremaForm" enctype="multipart/form-data">';
print '<div class="seup-modal-body">';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_datum_otpreme"><i class="fas fa-calendar me-2"></i>Datum otpreme *</label>';
print '<input type="date" class="seup-form-input" id="edit_datum_otpreme" name="datum_otpreme" required>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_nacin_otpreme"><i class="fas fa-shipping-fast me-2"></i>Način otpreme *</label>';
print '<select class="seup-form-input" id="edit_nacin_otpreme" name="nacin_otpreme" required>';
print '<option value="posta">Pošta</option>';
print '<option value="email">E-mail</option>';
print '<option value="rucno">Na ruke</option>';
print '<option value="ostalo">Ostalo</option>';
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_primatelj_naziv"><i class="fas fa-user me-2"></i>Ime primatelja *</label>';
print '<input type="text" class="seup-form-input" id="edit_primatelj_naziv" name="primatelj_naziv" required>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_primatelj_adresa"><i class="fas fa-map-marker-alt me-2"></i>Adresa primatelja</label>';
print '<input type="text" class="seup-form-input" id="edit_primatelj_adresa" name="primatelj_adresa">';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_primatelj_email"><i class="fas fa-envelope me-2"></i>Email primatelja</label>';
print '<input type="email" class="seup-form-input" id="edit_primatelj_email" name="primatelj_email">';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_primatelj_telefon"><i class="fas fa-phone me-2"></i>Telefon primatelja</label>';
print '<input type="text" class="seup-form-input" id="edit_primatelj_telefon" name="primatelj_telefon">';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_napomena"><i class="fas fa-sticky-note me-2"></i>Napomena</label>';
print '<textarea class="seup-form-input" id="edit_napomena" name="napomena" rows="3"></textarea>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-form-label" for="edit_potvrda_file"><i class="fas fa-file-upload me-2"></i>Nova potvrda otpreme</label>';
print '<input type="file" class="seup-form-input" id="edit_potvrda_file" name="potvrda_file" accept=".pdf,.jpg,.jpeg,.png">';
print '<small class="seup-form-help">Opcionalno: Upload nove potvrde otpreme (PDF, JPG, PNG)</small>';
print '</div>';

print '<input type="hidden" id="edit_otprema_id" name="otprema_id">';

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
// Define functions globally FIRST so onclick attributes can find them
let currentDeleteId = null;
let currentEditId = null;

window.openEditModal = function(otpremaId) {
    console.log('openEditModal called with ID:', otpremaId);
    currentEditId = otpremaId;

    const formData = new FormData();
    formData.append('action', 'get_otprema');
    formData.append('otprema_id', otpremaId);

    fetch(window.location.pathname, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const otprema = data.data;

            document.getElementById('edit_otprema_id').value = otprema.ID_otpreme;
            document.getElementById('edit_datum_otpreme').value = otprema.datum_otpreme;
            document.getElementById('edit_nacin_otpreme').value = otprema.nacin_otpreme;
            document.getElementById('edit_primatelj_naziv').value = otprema.primatelj_naziv || '';
            document.getElementById('edit_primatelj_adresa').value = otprema.primatelj_adresa || '';
            document.getElementById('edit_primatelj_email').value = otprema.primatelj_email || '';
            document.getElementById('edit_primatelj_telefon').value = otprema.primatelj_telefon || '';
            document.getElementById('edit_napomena').value = otprema.napomena || '';

            document.getElementById('editModal').classList.add('show');
        } else {
            window.showMessage('Greška pri učitavanju otpreme: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Load error:', error);
        window.showMessage('Došlo je do greške pri učitavanju otpreme', 'error');
    });
};

window.closeEditModal = function() {
    document.getElementById('editModal').classList.remove('show');
    document.getElementById('editOtpremaForm').reset();
    currentEditId = null;
};

window.openDeleteModal = function(otpremaId) {
    currentDeleteId = otpremaId;
    document.getElementById('deleteModal').classList.add('show');
};

window.closeDeleteModal = function() {
    document.getElementById('deleteModal').classList.remove('show');
    currentDeleteId = null;
};

window.exportSingleOtprema = function(otpremaId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.pathname;

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'export_excel_single';

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'otprema_id';
    idInput.value = otpremaId;

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
    const filterKlasa = document.getElementById('filterKlasa');
    const filterGodina = document.getElementById('filterGodina');
    const filterMjesec = document.getElementById('filterMjesec');
    const filterNacin = document.getElementById('filterNacin');
    const tableRows = document.querySelectorAll('.seup-table-row[data-id]');
    const visibleCountSpan = document.getElementById('visibleCount');
    const exportAllBtn = document.getElementById('exportAllBtn');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedKlasa = filterKlasa.value;
        const selectedGodina = filterGodina.value;
        const selectedMjesec = filterMjesec.value;
        const selectedNacin = filterNacin.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.seup-table-td');
            const rowText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(' ');

            const matchesSearch = !searchTerm || rowText.includes(searchTerm);
            const matchesKlasa = !selectedKlasa || row.dataset.klasa === selectedKlasa;
            const matchesGodina = !selectedGodina || row.dataset.godina === selectedGodina;
            const matchesMjesec = !selectedMjesec || row.dataset.mjesec === selectedMjesec;
            const matchesNacin = !selectedNacin || row.dataset.nacin === selectedNacin;

            if (matchesSearch && matchesKlasa && matchesGodina && matchesMjesec && matchesNacin) {
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
    if (filterKlasa) filterKlasa.addEventListener('change', filterTable);
    if (filterGodina) filterGodina.addEventListener('change', filterTable);
    if (filterMjesec) filterMjesec.addEventListener('change', filterTable);
    if (filterNacin) filterNacin.addEventListener('change', filterTable);

    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    exportAllBtn.addEventListener('click', function() {
        exportFilteredOtpreme();
    });

    function confirmDelete() {
        if (!currentDeleteId) return;

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.classList.add('seup-loading');

        const formData = new FormData();
        formData.append('action', 'delete_otprema');
        formData.append('otprema_id', currentDeleteId);

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

                window.showMessage('Otprema uspješno obrisana!', 'success');
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

    function exportFilteredOtpreme() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.pathname;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'export_excel_filtered';

        const klasaInput = document.createElement('input');
        klasaInput.type = 'hidden';
        klasaInput.name = 'filter_klasa';
        klasaInput.value = filterKlasa.value;

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

        const searchInputHidden = document.createElement('input');
        searchInputHidden.type = 'hidden';
        searchInputHidden.name = 'search';
        searchInputHidden.value = searchInput.value;

        form.appendChild(actionInput);
        form.appendChild(klasaInput);
        form.appendChild(godinaInput);
        form.appendChild(mjesecInput);
        form.appendChild(nacinInput);
        form.appendChild(searchInputHidden);
        document.body.appendChild(form);
        form.submit();

        showMessage('Export svih filtriranih otprema pokrenut...', 'success');
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

    // Edit modal event listeners
    document.getElementById('closeEditModal').addEventListener('click', window.closeEditModal);
    document.getElementById('cancelEditBtn').addEventListener('click', window.closeEditModal);

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            window.closeEditModal();
        }
    });

    document.getElementById('saveEditBtn').addEventListener('click', function(e) {
        e.preventDefault();

        const form = document.getElementById('editOtpremaForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const saveBtn = this;
        saveBtn.classList.add('seup-loading');
        saveBtn.disabled = true;

        const formData = new FormData(form);
        formData.append('action', 'update_otprema');

        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showMessage('Otprema uspješno ažurirana!', 'success');
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
.seup-otpreme-container {
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
