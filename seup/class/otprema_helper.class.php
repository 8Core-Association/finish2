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

class Otprema_Helper
{
    public static function registrirajOtpremu(
        $db,
        $fk_ecm_file,
        $tip_dokumenta,
        $ID_predmeta,
        $primatelj_naziv,
        $datum_otpreme,
        $nacin_otpreme,
        $fk_user_creat,
        $primatelj_adresa = null,
        $primatelj_email = null,
        $primatelj_telefon = null,
        $naziv_predmeta = null,
        $klasifikacijska_oznaka = null,
        $fk_potvrda_ecm_file = null,
        $napomena = null
    ) {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_otprema (
                    fk_ecm_file,
                    tip_dokumenta,
                    ID_predmeta,
                    primatelj_naziv,
                    primatelj_adresa,
                    primatelj_email,
                    primatelj_telefon,
                    datum_otpreme,
                    nacin_otpreme,
                    naziv_predmeta,
                    klasifikacijska_oznaka,
                    fk_potvrda_ecm_file,
                    napomena,
                    fk_user_creat
                ) VALUES (
                    " . (int)$fk_ecm_file . ",
                    '" . $db->escape($tip_dokumenta) . "',
                    " . (int)$ID_predmeta . ",
                    '" . $db->escape($primatelj_naziv) . "',
                    " . ($primatelj_adresa ? "'" . $db->escape($primatelj_adresa) . "'" : "NULL") . ",
                    " . ($primatelj_email ? "'" . $db->escape($primatelj_email) . "'" : "NULL") . ",
                    " . ($primatelj_telefon ? "'" . $db->escape($primatelj_telefon) . "'" : "NULL") . ",
                    '" . $db->escape($datum_otpreme) . "',
                    '" . $db->escape($nacin_otpreme) . "',
                    " . ($naziv_predmeta ? "'" . $db->escape($naziv_predmeta) . "'" : "NULL") . ",
                    " . ($klasifikacijska_oznaka ? "'" . $db->escape($klasifikacijska_oznaka) . "'" : "NULL") . ",
                    " . ($fk_potvrda_ecm_file ? (int)$fk_potvrda_ecm_file : "NULL") . ",
                    " . ($napomena ? "'" . $db->escape($napomena) . "'" : "NULL") . ",
                    " . (int)$fk_user_creat . "
                )";

        $resql = $db->query($sql);

        if ($resql) {
            return $db->last_insert_id(MAIN_DB_PREFIX . "a_otprema");
        }

        return false;
    }

