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
 *	\file       seup/pages/suradnici.php
 *	\ingroup    seup
 *	\brief      Suradnici i treće osobe page
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
require_once __DIR__ . '/../class/suradnici_helper.class.php';
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
    
    // Handle suradnik update
    if ($action === 'update_suradnik') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $rowid = GETPOST('rowid', 'int');
        $naziv = trim(GETPOST('to_naziv', 'restricthtml'));
        $adresa = trim(GETPOST('to_adresa', 'restricthtml'));
        $oib = trim(GETPOST('to_oib', 'alphanohtml'));
        $telefon = trim(GETPOST('to_telefon', 'alphanohtml'));
        $kontakt_osoba = trim(GETPOST('to_kontakt_osoba', 'restricthtml'));
        $email = trim(GETPOST('to_email', 'alphanohtml'));
        
        // Validations
        $errs = array();
        if ($naziv === '') $errs[] = "Naziv je obavezan.";
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = "E-mail nije valjan.";
        
        if (empty($errs)) {
            try {
                $db->begin();
                
                // Check if OIB already exists (excluding current record)
                if ($oib !== '') {
                    $sql_check = "SELECT rowid FROM " . MAIN_DB_PREFIX . "a_posiljatelji 
                                 WHERE oib = '" . $db->escape($oib) . "' 
                                 AND rowid != " . (int)$rowid;
                    
                    $resql_check = $db->query($sql_check);
                    if ($resql_check && $db->num_rows($resql_check) > 0) {
                        throw new Exception('OIB već postoji na drugom zapisu');
                    }
                }
                
                // Update record
                $sql = "UPDATE " . MAIN_DB_PREFIX . "a_posiljatelji SET 
                        naziv = '" . $db->escape($naziv) . "',
                        adresa = '" . $db->escape($adresa) . "',
                        oib = " . ($oib !== '' ? "'" . $db->escape($oib) . "'" : "NULL") . ",
                        telefon = '" . $db->escape($telefon) . "',
                        kontakt_osoba = '" . $db->escape($kontakt_osoba) . "',
                        email = " . ($email !== '' ? "'" . $db->escape($email) . "'" : "NULL") . "
                        WHERE rowid = " . (int)$rowid;
                
                if (!$db->query($sql)) {
                    throw new Exception($db->lasterror());
                }
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Suradnik je uspješno ažuriran']);
                exit;
                
            } catch (Exception $e) {
                $db->rollback();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => implode(' ', $errs)]);
            exit;
        }
    }
    
    if ($action === 'export_csv') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $result = Suradnici_Helper::exportToCSV($db);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'export_excel') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $result = Suradnici_Helper::exportToExcel($db);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'get_suradnik_details') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $rowid = GETPOST('rowid', 'int');
        $result = Suradnici_Helper::getSuradnikDetails($db, $rowid);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'export_vcf') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $rowid = GETPOST('rowid', 'int');
        $result = Suradnici_Helper::exportToVCF($db, $rowid);
        echo json_encode($result);
        exit;
    }
}


if ($action === 'delete_suradnik') {
    header('Content-Type: application/json');
    ob_end_clean();
    $rowid = GETPOST('rowid', 'int');
    $ok = false; $err = null;
    if ($rowid > 0) {
        $table = MAIN_DB_PREFIX . "a_posiljatelji";
        $sql = "DELETE FROM `".$table."` WHERE rowid=".(int)$rowid." LIMIT 1";
        $ok = $db->query($sql);
        if (!$ok) { $err = $db->lasterror(); }
    } else {
        $err = 'Nevažeći ID.';
    }
    echo json_encode(['success' => (bool)$ok, 'error' => $err]);
    exit;
}
// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'naziv';
$sortOrder = GETPOST('order', 'aZ09') ?: 'ASC';

