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
 *	\file       seup/pages/arhivska_gradiva.php
 *	\ingroup    seup
 *	\brief      Arhivska gradiva page
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
require_once __DIR__ . '/../class/changelog_sistem.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Security check
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = GETPOST('action', 'alpha');
    
    // Handle gradivo update
    if ($action === 'update_gradivo') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $id = GETPOST('rowid', 'int');
        $oznaka = GETPOST('ag_oznaka', 'alphanohtml');
        $vrsta_gradiva = GETPOST('ag_vrsta_gradiva', 'restricthtml');
        $opisi_napomene = GETPOST('ag_opisi_napomene', 'restricthtml');
        
        try {
            $db->begin();
            
            // Check if oznaka already exists (excluding current record)
            $sql_check = "SELECT rowid FROM " . MAIN_DB_PREFIX . "a_arhivska_gradiva 
                         WHERE oznaka = '" . $db->escape($oznaka) . "' 
                         AND rowid != " . (int)$id;
            
            $resql_check = $db->query($sql_check);
            if ($resql_check && $db->num_rows($resql_check) > 0) {
                throw new Exception('Oznaka već postoji na drugom zapisu');
            }
            
            // Update record
            $sql = "UPDATE " . MAIN_DB_PREFIX . "a_arhivska_gradiva SET 
                    oznaka = '" . $db->escape($oznaka) . "',
                    vrsta_gradiva = '" . $db->escape($vrsta_gradiva) . "',
                    opisi_napomene = " . ($opisi_napomene ? "'" . $db->escape($opisi_napomene) . "'" : "NULL") . "
                    WHERE rowid = " . (int)$id;
            
            if (!$db->query($sql)) {
                throw new Exception($db->lasterror());
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Arhivsko gradivo je uspješno ažurirano']);
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($action === 'get_gradivo_details') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $rowid = GETPOST('rowid', 'int');
        
        if (!$rowid) {
            echo json_encode(['success' => false, 'error' => 'Missing ID']);
            exit;
        }
        
        $sql = "SELECT 
                    rowid,
                    oznaka,
                    vrsta_gradiva,
                    opisi_napomene,
                    DATE_FORMAT(datec, '%d.%m.%Y %H:%i') as datum_kreiranja
                FROM " . MAIN_DB_PREFIX . "a_arhivska_gradiva
                WHERE rowid = " . (int)$rowid;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            echo json_encode([
                'success' => true,
                'gradivo' => $obj
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Arhivsko gradivo nije pronađeno'
            ]);
        }
        exit;
    }
    
    if ($action === 'delete_gradivo') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $rowid = GETPOST('rowid', 'int');
        
        if (!$rowid) {
            echo json_encode(['success' => false, 'error' => 'Missing ID']);
            exit;
        }
        
        $db->begin();
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_arhivska_gradiva WHERE rowid = " . (int)$rowid;
        $result = $db->query($sql);
        
        if ($result) {
            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Arhivsko gradivo je uspješno obrisano'
            ]);
        } else {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'error' => 'Greška pri brisanju: ' . $db->lasterror()
            ]);
        }
        exit;
    }
}

// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'oznaka';
$sortOrder = GETPOST('order', 'aZ09') ?: 'ASC';

// Validate sort fields
$allowedSortFields = ['rowid', 'oznaka', 'vrsta_gradiva', 'datec'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'oznaka';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Fetch all arhivska gradiva
$sql = "SELECT 
            rowid,
            oznaka,
            vrsta_gradiva,
            opisi_napomene,
            DATE_FORMAT(datec, '%d.%m.%Y %H:%i') as datum_kreiranja
        FROM " . MAIN_DB_PREFIX . "a_arhivska_gradiva
        ORDER BY {$sortField} {$sortOrder}";

$resql = $db->query($sql);
$gradiva = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $gradiva[] = $obj;
    }
}

$form = new Form($db);
llxHeader("", "Arhivska gradiva", '', '', 0, 0, '', '', '', 'mod-seup page-arhivska-gradiva');

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
print '<h1 class="seup-settings-title">Vrste Arhivskog Gradiva</h1>';
print '<p class="seup-settings-subtitle">Upravljanje vrstama arhivskog gradiva i dokumentacije</p>';
print '</div>';

