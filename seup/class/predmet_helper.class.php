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
 *
 * VERSION: 2025-11-22-15-00-OTPREMA-BUTTON-FIX
 */

class Predmet_helper
{
    /**
     * Create SEUP database tables if they don't exist
     */
    public static function createSeupDatabaseTables($db)
    {
        // First ensure missing columns are added to existing tables
        self::ensurePredmetColumns($db);
        
        $sql_tables = [
            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_oznaka_ustanove (
                ID_ustanove int(11) NOT NULL AUTO_INCREMENT,
                singleton tinyint(1) DEFAULT 1,
                code_ustanova varchar(20) NOT NULL,
                name_ustanova varchar(255) NOT NULL,
                PRIMARY KEY (ID_ustanove),
                UNIQUE KEY singleton (singleton)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka (
                ID_klasifikacijske_oznake int(11) NOT NULL AUTO_INCREMENT,
                ID_ustanove int(11) NOT NULL,
                klasa_broj varchar(10) NOT NULL,
                sadrzaj varchar(10) NOT NULL,
                dosje_broj varchar(10) NOT NULL,
                vrijeme_cuvanja int(11) NOT NULL DEFAULT 0,
                opis_klasifikacijske_oznake text,
                PRIMARY KEY (ID_klasifikacijske_oznake),
                UNIQUE KEY unique_combination (klasa_broj, sadrzaj, dosje_broj),
                KEY fk_ustanova (ID_ustanove)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika (
                ID int(11) NOT NULL AUTO_INCREMENT,
                ID_ustanove int(11) NOT NULL,
                ime_prezime varchar(255) NOT NULL,
                rbr int(11) NOT NULL,
                naziv varchar(255) NOT NULL,
                PRIMARY KEY (ID),
                UNIQUE KEY unique_rbr (rbr),
                KEY fk_ustanova_korisnik (ID_ustanove)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_predmet (
                ID_predmeta int(11) NOT NULL AUTO_INCREMENT,
                klasa_br varchar(10) NOT NULL,
                sadrzaj varchar(10) NOT NULL,
                dosje_broj varchar(10) NOT NULL,
                godina varchar(2) NOT NULL,
                predmet_rbr int(11) NOT NULL,
                naziv_predmeta text NOT NULL,
                ID_ustanove int(11) NOT NULL,
                ID_interna_oznaka_korisnika int(11) NOT NULL,
                ID_klasifikacijske_oznake int(11) NOT NULL,
                vrijeme_cuvanja int(11) NOT NULL,
                tstamp_created timestamp DEFAULT CURRENT_TIMESTAMP,
                naziv VARCHAR(255) DEFAULT NULL COMMENT 'Naziv pošiljatelja',
                zaprimljeno_datum datetime DEFAULT NULL COMMENT 'Datum zaprimanja predmeta',
                PRIMARY KEY (ID_predmeta),
                UNIQUE KEY unique_predmet (klasa_br, sadrzaj, dosje_broj, godina, predmet_rbr),
                KEY fk_ustanova_predmet (ID_ustanove),
                KEY fk_korisnik_predmet (ID_interna_oznaka_korisnika),
                KEY fk_klasifikacija_predmet (ID_klasifikacijske_oznake)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_tagovi (
                rowid int(11) NOT NULL AUTO_INCREMENT,
                tag varchar(100) NOT NULL,
                color varchar(20) DEFAULT 'blue',
                entity int(11) NOT NULL DEFAULT 1,
                date_creation datetime NOT NULL,
                fk_user_creat int(11) NOT NULL,
                PRIMARY KEY (rowid),
                UNIQUE KEY unique_tag_entity (tag, entity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_predmet_tagovi (
                rowid int(11) NOT NULL AUTO_INCREMENT,
                fk_predmet int(11) NOT NULL,
                fk_tag int(11) NOT NULL,
                PRIMARY KEY (rowid),
                UNIQUE KEY unique_predmet_tag (fk_predmet, fk_tag),
                KEY fk_predmet_idx (fk_predmet),
                KEY fk_tag_idx (fk_tag)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_predmet_stranka (
                rowid int(11) NOT NULL AUTO_INCREMENT,
                ID_predmeta int(11) NOT NULL,
                fk_soc int(11) NOT NULL,
                role varchar(50) DEFAULT 'creator',
                date_stranka_opened datetime DEFAULT NULL,
                PRIMARY KEY (rowid),
                KEY fk_predmet_stranka (ID_predmeta),
                KEY fk_soc_stranka (fk_soc)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_arhiva (
                ID_arhive int(11) NOT NULL AUTO_INCREMENT,
                ID_predmeta int(11) NOT NULL,
                klasa_predmeta varchar(50) NOT NULL,
                naziv_predmeta text NOT NULL,
                lokacija_arhive varchar(500) NOT NULL,
                broj_dokumenata int(11) DEFAULT 0,
                razlog_arhiviranja text,
                datum_arhiviranja timestamp DEFAULT CURRENT_TIMESTAMP,
                fk_user_arhivirao int(11) NOT NULL,
                status_arhive enum('active','deleted') DEFAULT 'active',
                PRIMARY KEY (ID_arhive),
                KEY fk_predmet_arhiva (ID_predmeta),
                KEY fk_user_arhiva (fk_user_arhivirao)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        ];

        foreach ($sql_tables as $sql) {
            $resql = $db->query($sql);
            if (!$resql) {
                dol_syslog("Error creating table: " . $db->lasterror(), LOG_ERR);
            }
        }
    }

    /**
     * Ensure a_predmet table has all required columns
     */
    public static function ensurePredmetColumns($db)
    {
        try {
            // Check if naziv column exists (for pošiljatelj name)
            $sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "a_predmet LIKE 'naziv'";
            $result = $db->query($sql);
            
            if ($db->num_rows($result) == 0) {
                dol_syslog("Adding naziv column to a_predmet table", LOG_INFO);
                
                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "a_predmet 
                        ADD COLUMN naziv VARCHAR(255) DEFAULT NULL COMMENT 'Naziv pošiljatelja'";
                
                $result = $db->query($sql);
                if ($result) {
                    dol_syslog("naziv column added successfully", LOG_INFO);
                } else {
                    dol_syslog("Failed to add naziv column: " . $db->lasterror(), LOG_ERR);
                }
            }
            
            // Check if zaprimljeno_datum column exists
            $sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "a_predmet LIKE 'zaprimljeno_datum'";
            $result = $db->query($sql);
            
            if ($db->num_rows($result) == 0) {
                dol_syslog("Adding zaprimljeno_datum column to a_predmet table", LOG_INFO);
                
                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "a_predmet 
                        ADD COLUMN zaprimljeno_datum datetime DEFAULT NULL COMMENT 'Datum zaprimanja predmeta'";
                
                $result = $db->query($sql);
                if ($result) {
                    dol_syslog("zaprimljeno_datum column added successfully", LOG_INFO);
                } else {
                    dol_syslog("Failed to add zaprimljeno_datum column: " . $db->lasterror(), LOG_ERR);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            dol_syslog("Error ensuring predmet columns: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }

    /**
     * Generate sanitized folder name from klasa and opis
     */
    public static function generateFolderName($klasa_br, $sadrzaj, $dosje_broj, $godina, $predmet_rbr, $naziv_predmeta)
    {
        // Create klasa format: 010-05_25-12_4 (replace / with _ for folder name)
        $klasa_format = $klasa_br . '-' . $sadrzaj . '_' . $godina . '-' . $dosje_broj . '_' . $predmet_rbr;
        
        // Sanitize naziv_predmeta for folder name
        $sanitized_naziv = self::sanitizeForFolder($naziv_predmeta);
        
        // Combine klasa and naziv with separator
        $folder_name = $klasa_format . '-' . $sanitized_naziv;
        
        // Ensure folder name is not too long (max 200 chars for safety)
        if (strlen($folder_name) > 200) {
            $sanitized_naziv = substr($sanitized_naziv, 0, 200 - strlen($klasa_format) - 1);
            $folder_name = $klasa_format . '-' . $sanitized_naziv;
        }
        
        return $folder_name;
    }

    /**
     * Sanitize string for use in folder names
     */
    public static function sanitizeForFolder($string)
    {
        // Remove or replace problematic characters
        $string = trim($string);
        
        // Replace Croatian characters
        $croatian_chars = [
            'č' => 'c', 'ć' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z',
            'Č' => 'C', 'Ć' => 'C', 'Đ' => 'D', 'Š' => 'S', 'Ž' => 'Z'
        ];
        $string = strtr($string, $croatian_chars);
        
        // Replace spaces and special characters with underscores
        $string = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $string);
        
        // Remove multiple consecutive underscores
        $string = preg_replace('/_+/', '_', $string);
        
        // Remove leading/trailing underscores
        $string = trim($string, '_');
        
        return $string;
    }

    /**
     * Get predmet folder path
     */
    public static function getPredmetFolderPath($predmet_id, $db)
    {
        // Fetch predmet details
        $sql = "SELECT 
                    p.klasa_br,
                    p.sadrzaj,
                    p.dosje_broj,
                    p.godina,
                    p.predmet_rbr,
                    p.naziv_predmeta
                FROM " . MAIN_DB_PREFIX . "a_predmet p
                WHERE p.ID_predmeta = " . (int)$predmet_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            $folder_name = self::generateFolderName(
                $obj->klasa_br,
                $obj->sadrzaj,
                $obj->dosje_broj,
                $obj->godina,
                $obj->predmet_rbr,
                $obj->naziv_predmeta
            );
            
            // Add year folder structure: SEUP/Predmeti/2025/010-05_25-12_6-Naziv/
            $full_year = '20' . $obj->godina;
            return 'SEUP/Predmeti/' . $full_year . '/' . $folder_name . '/';
        }
        
        // Fallback to old format if predmet not found
        return 'SEUP/predmet_' . $predmet_id . '/';
    }

    /**
     * Create predmet directory
     */
    public static function createPredmetDirectory($predmet_id, $db, $conf)
    {
        $relative_path = self::getPredmetFolderPath($predmet_id, $db);
        $full_path = DOL_DATA_ROOT . '/ecm/' . $relative_path;
        
        if (!is_dir($full_path)) {
            if (!dol_mkdir($full_path)) {
                dol_syslog("Failed to create directory: " . $full_path, LOG_ERR);
                return false;
            }
            dol_syslog("Created directory: " . $full_path, LOG_INFO);
        }
        
        return $relative_path;
    }

    /**
     * Fetch dropdown data for forms
     */
    public static function fetchDropdownData($db, $langs, &$klasaOptions, &$klasaMapJson, &$zaposlenikOptions)
    {
        // Fetch klasa options
        $sql = "SELECT DISTINCT klasa_broj FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ORDER BY klasa_broj ASC";
        $resql = $db->query($sql);
        $klasaOptions = '<option value="">Odaberite klasu</option>';
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $klasaOptions .= '<option value="' . $obj->klasa_broj . '">' . $obj->klasa_broj . '</option>';
            }
        }

        // Build klasa map for JavaScript
        $klasaMap = [];
        $sql = "SELECT klasa_broj, sadrzaj, dosje_broj FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ORDER BY klasa_broj, sadrzaj, dosje_broj";
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                if (!isset($klasaMap[$obj->klasa_broj])) {
                    $klasaMap[$obj->klasa_broj] = [];
                }
                if (!isset($klasaMap[$obj->klasa_broj][$obj->sadrzaj])) {
                    $klasaMap[$obj->klasa_broj][$obj->sadrzaj] = [];
                }
                $klasaMap[$obj->klasa_broj][$obj->sadrzaj][] = $obj->dosje_broj;
            }
        }
        $klasaMapJson = json_encode($klasaMap);

        // Fetch zaposlenik options
        $sql = "SELECT ID, ime_prezime FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika ORDER BY ime_prezime ASC";
        $resql = $db->query($sql);
        $zaposlenikOptions = '<option value="">Odaberite zaposlenika</option>';
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $zaposlenikOptions .= '<option value="' . $obj->ID . '">' . htmlspecialchars($obj->ime_prezime) . '</option>';
            }
        }
    }

    /**
     * Get next predmet sequential number
     */
    public static function getNextPredmetRbr($db, $klasa_br, $sadrzaj, $dosje_br, $god)
    {
        $sql = "SELECT MAX(predmet_rbr) as max_rbr 
                FROM " . MAIN_DB_PREFIX . "a_predmet 
                WHERE klasa_br = '" . $db->escape($klasa_br) . "'
                AND sadrzaj = '" . $db->escape($sadrzaj) . "'
                AND dosje_broj = '" . $db->escape($dosje_br) . "'
                AND godina = '" . $db->escape($god) . "'";

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return ($obj->max_rbr ? $obj->max_rbr + 1 : 1);
        }
        return 1;
    }

    /**
     * Check if predmet exists
     */
    public static function checkPredmetExists($db, $klasa_br, $sadrzaj, $dosje_br, $god)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM " . MAIN_DB_PREFIX . "a_predmet 
                WHERE klasa_br = '" . $db->escape($klasa_br) . "'
                AND sadrzaj = '" . $db->escape($sadrzaj) . "'
                AND dosje_broj = '" . $db->escape($dosje_br) . "'
                AND godina = '" . $db->escape($god) . "'";

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return $obj->count > 0;
        }
        return false;
    }

    /**
     * Get klasifikacijska oznaka details
     */
    public static function getKlasifikacijskaOznaka($db, $klasa_br, $sadrzaj, $dosje_br)
    {
        $sql = "SELECT ID_klasifikacijske_oznake, vrijeme_cuvanja, opis_klasifikacijske_oznake
                FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka 
                WHERE klasa_broj = '" . $db->escape($klasa_br) . "'
                AND sadrzaj = '" . $db->escape($sadrzaj) . "'
                AND dosje_broj = '" . $db->escape($dosje_br) . "'";

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return $obj;
        }
        return false;
    }

    /**
     * Get ustanova by zaposlenik
     */
    public static function getUstanovaByZaposlenik($db, $zaposlenik_id)
    {
        $sql = "SELECT u.ID_ustanove, u.code_ustanova, u.name_ustanova
                FROM " . MAIN_DB_PREFIX . "a_oznaka_ustanove u
                INNER JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON u.ID_ustanove = k.ID_ustanove
                WHERE k.ID = " . (int)$zaposlenik_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return $obj;
        }
        return false;
    }

