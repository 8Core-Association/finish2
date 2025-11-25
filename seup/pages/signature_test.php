<?php

/**
 * Test stranica za testiranje detekcije digitalnih potpisa
 * (c) 2025 8Core Association
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
require_once __DIR__ . '/../class/digital_signature_detector.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Security check
if (!$user->admin) {
    accessforbidden();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = GETPOST('action', 'alpha');
    
    if ($action === 'test_signature_detection') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        // Test with a sample PDF file
        $testFile = DOL_DATA_ROOT . '/ecm/SEUP/test/E-potpis.pdf';
        
        if (!file_exists($testFile)) {
            echo json_encode([
                'success' => false,
                'error' => 'Test file not found. Please upload E-potpis.pdf to /ecm/SEUP/test/ folder.'
            ]);
            exit;
        }
        
        $result = Digital_Signature_Detector::detectPDFSignature($testFile);
        echo json_encode([
            'success' => true,
            'result' => $result
        ]);
        exit;
    }
    
    if ($action === 'scan_all_signatures') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $result = Digital_Signature_Detector::bulkScanSignatures($db, $conf, 50);
        echo json_encode($result);
        exit;
    }
}

$form = new Form($db);
llxHeader("", "Digital Signature Test", '', '', 0, 0, '', '', '', 'mod-seup page-signature-test');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Main hero section
print '<main class="seup-settings-hero">';

// Floating background elements
print '<div class="seup-floating-elements">';
for ($i = 1; $i <= 5; $i++) {
    print '<div class="seup-floating-element"></div>';
}
print '</div>';

print '<div class="seup-settings-content">';

// Header section
print '<div class="seup-settings-header">';
print '<h1 class="seup-settings-title">Test Detekcije Digitalnih Potpisa</h1>';
print '<p class="seup-settings-subtitle">Testirajte funkcionalnost detekcije i validacije digitalnih potpisa u PDF dokumentima</p>';
print '</div>';

// Test section
print '<div class="seup-signature-test-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-certificate"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Testiranje Detekcije Potpisa</h3>';
print '<p class="seup-card-description">Testirajte detekciju digitalnih potpisa na vašem E-potpis.pdf dokumentu</p>';
print '</div>';
print '</div>';

print '<div class="seup-form">';

// Test buttons
print '<div class="seup-form-actions">';
print '<button type="button" class="seup-btn seup-btn-primary" id="testDetectionBtn">';
print '<i class="fas fa-search me-2"></i>Test Detekcije';
print '</button>';
print '<button type="button" class="seup-btn seup-btn-secondary" id="scanAllBtn">';
print '<i class="fas fa-certificate me-2"></i>Skeniraj Sve PDF-ove';
print '</button>';
print '</div>';

// Results area
print '<div id="testResults" class="seup-test-results" style="margin-top: var(--space-6);"></div>';

print '</div>'; // seup-form
print '</div>'; // seup-settings-card

// Statistics section
print '<div class="seup-settings-card animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-chart-bar"></i></div>';
print '<h3 class="seup-card-title">Statistike Potpisa</h3>';
print '<p class="seup-card-description">Pregled digitalnih potpisa u sustavu</p>';
print '</div>';

// Get current statistics
$stats = Digital_Signature_Detector::getSignatureStatistics($db, $conf);

print '<div class="seup-signature-stats">';
if ($stats) {
    print '<div class="seup-stats-grid">';
    
    print '<div class="seup-stat-card">';
    print '<div class="seup-stat-icon"><i class="fas fa-file-pdf"></i></div>';
    print '<div class="seup-stat-number">' . $stats['total_pdfs'] . '</div>';
    print '<div class="seup-stat-label">Ukupno PDF-ova</div>';
    print '</div>';
    
    print '<div class="seup-stat-card">';
    print '<div class="seup-stat-icon"><i class="fas fa-certificate"></i></div>';
    print '<div class="seup-stat-number">' . $stats['signed_pdfs'] . '</div>';
    print '<div class="seup-stat-label">Potpisanih</div>';
    print '</div>';
    
    print '<div class="seup-stat-card">';
    print '<div class="seup-stat-icon"><i class="fas fa-shield-alt"></i></div>';
    print '<div class="seup-stat-number">' . $stats['fina_signatures'] . '</div>';
    print '<div class="seup-stat-label">FINA Potpisi</div>';
    print '</div>';
    
    print '<div class="seup-stat-card">';
    print '<div class="seup-stat-icon"><i class="fas fa-check-circle"></i></div>';
    print '<div class="seup-stat-number">' . $stats['valid_signatures'] . '</div>';
    print '<div class="seup-stat-label">Valjani</div>';
    print '</div>';
    
    print '</div>'; // seup-stats-grid
} else {
    print '<div class="seup-alert seup-alert-info">';
    print '<i class="fas fa-info-circle me-2"></i>';
    print 'Statistike potpisa nisu dostupne. Pokrenite skeniranje za ažuriranje podataka.';
    print '</div>';
}
print '</div>'; // seup-signature-stats

print '</div>'; // seup-settings-card
print '</div>'; // seup-signature-test-container

print '</div>'; // seup-settings-content
print '</main>';

?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const testDetectionBtn = document.getElementById("testDetectionBtn");
    const scanAllBtn = document.getElementById("scanAllBtn");
    const testResults = document.getElementById("testResults");

    if (testDetectionBtn) {
        testDetectionBtn.addEventListener("click", function() {
            this.classList.add('seup-loading');
            testResults.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Testiram detekciju potpisa...</div>';
            
            const formData = new FormData();
            formData.append("action", "test_signature_detection");
            
            fetch("", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const result = data.result;
                    let html = '<div class="seup-test-result-card">';
                    html += '<h4><i class="fas fa-file-pdf me-2"></i>Rezultat Detekcije</h4>';
                    
                    if (result.has_signature) {
                        html += '<div class="seup-alert seup-alert-success">';
                        html += '<i class="fas fa-certificate me-2"></i>';
                        html += '<strong>Digitalni potpis detektiran!</strong>';
                        html += '</div>';
                        
                        if (result.signature_info) {
                            html += '<div class="seup-signature-details-card">';
                            html += '<h5>Detalji potpisa:</h5>';
                            html += '<ul>';
                            
                            Object.keys(result.signature_info).forEach(key => {
                                const value = result.signature_info[key];
                                html += `<li><strong>${key}:</strong> ${value}</li>`;
                            });
                            
                            html += '</ul>';
                            html += '</div>';
                        }
                    } else {
                        html += '<div class="seup-alert seup-alert-error">';
                        html += '<i class="fas fa-times-circle me-2"></i>';
                        html += 'Digitalni potpis nije detektiran';
                        if (result.error) {
                            html += ': ' + result.error;
                        }
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    testResults.innerHTML = html;
                } else {
                    testResults.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>' + data.error + '</div>';
                }
            })
            .catch(error => {
                testResults.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>Greška pri testiranju: ' + error.message + '</div>';
            })
            .finally(() => {
                this.classList.remove('seup-loading');
            });
        });
    }

    if (scanAllBtn) {
        scanAllBtn.addEventListener("click", function() {
            this.classList.add('seup-loading');
            testResults.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Skeniram sve PDF dokumente...</div>';
            
            const formData = new FormData();
            formData.append("action", "scan_all_signatures");
            
            fetch("", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '<div class="seup-test-result-card">';
                    html += '<h4><i class="fas fa-chart-bar me-2"></i>Rezultat Skeniranja</h4>';
                    html += '<div class="seup-alert seup-alert-success">';
                    html += '<i class="fas fa-check-circle me-2"></i>';
                    html += data.message;
                    html += '</div>';
                    
                    html += '<div class="seup-scan-stats">';
                    html += `<div class="seup-stat-item">Skenirano datoteka: <strong>${data.scanned_files}</strong></div>`;
                    html += `<div class="seup-stat-item">Pronađeno potpisa: <strong>${data.signatures_found}</strong></div>`;
                    
                    if (data.errors && data.errors.length > 0) {
                        html += '<div class="seup-scan-errors">';
                        html += '<h5>Greške:</h5>';
                        html += '<ul>';
                        data.errors.forEach(error => {
                            html += `<li>${error}</li>`;
                        });
                        html += '</ul>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    html += '</div>';
                    testResults.innerHTML = html;
                    
                    // Refresh page after 3 seconds to show updated stats
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    testResults.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>' + data.error + '</div>';
                }
            })
            .catch(error => {
                testResults.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>Greška pri skeniranju: ' + error.message + '</div>';
            })
            .finally(() => {
                this.classList.remove('seup-loading');
            });
        });
    }
});
</script>

<style>
/* Test page specific styles */
.seup-signature-test-container {
  max-width: 1200px;
  margin: 0 auto;
}

