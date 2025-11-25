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
 */
/**
 *	\file       seup/pages/plan_klasifikacijskih_oznaka.php
 *	\ingroup    seup
 *	\brief      Plan klasifikacijskih oznaka page
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
require_once __DIR__ . '/../class/klasifikacijska_oznaka.class.php';
require_once __DIR__ . '/../class/changelog_sistem.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Security check
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

// Handle AJAX requests for export
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = GETPOST('action', 'alpha');
    
    // Handle klasifikacija update
    if ($action === 'update_klasifikacija') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $id = GETPOST('id_klasifikacijske_oznake', 'int');
        $klasa_br = GETPOST('klasa_br', 'int');
        $sadrzaj = GETPOST('sadrzaj', 'int');
        $dosje_br = GETPOST('dosje_br', 'int');
        $vrijeme_cuvanja = GETPOST('vrijeme_cuvanja', 'alpha');
        $opis = GETPOST('opis_klasifikacije', 'alphanohtml');
        
        // Convert vrijeme_cuvanja
        if ($vrijeme_cuvanja === 'permanent') {
            $vrijeme_cuvanja = 0;
        } else {
            $vrijeme_cuvanja = (int)$vrijeme_cuvanja;
        }
        
        try {
            $db->begin();
            
            // Check if combination already exists (excluding current record)
            $sql_check = "SELECT ID_klasifikacijske_oznake FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka 
                         WHERE klasa_broj = " . (int)$klasa_br . " 
                         AND sadrzaj = " . (int)$sadrzaj . " 
                         AND dosje_broj = " . (int)$dosje_br . " 
                         AND ID_klasifikacijske_oznake != " . (int)$id;
            
            $resql_check = $db->query($sql_check);
            if ($resql_check && $db->num_rows($resql_check) > 0) {
                throw new Exception('Kombinacija klase, sadržaja i dosje broja već postoji');
            }
            
            // Update record
            $sql = "UPDATE " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka SET 
                    klasa_broj = " . (int)$klasa_br . ",
                    sadrzaj = " . (int)$sadrzaj . ",
                    dosje_broj = " . (int)$dosje_br . ",
                    vrijeme_cuvanja = " . (int)$vrijeme_cuvanja . ",
                    opis_klasifikacijske_oznake = '" . $db->escape($opis) . "'
                    WHERE ID_klasifikacijske_oznake = " . (int)$id;
            
            if (!$db->query($sql)) {
                throw new Exception($db->lasterror());
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Klasifikacijska oznaka je uspješno ažurirana']);
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($action === 'export_csv') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $result = exportKlasifikacijeToCSV($db);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'export_excel') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $result = exportKlasifikacijeToExcel($db);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'get_klasifikacija_details') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $id = GETPOST('id', 'int');
        $result = getKlasifikacijaDetails($db, $id);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'delete_klasifikacija') {
        header('Content-Type: application/json');
        ob_end_clean();
        $id = GETPOST('id', 'int');
        $ok = false; $err = null;
        if ($id > 0) {
            $table = MAIN_DB_PREFIX . "a_klasifikacijska_oznaka";
            $sql = "DELETE FROM `".$table."` WHERE ID_klasifikacijske_oznake=".(int)$id." LIMIT 1";
            $ok = $db->query($sql);
            if (!$ok) { $err = $db->lasterror(); }
        } else {
            $err = 'Nevažeći ID.';
        }
        echo json_encode(['success' => (bool)$ok, 'error' => $err]);
        exit;
    }
}

// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'klasa_broj';
$sortOrder = GETPOST('order', 'aZ09') ?: 'ASC';

// Validate sort fields
$allowedSortFields = ['ID_klasifikacijske_oznake', 'klasa_broj', 'sadrzaj', 'dosje_broj', 'vrijeme_cuvanja', 'opis_klasifikacijske_oznake'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'klasa_broj';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Fetch all klasifikacijske oznake
$sql = "SELECT 
            ID_klasifikacijske_oznake,
            klasa_broj,
            sadrzaj,
            dosje_broj,
            vrijeme_cuvanja,
            opis_klasifikacijske_oznake,
            CONCAT(klasa_broj, '-', LPAD(sadrzaj, 2, '0'), '-', LPAD(dosje_broj, 2, '0')) as puna_oznaka
        FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka
        ORDER BY {$sortField} {$sortOrder}";

$resql = $db->query($sql);
$klasifikacije = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $klasifikacije[] = $obj;
    }
}

// Helper functions
function exportKlasifikacijeToCSV($db) {
    try {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ORDER BY klasa_broj ASC";
        $resql = $db->query($sql);
        
        if (!$resql) {
            return ['success' => false, 'error' => 'Greška pri dohvaćanju podataka'];
        }
        
        $filename = 'klasifikacijske_oznake_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = DOL_DATA_ROOT . '/temp/' . $filename;
        
        // Ensure temp directory exists
        if (!is_dir(DOL_DATA_ROOT . '/temp/')) {
            mkdir(DOL_DATA_ROOT . '/temp/', 0755, true);
        }
        
        $file = fopen($filepath, 'w');
        if (!$file) {
            return ['success' => false, 'error' => 'Ne mogu kreirati datoteku'];
        }
        
        // UTF-8 BOM for Excel compatibility
        fwrite($file, "\xEF\xBB\xBF");
        
        // Headers
        fputcsv($file, ['ID', 'Klasa broj', 'Sadržaj', 'Dosje broj', 'Vrijeme čuvanja', 'Opis'], ';');
        
        while ($obj = $db->fetch_object($resql)) {
            $vrijeme_text = ($obj->vrijeme_cuvanja == 0) ? 'Trajno' : $obj->vrijeme_cuvanja . ' godina';
            fputcsv($file, [
                $obj->ID_klasifikacijske_oznake,
                $obj->klasa_broj,
                str_pad($obj->sadrzaj, 2, '0', STR_PAD_LEFT),
                str_pad($obj->dosje_broj, 2, '0', STR_PAD_LEFT),
                $vrijeme_text,
                $obj->opis_klasifikacijske_oznake
            ], ';');
        }
        
        fclose($file);
        
        return [
            'success' => true,
            'download_url' => DOL_URL_ROOT . '/document.php?modulepart=temp&file=' . urlencode($filename),
            'filename' => $filename
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function exportKlasifikacijeToExcel($db) {
    // Simple Excel export (CSV with .xls extension for basic Excel compatibility)
    $result = exportKlasifikacijeToCSV($db);
    if ($result['success']) {
        $result['filename'] = str_replace('.csv', '.xls', $result['filename']);
        $result['download_url'] = str_replace('.csv', '.xls', $result['download_url']);
    }
    return $result;
}

function getKlasifikacijaDetails($db, $id) {
    try {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka WHERE ID_klasifikacijske_oznake = " . (int)$id;
        $resql = $db->query($sql);
        
        if (!$resql || $db->num_rows($resql) == 0) {
            return ['success' => false, 'error' => 'Klasifikacija nije pronađena'];
        }
        
        $obj = $db->fetch_object($resql);
        
        return [
            'success' => true,
            'klasifikacija' => [
                'ID_klasifikacijske_oznake' => $obj->ID_klasifikacijske_oznake,
                'klasa_broj' => $obj->klasa_broj,
                'sadrzaj' => str_pad($obj->sadrzaj, 2, '0', STR_PAD_LEFT),
                'dosje_broj' => str_pad($obj->dosje_broj, 2, '0', STR_PAD_LEFT),
                'puna_oznaka' => $obj->klasa_broj . '-' . str_pad($obj->sadrzaj, 2, '0', STR_PAD_LEFT) . '-' . str_pad($obj->dosje_broj, 2, '0', STR_PAD_LEFT),
                'vrijeme_cuvanja' => $obj->vrijeme_cuvanja,
                'vrijeme_cuvanja_text' => ($obj->vrijeme_cuvanja == 0) ? 'Trajno' : $obj->vrijeme_cuvanja . ' godina',
                'opis_klasifikacijske_oznake' => $obj->opis_klasifikacijske_oznake
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

$form = new Form($db);
llxHeader("", "Plan klasifikacijskih oznaka", '', '', 0, 0, '', '', '', 'mod-seup page-plan-klasifikacijskih-oznaka');

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
print '<h1 class="seup-settings-title">Plan Klasifikacijskih Oznaka</h1>';
print '<p class="seup-settings-subtitle">Pregled i upravljanje sustavom klasifikacije dokumentacije</p>';
print '</div>';

// Main content card
print '<div class="seup-suradnici-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-sitemap"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Popis Klasifikacijskih Oznaka</h3>';
print '<p class="seup-card-description">Pregled svih definiranih klasifikacijskih oznaka u sustavu</p>';
print '</div>';
print '<div class="seup-card-actions">';
print '<button type="button" class="seup-btn seup-btn-primary" id="novaKlasifikacijaBtn">';
print '<i class="fas fa-plus me-2"></i>Nova Klasifikacija';
print '</button>';
print '</div>';
print '</div>';

// Search and filter section
print '<div class="seup-table-controls">';
print '<div class="seup-search-container">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-search seup-search-icon"></i>';
print '<input type="text" id="searchOznaka" class="seup-search-input" placeholder="Pretraži po oznaci...">';
print '</div>';
print '</div>';
print '<div class="seup-filter-controls">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-filter seup-search-icon"></i>';
print '<input type="text" id="searchOpis" class="seup-search-input" placeholder="Pretraži po opisu...">';
print '</div>';
print '<select id="sortOrder" class="seup-filter-select">';
print '<option value="ASC"' . ($sortOrder === 'ASC' ? ' selected' : '') . '>Uzlazno</option>';
print '<option value="DESC"' . ($sortOrder === 'DESC' ? ' selected' : '') . '>Silazno</option>';
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
print '<th class="seup-table-th"><i class="fas fa-hashtag me-2"></i>Rb.</th>';
print sortableHeader('klasa_broj', 'Klasa', $sortField, $sortOrder, 'fas fa-folder');
print sortableHeader('sadrzaj', 'Sadržaj', $sortField, $sortOrder, 'fas fa-list');
print sortableHeader('dosje_broj', 'Dosje', $sortField, $sortOrder, 'fas fa-file');
print '<th class="seup-table-th"><i class="fas fa-tag me-2"></i>Puna Oznaka</th>';
print sortableHeader('vrijeme_cuvanja', 'Čuvanje', $sortField, $sortOrder, 'fas fa-clock');
print '<th class="seup-table-th"><i class="fas fa-align-left me-2"></i>Opis</th>';
print '<th class="seup-table-th"><i class="fas fa-cogs me-2"></i>Akcije</th>';
print '</tr>';
print '</thead>';
print '<tbody class="seup-table-body">';

if (count($klasifikacije)) {
    foreach ($klasifikacije as $index => $klasifikacija) {
        $rowClass = ($index % 2 === 0) ? 'seup-table-row-even' : 'seup-table-row-odd';
        print '<tr class="seup-table-row ' . $rowClass . '" data-id="' . $klasifikacija->ID_klasifikacijske_oznake . '">';
        
        print '<td class="seup-table-td">';
        print '<span class="seup-badge seup-badge-neutral">' . ($index + 1) . '</span>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<span class="seup-klasa-badge">' . htmlspecialchars($klasifikacija->klasa_broj) . '</span>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<span class="seup-sadrzaj-badge">' . str_pad($klasifikacija->sadrzaj, 2, '0', STR_PAD_LEFT) . '</span>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<span class="seup-dosje-badge">' . str_pad($klasifikacija->dosje_broj, 2, '0', STR_PAD_LEFT) . '</span>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-puna-oznaka clickable-oznaka" data-id="' . $klasifikacija->ID_klasifikacijske_oznake . '" title="Kliknite za detalje">';
        print '<i class="fas fa-tag me-2"></i>';
        print '<strong>' . htmlspecialchars($klasifikacija->puna_oznaka) . '</strong>';
        print '</div>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        $vrijeme_text = ($klasifikacija->vrijeme_cuvanja == 0) ? 'Trajno' : $klasifikacija->vrijeme_cuvanja . ' god.';
        print '<span class="seup-vrijeme-badge ' . ($klasifikacija->vrijeme_cuvanja == 0 ? 'trajno' : 'godina') . '">' . $vrijeme_text . '</span>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        $opis = htmlspecialchars($klasifikacija->opis_klasifikacijske_oznake);
        if (strlen($opis) > 50) {
            print '<span title="' . $opis . '">' . substr($opis, 0, 50) . '...</span>';
        } else {
            print $opis ?: '<span class="seup-empty-field">—</span>';
        }
        print '</td>';

        // Action buttons
        print '<td class="seup-table-td">';
        print '<div class="seup-action-buttons">';
        print '<button class="seup-action-btn seup-btn-view" title="Pregled detalja" data-id="' . $klasifikacija->ID_klasifikacijske_oznake . '">';
        print '<i class="fas fa-eye"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-edit" title="Uredi" data-id="' . $klasifikacija->ID_klasifikacijske_oznake . '">';
        print '<i class="fas fa-edit"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-delete" title="Obriši" data-id="' . $klasifikacija->ID_klasifikacijske_oznake . '">';
        print '<i class="fas fa-trash"></i>';
        print '</button>';
        print '</div>';
        print '</td>';

        print '</tr>';
    }
} else {
    print '<tr class="seup-table-row">';
    print '<td colspan="8" class="seup-table-empty">';
    print '<div class="seup-empty-state">';
    print '<i class="fas fa-sitemap seup-empty-icon"></i>';
    print '<h4 class="seup-empty-title">Nema definiranih klasifikacijskih oznaka</h4>';
    print '<p class="seup-empty-description">Dodajte prvu klasifikacijsku oznaku za početak rada</p>';
    print '<button type="button" class="seup-btn seup-btn-primary mt-3" id="novaKlasifikacijaBtn2">';
    print '<i class="fas fa-plus me-2"></i>Dodaj prvu klasifikaciju';
    print '</button>';
    print '</div>';
    print '</td>';
    print '</tr>';
}

print '</tbody>';
print '</table>';
print '</div>'; // seup-table-container

// Table footer with stats and actions
print '<div class="seup-table-footer">';
print '<div class="seup-table-stats">';
print '<i class="fas fa-info-circle me-2"></i>';
print '<span>Prikazano <strong id="visibleCount">' . count($klasifikacije) . '</strong> od <strong>' . count($klasifikacije) . '</strong> klasifikacija</span>';
print '</div>';
print '<div class="seup-table-actions">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-btn-sm" id="exportCSVBtn">';
print '<i class="fas fa-file-csv me-2"></i>Izvoz CSV';
print '</button>';
print '<button type="button" class="seup-btn seup-btn-secondary seup-btn-sm" id="exportExcelBtn">';
print '<i class="fas fa-file-excel me-2"></i>Izvoz Excel';
print '</button>';
print '</div>';
print '</div>';

print '</div>'; // seup-settings-card
print '</div>'; // seup-suradnici-container

print '</div>'; // seup-settings-content
print '</main>';

// Details Modal
print '<div class="seup-modal" id="detailsModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-sitemap me-2"></i>Detalji Klasifikacijske Oznake</h5>';
print '<button type="button" class="seup-modal-close" id="closeDetailsModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div id="klasifikacijaDetailsContent">';
print '<div class="seup-loading-message">';
print '<i class="fas fa-spinner fa-spin"></i> Učitavam detalje...';
print '</div>';
print '</div>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="closeDetailsBtn">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Delete Confirmation Modal
print '<div class="seup-modal" id="deleteModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Potvrda Brisanja</h5>';
print '<button type="button" class="seup-modal-close" id="closeDeleteModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div class="seup-delete-confirmation">';
print '<div class="seup-delete-icon">';
print '<i class="fas fa-trash-alt"></i>';
print '</div>';
print '<h4 class="seup-delete-title">Jeste li sigurni?</h4>';
print '<p class="seup-delete-message">Ova akcija će trajno obrisati klasifikacijsku oznaku <strong id="deleteTargetName"></strong> iz sustava.</p>';
print '<div class="seup-delete-warning">';
print '<i class="fas fa-exclamation-triangle me-2"></i>';
print '<span>Ova akcija se ne može poništiti!</span>';
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
print '<div class="seup-modal-content seup-modal-large">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-edit me-2"></i>Uredi Klasifikacijsku Oznaku</h5>';
print '<button type="button" class="seup-modal-close" id="closeEditModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" id="editForm" class="seup-form">';
print '<input type="hidden" id="edit_id_klasifikacijske_oznake" name="id_klasifikacijske_oznake" value="">';
print '<div class="seup-form-grid seup-grid-3">';
print '<div class="seup-form-group"><label class="seup-label">Klasa broj (000)</label><input type="text" id="edit_klasa_br" name="klasa_br" class="seup-input" pattern="\\d{3}" maxlength="3" placeholder="000" required></div>';
print '<div class="seup-form-group"><label class="seup-label">Sadržaj (00)</label><input type="text" id="edit_sadrzaj" name="sadrzaj" class="seup-input" pattern="\\d{2}" maxlength="2" placeholder="00" required></div>';
print '<div class="seup-form-group"><label class="seup-label">Dosje broj</label><select id="edit_dosje_br" name="dosje_br" class="seup-select" required><option value="">Odaberite dosje</option>';
for ($i=1;$i<=50;$i++){ $val=sprintf('%02d',$i); print '<option value="'.$val.'">'.$val.'</option>'; }
print '</select></div></div>';
print '<div class="seup-form-grid"><div class="seup-form-group"><label class="seup-label">Vrijeme čuvanja</label><select id="edit_vrijeme_cuvanja" name="vrijeme_cuvanja" class="seup-select" required><option value="permanent">Trajno</option>';
for ($g=1;$g<=10;$g++) print '<option value="'.$g.'">'.$g.' godina</option>';
print '</select></div><div class="seup-form-group"><label class="seup-label">Opis klasifikacije</label><textarea id="edit_opis_klasifikacije" name="opis_klasifikacije" class="seup-textarea" rows="3"></textarea></div></div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelEditBtn">Odustani</button>';
print '<button type="submit" form="editForm" name="action_klasifikacija" value="update" class="seup-btn seup-btn-primary" id="saveChangesBtn">';
print '<i class="fas fa-save me-2"></i>Spremi Promjene';
print '</button>';
print '</div>';
print '</div>';
print '</div>';

// JavaScript for enhanced functionality
print '<script src="/custom/seup/js/seup-modern.js"></script>';

?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Navigation buttons
    const novaKlasifikacijaBtn = document.getElementById("novaKlasifikacijaBtn");
    const novaKlasifikacijaBtn2 = document.getElementById("novaKlasifikacijaBtn2");
    
    if (novaKlasifikacijaBtn) {
        novaKlasifikacijaBtn.addEventListener("click", function() {
            this.classList.add('seup-loading');
            window.location.href = "postavke.php#klasifikacijske_oznake";
        });
    }
    
    if (novaKlasifikacijaBtn2) {
        novaKlasifikacijaBtn2.addEventListener("click", function() {
            this.classList.add('seup-loading');
            window.location.href = "postavke.php#klasifikacijske_oznake";
        });
    }

    // Enhanced search and filter functionality
    const searchOznaka = document.getElementById('searchOznaka');
    const searchOpis = document.getElementById('searchOpis');
    const sortOrder = document.getElementById('sortOrder');
    const tableRows = document.querySelectorAll('.seup-table-row[data-id]');
    const visibleCountSpan = document.getElementById('visibleCount');

    function filterTable() {
        const oznakaTerm = searchOznaka.value.toLowerCase();
        const opisTerm = searchOpis.value.toLowerCase();
        let visibleCount = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.seup-table-td');
            const oznakaText = cells[4].textContent.toLowerCase(); // Puna oznaka
            const opisText = cells[6].textContent.toLowerCase(); // Opis
            
            // Check search terms
            const matchesOznaka = !oznakaTerm || oznakaText.includes(oznakaTerm);
            const matchesOpis = !opisTerm || opisText.includes(opisTerm);

            if (matchesOznaka && matchesOpis) {
                row.style.display = '';
                visibleCount++;
                // Add staggered animation
                row.style.animationDelay = `${visibleCount * 50}ms`;
                row.classList.add('animate-fade-in-up');
            } else {
                row.style.display = 'none';
                row.classList.remove('animate-fade-in-up');
            }
        });

        // Update visible count
        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleCount;
        }
    }

    if (searchOznaka) {
        searchOznaka.addEventListener('input', debounce(filterTable, 300));
    }
    
    if (searchOpis) {
        searchOpis.addEventListener('input', debounce(filterTable, 300));
    }

    if (sortOrder) {
        sortOrder.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', 'klasa_broj');
            currentUrl.searchParams.set('order', this.value);
            window.location.href = currentUrl.toString();
        });
    }

    // Enhanced row interactions
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // Clickable oznaka functionality
    document.querySelectorAll('.clickable-oznaka').forEach(oznakaCell => {
        oznakaCell.addEventListener('click', function() {
            const id = this.dataset.id;
            openDetailsModal(id);
        });
    });

    // Action button handlers
    document.querySelectorAll('.seup-btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            openDetailsModal(id);
        });
    });

    document.querySelectorAll('.seup-btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            openEditModal(id);
        });
    });

    // Delete handlers
    document.querySelectorAll('.seup-btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const row = this.closest('tr');
            const oznakaCell = row.querySelector('.clickable-oznaka');
            const oznaka = oznakaCell ? oznakaCell.textContent.trim() : 'Klasifikacija #' + id;
            openDeleteModal(id, oznaka);
        });
    });

    // Export handlers
    document.getElementById('exportCSVBtn').addEventListener('click', function() {
        this.classList.add('seup-loading');
        
        const formData = new FormData();
        formData.append('action', 'export_csv');
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create download link
                const link = document.createElement('a');
                link.href = data.download_url;
                link.download = data.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showMessage('CSV datoteka je pripremljena za preuzimanje', 'success');
            } else {
                showMessage('Greška pri kreiranju CSV datoteke: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Export error:', error);
            showMessage('Došlo je do greške pri izvozu', 'error');
        })
        .finally(() => {
            this.classList.remove('seup-loading');
        });
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        this.classList.add('seup-loading');
        
        const formData = new FormData();
        formData.append('action', 'export_excel');
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create download link
                const link = document.createElement('a');
                link.href = data.download_url;
                link.download = data.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showMessage('Excel datoteka je pripremljena za preuzimanje', 'success');
            } else {
                showMessage('Greška pri kreiranju Excel datoteke: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Export error:', error);
            showMessage('Došlo je do greške pri izvozu', 'error');
        })
        .finally(() => {
            this.classList.remove('seup-loading');
        });
    });

    // Modal functionality
    let currentKlasifikacijaId = null;
    let currentDeleteId = null;
    let currentEditId = null;

    function openDetailsModal(klasifikacijaId) {
        currentKlasifikacijaId = klasifikacijaId;
        
        // Show modal
        const modal = document.getElementById('detailsModal');
        modal.classList.add('show');
        
        // Load details
        loadKlasifikacijaDetails(klasifikacijaId);
    }

    function closeDetailsModal() {
        const modal = document.getElementById('detailsModal');
        modal.classList.remove('show');
        currentKlasifikacijaId = null;
    }

    function openDeleteModal(klasifikacijaId, oznaka) {
        currentDeleteId = klasifikacijaId;
        
        // Update modal content
        const targetName = document.getElementById('deleteTargetName');
        if (targetName) {
            targetName.textContent = oznaka;
        }
        
        // Show modal
        const modal = document.getElementById('deleteModal');
        modal.classList.add('show');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('show');
        currentDeleteId = null;
    }

    function openEditModal(klasifikacijaId) {
        currentEditId = klasifikacijaId;
        
        // Show modal
        const modal = document.getElementById('editModal');
        modal.classList.add('show');
        
        // Load data for editing
        loadKlasifikacijaForEdit(klasifikacijaId);
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.remove('show');
        currentEditId = null;
        
        // Reset form
        document.getElementById('editForm').reset();
    }

    function confirmDelete() {
        if (!currentDeleteId) return;
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.classList.add('seup-loading');
        
        const formData = new FormData();
        formData.append('action', 'delete_klasifikacija');
        formData.append('id', currentDeleteId);

        fetch('', { method: 'POST', body: formData })
          .then(r => r.json())
          .then(data => {
              if (data && data.success) {
                  // Remove row from table
                  const rowEl = document.querySelector(`tr[data-id="${currentDeleteId}"]`);
                  if (rowEl) rowEl.remove();
                  
                  // Update visible counter
                  const visibleCountSpan = document.getElementById('visibleCount');
                  if (visibleCountSpan) {
                      const cur = parseInt(visibleCountSpan.textContent, 10) || 1;
                      visibleCountSpan.textContent = Math.max(0, cur - 1);
                  }
                  
                  // Close modal and show success message
                  closeDeleteModal();
                  if (typeof showMessage === 'function') {
                      showMessage('Klasifikacijska oznaka je uspješno obrisana.', 'success');
                  }
              } else {
                  const msg = (data && data.error) ? data.error : 'Brisanje nije uspjelo.';
                  if (typeof showMessage === 'function') showMessage(msg, 'error');
                  console.error('Delete error:', data);
              }
          })
          .catch(err => {
              if (typeof showMessage === 'function') showMessage('Greška pri brisanju.', 'error');
              console.error('Delete fetch error:', err);
          })
          .finally(() => {
              confirmBtn.classList.remove('seup-loading');
          });
    }

    function loadKlasifikacijaDetails(klasifikacijaId) {
        const content = document.getElementById('klasifikacijaDetailsContent');
        content.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Učitavam detalje...</div>';
        
        const formData = new FormData();
        formData.append('action', 'get_klasifikacija_details');
        formData.append('id', klasifikacijaId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderKlasifikacijaDetails(data.klasifikacija);
            } else {
                content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading details:', error);
            content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>Greška pri učitavanju detalja</div>';
        });
    }

    function loadKlasifikacijaForEdit(klasifikacijaId) {
        const formData = new FormData();
        formData.append('action', 'get_klasifikacija_details');
        formData.append('id', klasifikacijaId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.klasifikacija);
            } else {
                showMessage('Greška pri učitavanju podataka: ' + data.error, 'error');
                closeEditModal();
            }
        })
        .catch(error => {
            console.error('Error loading data for edit:', error);
            showMessage('Došlo je do greške pri učitavanju podataka', 'error');
            closeEditModal();
        });
    }

    function populateEditForm(klasifikacija) {
        document.getElementById('edit_klasa_br').value = klasifikacija.klasa_broj;
        document.getElementById('edit_sadrzaj').value = klasifikacija.sadrzaj;
        document.getElementById('edit_dosje_br').value = klasifikacija.dosje_broj;
        document.getElementById('edit_vrijeme_cuvanja').value = klasifikacija.vrijeme_cuvanja == 0 ? 'permanent' : klasifikacija.vrijeme_cuvanja;
        document.getElementById('edit_opis_klasifikacije').value = klasifikacija.opis_klasifikacijske_oznake || '';
        document.getElementById('edit_id_klasifikacijske_oznake').value = klasifikacija.ID_klasifikacijske_oznake;
    }

    function renderKlasifikacijaDetails(klasifikacija) {
        const content = document.getElementById('klasifikacijaDetailsContent');
        
        let html = '<div class="seup-klasifikacija-details">';
        
        // Header with oznaka and basic info
        html += '<div class="seup-details-header">';
        html += '<div class="seup-details-avatar"><i class="fas fa-sitemap"></i></div>';
        html += '<div class="seup-details-basic">';
        html += '<h4>' + escapeHtml(klasifikacija.puna_oznaka) + '</h4>';
        html += '<p class="seup-klasifikacija-type">Klasifikacijska oznaka</p>';
        html += '</div>';
        html += '</div>';
        
        // Details grid
        html += '<div class="seup-details-grid">';
        
        // Klasa broj
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-folder me-2"></i>Klasa broj</div>';
        html += '<div class="seup-detail-value">' + escapeHtml(klasifikacija.klasa_broj) + '</div>';
        html += '</div>';
        
        // Sadržaj
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-list me-2"></i>Sadržaj</div>';
        html += '<div class="seup-detail-value">' + escapeHtml(klasifikacija.sadrzaj) + '</div>';
        html += '</div>';
        
        // Dosje broj
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-file me-2"></i>Dosje broj</div>';
        html += '<div class="seup-detail-value">' + escapeHtml(klasifikacija.dosje_broj) + '</div>';
        html += '</div>';
        
        // Vrijeme čuvanja
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-clock me-2"></i>Vrijeme čuvanja</div>';
        html += '<div class="seup-detail-value">' + escapeHtml(klasifikacija.vrijeme_cuvanja_text) + '</div>';
        html += '</div>';
        
        // Opis
        html += '<div class="seup-detail-item seup-detail-wide">';
        html += '<div class="seup-detail-label"><i class="fas fa-align-left me-2"></i>Opis</div>';
        html += '<div class="seup-detail-value">' + (klasifikacija.opis_klasifikacijske_oznake || '—') + '</div>';
        html += '</div>';
        
        html += '</div>'; // seup-details-grid
        html += '</div>'; // seup-klasifikacija-details
        
        content.innerHTML = html;
    }

    // Modal event listeners
    document.getElementById('closeDetailsModal').addEventListener('click', closeDetailsModal);
    document.getElementById('closeDetailsBtn').addEventListener('click', closeDetailsModal);

    // Delete modal event listeners
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

    // Edit modal event listeners
    document.getElementById('closeEditModal').addEventListener('click', closeEditModal);
    document.getElementById('cancelEditBtn').addEventListener('click', closeEditModal);
    
    // Handle edit form submission
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const saveBtn = document.getElementById('saveChangesBtn');
        saveBtn.classList.add('seup-loading');
        
        const formData = new FormData(this);
        formData.append('action', 'update_klasifikacija');
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                closeEditModal();
                // Reload page to show changes
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showMessage('Greška: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Došlo je do greške pri spremanju', 'error');
        })
        .finally(() => {
            saveBtn.classList.remove('seup-loading');
        });
    });

    // Close modal when clicking outside
    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetailsModal();
        }
    });

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Utility functions
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Toast message function
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

    // Initial staggered animation for existing rows
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 100}ms`;
        row.classList.add('animate-fade-in-up');
    });
});
</script>

<style>
/* Additional styles for klasifikacijske oznake */
.seup-klasa-badge,
.seup-sadrzaj-badge,
.seup-dosje-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
    text-align: center;
    min-width: 40px;
}

