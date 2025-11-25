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
 * Akt Helper Class for SEUP Module
 * Handles akt numbering and management
 */
class Akt_Helper
{
    /**
     * Create a_akti table if it doesn't exist
     */
    public static function createAktiTable($db)
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_akti (
            ID_akta int(11) NOT NULL AUTO_INCREMENT,
            ID_predmeta int(11) NOT NULL,
            urb_broj varchar(10) NOT NULL COMMENT 'Urudžbeni broj/Registry number',
            fk_ecm_file int(11) NOT NULL COMMENT 'Reference to ecm_files',
            datum_kreiranja timestamp DEFAULT CURRENT_TIMESTAMP,
            fk_user_creat int(11) NOT NULL,
            PRIMARY KEY (ID_akta),
            UNIQUE KEY unique_predmet_urb (ID_predmeta, urb_broj),
            KEY fk_predmet_akt (ID_predmeta),
            KEY fk_ecm_file_akt (fk_ecm_file),
            KEY fk_user_akt (fk_user_creat)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $result = $db->query($sql);
        if (!$result) {
            dol_syslog("Error creating a_akti table: " . $db->lasterror(), LOG_ERR);
            return false;
        }

        dol_syslog("a_akti table created or already exists", LOG_INFO);
        return true;
    }

    /**
     * Get next urb broj for predmet
     */
    public static function getNextUrbBroj($db, $predmet_id)
    {
        $sql = "SELECT MAX(CAST(urb_broj AS UNSIGNED)) as max_urb 
                FROM " . MAIN_DB_PREFIX . "a_akti 
                WHERE ID_predmeta = " . (int)$predmet_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            $next_number = ($obj->max_urb ? $obj->max_urb + 1 : 1);
            return sprintf('%02d', $next_number);
        }
        
        return '01';
    }

    /**
     * Create akt record
     */
    public static function createAkt($db, $predmet_id, $ecm_file_id, $user_id)
    {
        try {
            $urb_broj = self::getNextUrbBroj($db, $predmet_id);
            
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_akti 
                    (ID_predmeta, urb_broj, fk_ecm_file, fk_user_creat) 
                    VALUES (
                        " . (int)$predmet_id . ",
                        '" . $db->escape($urb_broj) . "',
                        " . (int)$ecm_file_id . ",
                        " . (int)$user_id . "
                    )";

            $result = $db->query($sql);
            if ($result) {
                // Auto-scan for digital signature after creating akt
                self::autoScanAktSignature($db, $ecm_file_id);
                
                dol_syslog("Created akt record: predmet=$predmet_id, urb=$urb_broj, ecm=$ecm_file_id", LOG_INFO);
                return [
                    'success' => true,
                    'urb_broj' => $urb_broj,
                    'akt_id' => $db->last_insert_id(MAIN_DB_PREFIX . 'a_akti', 'ID_akta')
                ];
            } else {
                throw new Exception("Database error: " . $db->lasterror());
            }

        } catch (Exception $e) {
            dol_syslog("Error creating akt: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Auto-scan akt document for digital signature
     */
    private static function autoScanAktSignature($db, $ecm_file_id)
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
                        dol_syslog("Digital signature detected in akt: " . $file->filename, LOG_INFO);
                    }
                }
            }
        } catch (Exception $e) {
            dol_syslog("Error auto-scanning akt signature: " . $e->getMessage(), LOG_ERR);
        }
    }

    /**
     * Get akt urb broj by ECM file ID
     */
    public static function getUrbBrojByEcmFile($db, $ecm_file_id)
    {
        $sql = "SELECT urb_broj FROM " . MAIN_DB_PREFIX . "a_akti 
                WHERE fk_ecm_file = " . (int)$ecm_file_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return $obj->urb_broj;
        }
        
        return null;
    }

    /**
     * Delete akt record when ECM file is deleted
     */
    public static function deleteAktByEcmFile($db, $ecm_file_id)
    {
        // First delete all prilozi associated with this akt
        $sql = "SELECT ID_akta FROM " . MAIN_DB_PREFIX . "a_akti 
                WHERE fk_ecm_file = " . (int)$ecm_file_id;
        $resql = $db->query($sql);
        
        if ($resql && $obj = $db->fetch_object($resql)) {
            $akt_id = $obj->ID_akta;
            
            // Delete all prilozi for this akt (cascade delete)
            require_once __DIR__ . '/prilog_helper.class.php';
            Prilog_Helper::deletePriloziByAkt($db, $akt_id);
        }
        
        // Then delete the akt record
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_akti 
                WHERE fk_ecm_file = " . (int)$ecm_file_id;

        $result = $db->query($sql);
        if ($result) {
            dol_syslog("Deleted akt record for ECM file: $ecm_file_id", LOG_INFO);
            return true;
        } else {
            dol_syslog("Error deleting akt record: " . $db->lasterror(), LOG_ERR);
            return false;
        }
    }

    /**
     * Get akt statistics for predmet
     */
    public static function getAktStatistics($db, $predmet_id)
    {
        $stats = [
            'total_akti' => 0,
            'latest_urb' => '00'
        ];

        // Count total akti
        $sql = "SELECT COUNT(*) as count, MAX(urb_broj) as max_urb 
                FROM " . MAIN_DB_PREFIX . "a_akti 
                WHERE ID_predmeta = " . (int)$predmet_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            $stats['total_akti'] = (int)$obj->count;
            $stats['latest_urb'] = $obj->max_urb ?: '00';
        }

        return $stats;
    }
}