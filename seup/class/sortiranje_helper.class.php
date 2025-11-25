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
 * Sortiranje Helper Class for SEUP Module
 * Handles sorting and assignment of unassigned documents
 */
class Sortiranje_Helper
{
    /**
     * Get unassigned documents for a predmet
     * Documents that exist in ECM but are not assigned as akt or prilog
     */
    public static function getNedodjeljeneDokumente($db, $conf, $predmet_id)
    {
        try {
            require_once __DIR__ . '/predmet_helper.class.php';
            $relative_path = Predmet_helper::getPredmetFolderPath($predmet_id, $db);
            
            $sql = "SELECT 
                        ef.rowid,
                        ef.filename,
                        ef.filepath,
                        ef.date_c,
                        ef.label,
                        CONCAT(u.firstname, ' ', u.lastname) as created_by
                    FROM " . MAIN_DB_PREFIX . "ecm_files ef
                    LEFT JOIN " . MAIN_DB_PREFIX . "user u ON ef.fk_user_c = u.rowid
                    WHERE ef.filepath = '" . $db->escape(rtrim($relative_path, '/')) . "'
                    AND ef.entity = " . $conf->entity . "
                    AND ef.rowid NOT IN (
                        SELECT fk_ecm_file FROM " . MAIN_DB_PREFIX . "a_akti 
                        WHERE ID_predmeta = " . (int)$predmet_id . "
                    )
                    AND ef.rowid NOT IN (
                        SELECT fk_ecm_file FROM " . MAIN_DB_PREFIX . "a_prilozi 
                        WHERE ID_predmeta = " . (int)$predmet_id . "
                    )
                    ORDER BY ef.date_c ASC";

            $resql = $db->query($sql);
            $nedodjeljeni = [];
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $nedodjeljeni[] = $obj;
                }
            }