// Validate sort fields
$allowedSortFields = ['rowid', 'naziv', 'oib', 'telefon', 'email', 'datec'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'naziv';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Fetch all suradnici
$sql = "SELECT 
            rowid,
            naziv,
            adresa,
            oib,
            telefon,
            kontakt_osoba,
            email,
            DATE_FORMAT(datec, '%d.%m.%Y %H:%i') as datum_kreiranja
        FROM " . MAIN_DB_PREFIX . "a_posiljatelji
        ORDER BY {$sortField} {$sortOrder}";

$resql = $db->query($sql);
$suradnici = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $suradnici[] = $obj;
    }
}

$form = new Form($db);
llxHeader("", "Suradnici i treće osobe", '', '', 0, 0, '', '', '', 'mod-seup page-suradnici');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
print '<link href="/custom/seup/css/suradnici.css" rel="stylesheet">';

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
print '<h1 class="seup-settings-title">Suradnici i Treće Osobe</h1>';
print '<p class="seup-settings-subtitle">Upravljanje kontaktima, suradnicima i vanjskim partnerima</p>';
print '</div>';

// Main content card
print '<div class="seup-suradnici-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-users"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Popis Suradnika</h3>';
print '<p class="seup-card-description">Pregled svih registriranih suradnika i vanjskih partnera</p>';
print '</div>';
print '<div class="seup-card-actions">';
print '<button type="button" class="seup-btn seup-btn-primary" id="noviSuradnikBtn">';
print '<i class="fas fa-plus me-2"></i>Novi Suradnik';
print '</button>';
print '</div>';
print '</div>';

// Search and filter section
print '<div class="seup-table-controls">';
print '<div class="seup-search-container">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-search seup-search-icon"></i>';
print '<input type="text" id="searchNaziv" class="seup-search-input" placeholder="Pretraži po nazivu...">';
print '</div>';
print '</div>';
print '<div class="seup-filter-controls">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-id-card seup-search-icon"></i>';
print '<input type="text" id="searchOIB" class="seup-search-input" placeholder="Pretraži po OIB-u..." maxlength="11">';
print '</div>';
print '<select id="sortOrder" class="seup-filter-select">';
print '<option value="ASC"' . ($sortOrder === 'ASC' ? ' selected' : '') . '>A → Ž</option>';
print '<option value="DESC"' . ($sortOrder === 'DESC' ? ' selected' : '') . '>Ž → A</option>';
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
print sortableHeader('naziv', 'Naziv', $sortField, $sortOrder, 'fas fa-user');
print sortableHeader('oib', 'OIB', $sortField, $sortOrder, 'fas fa-id-card');
print sortableHeader('telefon', 'Telefon', $sortField, $sortOrder, 'fas fa-phone');
print sortableHeader('email', 'Email', $sortField, $sortOrder, 'fas fa-envelope');
print '<th class="seup-table-th"><i class="fas fa-cogs me-2"></i>Akcije</th>';
print '</tr>';
print '</thead>';
print '<tbody class="seup-table-body">';

