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
 * Suradnici Helper Class for SEUP Module
 * Handles suradnici/third parties management and export functionality
 */
class Suradnici_Helper
{
    /**
     * Get suradnik details by ID
     */
    public static function getSuradnikDetails($db, $rowid)
    {
        try {
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
                    WHERE rowid = " . (int)$rowid;

            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                return [
                    'success' => true,
                    'suradnik' => $obj
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Suradnik nije pronađen'
                ];
            }

        } catch (Exception $e) {
            dol_syslog("Error getting suradnik details: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Export suradnici to CSV format
     */
    public static function exportToCSV($db)
    {
        try {
            // Get all suradnici
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
                    ORDER BY naziv ASC";

            $resql = $db->query($sql);
            $suradnici = [];
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $suradnici[] = $obj;
                }
            }

            if (empty($suradnici)) {
                return [
                    'success' => false,
                    'error' => 'Nema podataka za izvoz'
                ];
            }

            // Create CSV content
            $csvContent = "Rb.,Naziv,OIB,Telefon,Email,Adresa,Kontakt_osoba,Datum_kreiranja\n";
            
            foreach ($suradnici as $index => $suradnik) {
                $csvContent .= sprintf(
                    "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                    $index + 1,
                    str_replace('"', '""', $suradnik->naziv),
                    $suradnik->oib ?: '',
                    $suradnik->telefon ?: '',
                    $suradnik->email ?: '',
                    str_replace('"', '""', $suradnik->adresa ?: ''),
                    str_replace('"', '""', $suradnik->kontakt_osoba ?: ''),
                    $suradnik->datum_kreiranja
                );
            }

            // Save to ECM temp folder instead of temp
            $filename = 'suradnici_' . date('Y-m-d_H-i-s') . '.csv';
            $tempPath = DOL_DATA_ROOT . '/ecm/temp/' . $filename;
            
            // Ensure temp directory exists
            if (!is_dir(DOL_DATA_ROOT . '/ecm/temp/')) {
                dol_mkdir(DOL_DATA_ROOT . '/ecm/temp/');
            }

            // Write CSV with UTF-8 BOM for proper Excel encoding
            $csvWithBOM = "\xEF\xBB\xBF" . $csvContent;
            file_put_contents($tempPath, $csvWithBOM);

            $downloadUrl = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode('temp/' . $filename);

            return [
                'success' => true,
                'message' => 'CSV datoteka je kreirana',
                'filename' => $filename,
                'download_url' => $downloadUrl,
                'records_count' => count($suradnici)
            ];

        } catch (Exception $e) {
            dol_syslog("Error exporting CSV: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Export suradnici to Excel format (HTML table that Excel can open)
     */
    public static function exportToExcel($db)
    {
        try {
            // Get all suradnici
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
                    ORDER BY naziv ASC";

            $resql = $db->query($sql);
            $suradnici = [];
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $suradnici[] = $obj;
                }
            }

            if (empty($suradnici)) {
                return [
                    'success' => false,
                    'error' => 'Nema podataka za izvoz'
                ];
            }

            // Create Excel-compatible HTML
            $excelContent = "Rb.\tNaziv\tOIB\tTelefon\tEmail\tAdresa\tKontakt_osoba\tDatum_kreiranja\n";
            
            // Data rows
            foreach ($suradnici as $index => $suradnik) {
                $excelContent .= sprintf(
                    "%d\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n",
                    $index + 1,
                    str_replace(["\t", "\n", "\r"], [' ', ' ', ' '], $suradnik->naziv),
                    $suradnik->oib ?: '',
                    $suradnik->telefon ?: '',
                    $suradnik->email ?: '',
                    str_replace(["\t", "\n", "\r"], [' ', ' ', ' '], $suradnik->adresa ?: ''),
                    str_replace(["\t", "\n", "\r"], [' ', ' ', ' '], $suradnik->kontakt_osoba ?: ''),
                    $suradnik->datum_kreiranja
                );
            }

            // Save to temp file
            $filename = 'suradnici_' . date('Y-m-d_H-i-s') . '.xls';
            $tempPath = DOL_DATA_ROOT . '/ecm/temp/' . $filename;
            
            // Ensure temp directory exists
            if (!is_dir(DOL_DATA_ROOT . '/ecm/temp/')) {
                dol_mkdir(DOL_DATA_ROOT . '/ecm/temp/');
            }

            // Add UTF-8 BOM for proper Excel encoding
            $excelContent = "\xEF\xBB\xBF" . $excelContent;
            file_put_contents($tempPath, $excelContent);

            $downloadUrl = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode('temp/' . $filename);

            return [
                'success' => true,
                'message' => 'Excel datoteka je kreirana',
                'filename' => $filename,
                'download_url' => $downloadUrl,
                'records_count' => count($suradnici)
            ];

        } catch (Exception $e) {
            dol_syslog("Error exporting Excel: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Export single suradnik to VCF (vCard) format
     */
    public static function exportToVCF($db, $rowid)
    {
        try {
            $sql = "SELECT 
                        naziv,
                        adresa,
                        oib,
                        telefon,
                        kontakt_osoba,
                        email
                    FROM " . MAIN_DB_PREFIX . "a_posiljatelji
                    WHERE rowid = " . (int)$rowid;

            $resql = $db->query($sql);
            if (!$resql || !($suradnik = $db->fetch_object($resql))) {
                return [
                    'success' => false,
                    'error' => 'Suradnik nije pronađen'
                ];
            }

            // Create vCard content
            $vcfContent = "BEGIN:VCARD\n";
            $vcfContent .= "VERSION:3.0\n";
            $vcfContent .= "FN:" . $suradnik->naziv . "\n";
            $vcfContent .= "N:" . $suradnik->naziv . ";;;;\n";
            
            if ($suradnik->telefon) {
                $vcfContent .= "TEL;TYPE=WORK:" . $suradnik->telefon . "\n";
            }
            
            if ($suradnik->email) {
                $vcfContent .= "EMAIL;TYPE=WORK:" . $suradnik->email . "\n";
            }
            
            if ($suradnik->adresa) {
                $vcfContent .= "ADR;TYPE=WORK:;;" . $suradnik->adresa . ";;;;\n";
            }
            
            if ($suradnik->kontakt_osoba) {
                $vcfContent .= "NOTE:Kontakt osoba: " . $suradnik->kontakt_osoba . "\n";
            }
            
            if ($suradnik->oib) {
                $vcfContent .= "NOTE:OIB: " . $suradnik->oib . "\n";
            }
            
            $vcfContent .= "ORG:SEUP\n";
            $vcfContent .= "END:VCARD\n";

            // Save to ECM temp folder
            $filename = 'kontakt_' . preg_replace('/[^a-zA-Z0-9]/', '_', $suradnik->naziv) . '_' . date('Y-m-d_H-i-s') . '.vcf';
            $tempPath = DOL_DATA_ROOT . '/ecm/temp/' . $filename;
            
            // Ensure temp directory exists
            if (!is_dir(DOL_DATA_ROOT . '/ecm/temp/')) {
                dol_mkdir(DOL_DATA_ROOT . '/ecm/temp/');
            }

            file_put_contents($tempPath, $vcfContent);

            $downloadUrl = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode('temp/' . $filename);

            return [
                'success' => true,
                'message' => 'VCF kontakt je kreiran',
                'filename' => $filename,
                'download_url' => $downloadUrl,
                'contact_name' => $suradnik->naziv
            ];

        } catch (Exception $e) {
            dol_syslog("Error exporting VCF: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get suradnici statistics
     */
    public static function getSuradniciStatistics($db)
    {
        try {
            $stats = [
                'total_suradnici' => 0,
                'with_oib' => 0,
                'with_email' => 0,
                'with_telefon' => 0,
                'with_adresa' => 0
            ];

            // Total suradnici
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_posiljatelji";
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['total_suradnici'] = (int)$obj->count;
            }

            // With OIB
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_posiljatelji 
                    WHERE oib IS NOT NULL AND oib != ''";
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['with_oib'] = (int)$obj->count;
            }

            // With email
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_posiljatelji 
                    WHERE email IS NOT NULL AND email != ''";
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['with_email'] = (int)$obj->count;
            }

            // With telefon
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_posiljatelji 
                    WHERE telefon IS NOT NULL AND telefon != ''";
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['with_telefon'] = (int)$obj->count;
            }

            // With adresa
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_posiljatelji 
                    WHERE adresa IS NOT NULL AND adresa != ''";
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['with_adresa'] = (int)$obj->count;
            }

            return $stats;

        } catch (Exception $e) {
            dol_syslog("Error getting suradnici statistics: " . $e->getMessage(), LOG_ERR);
            return null;
        }
    }

    /**
     * Search suradnici by naziv or OIB
     */
    public static function searchSuradnici($db, $searchTerm, $searchField = 'naziv')
    {
        try {
            $searchTerm = trim($searchTerm);
            if (empty($searchTerm)) {
                return ['success' => true, 'results' => []];
            }

            $whereClause = '';
            if ($searchField === 'naziv') {
                $whereClause = "naziv LIKE '%" . $db->escape($searchTerm) . "%'";
            } elseif ($searchField === 'oib') {
                $whereClause = "oib LIKE '%" . $db->escape($searchTerm) . "%'";
            } else {
                $whereClause = "(naziv LIKE '%" . $db->escape($searchTerm) . "%' OR oib LIKE '%" . $db->escape($searchTerm) . "%')";
            }

            $sql = "SELECT 
                        rowid,
                        naziv,
                        adresa,
                        oib,
                        telefon,
                        kontakt_osoba,
                        email
                    FROM " . MAIN_DB_PREFIX . "a_posiljatelji
                    WHERE {$whereClause}
                    ORDER BY naziv ASC
                    LIMIT 20";

            $resql = $db->query($sql);
            $results = [];
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $results[] = $obj;
                }
            }

            return [
                'success' => true,
                'results' => $results,
                'count' => count($results)
            ];

        } catch (Exception $e) {
            dol_syslog("Error searching suradnici: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate OIB format (Croatian personal identification number)
     */
    public static function validateOIB($oib)
    {
        // Remove spaces and non-numeric characters
        $oib = preg_replace('/[^0-9]/', '', $oib);
        
        // Check length
        if (strlen($oib) !== 11) {
            return false;
        }

        // Check if all digits are the same
        if (preg_match('/^(\d)\1{10}$/', $oib)) {
            return false;
        }

        // Calculate control digit using ISO 7064, MOD 11-10
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int)$oib[$i] * (10 - $i);
        }
        
        $controlDigit = (11 - ($sum % 11)) % 10;
        
        return $controlDigit == (int)$oib[10];
    }

    /**
     * Format phone number for display
     */
    public static function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Croatian phone number formatting
        if (preg_match('/^\+385(\d{8,9})$/', $phone, $matches)) {
            $number = $matches[1];
            if (strlen($number) === 8) {
                // Mobile: +385 99 123 4567
                return '+385 ' . substr($number, 0, 2) . ' ' . substr($number, 2, 3) . ' ' . substr($number, 5);
            } elseif (strlen($number) === 9) {
                // Landline: +385 1 234 5678
                return '+385 ' . substr($number, 0, 1) . ' ' . substr($number, 1, 3) . ' ' . substr($number, 4);
            }
        }

        return $phone;
    }