    /**
     * Insert new predmet with new folder structure
     */
    public static function insertPredmet($db, $klasa_br, $sadrzaj, $dosje_br, $god, $rbr_predmeta, $naziv_predmeta, $id_ustanove, $id_zaposlenik, $id_klasifikacijske_oznake, $vrijeme_cuvanja, $posiljatelj_naziv = null, $zaprimljeno_datum = null)
    {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_predmet (
                    klasa_br, sadrzaj, dosje_broj, godina, predmet_rbr, naziv_predmeta,
                    ID_ustanove, ID_interna_oznaka_korisnika, ID_klasifikacijske_oznake,
                    vrijeme_cuvanja, tstamp_created, naziv, zaprimljeno_datum
                ) VALUES (
                    '" . $db->escape($klasa_br) . "',
                    '" . $db->escape($sadrzaj) . "',
                    '" . $db->escape($dosje_br) . "',
                    '" . $db->escape($god) . "',
                    " . (int)$rbr_predmeta . ",
                    '" . $db->escape($naziv_predmeta) . "',
                    " . (int)$id_ustanove . ",
                    " . (int)$id_zaposlenik . ",
                    " . (int)$id_klasifikacijske_oznake . ",
                    " . (int)$vrijeme_cuvanja . ",
                    NOW(),
                    " . ($posiljatelj_naziv ? "'" . $db->escape($posiljatelj_naziv) . "'" : "NULL") . ",
                    " . ($zaprimljeno_datum ? "'" . $db->escape($zaprimljeno_datum) . "'" : "NULL") . "
                )";

        return $db->query($sql);
    }

