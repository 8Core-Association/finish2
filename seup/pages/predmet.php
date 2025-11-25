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
 *	\file       seup/predmet.php
 *	\ingroup    seup
 *	\brief      Individual predmet view with documents and details
 */

define('NOCSRFCHECK', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $ajax_action = $_POST['action'];
    if (in_array($ajax_action, ['upload_akt', 'upload_prilog', 'delete_document', 'generate_omot', 'preview_omot'])) {
        define('AJAX_REQUEST', 1);
        define('NOTOKENRENEWAL', 1);
    }
}

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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

require_once __DIR__ . '/../class/predmet_action_handler.class.php';
require_once __DIR__ . '/../class/predmet_data_loader.class.php';
require_once __DIR__ . '/../class/predmet_view.class.php';
require_once __DIR__ . '/../class/request_handler.class.php';

$langs->loadLangs(array("seup@seup"));

$caseId = GETPOST('id', 'int');
if (!$caseId) {
    header('Location: predmeti.php');
    exit;
}

$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $socid = $user->socid;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    define('NOTOKENRENEWAL', 1);

    $action = isset($_POST['action']) ? $_POST['action'] : GETPOST('action', 'alpha');

    switch ($action) {
        case 'delete_document':
            Predmet_Action_Handler::handleDeleteDocument($db, $conf);
            break;

        case 'upload_document':
            Request_Handler::handleUploadDocument($db, '', $langs, $conf, $user);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $caseId);
            exit;

        case 'upload_akt':
            Predmet_Action_Handler::handleUploadAkt($db, $conf, $user, $caseId);
            break;

        case 'upload_prilog':
            Predmet_Action_Handler::handleUploadPrilog($db, $conf, $user, $caseId);
            break;

        case 'generate_omot':
            Predmet_Action_Handler::handleGenerateOmot($db, $conf, $user, $langs, $caseId);
            break;

        case 'preview_omot':
            Predmet_Action_Handler::handlePreviewOmot($db, $conf, $user, $langs, $caseId);
            break;

        case 'get_nedodjeljeni':
            Predmet_Action_Handler::handleGetNedodjeljeni($db, $conf);
            break;

        case 'bulk_assign_documents':
            Predmet_Action_Handler::handleBulkAssignDocuments($db, $conf, $user);
            break;

        case 'registriraj_otpremu':
            Predmet_Action_Handler::handleRegistrirajOtpremu($db, $conf, $user);
            break;

        case 'registriraj_zaprimanje':
            Predmet_Action_Handler::handleRegistrirajZaprimanje($db, $conf, $user, $caseId);
            break;

        case 'search_posiljatelji':
            Predmet_Action_Handler::handleSearchPosiljatelji($db);
            break;

        case 'bulk_download_zip':
            Predmet_Action_Handler::handleBulkDownloadZip($db, $conf, $user);
            break;

        case 'bulk_otprema':
            Predmet_Action_Handler::handleBulkOtprema($db, $conf, $user);
            break;

        case 'get_zaprimanje_details':
            require_once __DIR__ . '/../class/zaprimanje_helper.class.php';
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

        case 'get_otprema_details':
            require_once __DIR__ . '/../class/otprema_helper.class.php';
            header('Content-Type: application/json');
            ob_end_clean();

            $ecm_file_id = GETPOST('ecm_file_id', 'int');

            if (!$ecm_file_id) {
                echo json_encode(['success' => false, 'error' => 'Missing ECM file ID']);
                exit;
            }

            $otprema_records = Otprema_Helper::getOtpremeByEcmFileId($db, $ecm_file_id);

            if ($otprema_records && count($otprema_records) > 0) {
                echo json_encode([
                    'success' => true,
                    'data' => $otprema_records
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No otprema records found']);
            }
            exit;
    }
}

$predmet = Predmet_Data_Loader::loadPredmetDetails($db, $caseId);

if (!$predmet) {
    header('Location: predmeti.php');
    exit;
}

$documentTableHTML = Predmet_Data_Loader::loadDocuments($db, $conf, $langs, $caseId);
$availableAkti = Predmet_Data_Loader::loadAvailableAkti($db, $caseId);
$doc_count = Predmet_Data_Loader::countDocuments($db, $conf, $caseId);

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", "Predmet: " . $predmet->klasa_format, '', '', 0, 0, '', '', '', 'mod-seup page-predmet');

Predmet_View::printHeader($predmet);

print '<div class="seup-predmet-container">';

Predmet_View::printCaseDetails($predmet);
Predmet_View::printTabs();

print '<div class="seup-tab-content">';
Predmet_View::printPriloziTab($documentTableHTML);
Predmet_View::printZaprimanjaTab($db, $caseId);
Predmet_View::printOtpremaTab($db, $caseId);
Predmet_View::printPrepregledTab();
Predmet_View::printStatistikeTab($predmet, $doc_count);
print '</div>';

print '</div>';

Predmet_View::printModals($caseId, $availableAkti);

llxFooter();

Predmet_View::printScripts();

$db->close();
?>
