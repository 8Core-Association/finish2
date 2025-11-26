<?php

require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
ob_end_clean();

$langs->load("main");
$langs->load("errors");

$predmet_id = GETPOST('predmet_id', 'int');

if (!$predmet_id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Nedostaje ID predmeta']);
    exit;
}

$sql_predmet = "SELECT
    p.ID_predmeta,
    p.klasa_br,
    p.sadrzaj,
    p.dosje_broj,
    p.godina,
    p.predmet_rbr,
    p.naziv_predmeta,
    u.name_ustanova,
    u.code_ustanova,
    k.opis_klasifikacijske_oznake,
    CONCAT(k.klasa_broj, '/', k.sadrzaj, '-', k.dosje_broj) as klasifikacijska_oznaka_full,
    io.naziv as interna_oznaka_naziv,
    io.rbr as interna_oznaka_rbr
FROM llx_a_predmet p
LEFT JOIN llx_a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
LEFT JOIN llx_a_klasifikacijska_oznaka k ON p.ID_klasifikacijske_oznake = k.ID_klasifikacijske_oznake
LEFT JOIN llx_a_interna_oznaka_korisnika io ON p.ID_interna_oznaka_korisnika = io.ID
WHERE p.ID_predmeta = " . ((int) $predmet_id);

$result_predmet = $db->query($sql_predmet);
if (!$result_predmet || $db->num_rows($result_predmet) == 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Predmet nije pronađen']);
    exit;
}

$predmet = $db->fetch_object($result_predmet);

$sql_akti = "SELECT
    a.ID_akta,
    a.urb_broj,
    a.datum_kreiranja,
    e.filename as akt_filename
FROM llx_a_akti a
LEFT JOIN llx_ecm_files e ON a.fk_ecm_file = e.rowid
WHERE a.ID_predmeta = " . ((int) $predmet_id) . "
ORDER BY a.urb_broj ASC";

$result_akti = $db->query($sql_akti);
$akti = [];
while ($result_akti && ($akt_obj = $db->fetch_object($result_akti))) {
    $id_akta = $akt_obj->ID_akta;

    $akti[$id_akta] = [
        'data' => $akt_obj,
        'prilozi' => [],
        'otpreme' => [],
        'zaprimanja' => []
    ];

    $sql_prilozi = "SELECT
        pr.ID_priloga,
        pr.prilog_rbr,
        e.filename as prilog_filename
    FROM llx_a_prilozi pr
    LEFT JOIN llx_ecm_files e ON pr.fk_ecm_file = e.rowid
    WHERE pr.ID_akta = " . ((int) $id_akta) . "
    ORDER BY pr.prilog_rbr ASC";

    $result_prilozi = $db->query($sql_prilozi);
    while ($result_prilozi && ($prilog_obj = $db->fetch_object($result_prilozi))) {
        $akti[$id_akta]['prilozi'][] = $prilog_obj;
    }

    $sql_otpreme = "SELECT
        o.ID_otpreme,
        o.primatelj_naziv,
        o.datum_otpreme,
        o.nacin_otpreme
    FROM llx_a_otprema o
    INNER JOIN llx_a_akti a ON o.fk_ecm_file = a.fk_ecm_file
    WHERE a.ID_akta = " . ((int) $id_akta) . "
    ORDER BY o.datum_otpreme ASC";

    $result_otpreme = $db->query($sql_otpreme);
    while ($result_otpreme && ($otprema_obj = $db->fetch_object($result_otpreme))) {
        $akti[$id_akta]['otpreme'][] = $otprema_obj;
    }

    $sql_zaprimanja = "SELECT
        z.ID_zaprimanja,
        z.posiljatelj_naziv,
        z.datum_zaprimanja,
        z.nacin_zaprimanja
    FROM llx_a_zaprimanja z
    INNER JOIN llx_a_akti a ON z.fk_ecm_file = a.fk_ecm_file
    WHERE a.ID_akta = " . ((int) $id_akta) . "
    ORDER BY z.datum_zaprimanja ASC";

    $result_zaprimanja = $db->query($sql_zaprimanja);
    while ($result_zaprimanja && ($zaprimanje_obj = $db->fetch_object($result_zaprimanja))) {
        $akti[$id_akta]['zaprimanja'][] = $zaprimanje_obj;
    }
}

$pdf = pdf_getInstance();
$pdf->SetFont(pdf_getPDFFont($langs), '', 11);
$pdf->Open();

$pdf->AddPage('P', 'A4');

$pdf->SetFont('', 'B', 18);
$pdf->Cell(0, 15, dol_trunc($predmet->name_ustanova, 60), 0, 1, 'C');