            return [
                'success' => true,
                'documents' => $nedodjeljeni,
                'count' => count($nedodjeljeni)
            ];

        } catch (Exception $e) {
            dol_syslog("Error getting unassigned documents: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'documents' => [],
                'count' => 0
            ];
        }
    }

    /**
     * Auto-assign document as akt
     */
    public static function autoAssignAsAkt($db, $predmet_id, $ecm_file_id, $user_id)
    {
        try {
            require_once __DIR__ . '/akt_helper.class.php';
            
            // Ensure a_akti table exists
            Akt_Helper::createAktiTable($db);
            
            $result = Akt_Helper::createAkt($db, $predmet_id, $ecm_file_id, $user_id);
            
            if ($result['success']) {
                dol_syslog("Auto-assigned document as akt: ECM $ecm_file_id -> Akt " . $result['urb_broj'], LOG_INFO);
            }
            
            return $result;

        } catch (Exception $e) {
            dol_syslog("Error auto-assigning as akt: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Assign document as prilog to existing akt
     */
    public static function assignAsPrilog($db, $akt_id, $predmet_id, $ecm_file_id, $user_id)
    {
        try {
            require_once __DIR__ . '/prilog_helper.class.php';
            
            // Ensure a_prilozi table exists
            Prilog_Helper::createPriloziTable($db);
            
            $result = Prilog_Helper::createPrilog($db, $akt_id, $predmet_id, $ecm_file_id, $user_id);
            
            if ($result['success']) {
                dol_syslog("Assigned document as prilog: ECM $ecm_file_id -> Prilog " . $result['prilog_rbr'], LOG_INFO);
            }
            
            return $result;

        } catch (Exception $e) {
            dol_syslog("Error assigning as prilog: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk assign multiple documents
     */
    public static function bulkAssign($db, $conf, $user, $predmet_id, $assignments)
    {
        try {
            $db->begin();
            
            $results = [
                'success' => true,
                'processed' => 0,
                'akti_created' => 0,
                'prilozi_created' => 0,
                'errors' => []
            ];

            foreach ($assignments as $assignment) {
                $ecm_file_id = (int)$assignment['ecm_file_id'];
                $action = $assignment['action']; // 'akt', 'prilog', 'skip'
                $akt_id = isset($assignment['akt_id']) ? (int)$assignment['akt_id'] : null;

                if ($action === 'akt') {
                    $result = self::autoAssignAsAkt($db, $predmet_id, $ecm_file_id, $user->id);
                    if ($result['success']) {
                        $results['akti_created']++;
                    } else {
                        $results['errors'][] = "ECM $ecm_file_id: " . $result['error'];
                    }
                } elseif ($action === 'prilog' && $akt_id) {
                    $result = self::assignAsPrilog($db, $akt_id, $predmet_id, $ecm_file_id, $user->id);
                    if ($result['success']) {
                        $results['prilozi_created']++;
                    } else {
                        $results['errors'][] = "ECM $ecm_file_id: " . $result['error'];
                    }
                }
                // 'skip' action - do nothing
                
                $results['processed']++;
            }

            if (empty($results['errors'])) {
                $db->commit();
                $results['message'] = "Uspješno obrađeno {$results['processed']} dokumenata. " .
                                    "Kreirano {$results['akti_created']} akata i {$results['prilozi_created']} priloga.";
            } else {
                $db->rollback();
                $results['success'] = false;
                $results['message'] = "Greške pri obradi: " . implode(', ', $results['errors']);
            }

            return $results;

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("Error in bulk assign: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processed' => 0
            ];
        }
    }

    /**
     * Get available akti for assignment dropdown
     */
    public static function getAvailableAktiForAssignment($db, $predmet_id)
    {
        $sql = "SELECT 
                    a.ID_akta,
                    a.urb_broj,
                    ef.filename,
                    COUNT(p.ID_priloga) as prilog_count
                FROM " . MAIN_DB_PREFIX . "a_akti a
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files ef ON a.fk_ecm_file = ef.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_prilozi p ON a.ID_akta = p.ID_akta
                WHERE a.ID_predmeta = " . (int)$predmet_id . "
                GROUP BY a.ID_akta, a.urb_broj, ef.filename
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
     * Get sortiranje statistics for predmet
     */
    public static function getSortiranjeStatistics($db, $predmet_id)
    {
        try {
            require_once __DIR__ . '/predmet_helper.class.php';
            $relative_path = Predmet_helper::getPredmetFolderPath($predmet_id, $db);
            
            $stats = [
                'total_documents' => 0,
                'assigned_akti' => 0,
                'assigned_prilozi' => 0,
                'unassigned' => 0
            ];

            // Total documents
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filepath = '" . $db->escape(rtrim($relative_path, '/')) . "'
                    AND entity = " . $GLOBALS['conf']->entity;
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['total_documents'] = (int)$obj->count;
            }

            // Assigned as akti
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_akti 
                    WHERE ID_predmeta = " . (int)$predmet_id;
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['assigned_akti'] = (int)$obj->count;
            }

            // Assigned as prilozi
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_prilozi 
                    WHERE ID_predmeta = " . (int)$predmet_id;
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['assigned_prilozi'] = (int)$obj->count;
            }

            // Calculate unassigned
            $stats['unassigned'] = $stats['total_documents'] - $stats['assigned_akti'] - $stats['assigned_prilozi'];

            return $stats;

        } catch (Exception $e) {
            dol_syslog("Error getting sortiranje statistics: " . $e->getMessage(), LOG_ERR);
            return [
                'total_documents' => 0,
                'assigned_akti' => 0,
                'assigned_prilozi' => 0,
                'unassigned' => 0
            ];
        }
    }

    /**
     * Validate assignment data
     */
    public static function validateAssignment($assignment)
    {
        if (!isset($assignment['ecm_file_id']) || !is_numeric($assignment['ecm_file_id'])) {
            return ['valid' => false, 'error' => 'Invalid ECM file ID'];
        }

        if (!isset($assignment['action']) || !in_array($assignment['action'], ['akt', 'prilog', 'skip'])) {
            return ['valid' => false, 'error' => 'Invalid action'];
        }

        if ($assignment['action'] === 'prilog') {
            if (!isset($assignment['akt_id']) || !is_numeric($assignment['akt_id'])) {
                return ['valid' => false, 'error' => 'Akt ID required for prilog assignment'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Get document preview info for sortiranje modal
     */
    public static function getDocumentPreviewInfo($db, $ecm_file_id)
    {
        $sql = "SELECT 
                    ef.filename,
                    ef.filepath,
                    ef.date_c,
                    ef.label,
                    CONCAT(u.firstname, ' ', u.lastname) as created_by
                FROM " . MAIN_DB_PREFIX . "ecm_files ef
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON ef.fk_user_c = u.rowid
                WHERE ef.rowid = " . (int)$ecm_file_id;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            // Get file size if file exists
            $full_path = DOL_DATA_ROOT . '/ecm/' . $obj->filepath . '/' . $obj->filename;
            $file_size = file_exists($full_path) ? filesize($full_path) : 0;
            
            return [
                'filename' => $obj->filename,
                'filepath' => $obj->filepath,
                'date_c' => $obj->date_c,
                'label' => $obj->label,
                'created_by' => $obj->created_by,
                'file_size' => $file_size,
                'file_extension' => strtolower(pathinfo($obj->filename, PATHINFO_EXTENSION))
            ];
        }
        
        return null;
    }
}