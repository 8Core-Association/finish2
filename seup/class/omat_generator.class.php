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
 * A3 Omat Spisa Generator for SEUP Module
 * Generates A3 format document covers with predmet information and attachments list
 */
class Omat_Generator
{
    private $db;
    private $conf;
    private $user;
    private $langs;

    public function __construct($db, $conf, $user, $langs)
    {
        $this->db = $db;
        $this->conf = $conf;
        $this->user = $user;
        $this->langs = $langs;
    }

    /**
     * Generate A3 omat spisa for predmet
     */
    public function generateOmat($predmet_id, $save_to_ecm = true)
    {
        try {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
            require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
            require_once __DIR__ . '/predmet_helper.class.php';

            // Get predmet data
            $predmetData = $this->getPredmetData($predmet_id);
            if (!$predmetData) {
                throw new Exception('Predmet not found');
            }

            // Get attachments list
            $attachments = $this->getAttachmentsList($predmet_id);

            // Create PDF instance
            $pdf = pdf_getInstance();
            $pdf->SetFont(pdf_getPDFFont($this->langs), '', 12);
            
            // Set A3 format (297 x 420 mm)
            $pdf->AddPage('P', array(297, 420));
            
            // Generate content
            $this->generatePage1($pdf, $predmetData);
            $this->generatePage2and3($pdf, $attachments);
            $this->generatePage4($pdf);

            // Generate filename
            $filename = $this->generateFilename($predmetData);
            
            if ($save_to_ecm) {
                return $this->saveToECM($pdf, $filename, $predmet_id);
            } else {
                return $this->generatePreview($pdf, $predmetData, $attachments);
            }

        } catch (Exception $e) {
            dol_syslog("Omat generation error: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get predmet data with all related information
     */
    private function getPredmetData($predmet_id)
    {
        $sql = "SELECT 
                    p.ID_predmeta,
                    p.klasa_br,
                    p.sadrzaj,
                    p.dosje_broj,
                    p.godina,
                    p.predmet_rbr,
                    p.naziv_predmeta,
                    p.tstamp_created,
                    u.name_ustanova,
                    u.code_ustanova,
                    k.ime_prezime,
                    k.rbr as korisnik_rbr,
                    k.naziv as radno_mjesto,
                    ko.opis_klasifikacijske_oznake,
                    ko.vrijeme_cuvanja
                FROM " . MAIN_DB_PREFIX . "a_predmet p
                LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
                LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON p.ID_interna_oznaka_korisnika = k.ID
                LEFT JOIN " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ko ON p.ID_klasifikacijske_oznake = ko.ID_klasifikacijske_oznake
                WHERE p.ID_predmeta = " . (int)$predmet_id;

        $resql = $this->db->query($sql);
        if ($resql && $obj = $this->db->fetch_object($resql)) {
            // Format klasa
            $obj->klasa_format = $obj->klasa_br . '-' . $obj->sadrzaj . '/' . 
                                $obj->godina . '-' . $obj->dosje_broj . '/' . 
                                $obj->predmet_rbr;
            return $obj;
        }
        
        return false;
    }

    /**
     * Get list of attachments for predmet
     */
    private function getAttachmentsList($predmet_id)
    {
        $relative_path = Predmet_helper::getPredmetFolderPath($predmet_id, $this->db);
        
        $sql = "SELECT 
                    ef.filename,
                    ef.date_c,
                    ef.label,
                    CONCAT(u.firstname, ' ', u.lastname) as created_by
                FROM " . MAIN_DB_PREFIX . "ecm_files ef
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON ef.fk_user_c = u.rowid
                WHERE ef.filepath = '" . $this->db->escape(rtrim($relative_path, '/')) . "'
                AND ef.entity = " . $this->conf->entity . "
                ORDER BY ef.date_c ASC";

        $resql = $this->db->query($sql);
        $attachments = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $attachments[] = $obj;
            }
        }
        
        return $attachments;
    }

    /**
     * Generate page 1 - Front page with basic information
     */
    private function generatePage1($pdf, $predmetData)
    {
        // Set margins for A3
        $pdf->SetMargins(20, 20, 20);
        
        // Set font with UTF-8 support for Croatian characters
        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 24);
        
        // Title
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 24);
        $pdf->Cell(0, 20, $this->encodeText('OMOT SPISA'), 0, 1, 'C');
        $pdf->Ln(20);