.seup-test-results {
  min-height: 100px;
}

.seup-test-result-card {
  background: white;
  border: 1px solid var(--neutral-200);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  margin-top: var(--space-4);
}

.seup-test-result-card h4 {
  margin: 0 0 var(--space-4) 0;
  color: var(--secondary-900);
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
}

.seup-signature-details-card {
  background: var(--primary-50);
  border: 1px solid var(--primary-200);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
  margin-top: var(--space-3);
}

.seup-signature-details-card h5 {
  margin: 0 0 var(--space-3) 0;
  color: var(--primary-800);
  font-size: var(--text-base);
  font-weight: var(--font-semibold);
}

.seup-signature-details-card ul {
  margin: 0;
  padding-left: var(--space-4);
}

.seup-signature-details-card li {
  margin-bottom: var(--space-2);
  color: var(--primary-700);
}

.seup-loading-message {
  text-align: center;
  padding: var(--space-6);
  color: var(--primary-600);
  font-weight: var(--font-medium);
}

.seup-scan-stats {
  margin-top: var(--space-4);
}

.seup-stat-item {
  padding: var(--space-2) 0;
  border-bottom: 1px solid var(--neutral-100);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.seup-stat-item:last-child {
  border-bottom: none;
}

.seup-scan-errors {
  margin-top: var(--space-4);
  background: var(--error-50);
  border: 1px solid var(--error-200);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
}

.seup-scan-errors h5 {
  margin: 0 0 var(--space-2) 0;
  color: var(--error-800);
}

.seup-scan-errors ul {
  margin: 0;
  padding-left: var(--space-4);
}

.seup-scan-errors li {
  color: var(--error-700);
  margin-bottom: var(--space-1);
}

.seup-signature-stats {
  padding: var(--space-6);
}

.seup-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--space-4);
}

.seup-stat-card {
  background: white;
  padding: var(--space-4);
  border-radius: var(--radius-lg);
  text-align: center;
  border: 1px solid var(--neutral-200);
  transition: all var(--transition-normal);
}

.seup-stat-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-200);
}

.seup-stat-icon {
  font-size: 2rem;
  color: var(--primary-500);
  margin-bottom: var(--space-2);
}

.seup-stat-number {
  font-size: var(--text-2xl);
  font-weight: var(--font-bold);
  color: var(--secondary-900);
  margin-bottom: var(--space-1);
}

.seup-stat-label {
  font-size: var(--text-sm);
  color: var(--secondary-600);
  font-weight: var(--font-medium);
}

/* Alert styles */
.seup-alert {
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-4);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.seup-alert-success {
  background: var(--success-50);
  color: var(--success-800);
  border: 1px solid var(--success-200);
}

.seup-alert-error {
  background: var(--error-50);
  color: var(--error-800);
  border: 1px solid var(--error-200);
}

.seup-alert-info {
  background: var(--primary-50);
  color: var(--primary-800);
  border: 1px solid var(--primary-200);
}
</style>

<?php
llxFooter();
$db->close();
?>