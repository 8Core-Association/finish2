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
     * Get list of acts with attachments, shipments and receipts for predmet
     */
    private function getAttachmentsList($predmet_id)
    {
        $akti = [];

        // Get all acts for this predmet
        $sql_akti = "SELECT a.ID_akta, a.urb_broj, a.datum_kreiranja
                     FROM " . MAIN_DB_PREFIX . "a_akti a
                     WHERE a.ID_predmeta = " . (int)$predmet_id . "
                     ORDER BY a.urb_broj ASC";

        $res_akti = $this->db->query($sql_akti);
        if ($res_akti) {
            while ($akt = $this->db->fetch_object($res_akti)) {
                $akt_data = [
                    'ID_akta' => $akt->ID_akta,
                    'urb_broj' => $akt->urb_broj,
                    'datum_kreiranja' => $akt->datum_kreiranja,
                    'prilozi' => [],
                    'otpreme' => [],
                    'zaprimanja' => []
                ];

                // Get prilozi for this akt
                $sql_prilozi = "SELECT pr.prilog_rbr, pr.ID_priloga
                               FROM " . MAIN_DB_PREFIX . "a_prilozi pr
                               WHERE pr.ID_akta = " . (int)$akt->ID_akta . "
                               ORDER BY pr.prilog_rbr ASC";
                $res_prilozi = $this->db->query($sql_prilozi);
                if ($res_prilozi) {
                    while ($prilog = $this->db->fetch_object($res_prilozi)) {
                        $akt_data['prilozi'][] = $prilog;
                    }
                }

                // Get otpreme for this akt (via fk_ecm_file from akt)
                $sql_akt_ecm = "SELECT fk_ecm_file FROM " . MAIN_DB_PREFIX . "a_akti WHERE ID_akta = " . (int)$akt->ID_akta;
                $res_akt_ecm = $this->db->query($sql_akt_ecm);
                if ($res_akt_ecm && $akt_ecm = $this->db->fetch_object($res_akt_ecm)) {
                    $sql_otpreme = "SELECT o.primatelj_naziv, o.datum_otpreme, o.nacin_otpreme
                                   FROM " . MAIN_DB_PREFIX . "a_otprema o
                                   WHERE o.fk_ecm_file = " . (int)$akt_ecm->fk_ecm_file . "
                                   ORDER BY o.datum_otpreme ASC";
                    $res_otpreme = $this->db->query($sql_otpreme);
                    if ($res_otpreme) {
                        while ($otprema = $this->db->fetch_object($res_otpreme)) {
                            $akt_data['otpreme'][] = $otprema;
                        }
                    }

                    // Get zaprimanja for this akt
                    $sql_zaprimanja = "SELECT z.posiljatelj_naziv, z.datum_zaprimanja, z.nacin_zaprimanja
                                      FROM llx_a_zaprimanja z
                                      WHERE z.fk_ecm_file = " . (int)$akt_ecm->fk_ecm_file . "
                                      ORDER BY z.datum_zaprimanja ASC";
                    $res_zaprimanja = $this->db->query($sql_zaprimanja);
                    if ($res_zaprimanja) {
                        while ($zaprimanje = $this->db->fetch_object($res_zaprimanja)) {
                            $akt_data['zaprimanja'][] = $zaprimanje;
                        }
                    }
                }

                $akti[] = $akt_data;
            }
        }

        return $akti;
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
        $pdf->Cell(0, 15, $this->encodeText('NAZIV TIJELA'), 0, 1, 'L');
        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 14);
        $naziv_tjela = $this->encodeText($predmetData->name_ustanova);
        $pdf->Cell(0, 12, $naziv_tjela, 0, 1, 'L');
        $pdf->Ln(10);

        // Oznaka unutarnje ustrojstvene jedinice
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 16);
        $pdf->Cell(0, 15, $this->encodeText('OZNAKA UNUTARNJE USTROJSTVENE JEDINICE:'), 0, 1, 'L');
        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 14);
        $unutarnja_oznaka = $this->encodeText($predmetData->korisnik_rbr . ' "' . $predmetData->radno_mjesto . '"');
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
     * Generate pages 2 and 3 - Acts list with attachments, shipments and receipts
     */
    private function generatePage2and3($pdf, $akti)
    {
        // Add new page for content
        $pdf->AddPage('P', array(297, 420));

        // Title
        $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 20);
        $pdf->Cell(0, 15, $this->encodeText('POPIS SADRŽAJA'), 0, 1, 'C');
        $pdf->Ln(10);

        if (empty($akti)) {
            $pdf->SetFont(pdf_getPDFFont($this->langs), 'I', 14);
            $pdf->Cell(0, 12, $this->encodeText('Nema akata'), 0, 1, 'C');
            return;
        }

        // Get predmet data for building akt number
        $predmet_id = 0;
        if (!empty($akti)) {
            $sql_predmet = "SELECT p.ID_predmeta, u.code_ustanova, io.rbr as korisnik_rbr, p.godina
                           FROM " . MAIN_DB_PREFIX . "a_akti a
                           LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet p ON a.ID_predmeta = p.ID_predmeta
                           LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
                           LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika io ON p.ID_interna_oznaka_korisnika = io.ID
                           WHERE a.ID_akta = " . (int)$akti[0]['ID_akta'];
            $res_predmet = $this->db->query($sql_predmet);
            if ($res_predmet && $predmet_obj = $this->db->fetch_object($res_predmet)) {
                $code_ustanova = $predmet_obj->code_ustanova;
                $korisnik_rbr = $predmet_obj->korisnik_rbr;
                $godina = $predmet_obj->godina;
            }
        }

        $pdf->SetFont(pdf_getPDFFont($this->langs), '', 11);

        foreach ($akti as $akt_data) {
            // Check if we need a new page
            if ($pdf->GetY() > 380) {
                $pdf->AddPage('P', array(297, 420));
            }

            // Format akt number: [Ustanova]-[Zaposlenik]-[Godina]-[Akt]
            $akt_broj = $code_ustanova . '-' . $korisnik_rbr . '-' . $godina . '-' . $akt_data['urb_broj'];

            // Print akt number (bold)
            $pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 12);
            $pdf->Cell(0, 8, $this->encodeText($akt_broj), 0, 1, 'L');

            $pdf->SetFont(pdf_getPDFFont($this->langs), '', 10);

            // Print prilozi
            if (!empty($akt_data['prilozi'])) {
                foreach ($akt_data['prilozi'] as $prilog) {
                    $pdf->Cell(10, 6, '', 0, 0, 'L');
                    $pdf->Cell(0, 6, $this->encodeText('- Prilog rb: ' . $prilog->prilog_rbr), 0, 1, 'L');
                }
            }

            // Print otpreme
            if (!empty($akt_data['otpreme'])) {
                $pdf->Cell(10, 6, '', 0, 0, 'L');
                $pdf->Cell(0, 6, $this->encodeText('- Otpreme:'), 0, 1, 'L');
                foreach ($akt_data['otpreme'] as $otprema) {
                    $pdf->Cell(15, 5, '', 0, 0, 'L');
                    $datum_otp = dol_print_date($otprema->datum_otpreme, '%d.%m.%Y');
                    $pdf->Cell(0, 5, $this->encodeText($otprema->primatelj_naziv . ' (' . $datum_otp . ')'), 0, 1, 'L');
                }
            }

            // Print zaprimanja
            if (!empty($akt_data['zaprimanja'])) {
                $pdf->Cell(10, 6, '', 0, 0, 'L');
                $pdf->Cell(0, 6, $this->encodeText('- Zaprimanja:'), 0, 1, 'L');
                foreach ($akt_data['zaprimanja'] as $zaprimanje) {
                    $pdf->Cell(15, 5, '', 0, 0, 'L');
                    $datum_zap = dol_print_date($zaprimanje->datum_zaprimanja, '%d.%m.%Y %H:%M');
                    $pdf->Cell(0, 5, $this->encodeText($zaprimanje->posiljatelj_naziv . ' (' . $datum_zap . ')'), 0, 1, 'L');
                }
            }

            // Add spacing between acts
            $pdf->Ln(4);
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
        $html .= '<h4>NAZIV TIJELA</h4>';
        $html .= '<p>' . htmlspecialchars($predmetData->name_ustanova) . '</p>';
        $html .= '</div>';

        $html .= '<div class="seup-omat-section">';
        $html .= '<h4>OZNAKA UNUTARNJE USTROJSTVENE JEDINICE:</h4>';
        $html .= '<p>' . htmlspecialchars($predmetData->korisnik_rbr . ' "' . $predmetData->radno_mjesto . '"') . '</p>';
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
        
        // Acts list preview
        $html .= '<div class="seup-omat-page">';
        $html .= '<h3 class="seup-omat-title">POPIS SADRŽAJA</h3>';

        if (empty($attachments)) {
            $html .= '<p class="seup-omat-empty">Nema akata</p>';
        } else {
            // Get code_ustanova, korisnik_rbr, godina for akt numbering
            $code_ustanova = '';
            $korisnik_rbr = '';
            $godina = '';
            if (!empty($attachments)) {
                $sql_predmet = "SELECT u.code_ustanova, io.rbr as korisnik_rbr, p.godina
                               FROM " . MAIN_DB_PREFIX . "a_akti a
                               LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet p ON a.ID_predmeta = p.ID_predmeta
                               LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
                               LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika io ON p.ID_interna_oznaka_korisnika = io.ID
                               WHERE a.ID_akta = " . (int)$attachments[0]['ID_akta'];
                $res_predmet = $this->db->query($sql_predmet);
                if ($res_predmet && $predmet_obj = $this->db->fetch_object($res_predmet)) {
                    $code_ustanova = $predmet_obj->code_ustanova;
                    $korisnik_rbr = $predmet_obj->korisnik_rbr;
                    $godina = $predmet_obj->godina;
                }
            }

            $html .= '<div class="seup-omat-acts-list">';
            foreach ($attachments as $akt_data) {
                $akt_broj = $code_ustanova . '-' . $korisnik_rbr . '-' . $godina . '-' . $akt_data['urb_broj'];

                $html .= '<div class="seup-omat-akt">';
                $html .= '<strong>' . htmlspecialchars($akt_broj) . '</strong><br>';

                // Prilozi
                if (!empty($akt_data['prilozi'])) {
                    foreach ($akt_data['prilozi'] as $prilog) {
                        $html .= '<div style="margin-left: 20px;">- Prilog rb: ' . htmlspecialchars($prilog->prilog_rbr) . '</div>';
                    }
                }

                // Otpreme
                if (!empty($akt_data['otpreme'])) {
                    $html .= '<div style="margin-left: 20px;">- Otpreme:</div>';
                    foreach ($akt_data['otpreme'] as $otprema) {
                        $datum_otp = dol_print_date($otprema->datum_otpreme, '%d.%m.%Y');
                        $html .= '<div style="margin-left: 35px;">' . htmlspecialchars($otprema->primatelj_naziv) . ' (' . $datum_otp . ')</div>';
                    }
                }

                // Zaprimanja
                if (!empty($akt_data['zaprimanja'])) {
                    $html .= '<div style="margin-left: 20px;">- Zaprimanja:</div>';
                    foreach ($akt_data['zaprimanja'] as $zaprimanje) {
                        $datum_zap = dol_print_date($zaprimanje->datum_zaprimanja, '%d.%m.%Y %H:%M');
                        $html .= '<div style="margin-left: 35px;">' . htmlspecialchars($zaprimanje->posiljatelj_naziv) . ' (' . $datum_zap . ')</div>';
                    }
                }

                $html .= '</div><br>';
            }
            $html .= '</div>';
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