    public static function getOtpremePoDokumentu($db, $fk_ecm_file)
    {
        $otpreme = [];

        $sql = "SELECT
                    o.ID_otpreme,
                    o.fk_ecm_file,
                    o.tip_dokumenta,
                    o.ID_predmeta,
                    o.primatelj_naziv,
                    o.primatelj_adresa,
                    o.primatelj_email,
                    o.primatelj_telefon,
                    o.datum_otpreme,
                    o.nacin_otpreme,
                    o.naziv_predmeta,
                    o.klasifikacijska_oznaka,
                    o.fk_potvrda_ecm_file,
                    o.napomena,
                    o.datum_kreiranja,
                    u.firstname,
                    u.lastname,
                    e.filename as potvrda_filename
                FROM " . MAIN_DB_PREFIX . "a_otprema o
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON o.fk_user_creat = u.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files e ON o.fk_potvrda_ecm_file = e.rowid
                WHERE o.fk_ecm_file = " . (int)$fk_ecm_file . "
                ORDER BY o.datum_otpreme DESC, o.datum_kreiranja DESC";

        $resql = $db->query($sql);

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $otpreme[] = $obj;
            }
        }

        return $otpreme;
    }

    public static function getOtpremeByEcmFileId($db, $fk_ecm_file)
    {
        return self::getOtpremePoDokumentu($db, $fk_ecm_file);
    }

    public static function getOtpremePoPredmetu($db, $ID_predmeta)
    {
        $otpreme = [];

        $sql = "SELECT
                    o.ID_otpreme,
                    o.fk_ecm_file,
                    o.tip_dokumenta,
                    o.ID_predmeta,
                    o.primatelj_naziv,
                    o.primatelj_adresa,
                    o.primatelj_email,
                    o.primatelj_telefon,
                    o.datum_otpreme,
                    o.nacin_otpreme,
                    o.naziv_predmeta,
                    o.klasifikacijska_oznaka,
                    o.fk_potvrda_ecm_file,
                    o.napomena,
                    o.datum_kreiranja,
                    u.firstname,
                    u.lastname,
                    e.filename as doc_filename,
                    potvrda.filename as potvrda_filename,
                    potvrda.filepath as potvrda_filepath
                FROM " . MAIN_DB_PREFIX . "a_otprema o
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON o.fk_user_creat = u.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files e ON o.fk_ecm_file = e.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files potvrda ON o.fk_potvrda_ecm_file = potvrda.rowid
                WHERE o.ID_predmeta = " . (int)$ID_predmeta . "
                ORDER BY o.datum_otpreme DESC, o.datum_kreiranja DESC";

        $resql = $db->query($sql);

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $otpreme[] = $obj;
            }
        }

        return $otpreme;
    }

    public static function uploadPotvrdaOtpreme($db, $conf, $file, $datum_otpreme)
    {
        require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

        $date = new DateTime($datum_otpreme);
        $year = $date->format('Y');
        $month = $date->format('m');

        $relative_path = 'SEUP/Otpreme/' . $year . '/' . $month;
        $full_path = DOL_DATA_ROOT . '/ecm/' . $relative_path;

        if (!is_dir($full_path)) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            dol_mkdir($full_path);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'potvrda_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $dest_file = $full_path . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
            return false;
        }

        $ecmfile = new EcmFiles($db);
        $ecmfile->filepath = $relative_path;
        $ecmfile->filename = $filename;
        $ecmfile->label = 'Potvrda otpreme';
        $ecmfile->fullpath_orig = $file['name'];
        $ecmfile->gen_or_uploaded = 'uploaded';
        $ecmfile->description = 'Potvrda otpreme dokumenta';
        $ecmfile->entity = $conf->entity;

        $result = $ecmfile->create($GLOBALS['user']);

        if ($result > 0) {
            return $ecmfile->id;
        }

        return false;
    }

    public static function deleteOtprema($db, $ID_otpreme)
    {
        try {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_otprema
                    WHERE ID_otpreme = " . (int)$ID_otpreme;

            $resql = $db->query($sql);

            if (!$resql) {
                throw new Exception('Database error: ' . $db->lasterror());
            }

            return [
                'success' => true,
                'message' => 'Otprema uspješno obrisana'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function getOtpremaById($db, $ID_otpreme)
    {
        $sql = "SELECT
                    o.*,
                    u.firstname,
                    u.lastname,
                    e.filename as doc_filename,
                    potvrda.filename as potvrda_filename,
                    potvrda.filepath as potvrda_filepath
                FROM " . MAIN_DB_PREFIX . "a_otprema o
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON o.fk_user_creat = u.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files e ON o.fk_ecm_file = e.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files potvrda ON o.fk_potvrda_ecm_file = potvrda.rowid
                WHERE o.ID_otpreme = " . (int)$ID_otpreme;

        $resql = $db->query($sql);

        if ($resql && $db->num_rows($resql) > 0) {
            return $db->fetch_object($resql);
        }

        return null;
    }

    public static function updateOtprema(
        $db,
        $ID_otpreme,
        $datum_otpreme,
        $nacin_otpreme,
        $primatelj_naziv,
        $primatelj_adresa = null,
        $primatelj_email = null,
        $primatelj_telefon = null,
        $napomena = null,
        $fk_potvrda_ecm_file = null
    ) {
        try {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "a_otprema SET
                        datum_otpreme = '" . $db->escape($datum_otpreme) . "',
                        nacin_otpreme = '" . $db->escape($nacin_otpreme) . "',
                        primatelj_naziv = '" . $db->escape($primatelj_naziv) . "',
                        primatelj_adresa = " . ($primatelj_adresa ? "'" . $db->escape($primatelj_adresa) . "'" : "NULL") . ",
                        primatelj_email = " . ($primatelj_email ? "'" . $db->escape($primatelj_email) . "'" : "NULL") . ",
                        primatelj_telefon = " . ($primatelj_telefon ? "'" . $db->escape($primatelj_telefon) . "'" : "NULL") . ",
                        napomena = " . ($napomena ? "'" . $db->escape($napomena) . "'" : "NULL");

            if ($fk_potvrda_ecm_file !== null) {
                $sql .= ", fk_potvrda_ecm_file = " . (int)$fk_potvrda_ecm_file;
            }

            $sql .= " WHERE ID_otpreme = " . (int)$ID_otpreme;

            $resql = $db->query($sql);

            if (!$resql) {
                throw new Exception('Database error: ' . $db->lasterror());
            }

            return [
                'success' => true,
                'message' => 'Otprema uspješno ažurirana'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function getBrojOtpremaPoDokumentu($db, $fk_ecm_file)
    {
        $sql = "SELECT COUNT(*) as broj
                FROM " . MAIN_DB_PREFIX . "a_otprema
                WHERE fk_ecm_file = " . (int)$fk_ecm_file;

        $resql = $db->query($sql);

        if ($resql) {
            $obj = $db->fetch_object($resql);
            return (int)$obj->broj;
        }

        return 0;
    }

    public static function exportExcelSingle($db, $otprema_id)
    {
        $sql = "SELECT
                    o.ID_otpreme,
                    o.datum_otpreme,
                    o.nacin_otpreme,
                    o.primatelj_naziv,
                    o.primatelj_adresa,
                    o.primatelj_email,
                    o.primatelj_telefon,
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
                    DATE_FORMAT(o.datum_otpreme, '%d.%m.%Y') as datum_format
                FROM " . MAIN_DB_PREFIX . "a_otprema o
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files ef ON o.fk_ecm_file = ef.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet p ON o.ID_predmeta = p.ID_predmeta
                WHERE o.ID_otpreme = " . (int)$otprema_id;

        $resql = $db->query($sql);
        if (!$resql || !($otprema = $db->fetch_object($resql))) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Otprema not found']);
            exit;
        }

        self::generateExcelOutput([$otprema], 'otprema_' . $otprema_id . '_' . date('Y-m-d'));
    }

    public static function exportExcelFiltered($db, $filters)
    {
        $sql = "SELECT
                    o.ID_otpreme,
                    o.datum_otpreme,
                    o.nacin_otpreme,
                    o.primatelj_naziv,
                    o.primatelj_adresa,
                    o.primatelj_email,
                    o.primatelj_telefon,
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
                    DATE_FORMAT(o.datum_otpreme, '%d.%m.%Y') as datum_format
                FROM " . MAIN_DB_PREFIX . "a_otprema o
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files ef ON o.fk_ecm_file = ef.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet p ON o.ID_predmeta = p.ID_predmeta
                WHERE 1=1";

        if (!empty($filters['klasa'])) {
            $sql .= " AND o.klasifikacijska_oznaka = '" . $db->escape($filters['klasa']) . "'";
        }
        if (!empty($filters['godina'])) {
            $sql .= " AND YEAR(o.datum_otpreme) = " . (int)$filters['godina'];
        }
        if (!empty($filters['mjesec'])) {
            $sql .= " AND MONTH(o.datum_otpreme) = " . (int)$filters['mjesec'];
        }
        if (!empty($filters['nacin'])) {
            $sql .= " AND o.nacin_otpreme = '" . $db->escape($filters['nacin']) . "'";
        }
        if (!empty($filters['search'])) {
            $search = $db->escape($filters['search']);
            $sql .= " AND (o.primatelj_naziv LIKE '%" . $search . "%'
                      OR o.naziv_predmeta LIKE '%" . $search . "%'
                      OR o.klasifikacijska_oznaka LIKE '%" . $search . "%'
                      OR ef.filename LIKE '%" . $search . "%')";
        }

        $sql .= " ORDER BY o.datum_otpreme DESC";

        $resql = $db->query($sql);
        $otpreme = [];
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $otpreme[] = $obj;
            }
        }

        self::generateExcelOutput($otpreme, 'otpreme_filtered_' . date('Y-m-d'));
    }

    private static function generateExcelOutput($data, $filename)
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
        echo '<xml>';
        echo '<x:ExcelWorkbook>';
        echo '<x:ExcelWorksheets>';
        echo '<x:ExcelWorksheet>';
        echo '<x:Name>Otpreme</x:Name>';
        echo '<x:WorksheetOptions>';
        echo '<x:DisplayGridlines/>';
        echo '</x:WorksheetOptions>';
        echo '</x:ExcelWorksheet>';
        echo '</x:ExcelWorksheets>';
        echo '</x:ExcelWorkbook>';
        echo '</xml>';
        echo '</head>';
        echo '<body>';
        echo '<table border="1">';

        echo '<thead>';
        echo '<tr style="background-color: #4a5568; color: white; font-weight: bold;">';
        echo '<th>ID</th>';
        echo '<th>Datum Otpreme</th>';
        echo '<th>Klasa Predmeta</th>';
        echo '<th>Naziv Predmeta</th>';
        echo '<th>Primatelj</th>';
        echo '<th>Adresa</th>';
        echo '<th>Email</th>';
        echo '<th>Telefon</th>';
        echo '<th>Dokument</th>';
        echo '<th>Način Otpreme</th>';
        echo '<th>Napomena</th>';
        echo '</tr>';
        echo '</thead>';

        echo '<tbody>';
        foreach ($data as $otprema) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($otprema->ID_otpreme) . '</td>';
            echo '<td>' . htmlspecialchars($otprema->datum_format) . '</td>';
            echo '<td>' . htmlspecialchars($otprema->klasa_format ?: '—') . '</td>';
            echo '<td>' . htmlspecialchars($otprema->naziv_predmeta ?: '—') . '</td>';
            echo '<td>' . htmlspecialchars($otprema->primatelj_naziv) . '</td>';
            echo '<td>' . htmlspecialchars($otprema->primatelj_adresa ?: '—') . '</td>';
            echo '<td>' . htmlspecialchars($otprema->primatelj_email ?: '—') . '</td>';
            echo '<td>' . htmlspecialchars($otprema->primatelj_telefon ?: '—') . '</td>';
            echo '<td>' . htmlspecialchars($otprema->dokument_naziv ?: '—') . '</td>';

            $nacinLabels = [
                'posta' => 'Pošta',
                'email' => 'E-mail',
                'rucno' => 'Na ruke',
                'ostalo' => 'Ostalo'
            ];
            echo '<td>' . htmlspecialchars($nacinLabels[$otprema->nacin_otpreme] ?? $otprema->nacin_otpreme) . '</td>';
            echo '<td>' . htmlspecialchars($otprema->napomena ?: '—') . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';

        echo '</table>';
        echo '</body>';
        echo '</html>';

        exit;
    }
}