.seup-klasa-badge {
    background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
    color: var(--primary-800);
    border: 1px solid var(--primary-300);
}

.seup-sadrzaj-badge {
    background: linear-gradient(135deg, var(--accent-100), var(--accent-200));
    color: var(--accent-800);
    border: 1px solid var(--accent-300);
}

.seup-dosje-badge {
    background: linear-gradient(135deg, var(--success-100), var(--success-200));
    color: var(--success-800);
    border: 1px solid var(--success-300);
}

.seup-puna-oznaka {
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all var(--transition-fast);
    display: inline-flex;
    align-items: center;
    background: var(--neutral-50);
    border: 1px solid var(--neutral-200);
}

.seup-puna-oznaka:hover {
    background: var(--primary-50);
    border-color: var(--primary-300);
    transform: translateX(4px);
}

.seup-vrijeme-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
}

.seup-vrijeme-badge.trajno {
    background: linear-gradient(135deg, var(--warning-100), var(--warning-200));
    color: var(--warning-800);
    border: 1px solid var(--warning-300);
}

.seup-vrijeme-badge.godina {
    background: linear-gradient(135deg, var(--secondary-100), var(--secondary-200));
    color: var(--secondary-800);
    border: 1px solid var(--secondary-300);
}

.seup-klasifikacija-details .seup-klasifikacija-type {
    color: var(--secondary-600);
    font-size: var(--text-sm);
    margin: 0;
}

/* Message toast styles */
.seup-message-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 18px;
    border-radius: 10px;
    color: #fff;
    font-weight: 600;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
    transform: translateX(400px);
    transition: transform .25s;
    z-index: 9999;
    max-width: 400px;
}

.seup-message-toast.show {
    transform: translateX(0);
}

.seup-message-success {
    background: linear-gradient(135deg, #16a34a, #15803d);
}

.seup-message-error {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.seup-btn.seup-loading {
    position: relative;
    color: transparent;
}

.seup-btn.seup-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<?php
llxFooter();
$db->close();
?>