// Main content card
print '<div class="seup-suradnici-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-archive"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Popis Arhivskih Gradiva</h3>';
print '<p class="seup-card-description">Pregled svih registriranih vrsta arhivskog gradiva</p>';
print '</div>';
print '<div class="seup-card-actions">';
print '<button type="button" class="seup-btn seup-btn-primary" id="novoGradivoBtn">';
print '<i class="fas fa-plus me-2"></i>Novo Gradivo';
print '</button>';
print '</div>';
print '</div>';

// Search and filter section
print '<div class="seup-table-controls">';
print '<div class="seup-search-container">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-search seup-search-icon"></i>';
print '<input type="text" id="searchID" class="seup-search-input" placeholder="Pretraži po ID-u...">';
print '</div>';
print '</div>';
print '<div class="seup-filter-controls">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-tag seup-search-icon"></i>';
print '<input type="text" id="searchNaziv" class="seup-search-input" placeholder="Pretraži po nazivu...">';
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
print '<th class="seup-table-th"><i class="fas fa-hashtag me-2"></i>ID</th>';
print sortableHeader('oznaka', 'Oznaka', $sortField, $sortOrder, 'fas fa-tag');
print sortableHeader('vrsta_gradiva', 'Vrsta Gradiva', $sortField, $sortOrder, 'fas fa-archive');
print '<th class="seup-table-th"><i class="fas fa-align-left me-2"></i>Opisi/Napomene</th>';
print sortableHeader('datec', 'Datum Kreiranja', $sortField, $sortOrder, 'fas fa-calendar');
print '<th class="seup-table-th"><i class="fas fa-cogs me-2"></i>Akcije</th>';
print '</tr>';
print '</thead>';
print '<tbody class="seup-table-body">';