    /**
     * Fetch uploaded documents for a predmet
     */
    public static function fetchUploadedDocuments($db, $conf, &$documentTableHTML, $langs, $caseId)
    {
        // Ensure digital signature columns exist
        require_once __DIR__ . '/digital_signature_detector.class.php';
        Digital_Signature_Detector::ensureDigitalSignatureColumns($db);
        
        // Ensure prilozi table exists
        require_once __DIR__ . '/prilog_helper.class.php';
        Prilog_Helper::createPriloziTable($db);
        
        // Auto-scan ECM if enabled
        if (getDolGlobalString('SEUP_ECM_AUTO_SCAN', '0') === '1') {
            require_once __DIR__ . '/ecm_scanner.class.php';
            $scanResult = ECM_Scanner::scanPredmetFolder($db, $conf, $GLOBALS['user'], $caseId);
            if ($scanResult['success'] && $scanResult['files_added'] > 0) {
                dol_syslog("Auto-scan added " . $scanResult['files_added'] . " files to ECM", LOG_INFO);
            }
        }
        
        // Get predmet folder path
        $relative_path = self::getPredmetFolderPath($caseId, $db);
        
        // Get documents from ECM database
        $sql = "SELECT
                    ef.rowid,
                    ef.filename,
                    ef.filepath,
                    ef.date_c,
                    ef.digital_signature,
                    ef.signature_status,
                    ef.signer_name,
                    ef.signature_date,
                    ef.signature_info,
                    a.urb_broj,
                    a.ID_akta,
                    p.prilog_rbr,
                    p.ID_akta as prilog_akt_id,
                    CONCAT(u.firstname, ' ', u.lastname) as created_by,
                    z.ID_zaprimanja
                FROM " . MAIN_DB_PREFIX . "ecm_files ef
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON ef.fk_user_c = u.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_akti a ON ef.rowid = a.fk_ecm_file
                LEFT JOIN " . MAIN_DB_PREFIX . "a_prilozi p ON ef.rowid = p.fk_ecm_file
                LEFT JOIN " . MAIN_DB_PREFIX . "a_zaprimanje z ON ef.rowid = z.fk_ecm_file
                WHERE ef.filepath = '" . $db->escape(rtrim($relative_path, '/')) . "'
                AND ef.entity = " . $conf->entity . "
                ORDER BY 
                    CASE 
                        WHEN a.urb_broj IS NOT NULL THEN CAST(a.urb_broj AS UNSIGNED)
                        WHEN p.prilog_rbr IS NOT NULL THEN (
                            SELECT CAST(a2.urb_broj AS UNSIGNED) 
                            FROM " . MAIN_DB_PREFIX . "a_akti a2 
                            WHERE a2.ID_akta = p.ID_akta
                        )
                        ELSE 999
                    END ASC,
                    CASE 
                        WHEN a.urb_broj IS NOT NULL THEN 0
                        WHEN p.prilog_rbr IS NOT NULL THEN CAST(p.prilog_rbr AS UNSIGNED)
                        ELSE 0
                    END ASC,
                    ef.date_c ASC";

        $resql = $db->query($sql);
        $documents = [];
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $documents[] = $obj;
            }
        }

        if (count($documents) > 0) {
            $documentTableHTML = '<table class="seup-documents-table">';
            $documentTableHTML .= '<thead>';
            $documentTableHTML .= '<tr>';
            $documentTableHTML .= '<th style="width: 40px;"><input type="checkbox" id="selectAllDocs" title="Označi sve"></th>';
            $documentTableHTML .= '<th><i class="fas fa-hashtag me-2"></i>&nbsp;Urb.</th>';
            $documentTableHTML .= '<th style="width: 20%;"><i class="fas fa-file me-2"></i>&nbsp;Naziv datoteke</th>';
            $documentTableHTML .= '<th style="width: 60px; text-align: center;"><i class="fas fa-inbox me-2"></i>&nbsp;Zap</th>';
            $documentTableHTML .= '<th><i class="fas fa-calendar me-2"></i>&nbsp;Datum</th>';
            $documentTableHTML .= '<th><i class="fas fa-user me-2"></i>&nbsp;Kreirao</th>';
            $documentTableHTML .= '<th><i class="fas fa-certificate me-2"></i>&nbsp;Potpis</th>';
            $documentTableHTML .= '<th><i class="fas fa-cogs me-2"></i>&nbsp;Akcije</th>';
            $documentTableHTML .= '</tr>';
            $documentTableHTML .= '</thead>';
            $documentTableHTML .= '<tbody>';

            // Group documents by akt and organize hierarchically
            $currentAkt = null;
            $indentLevel = 0;

            foreach ($documents as $doc) {
                // Auto-scan PDF for signature if not scanned yet
                if (strtolower(pathinfo($doc->filename, PATHINFO_EXTENSION)) === 'pdf') {
                    if (!isset($doc->digital_signature) || $doc->digital_signature === null) {
                        $full_path = DOL_DATA_ROOT . '/ecm/' . rtrim($doc->filepath, '/') . '/' . $doc->filename;
                        if (file_exists($full_path)) {
                            $scanResult = Digital_Signature_Detector::autoScanOnUpload($db, $conf, $full_path, $doc->rowid);
                            if ($scanResult['success'] && $scanResult['has_signature']) {
                                // Refresh document data
                                $doc->digital_signature = 1;
                                $doc->signature_status = 'valid';
                                $doc->signer_name = $scanResult['signature_info']['signer_name'] ?? null;
                            }
                        }
                    }
                }
                
                // Determine if this is an akt or prilog and set row class
                $isAkt = !empty($doc->urb_broj);
                $isPrilog = !empty($doc->prilog_rbr);
                $rowClass = '';
                
                if ($isAkt) {
                    $currentAkt = $doc->urb_broj;
                    $rowClass = 'seup-akt-row';
                    $indentLevel = 0;
                } elseif ($isPrilog) {
                    $rowClass = 'seup-prilog-row';
                    $indentLevel = 1;
                } else {
                    $rowClass = 'seup-unassigned-row';
                    $indentLevel = 0;
                }
                
                $documentTableHTML .= '<tr class="' . $rowClass . '" data-doc-id="' . $doc->rowid . '">';

                // Checkbox column
                $documentTableHTML .= '<td>';
                $documentTableHTML .= '<input type="checkbox" class="doc-checkbox" value="' . $doc->rowid . '" ';
                $documentTableHTML .= 'data-filename="' . htmlspecialchars($doc->filename) . '" ';
                $documentTableHTML .= 'data-filepath="' . htmlspecialchars($doc->filepath) . '">';
                $documentTableHTML .= '</td>';
                
                // Urb broj column (now first)
                $documentTableHTML .= '<td>';
                if (!empty($doc->urb_broj)) {
                    $documentTableHTML .= '<span class="seup-urb-badge">' . $doc->urb_broj . '</span>';
                } elseif (!empty($doc->prilog_rbr)) {
                    // Get akt urb broj for this prilog
                    $akt_sql = "SELECT urb_broj FROM " . MAIN_DB_PREFIX . "a_akti WHERE ID_akta = " . (int)$doc->prilog_akt_id;
                    $akt_resql = $db->query($akt_sql);
                    $akt_urb = '00';
                    if ($akt_resql && $akt_obj = $db->fetch_object($akt_resql)) {
                        $akt_urb = $akt_obj->urb_broj;
                    }
                    $documentTableHTML .= '<span class="seup-prilog-badge">';
                    $documentTableHTML .= '<i class="fas fa-paperclip"></i>' . $akt_urb . '-' . $doc->prilog_rbr;
                    $documentTableHTML .= '</span>';
                } else {
                    $documentTableHTML .= '<span class="seup-urb-empty">—</span>';
                }
                $documentTableHTML .= '</td>';
                
                // Naziv datoteke column (now second, with indentation for prilozi)
                $documentTableHTML .= '<td>';
                $documentTableHTML .= '<div class="seup-document-name" style="margin-left: ' . ($indentLevel * 20) . 'px;">';
                if ($isPrilog) {
                    $documentTableHTML .= '<i class="fas fa-level-up-alt fa-rotate-90 me-2 text-muted"></i>';
                }
                $documentTableHTML .= '<div class="seup-file-icon ' . self::getFileIconClass($doc->filename) . '">';
                $documentTableHTML .= '<i class="' . self::getFileIcon($doc->filename) . '"></i>';
                $documentTableHTML .= '</div>';

                // Make PDF filename clickable for preview
                $isPDF = strtolower(pathinfo($doc->filename, PATHINFO_EXTENSION)) === 'pdf';
                $download_url = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($relative_path . $doc->filename);

                if ($isPDF) {
                    $documentTableHTML .= '<span class="seup-pdf-preview-trigger" data-pdf-url="' . htmlspecialchars($download_url) . '" data-filename="' . htmlspecialchars($doc->filename) . '" style="cursor: pointer; color: var(--primary-600);" title="Klikni za predpregled">';
                    $documentTableHTML .= htmlspecialchars($doc->filename);
                    $documentTableHTML .= '</span>';
                } else {
                    $documentTableHTML .= htmlspecialchars($doc->filename);
                }

                $documentTableHTML .= '</div>';
                $documentTableHTML .= '</td>';

                // Zaprimanje column
                $documentTableHTML .= '<td style="text-align: center;">';
                if (!empty($doc->ID_zaprimanja)) {
                    $documentTableHTML .= '<button class="seup-zap-indicator" data-zaprimanje-id="' . $doc->ID_zaprimanja . '" title="Prikaži podatke o zaprimanju">';
                    $documentTableHTML .= '<i class="fas fa-check-circle"></i>';
                    $documentTableHTML .= '</button>';
                } else {
                    $documentTableHTML .= '<span class="seup-zap-empty">—</span>';
                }
                $documentTableHTML .= '</td>';

                // Date formatting
                $date_formatted = '—';
                if (!empty($doc->date_c)) {
                    $date_formatted = dol_print_date($doc->date_c, '%d.%m.%y %H:%M');
                }
                $documentTableHTML .= '<td><div class="seup-document-date"><i class="fas fa-calendar me-1"></i>' . $date_formatted . '</div></td>';
                
                // Created by
                $created_by = $doc->created_by ?? 'N/A';
                $documentTableHTML .= '<td><div class="seup-document-user"><i class="fas fa-user me-1"></i>' . htmlspecialchars($created_by) . '</div></td>';
                
                // Digital signature status
                $documentTableHTML .= '<td>';
                if (!empty($doc->digital_signature)) {
                    $signatureBadge = Digital_Signature_Detector::getSignatureBadge(
                        true, 
                        $doc->signature_status ?? 'unknown',
                        $doc->signer_name ?? null
                    );
                    $documentTableHTML .= $signatureBadge;
                } else {
                    $documentTableHTML .= '<span class="seup-signature-none"><i class="fas fa-minus-circle"></i> Nije potpisan</span>';
                }
                $documentTableHTML .= '</td>';
                
                $documentTableHTML .= '<td>';
                $documentTableHTML .= '<div class="seup-document-actions">';
                
                // Download button
                $download_url = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($relative_path . $doc->filename);
                $documentTableHTML .= '<a href="' . $download_url . '" class="seup-document-btn seup-document-btn-download" target="_blank" title="Preuzmi">';
                $documentTableHTML .= '<i class="fas fa-download"></i>';
                $documentTableHTML .= '</a>';

                // Registriraj otpremu button
                $isAkt = !empty($doc->urb_broj) && empty($doc->prilog_rbr);
                $isPrilog = !empty($doc->prilog_rbr);
                $tip_dokumenta = $isAkt ? 'akt' : ($isPrilog ? 'prilog' : 'nedodijeljeni');

                $documentTableHTML .= '<button class="seup-document-btn seup-document-btn-otprema registriraj-otpremu-btn" ';
                $documentTableHTML .= 'data-ecm-file-id="' . $doc->rowid . '" ';
                $documentTableHTML .= 'data-tip-dokumenta="' . $tip_dokumenta . '" ';
                $documentTableHTML .= 'data-doc-name="' . htmlspecialchars($doc->filename) . '" ';
                $documentTableHTML .= 'title="Registriraj otpremu">';
                $documentTableHTML .= '<i class="fas fa-shipping-fast"></i>';
                $documentTableHTML .= '</button>';

                // Delete button
                $documentTableHTML .= '<button class="seup-document-btn seup-document-btn-delete delete-document-btn" ';
                $documentTableHTML .= 'data-filename="' . htmlspecialchars($doc->filename) . '" ';
                $documentTableHTML .= 'data-filepath="' . htmlspecialchars($doc->filepath) . '" ';
                $documentTableHTML .= 'title="Obriši dokument">';
                $documentTableHTML .= '<i class="fas fa-trash"></i>';
                $documentTableHTML .= '</button>';

                $documentTableHTML .= '</div>'; // seup-document-actions
                $documentTableHTML .= '</td>';
                $documentTableHTML .= '</tr>';
            }

            $documentTableHTML .= '</tbody>';
            $documentTableHTML .= '</table>';

            // Bulk actions toolbar
            $documentTableHTML .= '<div class="seup-bulk-actions" id="bulkActionsToolbar" style="display:none;">';
            $documentTableHTML .= '<div class="seup-bulk-actions-info">';
            $documentTableHTML .= '<span id="selectedCount">0</span> odabrano';
            $documentTableHTML .= '</div>';
            $documentTableHTML .= '<div class="seup-bulk-actions-controls">';
            $documentTableHTML .= '<select id="bulkActionSelect" class="seup-select">';
            $documentTableHTML .= '<option value="">-- Odaberi akciju --</option>';
            $documentTableHTML .= '<option value="otpremi">Otpremi označene</option>';
            $documentTableHTML .= '<option value="download">Preuzmi sve (ZIP)</option>';
            $documentTableHTML .= '<option value="delete">Obriši označene</option>';
            $documentTableHTML .= '</select>';
            $documentTableHTML .= '<button type="button" class="seup-btn seup-btn-primary" id="executeBulkAction">Izvrši</button>';
            $documentTableHTML .= '<button type="button" class="seup-btn seup-btn-secondary" id="cancelBulkAction">Otkaži</button>';
            $documentTableHTML .= '</div>';
            $documentTableHTML .= '</div>';
        } else {
            $documentTableHTML = '<div class="seup-no-documents">';
            $documentTableHTML .= '<i class="fas fa-file-alt seup-no-documents-icon"></i>';
            $documentTableHTML .= '<h4 class="seup-no-documents-title">Nema dokumenata</h4>';
            $documentTableHTML .= '<p class="seup-no-documents-description">Dodajte prvi dokument u ovaj predmet</p>';
            $documentTableHTML .= '<button type="button" class="seup-btn seup-btn-primary" onclick="document.getElementById(\'dodajAktBtn\').click()">';
            $documentTableHTML .= '<i class="fas fa-info-circle me-2"></i>';
            $documentTableHTML .= 'Dodaj prvi dokument';
            $documentTableHTML .= '</button>';
            $documentTableHTML .= '</div>';
        }
    }

    /**
     * Get file icon class for styling
     */
    public static function getFileIconClass($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $classMap = [
            'pdf' => 'pdf',
            'doc' => 'doc',
            'docx' => 'doc',
            'xls' => 'xls',
            'xlsx' => 'xls',
            'jpg' => 'img',
            'jpeg' => 'img',
            'png' => 'img',
            'gif' => 'img'
        ];
        
        return $classMap[$extension] ?? 'default';
    }

    /**
     * Format file size in human readable format
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Get appropriate icon for file type
     */
    public static function getFileIcon($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $iconMap = [
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc' => 'fas fa-file-word text-primary',
            'docx' => 'fas fa-file-word text-primary',
            'xls' => 'fas fa-file-excel text-success',
            'xlsx' => 'fas fa-file-excel text-success',
            'ppt' => 'fas fa-file-powerpoint text-warning',
            'pptx' => 'fas fa-file-powerpoint text-warning',
            'jpg' => 'fas fa-file-image text-info',
            'jpeg' => 'fas fa-file-image text-info',
            'png' => 'fas fa-file-image text-info',
            'gif' => 'fas fa-file-image text-info',
            'txt' => 'fas fa-file-alt text-secondary',
            'zip' => 'fas fa-file-archive text-dark',
            'rar' => 'fas fa-file-archive text-dark'
        ];
        
        return $iconMap[$extension] ?? 'fas fa-file text-muted';
    }

    /**
     * Archive predmet and move documents
     */
    public static function archivePredmet($db, $conf, $user, $predmet_id, $razlog = '')
    {
        try {
            $db->begin();

            // Get predmet details
            $sql = "SELECT 
                        p.klasa_br, p.sadrzaj, p.dosje_broj, p.godina, p.predmet_rbr,
                        p.naziv_predmeta, p.vrijeme_cuvanja
                    FROM " . MAIN_DB_PREFIX . "a_predmet p
                    WHERE p.ID_predmeta = " . (int)$predmet_id;

            $resql = $db->query($sql);
            if (!$resql || !($predmet = $db->fetch_object($resql))) {
                throw new Exception("Predmet not found");
            }

            $klasa = $predmet->klasa_br . '-' . $predmet->sadrzaj . '/' . 
                     $predmet->godina . '-' . $predmet->dosje_broj . '/' . 
                     $predmet->predmet_rbr;

            // Generate archive location
            $archive_location = 'ecm/SEUP/Arhiva/' . self::generateFolderName(
                $predmet->klasa_br,
                $predmet->sadrzaj,
                $predmet->dosje_broj,
                $predmet->godina,
                $predmet->predmet_rbr,
                $predmet->naziv_predmeta
            ) . '/';

            // Count documents
            $current_path = self::getPredmetFolderPath($predmet_id, $db);
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filepath = '" . $db->escape($current_path) . "'";
            $resql = $db->query($sql);
            $doc_count = 0;
            if ($resql && $obj = $db->fetch_object($resql)) {
                $doc_count = $obj->count;
            }

            // Create archive record
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_arhiva (
                        ID_predmeta, klasa_predmeta, naziv_predmeta, lokacija_arhive, broj_dokumenata,
                        razlog_arhiviranja, fk_user_arhivirao
                    ) VALUES (
                        " . (int)$predmet_id . ",
                        '" . $db->escape($klasa) . "',
                        '" . $db->escape($predmet->naziv_predmeta) . "',
                        '" . $db->escape($archive_location) . "',
                        " . (int)$doc_count . ",
                        '" . $db->escape($razlog) . "',
                        " . (int)$user->id . "
                    )";

            $result = $db->query($sql);
            if (!$result) {
                throw new Exception("Failed to create archive record: " . $db->lasterror());
            }

            // Move documents to archive folder
            $source_dir = DOL_DATA_ROOT . '/ecm/' . $current_path;
            $archive_dir = DOL_DATA_ROOT . '/' . $archive_location;
            
            $files_moved = 0;
            if (is_dir($source_dir)) {
                // Create archive directory
                if (!is_dir($archive_dir)) {
                    dol_mkdir($archive_dir);
                }
                
                // Move files
                $files = scandir($source_dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $source_file = $source_dir . $file;
                        $archive_file = $archive_dir . $file;
                        
                        if (is_file($source_file)) {
                            if (rename($source_file, $archive_file)) {
                                $files_moved++;
                            }
                        }
                    }
                }
                
                // Remove empty source directory
                if (count(scandir($source_dir)) == 2) { // Only . and ..
                    rmdir($source_dir);
                }
            }

            // Update ECM file paths
            $sql = "UPDATE " . MAIN_DB_PREFIX . "ecm_files 
                    SET filepath = '" . $db->escape(rtrim($archive_location, '/')) . "'
                    WHERE filepath = '" . $db->escape(rtrim($current_path, '/')) . "'
                    AND entity = " . $conf->entity;
            $db->query($sql);

            $db->commit();

            return [
                'success' => true,
                'message' => 'Predmet uspješno arhiviran',
                'files_moved' => $files_moved,
                'archive_location' => $archive_location
            ];

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("Error archiving predmet: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Archive predmet with new structure (arhivska gradiva integration)
     */
    public static function archivePredmetNew($db, $conf, $user, $predmet_id, $razlog = '', $fk_arhivska_gradiva = null, $postupak_po_isteku = 'predaja_arhivu')
    {
        try {
            $db->begin();

            // Get predmet details with vrijeme_cuvanja
            $sql = "SELECT 
                        p.klasa_br, p.sadrzaj, p.dosje_broj, p.godina, p.predmet_rbr,
                        p.naziv_predmeta, p.vrijeme_cuvanja
                    FROM " . MAIN_DB_PREFIX . "a_predmet p
                    WHERE p.ID_predmeta = " . (int)$predmet_id;

            $resql = $db->query($sql);
            if (!$resql || !($predmet = $db->fetch_object($resql))) {
                throw new Exception("Predmet not found");
            }

            $klasa = $predmet->klasa_br . '-' . $predmet->sadrzaj . '/' . 
                     $predmet->godina . '-' . $predmet->dosje_broj . '/' . 
                     $predmet->predmet_rbr;

            // Generate archive location
            $archive_location = 'ecm/SEUP/Arhiva/' . self::generateFolderName(
                $predmet->klasa_br,
                $predmet->sadrzaj,
                $predmet->dosje_broj,
                $predmet->godina,
                $predmet->predmet_rbr,
                $predmet->naziv_predmeta
            ) . '/';

            // Count documents
            $current_path = self::getPredmetFolderPath($predmet_id, $db);
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filepath = '" . $db->escape(rtrim($current_path, '/')) . "'
                    AND entity = " . $conf->entity;
            $resql = $db->query($sql);
            $doc_count = 0;
            if ($resql && $obj = $db->fetch_object($resql)) {
                $doc_count = $obj->count;
            }

            // Create new archive record with extended structure
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_arhiva (
                        ID_predmeta, 
                        klasa_predmeta, 
                        naziv_predmeta, 
                        lokacija_arhive, 
                        broj_dokumenata,
                        razlog_arhiviranja, 
                        fk_user_arhivirao,
                        fk_arhivska_gradiva,
                        postupak_po_isteku,
                        rok_cuvanja_godina
                    ) VALUES (
                        " . (int)$predmet_id . ",
                        '" . $db->escape($klasa) . "',
                        '" . $db->escape($predmet->naziv_predmeta) . "',
                        '" . $db->escape($archive_location) . "',
                        " . (int)$doc_count . ",
                        '" . $db->escape($razlog) . "',
                        " . (int)$user->id . ",
                        " . ($fk_arhivska_gradiva ? (int)$fk_arhivska_gradiva : "NULL") . ",
                        '" . $db->escape($postupak_po_isteku) . "',
                        " . (int)$predmet->vrijeme_cuvanja . "
                    )";

            $result = $db->query($sql);
            if (!$result) {
                throw new Exception("Failed to create archive record: " . $db->lasterror());
            }

            // Move documents to archive folder
            $source_dir = DOL_DATA_ROOT . '/ecm/' . $current_path;
            $archive_dir = DOL_DATA_ROOT . '/' . $archive_location;
            
            $files_moved = 0;
            if (is_dir($source_dir)) {
                // Create archive directory
                if (!is_dir($archive_dir)) {
                    dol_mkdir($archive_dir);
                }
                
                // Move files
                $files = scandir($source_dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $source_file = $source_dir . $file;
                        $archive_file = $archive_dir . $file;
                        
                        if (is_file($source_file)) {
                            if (rename($source_file, $archive_file)) {
                                $files_moved++;
                            }
                        }
                    }
                }
                
                // Remove empty source directory
                if (count(scandir($source_dir)) == 2) { // Only . and ..
                    rmdir($source_dir);
                }
            }

            // Update ECM file paths
            $sql = "UPDATE " . MAIN_DB_PREFIX . "ecm_files 
                    SET filepath = '" . $db->escape(rtrim($archive_location, '/')) . "'
                    WHERE filepath = '" . $db->escape(rtrim($current_path, '/')) . "'
                    AND entity = " . $conf->entity;
            $db->query($sql);

            $db->commit();

            return [
                'success' => true,
                'message' => 'Predmet uspješno arhiviran s novom strukturom',
                'files_moved' => $files_moved,
                'archive_location' => $archive_location
            ];

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("Error archiving predmet (new): " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ensure a_arhiva table has new structure
     */
    public static function ensureArhivaTableStructure($db)
    {
        try {
            // Check if new columns exist
            $sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "a_arhiva LIKE 'fk_arhivska_gradiva'";
            $result = $db->query($sql);
            
            if ($db->num_rows($result) == 0) {
                dol_syslog("Adding new columns to a_arhiva table", LOG_INFO);
                
                // Add new columns for extended archive structure
                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "a_arhiva 
                        ADD COLUMN fk_arhivska_gradiva INT(11) DEFAULT NULL COMMENT 'Link to arhivska gradiva',
                        ADD COLUMN postupak_po_isteku ENUM('predaja_arhivu','ibp_izlucivanje','ibp_brisanje') DEFAULT 'predaja_arhivu' COMMENT 'Postupak po isteku roka',
                        ADD COLUMN rok_cuvanja_godina INT(11) DEFAULT 0 COMMENT 'Rok čuvanja u godinama',
                        ADD KEY fk_arhivska_gradiva_idx (fk_arhivska_gradiva)";
                
                $result = $db->query($sql);
                if ($result) {
                    dol_syslog("New archive columns added successfully", LOG_INFO);
                    return true;
                } else {
                    dol_syslog("Failed to add new archive columns: " . $db->lasterror(), LOG_ERR);
                    return false;
                }
            }
            
            return true; // Columns already exist
            
        } catch (Exception $e) {
            dol_syslog("Error ensuring archive table structure: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }

    /**
     * Calculate expiration date for archived predmet
     */
    public static function calculateExpirationInfo($datum_arhiviranja, $rok_cuvanja_godina)
    {
        if ($rok_cuvanja_godina == 0) {
            return [
                'istek_datum' => null,
                'istek_text' => 'Trajno',
                'preostalo_godina' => null,
                'preostalo_text' => 'Trajno čuvanje'
            ];
        }

        $arhiva_timestamp = strtotime($datum_arhiviranja);
        $istek_timestamp = strtotime("+{$rok_cuvanja_godina} years", $arhiva_timestamp);
        $now = time();
        
        $preostalo_sekundi = $istek_timestamp - $now;
        $preostalo_godina = $preostalo_sekundi / (365.25 * 24 * 60 * 60);
        
        return [
            'istek_datum' => $istek_timestamp,
            'istek_text' => date('d.m.y', $istek_timestamp),
            'preostalo_godina' => $preostalo_godina,
            'preostalo_text' => $preostalo_godina > 0 
                ? sprintf('%.1f god', $preostalo_godina)
                : 'Istekao'
        ];
    }

    /**
     * Get arhivska gradiva options for dropdown
     */
    public static function getArhivskaGradivaOptions($db)
    {
        $options = [];
        
        $sql = "SELECT rowid, oznaka, vrsta_gradiva 
                FROM " . MAIN_DB_PREFIX . "a_arhivska_gradiva 
                ORDER BY oznaka ASC";
        
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $options[] = $obj;
            }
        }
        
        return $options;
    }

    /**
     * Restore predmet from archive
     */
    public static function restorePredmet($db, $conf, $user, $arhiva_id)
    {
        try {
            $db->begin();

            // Get archive details
            $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "a_arhiva 
                    WHERE ID_arhive = " . (int)$arhiva_id . "
                    AND status_arhive = 'active'";
            
            $resql = $db->query($sql);
            if (!$resql || !($arhiva = $db->fetch_object($resql))) {
                throw new Exception("Archive record not found");
            }

            // Get current predmet folder path
            $current_path = self::getPredmetFolderPath($arhiva->ID_predmeta, $db);
            
            // Move files back from archive
            $archive_dir = DOL_DATA_ROOT . '/' . $arhiva->lokacija_arhive;
            $restore_dir = DOL_DATA_ROOT . '/ecm/' . $current_path;
            
            $files_moved = 0;
            if (is_dir($archive_dir)) {
                // Create restore directory
                if (!is_dir($restore_dir)) {
                    dol_mkdir($restore_dir);
                }
                
                // Move files back
                $files = scandir($archive_dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $archive_file = $archive_dir . $file;
                        $restore_file = $restore_dir . $file;
                        
                        if (is_file($archive_file)) {
                            if (rename($archive_file, $restore_file)) {
                                $files_moved++;
                            }
                        }
                    }
                }
                
                // Remove empty archive directory
                if (count(scandir($archive_dir)) == 2) {
                    rmdir($archive_dir);
                }
            }

            // Update ECM file paths back
            $sql = "UPDATE " . MAIN_DB_PREFIX . "ecm_files 
                    SET filepath = '" . $db->escape(rtrim($current_path, '/')) . "'
                    WHERE filepath = '" . $db->escape(rtrim($arhiva->lokacija_arhive, '/')) . "'
                    AND entity = " . $conf->entity;
            $db->query($sql);

            // Delete archive record
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_arhiva 
                    WHERE ID_arhive = " . (int)$arhiva_id;
            
            if (!$db->query($sql)) {
                throw new Exception("Failed to delete archive record");
            }

            $db->commit();

            return [
                'success' => true,
                'message' => 'Predmet uspješno vraćen iz arhive',
                'files_moved' => $files_moved
            ];

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("Error restoring predmet: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete archive permanently
     */
   public static function deleteArchive($db, $conf, $user, $arhiva_id)
{
    try {
        $db->begin();

        // Get archive details
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "a_arhiva 
                WHERE ID_arhive = " . (int)$arhiva_id . "
                AND status_arhive = 'active'";
        $resql = $db->query($sql);
        if (!$resql || !($arhiva = $db->fetch_object($resql))) {
            throw new Exception("Archive record not found");
        }

        // Zapamti ID predmeta povezan s arhivom
        $predmet_id = (int) $arhiva->ID_predmeta;

        // Delete files from filesystem (postojeći kod)
        $archive_dir = DOL_DATA_ROOT . '/' . $arhiva->lokacija_arhive;
        if (is_dir($archive_dir)) {
            $files = scandir($archive_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $file_path = $archive_dir . $file;
                    if (is_file($file_path)) {
                        unlink($file_path);
                    }
                }
            }
            rmdir($archive_dir);
        }

        // Delete ECM records (postojeći kod)
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ecm_files 
                WHERE filepath = '" . $db->escape(rtrim($arhiva->lokacija_arhive, '/')) . "'
                AND entity = " . $conf->entity;
        $db->query($sql);

        // Delete archive record (postojeći kod)
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_arhiva 
                WHERE ID_arhive = " . (int)$arhiva_id;
        if (!$db->query($sql)) {
            throw new Exception("Failed to delete archive record");
        }

        // NOVO: obriši i predmet
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_predmet 
                WHERE ID_predmeta = " . $predmet_id;
        if (!$db->query($sql)) {
            throw new Exception("Failed to delete predmet record");
        }

        $db->commit();

        return [
            'success' => true,
            'message' => 'Arhiva i predmet su trajno obrisani'
        ];

    } catch (Exception $e) {
        $db->rollback();
        dol_syslog("Error deleting archive: " . $e->getMessage(), LOG_ERR);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

    /**
     * Build ORDER BY clause for klasifikacija sorting
     */
    public static function buildKlasifikacijaOrderBy($sortField, $sortOrder, $tableAlias = '')
    {
        $prefix = $tableAlias ? $tableAlias . '.' : '';
        
        if ($sortField === 'klasa_broj') {
            return "ORDER BY CAST({$prefix}klasa_broj AS UNSIGNED) {$sortOrder}";
        } elseif ($sortField === 'sadrzaj') {
            return "ORDER BY CAST({$prefix}sadrzaj AS UNSIGNED) {$sortOrder}";
        } elseif ($sortField === 'dosje_broj') {
            return "ORDER BY CAST({$prefix}dosje_broj AS UNSIGNED) {$sortOrder}";
        } elseif ($sortField === 'vrijeme_cuvanja') {
            return "ORDER BY CAST({$prefix}vrijeme_cuvanja AS UNSIGNED) {$sortOrder}";
        } else {
            return "ORDER BY {$prefix}{$sortField} {$sortOrder}";
        }
    }

    /**
     * Build ORDER BY clause for predmet sorting with klasa
     */
    public static function buildOrderByKlasa($sortField, $sortOrder)
    {
        if ($sortField === 'klasa_br') {
            return "ORDER BY CAST(p.klasa_br AS UNSIGNED) {$sortOrder}, CAST(p.sadrzaj AS UNSIGNED) {$sortOrder}, CAST(p.dosje_broj AS UNSIGNED) {$sortOrder}, p.predmet_rbr {$sortOrder}";
        } else {
            return "ORDER BY p.{$sortField} {$sortOrder}";
        }
    }
}