$pdf->Ln(5);

$pdf->SetFont('', 'B', 12);
$pdf->Cell(70, 7, 'Oznaka ustrojstvene jedinice:', 0, 0, 'L');
$pdf->SetFont('', '', 11);
$pdf->Cell(0, 7, $predmet->interna_oznaka_naziv . ' (' . $predmet->interna_oznaka_rbr . ')', 0, 1, 'L');

$pdf->SetFont('', 'B', 12);
$pdf->Cell(70, 7, 'Klasifikacijska oznaka:', 0, 0, 'L');
$pdf->SetFont('', '', 11);
$pdf->Cell(0, 7, $predmet->klasifikacijska_oznaka_full, 0, 1, 'L');

$pdf->Ln(3);

$pdf->SetFont('', 'B', 12);
$pdf->Cell(0, 7, 'PREDMET:', 0, 1, 'L');
$pdf->SetFont('', '', 11);
$pdf->MultiCell(0, 6, $predmet->naziv_predmeta, 0, 'L');

$pdf->Ln(10);

$pdf->SetFont('', 'I', 10);
$pdf->Cell(0, 7, '[Prostor za barkod / QR kod]', 1, 1, 'C');

$pdf->AddPage('P', 'A4');

$pdf->SetFont('', 'B', 14);
$pdf->Cell(0, 10, 'SADRŽAJ PREDMETA', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('', '', 10);

$page_line_count = 0;
$max_lines_per_page = 50;

foreach ($akti as $id_akta => $akt_data) {
    $akt = $akt_data['data'];
    $akt_broj = $predmet->code_ustanova . '-' .
                $predmet->interna_oznaka_rbr . '-' .
                $predmet->godina . '-' .
                $akt->urb_broj;

    if ($page_line_count > $max_lines_per_page) {
        $pdf->AddPage('P', 'A4');
        $page_line_count = 0;
    }

    $pdf->SetFont('', 'B', 10);
    $pdf->Cell(10, 6, '', 0, 0, 'L');
    $pdf->Cell(0, 6, 'Akt broj: ' . $akt_broj, 0, 1, 'L');
    $page_line_count++;

    if (!empty($akt_data['prilozi'])) {
        $pdf->SetFont('', '', 9);
        foreach ($akt_data['prilozi'] as $prilog) {
            $pdf->Cell(20, 5, '', 0, 0, 'L');
            $pdf->Cell(0, 5, 'Prilog rb. ' . $prilog->prilog_rbr . ': ' . $prilog->prilog_filename, 0, 1, 'L');
            $page_line_count++;
        }
    }

    if (!empty($akt_data['otpreme'])) {
        $pdf->SetFont('', 'I', 9);
        foreach ($akt_data['otpreme'] as $otprema) {
            $pdf->Cell(20, 5, '', 0, 0, 'L');
            $pdf->Cell(0, 5, 'Otprema: ' . $otprema->primatelj_naziv . ' (' . date('d.m.Y', strtotime($otprema->datum_otpreme)) . ')', 0, 1, 'L');
            $page_line_count++;
        }
    }

    if (!empty($akt_data['zaprimanja'])) {
        $pdf->SetFont('', 'I', 9);
        foreach ($akt_data['zaprimanja'] as $zaprimanje) {
            $pdf->Cell(20, 5, '', 0, 0, 'L');
            $pdf->Cell(0, 5, 'Zaprimanje: ' . $zaprimanje->posiljatelj_naziv . ' (' . date('d.m.Y H:i', strtotime($zaprimanje->datum_zaprimanja)) . ')', 0, 1, 'L');
            $page_line_count++;
        }
    }

    $pdf->Ln(2);
    $page_line_count++;
}

$pdf->AddPage('P', 'A4');

$subdir = 'temp';
$filename = 'omot_predmet_' . $predmet_id . '_' . dol_print_date(dol_now(), 'dayhourlog') . '.pdf';
$relpath = $subdir . '/' . $filename;
$fullpath = DOL_DATA_ROOT . '/ecm/' . $relpath;

dol_mkdir(dirname($fullpath));

$pdf->Output($fullpath, 'F');

require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
$ecmfile = new EcmFiles($db);
$ecmfile->filepath = $subdir;
$ecmfile->filename = $filename;
$ecmfile->label = 'Omot predmeta ' . $predmet_id;
$ecmfile->entity = $conf->entity;
$ecmfile->share = 'shared';
$result = $ecmfile->create($user);

if ($result < 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $ecmfile->error]);
    exit;
}

$download_url = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($relpath);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'file' => $download_url
]);
exit;