if (count($suradnici)) {
    foreach ($suradnici as $index => $suradnik) {
        $rowClass = ($index % 2 === 0) ? 'seup-table-row-even' : 'seup-table-row-odd';
        print '<tr class="seup-table-row ' . $rowClass . '" data-id="' . $suradnik->rowid . '">';
        
        print '<td class="seup-table-td">';
        print '<span class="seup-badge seup-badge-neutral">' . ($index + 1) . '</span>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-naziv-cell clickable-name" data-id="' . $suradnik->rowid . '" title="Kliknite za detalje">';
        print '<i class="fas fa-user me-2"></i>';
        print htmlspecialchars($suradnik->naziv);
        print '</div>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        if (!empty($suradnik->oib)) {
            print '<span class="seup-oib-badge">' . htmlspecialchars($suradnik->oib) . '</span>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';
        
        print '<td class="seup-table-td">';
        if (!empty($suradnik->telefon)) {
            print '<div class="seup-contact-info">';
            print '<i class="fas fa-phone me-2"></i>';
            print '<a href="tel:' . htmlspecialchars($suradnik->telefon) . '">' . htmlspecialchars($suradnik->telefon) . '</a>';
            print '</div>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';
        
        print '<td class="seup-table-td">';
        if (!empty($suradnik->email)) {
            print '<div class="seup-contact-info">';
            print '<i class="fas fa-envelope me-2"></i>';
            print '<a href="mailto:' . htmlspecialchars($suradnik->email) . '">' . htmlspecialchars($suradnik->email) . '</a>';
            print '</div>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';

        // Action buttons
        print '<td class="seup-table-td">';
        print '<div class="seup-action-buttons">';
        print '<button class="seup-action-btn seup-btn-view" title="Pregled detalja" data-id="' . $suradnik->rowid . '">';
        print '<i class="fas fa-eye"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-edit" title="Uredi" data-id="' . $suradnik->rowid . '">';
        print '<i class="fas fa-edit"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-vcf" title="Preuzmi VCF kontakt" data-id="' . $suradnik->rowid . '">';
        print '<i class="fas fa-address-card"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-delete" title="Obriši" data-id="' . $suradnik->rowid . '">';
        print '<i class="fas fa-trash"></i>';
        print '</button>';
        print '</div>';
        print '</td>';

        print '</tr>';
    }
} else {
    print '<tr class="seup-table-row">';
    print '<td colspan="6" class="seup-table-empty">';
    print '<div class="seup-empty-state">';
    print '<i class="fas fa-users seup-empty-icon"></i>';
    print '<h4 class="seup-empty-title">Nema registriranih suradnika</h4>';
    print '<p class="seup-empty-description">Dodajte prvog suradnika za početak rada</p>';
    print '<button type="button" class="seup-btn seup-btn-primary mt-3" id="noviSuradnikBtn2">';
    print '<i class="fas fa-plus me-2"></i>Dodaj prvog suradnika';
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
print '<span>Prikazano <strong id="visibleCount">' . count($suradnici) . '</strong> od <strong>' . count($suradnici) . '</strong> suradnika</span>';
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
print '<h5 class="seup-modal-title"><i class="fas fa-user me-2"></i>Detalji Suradnika</h5>';
print '<button type="button" class="seup-modal-close" id="closeDetailsModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div id="suradnikDetailsContent">';
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
print '<p class="seup-delete-message">Ova akcija će trajno obrisati suradnika <strong id="deleteTargetName"></strong> iz sustava.</p>';
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
print '<h5 class="seup-modal-title"><i class="fas fa-edit me-2"></i>Uredi Suradnika</h5>';
print '<button type="button" class="seup-modal-close" id="closeEditModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" id="editForm" class="seup-form">';
print '<input type="hidden" id="edit_rowid" name="rowid" value="">';
print '<div class="seup-form-grid seup-grid-2">';
print '<div class="seup-form-group"><label class="seup-label">Naziv / Ime i prezime *</label><input type="text" id="edit_to_naziv" name="to_naziv" class="seup-input" required></div>';
print '<div class="seup-form-group"><label class="seup-label">OIB</label><input type="text" id="edit_to_oib" name="to_oib" class="seup-input" pattern="\\d{11}" maxlength="11"></div>';
print '<div class="seup-form-group"><label class="seup-label">Adresa</label><input type="text" id="edit_to_adresa" name="to_adresa" class="seup-input"></div>';
print '<div class="seup-form-group"><label class="seup-label">Kontakt osoba</label><input type="text" id="edit_to_kontakt_osoba" name="to_kontakt_osoba" class="seup-input"></div>';
print '<div class="seup-form-group"><label class="seup-label">Kontakt telefon</label><input type="text" id="edit_to_telefon" name="to_telefon" class="seup-input"></div>';
print '<div class="seup-form-group"><label class="seup-label">E-mail</label><input type="email" id="edit_to_email" name="to_email" class="seup-input"></div>';
print '</div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelEditBtn">Odustani</button>';
print '<button type="submit" form="editForm" name="action" value="update_suradnik" class="seup-btn seup-btn-primary" id="saveChangesBtn">';
print '<i class="fas fa-save me-2"></i>Spremi Promjene';
print '</button>';
print '</div>';
print '</div>';
print '</div>';

// JavaScript for enhanced functionality
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/suradnici.js"></script>';

?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Navigation buttons
    const noviSuradnikBtn = document.getElementById("noviSuradnikBtn");
    const noviSuradnikBtn2 = document.getElementById("noviSuradnikBtn2");
    
    if (noviSuradnikBtn) {
        noviSuradnikBtn.addEventListener("click", function() {
            this.classList.add('seup-loading');
            window.location.href = "postavke.php#trece_osobe";
        });
    }
    
    if (noviSuradnikBtn2) {
        noviSuradnikBtn2.addEventListener("click", function() {
            this.classList.add('seup-loading');
            window.location.href = "postavke.php#trece_osobe";
        });
    }

    // Enhanced search and filter functionality
    const searchNaziv = document.getElementById('searchNaziv');
    const searchOIB = document.getElementById('searchOIB');
    const sortOrder = document.getElementById('sortOrder');
    const tableRows = document.querySelectorAll('.seup-table-row[data-id]');
    const visibleCountSpan = document.getElementById('visibleCount');

    function filterTable() {
        const nazivTerm = searchNaziv.value.toLowerCase();
        const oibTerm = searchOIB.value.toLowerCase();
        let visibleCount = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.seup-table-td');
            const nazivText = cells[1].textContent.toLowerCase();
            const oibText = cells[2].textContent.toLowerCase();
            
            // Check search terms
            const matchesNaziv = !nazivTerm || nazivText.includes(nazivTerm);
            const matchesOIB = !oibTerm || oibText.includes(oibTerm);

            if (matchesNaziv && matchesOIB) {
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

    if (searchNaziv) {
        searchNaziv.addEventListener('input', debounce(filterTable, 300));
    }
    
    if (searchOIB) {
        searchOIB.addEventListener('input', debounce(filterTable, 300));
    }

    if (sortOrder) {
        sortOrder.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', 'naziv');
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

    // Clickable name functionality
    document.querySelectorAll('.clickable-name').forEach(nameCell => {
        nameCell.addEventListener('click', function() {
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

    // VCF export handlers
    document.querySelectorAll('.seup-btn-vcf').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            exportVCF(id, this);
        });

// Delete handlers
document.querySelectorAll('.seup-btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const row = this.closest('tr');
        const nazivCell = row.querySelector('.clickable-name');
        const naziv = nazivCell ? nazivCell.textContent.trim() : 'Suradnik #' + id;
        openDeleteModal(id, naziv);
    });
});

    });

    // VCF export function
    function exportVCF(rowid, button) {
        button.classList.add('seup-loading');
        
        const formData = new FormData();
        formData.append('action', 'export_vcf');
        formData.append('rowid', rowid);
        
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
                
                showMessage(`VCF kontakt za ${data.contact_name} je preuzet`, 'success');
            } else {
                showMessage('Greška pri kreiranju VCF kontakta: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('VCF export error:', error);
            showMessage('Došlo je do greške pri kreiranju kontakta', 'error');
        })
        .finally(() => {
            button.classList.remove('seup-loading');
        });
    }
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
    let currentSuradnikId = null;
    let currentDeleteId = null;
    let currentEditId = null;

    function openDetailsModal(suradnikId) {
        currentSuradnikId = suradnikId;
        
        // Show modal
        const modal = document.getElementById('detailsModal');
        modal.classList.add('show');
        
        // Load details
        loadSuradnikDetails(suradnikId);
    }

    function closeDetailsModal() {
        const modal = document.getElementById('detailsModal');
        modal.classList.remove('show');
        currentSuradnikId = null;
    }

    function openDeleteModal(suradnikId, naziv) {
        currentDeleteId = suradnikId;
        
        // Update modal content
        const targetName = document.getElementById('deleteTargetName');
        if (targetName) {
            targetName.textContent = naziv;
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

    function openEditModal(suradnikId) {
        currentEditId = suradnikId;
        
        // Show modal
        const modal = document.getElementById('editModal');
        modal.classList.add('show');
        
        // Load data for editing
        loadSuradnikForEdit(suradnikId);
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
        formData.append('action', 'delete_suradnik');
        formData.append('rowid', currentDeleteId);

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
                      showMessage('Suradnik je uspješno obrisan.', 'success');
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
    function loadSuradnikDetails(suradnikId) {
        const content = document.getElementById('suradnikDetailsContent');
        content.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Učitavam detalje...</div>';
        
        const formData = new FormData();
        formData.append('action', 'get_suradnik_details');
        formData.append('rowid', suradnikId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSuradnikDetails(data.suradnik);
            } else {
                content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading details:', error);
            content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>Greška pri učitavanju detalja</div>';
        });
    }

    function loadSuradnikForEdit(suradnikId) {
        const formData = new FormData();
        formData.append('action', 'get_suradnik_details');
        formData.append('rowid', suradnikId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.suradnik);
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

    function populateEditForm(suradnik) {
        document.getElementById('edit_to_naziv').value = suradnik.naziv || '';
        document.getElementById('edit_to_oib').value = suradnik.oib || '';
        document.getElementById('edit_to_adresa').value = suradnik.adresa || '';
        document.getElementById('edit_to_kontakt_osoba').value = suradnik.kontakt_osoba || '';
        document.getElementById('edit_to_telefon').value = suradnik.telefon || '';
        document.getElementById('edit_to_email').value = suradnik.email || '';
        document.getElementById('edit_rowid').value = suradnik.rowid;
    }

    function renderSuradnikDetails(suradnik) {
        const content = document.getElementById('suradnikDetailsContent');
        
        let html = '<div class="seup-suradnik-details">';
        
        // Header with name and basic info
        html += '<div class="seup-details-header">';
        html += '<div class="seup-details-avatar"><i class="fas fa-user"></i></div>';
        html += '<div class="seup-details-basic">';
        html += '<h4>' + escapeHtml(suradnik.naziv) + '</h4>';
        if (suradnik.kontakt_osoba) {
            html += '<p class="seup-contact-person">Kontakt: ' + escapeHtml(suradnik.kontakt_osoba) + '</p>';
        }
        html += '</div>';
        html += '</div>';
        
        // Details grid
        html += '<div class="seup-details-grid">';
        
        // OIB
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-id-card me-2"></i>OIB</div>';
        html += '<div class="seup-detail-value">' + (suradnik.oib || '—') + '</div>';
        html += '</div>';
        
        // Telefon
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-phone me-2"></i>Telefon</div>';
        html += '<div class="seup-detail-value">';
        if (suradnik.telefon) {
            html += '<a href="tel:' + escapeHtml(suradnik.telefon) + '">' + escapeHtml(suradnik.telefon) + '</a>';
        } else {
            html += '—';
        }
        html += '</div>';
        html += '</div>';
        
        // Email
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-envelope me-2"></i>Email</div>';
        html += '<div class="seup-detail-value">';
        if (suradnik.email) {
            html += '<a href="mailto:' + escapeHtml(suradnik.email) + '">' + escapeHtml(suradnik.email) + '</a>';
        } else {
            html += '—';
        }
        html += '</div>';
        html += '</div>';
        
        // Adresa
        html += '<div class="seup-detail-item seup-detail-wide">';
        html += '<div class="seup-detail-label"><i class="fas fa-map-marker-alt me-2"></i>Adresa</div>';
        html += '<div class="seup-detail-value">' + (suradnik.adresa || '—') + '</div>';
        html += '</div>';
        
        // Datum kreiranja
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-calendar me-2"></i>Datum kreiranja</div>';
        html += '<div class="seup-detail-value">' + suradnik.datum_kreiranja + '</div>';
        html += '</div>';
        
        html += '</div>'; // seup-details-grid
        html += '</div>'; // seup-suradnik-details
        
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
        formData.append('action', 'update_suradnik');
        
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