if (count($gradiva)) {
    foreach ($gradiva as $index => $gradivo) {
        $rowClass = ($index % 2 === 0) ? 'seup-table-row-even' : 'seup-table-row-odd';
        print '<tr class="seup-table-row ' . $rowClass . '" data-id="' . $gradivo->rowid . '">';
        
        print '<td class="seup-table-td">';
        print '<span class="seup-badge seup-badge-neutral">' . $gradivo->rowid . '</span>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-naziv-cell clickable-name" data-id="' . $gradivo->rowid . '" title="Kliknite za detalje">';
        print '<i class="fas fa-tag me-2"></i>';
        print htmlspecialchars($gradivo->oznaka);
        print '</div>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-gradivo-info">';
        print '<i class="fas fa-archive me-2"></i>';
        print htmlspecialchars($gradivo->vrsta_gradiva);
        print '</div>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        if (!empty($gradivo->opisi_napomene)) {
            print '<div class="seup-opis-cell" title="' . htmlspecialchars($gradivo->opisi_napomene) . '">';
            print dol_trunc($gradivo->opisi_napomene, 50);
            print '</div>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-date-info">';
        print '<i class="fas fa-calendar me-2"></i>';
        print $gradivo->datum_kreiranja;
        print '</div>';
        print '</td>';

        // Action buttons
        print '<td class="seup-table-td">';
        print '<div class="seup-action-buttons">';
        print '<button class="seup-action-btn seup-btn-view" title="Pregled detalja" data-id="' . $gradivo->rowid . '">';
        print '<i class="fas fa-eye"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-edit" title="Uredi" data-id="' . $gradivo->rowid . '">';
        print '<i class="fas fa-edit"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-delete" title="Obriši" data-id="' . $gradivo->rowid . '">';
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
    print '<i class="fas fa-archive seup-empty-icon"></i>';
    print '<h4 class="seup-empty-title">Nema registriranih vrsta arhivskog gradiva</h4>';
    print '<p class="seup-empty-description">Dodajte prvu vrstu gradiva za početak rada</p>';
    print '<button type="button" class="seup-btn seup-btn-primary mt-3" id="novoGradivoBtn2">';
    print '<i class="fas fa-plus me-2"></i>Dodaj prvo gradivo';
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
print '<span>Prikazano <strong id="visibleCount">' . count($gradiva) . '</strong> od <strong>' . count($gradiva) . '</strong> vrsta gradiva</span>';
print '</div>';
print '<div class="seup-table-actions">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-btn-sm" id="exportBtn">';
print '<i class="fas fa-download me-2"></i>Izvoz Excel';
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
print '<h5 class="seup-modal-title"><i class="fas fa-archive me-2"></i>Detalji Arhivskog Gradiva</h5>';
print '<button type="button" class="seup-modal-close" id="closeDetailsModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div id="gradivoDetailsContent">';
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
print '<p class="seup-delete-message">Ova akcija će trajno obrisati arhivsko gradivo <strong id="deleteTargetName"></strong> iz sustava.</p>';
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
print '<h5 class="seup-modal-title"><i class="fas fa-edit me-2"></i>Uredi Arhivsko Gradivo</h5>';
print '<button type="button" class="seup-modal-close" id="closeEditModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" id="editForm" class="seup-form">';
print '<input type="hidden" id="edit_rowid" name="rowid" value="">';
print '<div class="seup-form-grid seup-grid-2">';
print '<div class="seup-form-group"><label class="seup-label">Oznaka *</label><input type="text" id="edit_ag_oznaka" name="ag_oznaka" class="seup-input" required placeholder="Unesite oznaku"></div>';
print '<div class="seup-form-group"><label class="seup-label">Vrsta Gradiva *</label><input type="text" id="edit_ag_vrsta_gradiva" name="ag_vrsta_gradiva" class="seup-input" required placeholder="Unesite vrstu gradiva"></div>';
print '</div>';
print '<div class="seup-form-group"><label class="seup-label">Opisi/Napomene</label><textarea id="edit_ag_opisi_napomene" name="ag_opisi_napomene" class="seup-textarea" rows="4" placeholder="Unesite opise ili napomene..."></textarea></div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelEditBtn">Odustani</button>';
print '<button type="submit" form="editForm" name="action" value="update_gradivo" class="seup-btn seup-btn-primary" id="saveChangesBtn">';
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
    const novoGradivoBtn = document.getElementById("novoGradivoBtn");
    const novoGradivoBtn2 = document.getElementById("novoGradivoBtn2");
    
    if (novoGradivoBtn) {
        novoGradivoBtn.addEventListener("click", function() {
            this.classList.add('seup-loading');
            window.location.href = "postavke.php#arhivska_gradiva";
        });
    }
    
    if (novoGradivoBtn2) {
        novoGradivoBtn2.addEventListener("click", function() {
            this.classList.add('seup-loading');
            window.location.href = "postavke.php#arhivska_gradiva";
        });
    }

    // Enhanced search and filter functionality
    const searchID = document.getElementById('searchID');
    const searchNaziv = document.getElementById('searchNaziv');
    const sortOrder = document.getElementById('sortOrder');
    const tableRows = document.querySelectorAll('.seup-table-row[data-id]');
    const visibleCountSpan = document.getElementById('visibleCount');

    function filterTable() {
        const idTerm = searchID.value.toLowerCase();
        const nazivTerm = searchNaziv.value.toLowerCase();
        let visibleCount = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.seup-table-td');
            const idText = cells[0].textContent.toLowerCase();
            const oznakaText = cells[1].textContent.toLowerCase();
            const vrstaText = cells[2].textContent.toLowerCase();
            
            // Check search terms
            const matchesID = !idTerm || idText.includes(idTerm);
            const matchesNaziv = !nazivTerm || oznakaText.includes(nazivTerm) || vrstaText.includes(nazivTerm);

            if (matchesID && matchesNaziv) {
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

    if (searchID) {
        searchID.addEventListener('input', debounce(filterTable, 300));
    }
    
    if (searchNaziv) {
        searchNaziv.addEventListener('input', debounce(filterTable, 300));
    }

    if (sortOrder) {
        sortOrder.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', 'oznaka');
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

    // Delete button handlers
    document.querySelectorAll('.seup-btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const row = this.closest('.seup-table-row');
            const oznakaCell = row.querySelector('.clickable-name');
            const oznaka = oznakaCell ? oznakaCell.textContent.trim() : 'Gradivo #' + id;
            openDeleteModal(id, oznaka);
        });
    });

    // Export handler
    document.getElementById('exportBtn').addEventListener('click', function() {
        this.classList.add('seup-loading');
        // Implement export functionality
        setTimeout(() => {
            this.classList.remove('seup-loading');
            showMessage('Excel izvoz je pokrenut', 'success');
        }, 2000);
    });

    // Modal functionality
    let currentGradivoId = null;
    let currentDeleteId = null;
    let currentEditId = null;

    function openDetailsModal(gradivoId) {
        currentGradivoId = gradivoId;
        
        // Show modal
        const modal = document.getElementById('detailsModal');
        modal.classList.add('show');
        
        // Load details
        loadGradivoDetails(gradivoId);
    }

    function closeDetailsModal() {
        const modal = document.getElementById('detailsModal');
        modal.classList.remove('show');
        currentGradivoId = null;
    }

    function openDeleteModal(gradivoId, oznaka) {
        currentDeleteId = gradivoId;
        
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

    function openEditModal(gradivoId) {
        currentEditId = gradivoId;
        
        // Show modal
        const modal = document.getElementById('editModal');
        modal.classList.add('show');
        
        // Load data for editing
        loadGradivoForEdit(gradivoId);
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
        formData.append('action', 'delete_gradivo');
        formData.append('rowid', currentDeleteId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
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
                showMessage('Arhivsko gradivo je uspješno obrisano.', 'success');
            } else {
                const msg = data.error || 'Brisanje nije uspjelo.';
                showMessage(msg, 'error');
                console.error('Delete error:', data);
            }
        })
        .catch(err => {
            showMessage('Greška pri brisanju.', 'error');
            console.error('Delete fetch error:', err);
        })
        .finally(() => {
            confirmBtn.classList.remove('seup-loading');
        });
    }

    function loadGradivoDetails(gradivoId) {
        const content = document.getElementById('gradivoDetailsContent');
        content.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Učitavam detalje...</div>';
        
        const formData = new FormData();
        formData.append('action', 'get_gradivo_details');
        formData.append('rowid', gradivoId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderGradivoDetails(data.gradivo);
            } else {
                content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading details:', error);
            content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>Greška pri učitavanju detalja</div>';
        });
    }

    function loadGradivoForEdit(gradivoId) {
        const formData = new FormData();
        formData.append('action', 'get_gradivo_details');
        formData.append('rowid', gradivoId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.gradivo);
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

    function populateEditForm(gradivo) {
        document.getElementById('edit_ag_oznaka').value = gradivo.oznaka || '';
        document.getElementById('edit_ag_vrsta_gradiva').value = gradivo.vrsta_gradiva || '';
        document.getElementById('edit_ag_opisi_napomene').value = gradivo.opisi_napomene || '';
        document.getElementById('edit_rowid').value = gradivo.rowid;
    }

    function renderGradivoDetails(gradivo) {
        const content = document.getElementById('gradivoDetailsContent');
        
        let html = '<div class="seup-suradnik-details">';
        
        // Header with oznaka and basic info
        html += '<div class="seup-details-header">';
        html += '<div class="seup-details-avatar"><i class="fas fa-archive"></i></div>';
        html += '<div class="seup-details-basic">';
        html += '<h4>' + escapeHtml(gradivo.oznaka) + '</h4>';
        html += '<p class="seup-contact-person">' + escapeHtml(gradivo.vrsta_gradiva) + '</p>';
        html += '</div>';
        html += '</div>';
        
        // Details grid
        html += '<div class="seup-details-grid">';
        
        // ID
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-hashtag me-2"></i>ID</div>';
        html += '<div class="seup-detail-value">' + gradivo.rowid + '</div>';
        html += '</div>';
        
        // Datum kreiranja
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-calendar me-2"></i>Datum kreiranja</div>';
        html += '<div class="seup-detail-value">' + gradivo.datum_kreiranja + '</div>';
        html += '</div>';
        
        // Opisi/Napomene (wide)
        if (gradivo.opisi_napomene) {
            html += '<div class="seup-detail-item seup-detail-wide">';
            html += '<div class="seup-detail-label"><i class="fas fa-align-left me-2"></i>Opisi/Napomene</div>';
            html += '<div class="seup-detail-value">' + escapeHtml(gradivo.opisi_napomene) + '</div>';
            html += '</div>';
        }
        
        html += '</div>'; // seup-details-grid
        html += '</div>'; // seup-suradnik-details
        
        content.innerHTML = html;
    }

    // Modal event listeners
    document.getElementById('closeDetailsModal').addEventListener('click', closeDetailsModal);
    document.getElementById('closeDetailsBtn').addEventListener('click', closeDetailsModal);
    
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
        formData.append('action', 'update_gradivo');
        
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
        if (!text) return '';
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

/* Arhivska gradiva specific styles */
.seup-gradivo-info {
  display: flex;
  align-items: center;
  font-size: var(--text-sm);
  color: var(--secondary-700);
  font-weight: var(--font-medium);
}

.seup-opis-cell {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  cursor: help;
  font-size: var(--text-sm);
  color: var(--secondary-600);
}

.seup-date-info {
  display: flex;
  align-items: center;
  font-size: var(--text-sm);
  color: var(--secondary-700);
}

/* Responsive design */
@media (max-width: 768px) {
  .seup-opis-cell {
    max-width: 120px;
  }
}
</style>

<?php
llxFooter();
$db->close();
?>