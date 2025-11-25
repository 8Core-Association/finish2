<?php

/**
 * Digital Signature Detector for SEUP Module
 * Detects and validates digital signatures in PDF documents
 * (c) 2025 8Core Association
 */

class Digital_Signature_Detector
{
    /**
     * Ensure digital signature columns exist in ECM table
     */
    public static function ensureDigitalSignatureColumns($db)
    {
        try {
            // Check if digital_signature column exists
            $sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "ecm_files LIKE 'digital_signature'";
            $result = $db->query($sql);
            
            if ($db->num_rows($result) == 0) {
                dol_syslog("Adding digital signature columns to ecm_files table", LOG_INFO);
                
                // Add digital signature columns
                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "ecm_files 
                        ADD COLUMN digital_signature TINYINT(1) DEFAULT 0 COMMENT 'Has digital signature',
                        ADD COLUMN signature_info JSON DEFAULT NULL COMMENT 'Signature metadata',
                        ADD COLUMN signature_date DATETIME DEFAULT NULL COMMENT 'Signature date',
                        ADD COLUMN signer_name VARCHAR(255) DEFAULT NULL COMMENT 'Signer name',
                        ADD COLUMN signature_status ENUM('valid','invalid','expired','unknown') DEFAULT 'unknown' COMMENT 'Signature validation status'";
                
                $result = $db->query($sql);
                if ($result) {
                    dol_syslog("Digital signature columns added successfully", LOG_INFO);
                    return true;
                } else {
                    dol_syslog("Failed to add digital signature columns: " . $db->lasterror(), LOG_ERR);
                    return false;
                }
            }
            
            return true; // Columns already exist
            
        } catch (Exception $e) {
            dol_syslog("Error ensuring digital signature columns: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }

    /**
     * Detect digital signature in PDF file
     */
    public static function detectPDFSignature($filePath)
    {
        try {
            if (!file_exists($filePath)) {
                return [
                    'has_signature' => false,
                    'error' => 'File not found'
                ];
            }

            // Read PDF content
            $pdfContent = file_get_contents($filePath);
            if ($pdfContent === false) {
                return [
                    'has_signature' => false,
                    'error' => 'Cannot read file'
                ];
            }

            // Check if it's a PDF file
            if (strpos($pdfContent, '%PDF-') !== 0) {
                return [
                    'has_signature' => false,
                    'error' => 'Not a PDF file'
                ];
            }

            // Look for signature indicators
            $hasSignature = false;
            $signatureInfo = [];

            // Check for /ByteRange and /Contents (standard PDF signature)
            if (preg_match('/\/ByteRange\s*\[([^\]]+)\]/', $pdfContent, $byteRangeMatch) &&
                preg_match('/\/Contents\s*<([^>]+)>/', $pdfContent, $contentsMatch)) {
                
                $hasSignature = true;
                $signatureInfo['type'] = 'PDF Digital Signature';
                $signatureInfo['byte_range'] = trim($byteRangeMatch[1]);
                $signatureInfo['contents_length'] = strlen($contentsMatch[1]) / 2; // Hex to bytes
                
                dol_syslog("PDF signature detected - ByteRange: " . $signatureInfo['byte_range'], LOG_INFO);
            }

            // Check for Adobe signature fields
            if (preg_match('/\/Type\s*\/Sig/', $pdfContent)) {
                $hasSignature = true;
                $signatureInfo['adobe_signature'] = true;
                dol_syslog("Adobe signature field detected", LOG_INFO);
            }

            // Extract signer information from certificate data
            $signerInfo = self::extractSignerInfo($pdfContent);
            if ($signerInfo) {
                $signatureInfo = array_merge($signatureInfo, $signerInfo);
                dol_syslog("Signer info extracted: " . json_encode($signerInfo), LOG_INFO);
            }

            // Extract signature date
            $signatureDate = self::extractSignatureDate($pdfContent);
            if ($signatureDate) {
                $signatureInfo['signature_date'] = $signatureDate;
                dol_syslog("Signature date extracted: " . $signatureDate, LOG_INFO);
            }

            // Validate FINA certificate if present
            if (isset($signatureInfo['issuer']) && 
                (strpos($signatureInfo['issuer'], 'Financijska agencija') !== false ||
                 strpos($signatureInfo['issuer'], 'FINA') !== false)) {
                $signatureInfo['ca_type'] = 'FINA';
                $signatureInfo['is_qualified'] = true;
                dol_syslog("FINA certificate detected", LOG_INFO);
            }

            dol_syslog("Final signature detection result: " . json_encode([
                'has_signature' => $hasSignature,
                'file' => basename($filePath)
            ]), LOG_INFO);

            return [
                'has_signature' => $hasSignature,
                'signature_info' => $signatureInfo,
                'file_size' => filesize($filePath),
                'scan_date' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            dol_syslog("Error detecting PDF signature: " . $e->getMessage(), LOG_ERR);
            return [
                'has_signature' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract signer information from PDF certificate data
     */
    private static function extractSignerInfo($pdfContent)
    {
        $signerInfo = [];

        // Look for common name (CN) in certificate - more specific pattern
        if (preg_match('/CN=([^,\n\r\)]+)/', $pdfContent, $cnMatch)) {
            $signerInfo['signer_name'] = trim($cnMatch[1]);
            dol_syslog("Extracted signer name: " . $signerInfo['signer_name'], LOG_DEBUG);
        }

        // Look for organization (O) in certificate - more specific pattern
        if (preg_match('/O=([^,\n\r\)]+)/', $pdfContent, $orgMatch)) {
            $signerInfo['organization'] = trim($orgMatch[1]);
        }

        // Look for issuer information - improved pattern
        if (preg_match('/Issuer.*?CN=([^,\n\r\)]+)/', $pdfContent, $issuerMatch)) {
            $signerInfo['issuer'] = trim($issuerMatch[1]);
        } elseif (preg_match('/Fina\s+RDC/', $pdfContent)) {
            $signerInfo['issuer'] = 'Fina RDC 2020';
            dol_syslog("FINA issuer detected via pattern match", LOG_DEBUG);
        }

        // Look for email in certificate - improved pattern
        if (preg_match('/emailAddress=([^,\s\n\r\)]+)/', $pdfContent, $emailMatch)) {
            $signerInfo['email'] = trim($emailMatch[1]);
        }

        // Look for serial number - improved pattern
        if (preg_match('/serialNumber=([^,\s\n\r\)]+)/', $pdfContent, $serialMatch)) {
            $signerInfo['serial_number'] = trim($serialMatch[1]);
        }

        dol_syslog("Extracted signer info: " . json_encode($signerInfo), LOG_DEBUG);
        return empty($signerInfo) ? null : $signerInfo;
    }

    /**
     * Extract signature date from PDF
     */
    private static function extractSignatureDate($pdfContent)
    {
        // Look for signature date in various formats
        $datePatterns = [
            '/\/M\s*\(D:(\d{14})/',  // PDF date format (simplified)
            '/signingTime.*?(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/',  // ISO format
            '/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/'  // Standard datetime
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $pdfContent, $dateMatch)) {
                $dateStr = $dateMatch[1];
                
                // Convert PDF date format to standard format
                if (strlen($dateStr) === 14 && is_numeric($dateStr)) {
                    // PDF date format: YYYYMMDDHHMMSS
                    $year = substr($dateStr, 0, 4);
                    $month = substr($dateStr, 4, 2);
                    $day = substr($dateStr, 6, 2);
                    $hour = substr($dateStr, 8, 2);
                    $minute = substr($dateStr, 10, 2);
                    $second = substr($dateStr, 12, 2);
                    
                    return "$year-$month-$day $hour:$minute:$second";
                }
                
                return $dateStr;
            }
        }

        return null;
    }

    /**
     * Update ECM file with signature information
     */
    public static function updateECMFileSignature($db, $ecmFileId, $signatureData)
    {
        try {
            $hasSignature = $signatureData['has_signature'] ? 1 : 0;
            $signatureInfo = isset($signatureData['signature_info']) ? 
                json_encode($signatureData['signature_info'], JSON_UNESCAPED_UNICODE) : null;
            
            $signerName = null;
            $signatureDate = null;
            $signatureStatus = $hasSignature ? 'valid' : 'unknown';

            if ($hasSignature && isset($signatureData['signature_info'])) {
                $info = $signatureData['signature_info'];
                $signerName = $info['signer_name'] ?? null;
                
                // Properly format signature date for MySQL
                if (isset($info['signature_date'])) {
                    $dateStr = $info['signature_date'];
                    // Validate date format before inserting
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateStr)) {
                        $signatureDate = $dateStr;
                    } else {
                        dol_syslog("Invalid signature date format: " . $dateStr, LOG_WARNING);
                        $signatureDate = null;
                    }
                }
                
                // Determine signature status
                if (isset($info['ca_type']) && $info['ca_type'] === 'FINA') {
                    $signatureStatus = 'valid';
                } elseif (isset($info['issuer'])) {
                    $signatureStatus = 'valid';
                }
            }

            $sql = "UPDATE " . MAIN_DB_PREFIX . "ecm_files SET 
                    digital_signature = " . $hasSignature . ",
                    signature_info = " . ($signatureInfo ? "'" . $db->escape($signatureInfo) . "'" : "NULL") . ",
                    signature_date = " . ($signatureDate ? "'" . $db->escape($signatureDate) . "'" : "NULL") . ",
                    signer_name = " . ($signerName ? "'" . $db->escape($signerName) . "'" : "NULL") . ",
                    signature_status = '" . $db->escape($signatureStatus) . "'
                    WHERE rowid = " . (int)$ecmFileId;

            $result = $db->query($sql);
            if ($result) {
                dol_syslog("Updated ECM file signature info for ID: $ecmFileId", LOG_INFO);
                return true;
            } else {
                dol_syslog("Failed to update ECM file signature: " . $db->lasterror(), LOG_ERR);
                return false;
            }

        } catch (Exception $e) {
            dol_syslog("Error updating ECM file signature: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }

    /**
     * Scan single file for digital signature
     */
    public static function scanFileSignature($db, $conf, $ecmFileId)
    {
        try {
            // Get file information from ECM
            $sql = "SELECT filepath, filename FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE rowid = " . (int)$ecmFileId;
            $result = $db->query($sql);
            
            if (!$result || $db->num_rows($result) == 0) {
                return ['success' => false, 'error' => 'ECM file not found'];
            }

            $file = $db->fetch_object($result);
            $fullPath = DOL_DATA_ROOT . '/ecm/' . $file->filepath . '/' . $file->filename;

            // Only scan PDF files
            $extension = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
            if ($extension !== 'pdf') {
                return ['success' => true, 'message' => 'Not a PDF file', 'has_signature' => false];
            }

            // Detect signature
            $signatureData = self::detectPDFSignature($fullPath);
            
            // Update database
            $updated = self::updateECMFileSignature($db, $ecmFileId, $signatureData);
            
            return [
                'success' => true,
                'has_signature' => $signatureData['has_signature'],
                'signature_info' => $signatureData['signature_info'] ?? null,
                'updated' => $updated
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk scan all PDF files in SEUP folders for signatures
     */
    public static function bulkScanSignatures($db, $conf, $limit = 50)
    {
        try {
            // Ensure columns exist first
            self::ensureDigitalSignatureColumns($db);

            // Get PDF files that haven't been scanned yet
            $sql = "SELECT rowid, filepath, filename FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filepath LIKE 'SEUP%' 
                    AND filename LIKE '%.pdf'
                    AND (digital_signature IS NULL OR signature_info IS NULL)
                    AND entity = " . $conf->entity . "
                    LIMIT " . (int)$limit;

            $result = $db->query($sql);
            $scannedFiles = 0;
            $signaturesFound = 0;
            $errors = [];

            if ($result) {
                while ($file = $db->fetch_object($result)) {
                    $fullPath = DOL_DATA_ROOT . '/ecm/' . $file->filepath . '/' . $file->filename;
                    
                    if (file_exists($fullPath)) {
                        $signatureData = self::detectPDFSignature($fullPath);
                        $updated = self::updateECMFileSignature($db, $file->rowid, $signatureData);
                        
                        if ($updated) {
                            $scannedFiles++;
                            if ($signatureData['has_signature']) {
                                $signaturesFound++;
                            }
                        } else {
                            $errors[] = "Failed to update: " . $file->filename;
                        }
                    } else {
                        $errors[] = "File not found: " . $file->filename;
                    }
                    
                    // Small delay to prevent server overload
                    usleep(100000); // 0.1 second
                }
            }

            return [
                'success' => true,
                'scanned_files' => $scannedFiles,
                'signatures_found' => $signaturesFound,
                'errors' => $errors,
                'message' => "Scanned {$scannedFiles} files, found {$signaturesFound} signatures"
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get signature statistics
     */
    public static function getSignatureStatistics($db, $conf)
    {
        try {
            $stats = [
                'total_pdfs' => 0,
                'signed_pdfs' => 0,
                'fina_signatures' => 0,
                'valid_signatures' => 0,
                'expired_signatures' => 0,
                'unknown_signatures' => 0
            ];

            // Total PDF files
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filepath LIKE 'SEUP%' 
                    AND filename LIKE '%.pdf'
                    AND entity = " . $conf->entity;
            $result = $db->query($sql);
            if ($result && $obj = $db->fetch_object($result)) {
                $stats['total_pdfs'] = (int)$obj->count;
            }

            // Signed PDF files
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filepath LIKE 'SEUP%' 
                    AND filename LIKE '%.pdf'
                    AND digital_signature = 1
                    AND entity = " . $conf->entity;
            $result = $db->query($sql);
            if ($result && $obj = $db->fetch_object($result)) {
                $stats['signed_pdfs'] = (int)$obj->count;
            }

            // FINA signatures
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filepath LIKE 'SEUP%' 
                    AND filename LIKE '%.pdf'
                    AND digital_signature = 1
                    AND signature_info LIKE '%FINA%'
                    AND entity = " . $conf->entity;
            $result = $db->query($sql);
            if ($result && $obj = $db->fetch_object($result)) {
                $stats['fina_signatures'] = (int)$obj->count;
            }

            // Signature status counts
            $statusCounts = ['valid', 'invalid', 'expired', 'unknown'];
            foreach ($statusCounts as $status) {
                $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                        WHERE filepath LIKE 'SEUP%' 
                        AND filename LIKE '%.pdf'
                        AND digital_signature = 1
                        AND signature_status = '" . $status . "'
                        AND entity = " . $conf->entity;
                $result = $db->query($sql);
                if ($result && $obj = $db->fetch_object($result)) {
                    $stats[$status . '_signatures'] = (int)$obj->count;
                }
            }

            return $stats;

        } catch (Exception $e) {
            dol_syslog("Error getting signature statistics: " . $e->getMessage(), LOG_ERR);
            return null;
        }
    }

    /**
     * Get signature badge HTML for document list
     */
    public static function getSignatureBadge($hasSignature, $signatureStatus = 'unknown', $signerName = null)
    {
        if (!$hasSignature) {
            return '<span class="seup-signature-none"><i class="fas fa-minus-circle"></i> Nije potpisan</span>';
        }

        $badgeClass = 'seup-signature-badge';
        $icon = 'fas fa-certificate';
        $title = 'Digitalno potpisan dokument';
        $text = 'Potpisan';

        switch ($signatureStatus) {
            case 'valid':
                $badgeClass .= ' seup-signature-valid';
                $icon = 'fas fa-certificate';
                $title = 'Valjan digitalni potpis';
                $text = 'Potpisan';
                if ($signerName) {
                    $title .= ' - ' . $signerName;
                    $text = 'Potpisan - ' . $signerName;
                }
                break;
            case 'invalid':
                $badgeClass .= ' seup-signature-invalid';
                $icon = 'fas fa-exclamation-triangle';
                $title = 'Nevaljan digitalni potpis';
                $text = 'Nevaljan';
                break;
            case 'expired':
                $badgeClass .= ' seup-signature-expired';
                $icon = 'fas fa-clock';
                $title = 'Istekao digitalni potpis';
                $text = 'Istekao';
                break;
            default:
                $badgeClass .= ' seup-signature-unknown';
                $icon = 'fas fa-question-circle';
                $title = 'Nepoznat status potpisa';
                $text = 'Nepoznato';
                break;
        }

        return '<span class="' . $badgeClass . '" title="' . htmlspecialchars($title) . '">' .
               '<i class="' . $icon . '"></i> ' . $text .
               '</span>';
    }

    /**
     * Auto-scan file for signature when uploading
     */
    public static function autoScanOnUpload($db, $conf, $filePath, $ecmFileId)
    {
        // Only scan PDF files
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return ['success' => true, 'message' => 'Not a PDF file'];
        }

        // Ensure columns exist
        self::ensureDigitalSignatureColumns($db);

        // Detect signature
        $signatureData = self::detectPDFSignature($filePath);
        
        // Update database
        $updated = self::updateECMFileSignature($db, $ecmFileId, $signatureData);
        
        if ($signatureData['has_signature']) {
            dol_syslog("Digital signature detected in uploaded file: " . basename($filePath), LOG_INFO);
        }

        return [
            'success' => true,
            'has_signature' => $signatureData['has_signature'],
            'signature_info' => $signatureData['signature_info'] ?? null,
            'updated' => $updated
        ];
    }

    /**
     * Validate signature against known CAs
     */
    public static function validateSignature($signatureInfo)
    {
        if (!$signatureInfo || !is_array($signatureInfo)) {
            return 'unknown';
        }

        // Check for FINA (Croatian qualified certificates)
        if (isset($signatureInfo['ca_type']) && $signatureInfo['ca_type'] === 'FINA') {
            return 'valid';
        }

        // Check for other known CAs
        $trustedCAs = [
            'Financijska agencija',
            'FINA',
            'Adobe',
            'DocuSign',
            'GlobalSign',
            'DigiCert'
        ];

        if (isset($signatureInfo['issuer'])) {
            foreach ($trustedCAs as $ca) {
                if (stripos($signatureInfo['issuer'], $ca) !== false) {
                    return 'valid';
                }
            }
        }

        return 'unknown';
    }

    /**
     * Get detailed signature information for display
     */
    public static function getSignatureDetails($signatureInfo)
    {
        if (!$signatureInfo) {
            return null;
        }

        if (is_string($signatureInfo)) {
            $signatureInfo = json_decode($signatureInfo, true);
        }

        if (!is_array($signatureInfo)) {
            return null;
        }

        $details = [];
        
        if (isset($signatureInfo['signer_name'])) {
            $details['Potpisnik'] = $signatureInfo['signer_name'];
        }
        
        if (isset($signatureInfo['organization'])) {
            $details['Organizacija'] = $signatureInfo['organization'];
        }
        
        if (isset($signatureInfo['issuer'])) {
            $details['Izdavatelj'] = $signatureInfo['issuer'];
        }
        
        if (isset($signatureInfo['signature_date'])) {
            $details['Datum potpisa'] = $signatureInfo['signature_date'];
        }
        
        if (isset($signatureInfo['ca_type'])) {
            $details['Tip certifikata'] = $signatureInfo['ca_type'];
        }
        
        if (isset($signatureInfo['is_qualified']) && $signatureInfo['is_qualified']) {
            $details['Kvalificirani'] = 'Da';
        }

        return $details;
    }
}