        // Main information sections
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 16);
        
        // Naziv tjela
        $pdf->Cell(0, 15, $this->encodeText('NAZIV TIJELA:'), 0, 1, 'L');
        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 14);
        $naziv_tjela = $this->encodeText($predmetData->name_ustanova . ' (' . $predmetData->code_ustanova . ')');
        $pdf->Cell(0, 12, $naziv_tjela, 0, 1, 'L');
        $pdf->Ln(10);

        // Oznaka unutarnje ustrojstvene jedinice
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 16);
        $pdf->Cell(0, 15, $this->encodeText('OZNAKA UNUTARNJE USTROJSTVENE JEDINICE:'), 0, 1, 'L');
        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 14);
        $unutarnja_oznaka = $this->encodeText($predmetData->ime_prezime . ' (' . $predmetData->korisnik_rbr . ') - ' . $predmetData->radno_mjesto);
        $pdf->Cell(0, 12, $unutarnja_oznaka, 0, 1, 'L');
        $pdf->Ln(10);

        // Klasifikacijska oznaka
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 16);
        $pdf->Cell(0, 15, $this->encodeText('KLASIFIKACIJSKA OZNAKA:'), 0, 1, 'L');
        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 14);
        $pdf->Cell(0, 12, $predmetData->klasa_format, 0, 1, 'L');
        if ($predmetData->opis_klasifikacijske_oznake) {
            $pdf->SetFont(pdf_getPDFFont($this->langs), 'I', 12);
            $pdf->MultiCell(0, 8, $this->encodeText($predmetData->opis_klasifikacijske_oznake), 0, 'L');
        }
        $pdf->Ln(10);

        // Predmet
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 16);
        $pdf->Cell(0, 15, $this->encodeText('PREDMET:'), 0, 1, 'L');
        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 14);
        $pdf->MultiCell(0, 10, $this->encodeText($predmetData->naziv_predmeta), 0, 'L');
        $pdf->Ln(10);

        // Datum otvaranja
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 14);
        $pdf->Cell(0, 12, $this->encodeText('DATUM OTVARANJA: ' . dol_print_date($predmetData->tstamp_created, '%d.%m.%Y')), 0, 1, 'L');
        
        // Vrijeme čuvanja
        if ($predmetData->vrijeme_cuvanja == 0) {
            $vrijeme_text = $this->encodeText('TRAJNO');
        } else {
            $vrijeme_text = $this->encodeText($predmetData->vrijeme_cuvanja . ' GODINA');
        }
        $pdf->Cell(0, 12, $this->encodeText('VRIJEME ČUVANJA: ') . $vrijeme_text, 0, 1, 'L');
    }

    /**
     * Generate pages 2 and 3 - Attachments list
     */
    private function generatePage2and3($pdf, $attachments)
    {
        // Add new page for attachments
        $pdf->AddPage('P', array(297, 420));
        
        // Title
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 20);
        $pdf->Cell(0, 15, $this->encodeText('POPIS PRIVITAKA'), 0, 1, 'C');
        $pdf->Ln(10);

        if (empty($attachments)) {
            $pdf->SetFont(pdf_getPDFFont($this->langs), 'I', 14);
            $pdf->Cell(0, 12, $this->encodeText('Nema privitaka'), 0, 1, 'C');
            return;
        }

        // Table header
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 12);
        $pdf->Cell(20, 10, $this->encodeText('Rb.'), 1, 0, 'C');
        $pdf->Cell(180, 10, $this->encodeText('Opis'), 1, 0, 'C');
        $pdf->Cell(50, 10, $this->encodeText('Datum dodavanja'), 1, 1, 'C');

        // Table content
        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 11);
        $rb = 1;
        
        foreach ($attachments as $attachment) {
            // Check if we need a new page
            if ($pdf->GetY() > 380) { // Near bottom of A3 page
                $pdf->AddPage('P', array(297, 420));
                
                // Repeat header on new page
                $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 12);
                $pdf->Cell(20, 10, $this->encodeText('Rb.'), 1, 0, 'C');
                $pdf->Cell(180, 10, $this->encodeText('Opis'), 1, 0, 'C');
                $pdf->Cell(50, 10, $this->encodeText('Datum dodavanja'), 1, 1, 'C');
                $pdf->SetFont(pdf_getPDFFont($this->langs), '', 11);
            }

            $datum_formatted = dol_print_date($attachment->date_c, '%d.%m.%Y');
            
            // Calculate row height based on description length
            $desc_lines = $pdf->GetStringWidth($attachment->filename) > 170 ? 2 : 1;
            $row_height = $desc_lines * 8;

            $pdf->Cell(20, $row_height, $rb, 1, 0, 'C');
            
            // Multi-line description if needed
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->MultiCell(180, 8, $this->encodeText($attachment->filename), 1, 'L');
            $pdf->SetXY($x + 180, $y);
            
            $pdf->Cell(50, $row_height, $datum_formatted, 1, 1, 'C');
            
            $rb++;
        }
    }

    /**
     * Generate page 4 - Empty back page
     */
    private function generatePage4($pdf)
    {
        // Add new page for back cover
        $pdf->AddPage('P', array(297, 420));
        
        // For now, just add a small footer
        $pdf->SetY(-30);
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'I', 10);
        $pdf->Cell(0, 10, $this->encodeText('Generirano: ' . dol_print_date(dol_now(), '%d.%m.%Y %H:%M')), 0, 1, 'C');
    }

    /**
     * Encode text for proper Croatian character display in PDF
     */
    private function encodeText($text)
    {
        // Ensure UTF-8 encoding
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        // Alternative: Manual character replacement if font doesn't support UTF-8
        // Uncomment if needed:
        /*
        $croatian_chars = [
            'č' => 'c', 'ć' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z',
            'Č' => 'C', 'Ć' => 'C', 'Đ' => 'D', 'Š' => 'S', 'Ž' => 'Z'
        ];
        $text = strtr($text, $croatian_chars);
        */
        
        return $text;
    }

    /**
     * Save PDF to ECM as attachment
     */
    private function saveToECM($pdf, $filename, $predmet_id)
    {
        try {
            // Get predmet folder path
            $relative_path = Predmet_helper::getPredmetFolderPath($predmet_id, $this->db);
            $full_path = DOL_DATA_ROOT . '/ecm/' . $relative_path;
            
            // Ensure directory exists
            if (!is_dir($full_path)) {
                dol_mkdir($full_path);
            }
            
            // Save PDF file
            $filepath = $full_path . $filename;
            $pdf->Output($filepath, 'F');
            
            // Create ECM record
            $ecmfile = new EcmFiles($this->db);
            $ecmfile->filepath = rtrim($relative_path, '/');
            $ecmfile->filename = $filename;
            $ecmfile->label = 'Omot spisa - ' . $filename;
            $ecmfile->entity = $this->conf->entity;
            $ecmfile->gen_or_uploaded = 'generated';
            $ecmfile->description = 'Automatski generirani omot spisa za predmet ' . $predmet_id;
            $ecmfile->fk_user_c = $this->user->id;
            $ecmfile->fk_user_m = $this->user->id;
            $ecmfile->date_c = dol_now();
            $ecmfile->date_m = dol_now();
            
            $result = $ecmfile->create($this->user);
            if ($result > 0) {
                dol_syslog("Omot spisa saved to ECM: " . $filename, LOG_INFO);
                
                return [
                    'success' => true,
                    'message' => 'Omot spisa je uspješno kreiran i dodan u privitak',
                    'filename' => $filename,
                    'ecm_id' => $result,
                    'download_url' => DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($relative_path . $filename)
                ];
            } else {
                throw new Exception('Failed to create ECM record: ' . $ecmfile->error);
            }

        } catch (Exception $e) {
            dol_syslog("Error saving omot to ECM: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate preview data for modal
     */
    public function generatePreview($predmet_id)
    {
        try {
            $predmetData = $this->getPredmetData($predmet_id);
            if (!$predmetData) {
                throw new Exception('Predmet not found');
            }

            $attachments = $this->getAttachmentsList($predmet_id);

            return [
                'success' => true,
                'predmet' => $predmetData,
                'attachments' => $attachments,
                'preview_html' => $this->generatePreviewHTML($predmetData, $attachments)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate HTML preview for modal
     */
    private function generatePreviewHTML($predmetData, $attachments)
    {
        $html = '<div class="seup-omat-preview">';
        
        // Page 1 preview
        $html .= '<div class="seup-omat-page">';
        $html .= '<h3 class="seup-omat-title">OMOT SPISA</h3>';
        
        $html .= '<div class="seup-omat-section">';
        $html .= '<h4>NAZIV TIJELA:</h4>';
        $html .= '<p>' . htmlspecialchars($predmetData->name_ustanova . ' (' . $predmetData->code_ustanova . ')') . '</p>';
        $html .= '</div>';
        
        $html .= '<div class="seup-omat-section">';
        $html .= '<h4>OZNAKA UNUTARNJE USTROJSTVENE JEDINICE:</h4>';
        $html .= '<p>' . htmlspecialchars($predmetData->ime_prezime . ' (' . $predmetData->korisnik_rbr . ') - ' . $predmetData->radno_mjesto) . '</p>';
        $html .= '</div>';
        
        $html .= '<div class="seup-omat-section">';
        $html .= '<h4>KLASIFIKACIJSKA OZNAKA:</h4>';
        $html .= '<p>' . htmlspecialchars($predmetData->klasa_format) . '</p>';
        if ($predmetData->opis_klasifikacijske_oznake) {
            $html .= '<p class="seup-omat-desc">' . htmlspecialchars($predmetData->opis_klasifikacijske_oznake) . '</p>';
        }
        $html .= '</div>';
        
        $html .= '<div class="seup-omat-section">';
        $html .= '<h4>PREDMET:</h4>';
        $html .= '<p>' . htmlspecialchars($predmetData->naziv_predmeta) . '</p>';
        $html .= '</div>';
        
        $html .= '<div class="seup-omat-meta">';
        $html .= '<p><strong>Datum otvaranja:</strong> ' . dol_print_date($predmetData->tstamp_created, '%d.%m.%Y') . '</p>';
        $vrijeme_text = ($predmetData->vrijeme_cuvanja == 0) ? 'TRAJNO' : $predmetData->vrijeme_cuvanja . ' GODINA';
        $html .= '<p><strong>Vrijeme čuvanja:</strong> ' . $vrijeme_text . '</p>';
        $html .= '</div>';
        
        $html .= '</div>'; // seup-omat-page
        
        // Attachments preview
        $html .= '<div class="seup-omat-page">';
        $html .= '<h3 class="seup-omat-title">POPIS PRIVITAKA</h3>';
        
        if (empty($attachments)) {
            $html .= '<p class="seup-omat-empty">Nema privitaka</p>';
        } else {
            $html .= '<table class="seup-omat-table">';
            $html .= '<thead>';
            $html .= '<tr><th>Rb.</th><th>Opis</th><th>Datum dodavanja</th></tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            
            foreach ($attachments as $index => $attachment) {
                $html .= '<tr>';
                $html .= '<td>' . ($index + 1) . '</td>';
                $html .= '<td>' . htmlspecialchars($attachment->filename) . '</td>';
                $html .= '<td>' . dol_print_date($attachment->date_c, '%d.%m.%Y') . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody>';
            $html .= '</table>';
        }
        
        $html .= '</div>'; // seup-omat-page
        
        $html .= '</div>'; // seup-omat-preview
        
        return $html;
    }

    /**
     * Generate filename for omat
     */
    private function generateFilename($predmetData)
    {
        $klasa_safe = str_replace('/', '_', $predmetData->klasa_format);
        $datum = dol_print_date(dol_now(), '%Y%m%d_%H%M%S');
        
        return 'Omot_' . $klasa_safe . '_' . $datum . '.pdf';
    }

    /**
     * Get omot statistics
     */
    public static function getOmotStatistics($db, $conf)
    {
        try {
            $stats = [
                'total_omoti' => 0,
                'generated_today' => 0,
                'generated_this_month' => 0
            ];

            // Count total generated omoti
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filename LIKE 'Omot_%'
                    AND filepath LIKE 'SEUP%'
                    AND entity = " . $conf->entity;
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['total_omoti'] = (int)$obj->count;
            }

            // Count generated today
            $today = dol_print_date(dol_now(), '%Y-%m-%d');
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filename LIKE 'Omot_%'
                    AND filepath LIKE 'SEUP%'
                    AND DATE(FROM_UNIXTIME(date_c)) = '" . $today . "'
                    AND entity = " . $conf->entity;
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['generated_today'] = (int)$obj->count;
            }

            // Count generated this month
            $month = dol_print_date(dol_now(), '%Y-%m');
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filename LIKE 'Omot_%'
                    AND filepath LIKE 'SEUP%'
                    AND DATE_FORMAT(FROM_UNIXTIME(date_c), '%Y-%m') = '" . $month . "'
                    AND entity = " . $conf->entity;
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['generated_this_month'] = (int)$obj->count;
            }

            return $stats;

        } catch (Exception $e) {
            dol_syslog("Error getting omot statistics: " . $e->getMessage(), LOG_ERR);
            return null;
        }
    }
}