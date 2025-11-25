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
 * Prilog Helper Class for SEUP Module
 * Handles prilog numbering and management
 */
class Prilog_Helper
{
    /**
     * Create a_prilozi table if it doesn't exist
     */
    public static function createPriloziTable($db)
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_prilozi (
            ID_priloga int(11) NOT NULL AUTO_INCREMENT,
            ID_akta int(11) NOT NULL,
            ID_predmeta int(11) NOT NULL,
            prilog_rbr varchar(10) NOT NULL,
            fk_ecm_file int(11) NOT NULL,
            datum_kreiranja timestamp DEFAULT CURRENT_TIMESTAMP,
            fk_user_creat int(11) NOT NULL,
            PRIMARY KEY (ID_priloga),
            UNIQUE KEY unique_akt_prilog (ID_akta, prilog_rbr),
            KEY fk_akt_prilog (ID_akta),
            KEY fk_predmet_prilog (ID_predmeta),
            KEY fk_ecm_file_prilog (fk_ecm_file),
            KEY fk_user_prilog (fk_user_creat)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $result = $db->query($sql);
        if (!$result) {
            dol_syslog("Error creating a_prilozi table: " . $db->lasterror(), LOG_ERR);
            return false;
        }
        
        dol_syslog("a_prilozi table created or already exists", LOG_INFO);
        return true;
    }

    /**
     * Get next prilog broj for specific akt
     */
    public static function getNextPrilogBroj($db, $akt_id)
    {
        $sql = "SELECT MAX(CAST(prilog_rbr AS UNSIGNED)) as max_prilog 
                FROM " . MAIN_DB_PREFIX . "a_prilozi 
                WHERE ID_akta = " . (int)$akt_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            $next_number = ($obj->max_prilog ? $obj->max_prilog + 1 : 1);
            return sprintf('%02d', $next_number);
        }
        
        return '01';
    }

    /**
     * Create prilog record
     */
    public static function createPrilog($db, $akt_id, $predmet_id, $ecm_file_id, $user_id)
    {
        try {
            $prilog_rbr = self::getNextPrilogBroj($db, $akt_id);
            
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_prilozi 
                    (ID_akta, ID_predmeta, prilog_rbr, fk_ecm_file, fk_user_creat) 
                    VALUES (
                        " . (int)$akt_id . ",
                        " . (int)$predmet_id . ",
                        '" . $db->escape($prilog_rbr) . "',
                        " . (int)$ecm_file_id . ",
                        " . (int)$user_id . "
                    )";

            $result = $db->query($sql);
            if ($result) {
                // Auto-scan for digital signature after creating prilog
                self::autoScanPrilogSignature($db, $ecm_file_id);
                
                dol_syslog("Created prilog record: akt=$akt_id, prilog=$prilog_rbr, ecm=$ecm_file_id", LOG_INFO);
                return [
                    'success' => true,
                    'prilog_rbr' => $prilog_rbr,
                    'prilog_id' => $db->last_insert_id(MAIN_DB_PREFIX . 'a_prilozi', 'ID_priloga')
                ];
            } else {
                throw new Exception("Database error: " . $db->lasterror());
            }

        } catch (Exception $e) {
            dol_syslog("Error creating prilog: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Auto-scan prilog document for digital signature
     */
    private static function autoScanPrilogSignature($db, $ecm_file_id)
    {
        try {
            // Get file path from ECM
            $sql = "SELECT filepath, filename FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE rowid = " . (int)$ecm_file_id;
            $result = $db->query($sql);
            
            if ($result && $file = $db->fetch_object($result)) {
                $full_path = DOL_DATA_ROOT . '/ecm/' . $file->filepath . '/' . $file->filename;
                
                // Only scan PDF files
                $extension = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
                if ($extension === 'pdf' && file_exists($full_path)) {
                    require_once __DIR__ . '/digital_signature_detector.class.php';
                    $scanResult = Digital_Signature_Detector::autoScanOnUpload($db, $GLOBALS['conf'], $full_path, $ecm_file_id);
                    
                    if ($scanResult['has_signature']) {
                        dol_syslog("Digital signature detected in prilog: " . $file->filename, LOG_INFO);
                    }
                }
            }
        } catch (Exception $e) {
            dol_syslog("Error auto-scanning prilog signature: " . $e->getMessage(), LOG_ERR);
        }
    }

    /**
     * Get prilog broj by ECM file ID
     */
    public static function getPrilogBrojByEcmFile($db, $ecm_file_id)
    {
        $sql = "SELECT p.prilog_rbr, a.urb_broj 
                FROM " . MAIN_DB_PREFIX . "a_prilozi p
                LEFT JOIN " . MAIN_DB_PREFIX . "a_akti a ON p.ID_akta = a.ID_akta
                WHERE p.fk_ecm_file = " . (int)$ecm_file_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return [
                'prilog_rbr' => $obj->prilog_rbr,
                'akt_urb' => $obj->urb_broj
            ];
        }
        
        return null;
    }

    /**
     * Delete prilog record when ECM file is deleted
     */
    public static function deletePrilogByEcmFile($db, $ecm_file_id)
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_prilozi 
                WHERE fk_ecm_file = " . (int)$ecm_file_id;

        $result = $db->query($sql);
        if ($result) {
            dol_syslog("Deleted prilog record for ECM file: $ecm_file_id", LOG_INFO);
            return true;
        } else {
            dol_syslog("Error deleting prilog record: " . $db->lasterror(), LOG_ERR);
            return false;
        }
    }

    /**
     * Delete all prilozi for specific akt (cascade delete)
     */
    public static function deletePriloziByAkt($db, $akt_id)
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_prilozi 
                WHERE ID_akta = " . (int)$akt_id;

        $result = $db->query($sql);
        if ($result) {
            $affected_rows = $db->affected_rows($result);
            dol_syslog("Deleted $affected_rows prilog records for akt: $akt_id", LOG_INFO);
            return $affected_rows;
        } else {
            dol_syslog("Error deleting prilozi for akt: " . $db->lasterror(), LOG_ERR);
            return false;
        }
    }

    /**
     * Get available akti for predmet (for dropdown)
     */
    public static function getAvailableAkti($db, $predmet_id)
    {
        $sql = "SELECT 
                    a.ID_akta,
                    a.urb_broj,
                    ef.filename
                FROM " . MAIN_DB_PREFIX . "a_akti a
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files ef ON a.fk_ecm_file = ef.rowid
                WHERE a.ID_predmeta = " . (int)$predmet_id . "
                ORDER BY CAST(a.urb_broj AS UNSIGNED) ASC";

        $resql = $db->query($sql);
        $akti = [];
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $akti[] = $obj;
            }
        }
        
        return $akti;
    }

    /**
     * Get prilog statistics for akt
     */
    public static function getPrilogStatistics($db, $akt_id)
    {
        $stats = [
            'total_prilozi' => 0,
            'latest_prilog' => '00'
        ];

        // Count total prilozi
        $sql = "SELECT COUNT(*) as count, MAX(prilog_rbr) as max_prilog 
                FROM " . MAIN_DB_PREFIX . "a_prilozi 
                WHERE ID_akta = " . (int)$akt_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            $stats['total_prilozi'] = (int)$obj->count;
            $stats['latest_prilog'] = $obj->max_prilog ?: '00';
        }

        return $stats;
    }
}