    /**
     * Get suradnici usage statistics (how many times used in predmeti)
     */
    public static function getSuradniciUsageStats($db)
    {
        try {
            $sql = "SELECT 
                        p.naziv,
                        p.oib,
                        COUNT(ps.ID_predmeta) as usage_count
                    FROM " . MAIN_DB_PREFIX . "a_posiljatelji p
                    LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet_stranka ps ON p.rowid = ps.fk_soc
                    GROUP BY p.rowid, p.naziv, p.oib
                    ORDER BY usage_count DESC, p.naziv ASC";

            $resql = $db->query($sql);
            $usage_stats = [];
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $usage_stats[] = $obj;
                }
            }

            return [
                'success' => true,
                'usage_stats' => $usage_stats
            ];

        } catch (Exception $e) {
            dol_syslog("Error getting usage stats: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean up temp export files older than 24 hours
     */
    public static function cleanupTempFiles()
    {
        try {
            $tempDir = DOL_DATA_ROOT . '/ecm/temp/';
            if (!is_dir($tempDir)) {
                return ['success' => true, 'message' => 'Temp directory does not exist'];
            }

            $files = glob($tempDir . '{suradnici_*,kontakt_*}.{csv,xls,vcf}', GLOB_BRACE);
            $deletedCount = 0;
            $cutoffTime = time() - (24 * 60 * 60); // 24 hours ago

            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    if (unlink($file)) {
                        $deletedCount++;
                    }
                }
            }

            return [
                'success' => true,
                'message' => "Cleaned up {$deletedCount} old export files",
                'deleted_count' => $deletedCount
            ];

        } catch (Exception $e) {
            dol_syslog("Error cleaning temp files: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}