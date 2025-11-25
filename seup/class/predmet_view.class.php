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

class Predmet_View
{
    public static function printHeader($predmet)
    {
        print '<meta name="viewport" content="width=device-width, initial-scale=1">';
        print '<link rel="preconnect" href="https://fonts.googleapis.com">';
        print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
        print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
        print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
        print '<link href="/custom/seup/css/predmet.css" rel="stylesheet">';
        print '<link href="/custom/seup/css/prilozi.css" rel="stylesheet">';
        print '<link href="/custom/seup/css/dopina_predmet.css" rel="stylesheet">';
        print '<link href="/custom/seup/css/predmet-sortiranje.css" rel="stylesheet">';
        print '<link href="/custom/seup/css/otprema.css" rel="stylesheet">';
        print '<link href="/custom/seup/css/zaprimanja.css" rel="stylesheet">';
    }

    public static function printCaseDetails($predmet)
    {
        print '<div class="seup-case-details">';
        print '<div class="seup-case-header">';
        print '<div class="seup-case-icon"><i class="fas fa-folder-open"></i></div>';
        print '<div class="seup-case-title">';
        print '<h4>' . htmlspecialchars($predmet->naziv_predmeta) . '</h4>';
        print '<div class="seup-case-klasa">' . $predmet->klasa_format . '</div>';
        print '</div>';
        print '</div>';

        print '<div class="seup-case-grid">';

        print '<div class="seup-case-field">';
        print '<div class="seup-case-field-label"><i class="fas fa-building"></i>Ustanova</div>';
        print '<div class="seup-case-field-value">' . ($predmet->name_ustanova ?: 'N/A') . '</div>';
        print '</div>';

        print '<div class="seup-case-field">';
        print '<div class="seup-case-field-label"><i class="fas fa-user"></i>Zaposlenik</div>';
        print '<div class="seup-case-field-value">' . ($predmet->ime_prezime ?: 'N/A') . '</div>';
        print '</div>';

        print '<div class="seup-case-field">';
        print '<div class="seup-case-field-label"><i class="fas fa-calendar"></i>Datum Otvaranja</div>';
        print '<div class="seup-case-field-value">' . $predmet->datum_otvaranja . '</div>';
        print '</div>';

        print '<div class="seup-case-field">';
        print '<div class="seup-case-field-label"><i class="fas fa-clock"></i>Vrijeme Čuvanja</div>';
        $vrijeme_text = ($predmet->vrijeme_cuvanja == 0) ? 'Trajno' : $predmet->vrijeme_cuvanja . ' godina';
        print '<div class="seup-case-field-value">' . $vrijeme_text . '</div>';
        print '</div>';

        print '</div>';
        print '</div>';
    }

    public static function printTabs()
    {
        print '<div class="seup-tabs">';
        print '<button class="seup-tab active" data-tab="prilozi"><i class="fas fa-paperclip"></i>Prilozi</button>';
        print '<button class="seup-tab" data-tab="zaprimanja"><i class="fas fa-inbox"></i>Zaprimanja</button>';
        print '<button class="seup-tab" data-tab="otprema"><i class="fas fa-paper-plane"></i>Otprema</button>';
        print '<button class="seup-tab" data-tab="prepregled"><i class="fas fa-eye"></i>Prepregled</button>';
        print '<button class="seup-tab" data-tab="statistike"><i class="fas fa-chart-bar"></i>Statistike</button>';
        print '</div>';
    }

    public static function printPriloziTab($documentTableHTML)
    {
        print '<div class="seup-tab-pane active" id="prilozi">';

        print '<div class="seup-upload-section">';
        print '<i class="fas fa-cloud-upload-alt seup-upload-icon"></i>';
        print '<p class="seup-upload-text">Dodajte novi dokument u predmet</p>';
        print '<div class="seup-upload-buttons">';
        print '<button type="button" class="seup-btn seup-btn-primary" id="dodajAktBtn">';
        print '<i class="fas fa-file-alt me-2"></i>Dodaj Akt';
        print '</button>';
        print '<button type="button" class="seup-btn seup-btn-secondary" id="dodajPrilogBtn">';
        print '<i class="fas fa-paperclip me-2"></i>Dodaj Prilog';
        print '</button>';
        print '<button type="button" class="seup-btn seup-btn-warning" id="sortirajNedodjeljenoBtn">';
        print '<i class="fas fa-sort me-2"></i>Sortiraj nedodjeljeno';
        print '</button>';
        print '</div>';
        print '<div class="seup-upload-progress" id="uploadProgress">';
        print '<div class="seup-progress-bar"><div class="seup-progress-fill" id="progressFill"></div></div>';
        print '<div class="seup-progress-text" id="progressText">Učitavanje...</div>';
        print '</div>';
        print '</div>';

        print '<div class="seup-documents-header">';
        print '<h5 class="seup-documents-title"><i class="fas fa-paperclip"></i>Akti i Prilozi Predmeta</h5>';
        print '</div>';

        print $documentTableHTML;

        print '</div>';
    }

    public static function printZaprimanjaTab($db, $ID_predmeta)
    {
        require_once __DIR__ . '/zaprimanje_helper.class.php';

        Zaprimanje_Helper::ensureZaprimanjaTable($db);
        $zaprimanja = Zaprimanje_Helper::getZaprimanjaPoPredmetu($db, $ID_predmeta);

        print '<div class="seup-tab-pane" id="zaprimanja">';
        print '<div class="seup-zaprimanja-container">';

        print '<div class="seup-zaprimanja-header">';
        print '<h5 class="seup-documents-title"><i class="fas fa-inbox"></i>Zaprimanja Dokumenata</h5>';
        print '<p class="seup-zaprimanja-subtitle">Pregled svih zaprimljenih dokumenata od trećih strana</p>';
        print '<button type="button" class="seup-btn seup-btn-primary" id="zaprimiDokumentBtn" style="margin-top: 15px;">';
        print '<i class="fas fa-plus me-2"></i>Zaprimi Novi Dokument';
        print '</button>';
        print '</div>';

        if (empty($zaprimanja)) {
            print '<div class="seup-empty-state">';
            print '<i class="fas fa-inbox"></i>';
            print '<h4>Nema zaprimljenih dokumenata</h4>';
            print '<p>Zaprimite dokument od treće strane klikom na "Zaprimi Novi Dokument"</p>';
            print '</div>';
        } else {
            print '<div class="seup-zaprimanja-list">';

            foreach ($zaprimanja as $zaprimanje) {
                $datum_formatted = date('d.m.Y', strtotime($zaprimanje->datum_zaprimanja));
                $kreiran_formatted = date('d.m.Y H:i', strtotime($zaprimanje->datum_kreiranja));

                $nacin_icon = [
                    'posta' => 'fa-envelope',
                    'email' => 'fa-at',
                    'rucno' => 'fa-hand-holding',
                    'ostalo' => 'fa-ellipsis-h'
                ];

                $nacin_text = [
                    'posta' => 'Pošta',
                    'email' => 'E-mail',
                    'rucno' => 'Na ruke',
                    'ostalo' => 'Ostalo'
                ];

                print '<div class="seup-zaprimanja-item">';
                print '<div class="seup-zaprimanja-icon">';
                print '<i class="fas ' . ($nacin_icon[$zaprimanje->nacin_zaprimanja] ?? 'fa-inbox') . '"></i>';
                print '</div>';
                print '<div class="seup-zaprimanja-content">';

                print '<div class="seup-zaprimanja-main">';
                print '<div class="seup-zaprimanja-doc-info">';
                print '<span class="seup-doc-badge seup-doc-badge-' . $zaprimanje->tip_dokumenta . '">';
                print '<i class="fas ' . ($zaprimanje->tip_dokumenta == 'akt' ? 'fa-file-alt' : 'fa-paperclip') . '"></i> ';
                print strtoupper($zaprimanje->tip_dokumenta);
                print '</span>';
                print '<span class="seup-doc-filename">' . htmlspecialchars($zaprimanje->doc_filename) . '</span>';
                print '</div>';
                print '<div class="seup-zaprimanja-sender">';
                print '<i class="fas fa-user"></i> ' . htmlspecialchars($zaprimanje->posiljatelj_naziv ?? 'N/A');
                print '</div>';
                print '</div>';

                print '<div class="seup-zaprimanja-details">';
                print '<div class="seup-zaprimanja-detail-item">';
                print '<i class="fas fa-calendar"></i> ' . $datum_formatted;
                print '</div>';
                print '<div class="seup-zaprimanja-detail-item">';
                print '<i class="fas ' . ($nacin_icon[$zaprimanje->nacin_zaprimanja] ?? 'fa-inbox') . '"></i> ';
                print $nacin_text[$zaprimanje->nacin_zaprimanja] ?? 'N/A';
                print '</div>';

                if (!empty($zaprimanje->fk_potvrda_ecm_file)) {
                    print '<div class="seup-zaprimanja-detail-item seup-zaprimanja-potvrda">';
                    print '<i class="fas fa-check-circle" style="color: var(--success-color);"></i> ';
                    $potvrda_full_path = rtrim($zaprimanje->potvrda_filepath, '/') . '/' . $zaprimanje->potvrda_filename;
                    $download_url = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($potvrda_full_path);
                    print '<a href="' . $download_url . '" target="_blank" class="seup-download-link" title="' . htmlspecialchars($potvrda_full_path) . '">';
                    print '<i class="fas fa-download"></i> Preuzmi potvrdu';
                    print '</a>';
                    print '</div>';
                }
                print '</div>';

                if (!empty($zaprimanje->napomena)) {
                    print '<div class="seup-zaprimanja-napomena">';
                    print '<i class="fas fa-comment"></i> ' . htmlspecialchars($zaprimanje->napomena);
                    print '</div>';
                }

                print '<div class="seup-zaprimanja-footer">';
                print '<span class="seup-zaprimanja-user">Zaprimio: ' . htmlspecialchars($zaprimanje->firstname . ' ' . $zaprimanje->lastname) . '</span>';
                print '<span class="seup-zaprimanja-date">' . $kreiran_formatted . '</span>';
                print '</div>';

                print '</div>';
                print '</div>';
            }

            print '</div>';
        }

        print '</div>';
        print '</div>';
    }

    public static function printOtpremaTab($db, $ID_predmeta)
    {
        require_once __DIR__ . '/otprema_helper.class.php';

        $otpreme = Otprema_Helper::getOtpremePoPredmetu($db, $ID_predmeta);

        print '<div class="seup-tab-pane" id="otprema">';
        print '<div class="seup-otprema-container">';

        print '<div class="seup-otprema-header">';
        print '<h5 class="seup-documents-title"><i class="fas fa-paper-plane"></i>Povijest Otprema</h5>';
        print '<p class="seup-otprema-subtitle">Pregled svih registriranih otprema dokumenata iz ovog predmeta</p>';
        print '</div>';

        if (empty($otpreme)) {
            print '<div class="seup-empty-state">';
            print '<i class="fas fa-inbox"></i>';
            print '<h4>Nema registriranih otprema</h4>';
            print '<p>Otprema se registrira direktno na dokumentima (Akt ili Prilog) putem kontekstnog menija</p>';
            print '</div>';
        } else {
            print '<div class="seup-otprema-list">';

            foreach ($otpreme as $otprema) {
                $datum_formatted = date('d.m.Y', strtotime($otprema->datum_otpreme));
                $kreiran_formatted = date('d.m.Y H:i', strtotime($otprema->datum_kreiranja));

                $nacin_icon = [
                    'posta' => 'fa-envelope',
                    'email' => 'fa-at',
                    'rucno' => 'fa-hand-holding',
                    'ostalo' => 'fa-ellipsis-h'
                ];

                $nacin_text = [
                    'posta' => 'Pošta',
                    'email' => 'E-mail',
                    'rucno' => 'Na ruke',
                    'ostalo' => 'Ostalo'
                ];

                print '<div class="seup-otprema-item">';
                print '<div class="seup-otprema-icon">';
                print '<i class="fas ' . ($nacin_icon[$otprema->nacin_otpreme] ?? 'fa-paper-plane') . '"></i>';
                print '</div>';
                print '<div class="seup-otprema-content">';

                print '<div class="seup-otprema-main">';
                print '<div class="seup-otprema-doc-info">';
                print '<span class="seup-doc-badge seup-doc-badge-' . $otprema->tip_dokumenta . '">';
                print '<i class="fas ' . ($otprema->tip_dokumenta == 'akt' ? 'fa-file-alt' : 'fa-paperclip') . '"></i> ';
                print strtoupper($otprema->tip_dokumenta);
                print '</span>';
                print '<span class="seup-doc-filename">' . htmlspecialchars($otprema->doc_filename) . '</span>';
                print '</div>';
                print '<div class="seup-otprema-recipient">';
                print '<i class="fas fa-user"></i> ' . htmlspecialchars($otprema->primatelj_naziv);
                print '</div>';
                print '</div>';

                print '<div class="seup-otprema-details">';
                print '<div class="seup-otprema-detail-item">';
                print '<i class="fas fa-calendar"></i> ' . $datum_formatted;
                print '</div>';
                print '<div class="seup-otprema-detail-item">';
                print '<i class="fas ' . ($nacin_icon[$otprema->nacin_otpreme] ?? 'fa-paper-plane') . '"></i> ';
                print $nacin_text[$otprema->nacin_otpreme] ?? 'N/A';
                print '</div>';

                if (!empty($otprema->primatelj_adresa)) {
                    print '<div class="seup-otprema-detail-item">';
                    print '<i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($otprema->primatelj_adresa);
                    print '</div>';
                }

                if (!empty($otprema->primatelj_email)) {
                    print '<div class="seup-otprema-detail-item">';
                    print '<i class="fas fa-envelope"></i> <a href="mailto:' . htmlspecialchars($otprema->primatelj_email) . '">' . htmlspecialchars($otprema->primatelj_email) . '</a>';
                    print '</div>';
                }

                if (!empty($otprema->primatelj_telefon)) {
                    print '<div class="seup-otprema-detail-item">';
                    print '<i class="fas fa-phone"></i> <a href="tel:' . htmlspecialchars($otprema->primatelj_telefon) . '">' . htmlspecialchars($otprema->primatelj_telefon) . '</a>';
                    print '</div>';
                }

                if (!empty($otprema->fk_potvrda_ecm_file)) {
                    print '<div class="seup-otprema-detail-item seup-otprema-potvrda">';
                    print '<i class="fas fa-check-circle" style="color: var(--success-color);"></i> ';
                    $potvrda_full_path = rtrim($otprema->potvrda_filepath, '/') . '/' . $otprema->potvrda_filename;
                    $download_url = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($potvrda_full_path);
                    print '<a href="' . $download_url . '" target="_blank" class="seup-download-link" title="' . htmlspecialchars($potvrda_full_path) . '">';
                    print '<i class="fas fa-download"></i> Preuzmi potvrdu';
                    print '</a>';
                    print '</div>';
                }
                print '</div>';

                if (!empty($otprema->napomena)) {
                    print '<div class="seup-otprema-napomena">';
                    print '<i class="fas fa-comment"></i> ' . htmlspecialchars($otprema->napomena);
                    print '</div>';
                }

                print '<div class="seup-otprema-footer">';
                print '<span class="seup-otprema-user">Kreirao: ' . htmlspecialchars($otprema->firstname . ' ' . $otprema->lastname) . '</span>';
                print '<span class="seup-otprema-date">' . $kreiran_formatted . '</span>';
                print '</div>';

                print '</div>';
                print '</div>';
            }

            print '</div>';
        }

        print '</div>';
        print '</div>';
    }

    public static function printPrepregledTab()
    {
        print '<div class="seup-tab-pane" id="prepregled">';
        print '<div class="seup-preview-container">';
        print '<i class="fas fa-file-alt seup-preview-icon"></i>';
        print '<h4 class="seup-preview-title">Omot Spisa</h4>';
        print '<p class="seup-preview-description">Generirajte ili pregledajte A3 omat spisa s osnovnim informacijama i popisom privitaka</p>';

        print '<div class="seup-action-buttons">';
        print '<button type="button" class="seup-btn seup-btn-primary" id="generateOmotBtn">';
        print '<i class="fas fa-file-pdf me-2"></i>Kreiraj PDF';
        print '</button>';
        print '<button type="button" class="seup-btn seup-btn-secondary" id="printOmotBtn">';
        print '<i class="fas fa-print me-2"></i>Ispis';
        print '</button>';
        print '<button type="button" class="seup-btn seup-btn-success" id="previewOmotBtn">';
        print '<i class="fas fa-eye me-2"></i>Prepregled';
        print '</button>';
        print '</div>';

        print '</div>';
        print '</div>';
    }

    public static function printStatistikeTab($predmet, $doc_count)
    {
        $vrijeme_text = ($predmet->vrijeme_cuvanja == 0) ? 'Trajno' : $predmet->vrijeme_cuvanja . ' godina';

        print '<div class="seup-tab-pane" id="statistike">';
        print '<div class="seup-stats-container">';
        print '<h5><i class="fas fa-chart-bar"></i>Statistike Predmeta</h5>';
        print '<div class="seup-stats-grid">';

        print '<div class="seup-stat-card">';
        print '<i class="fas fa-file-alt seup-stat-icon"></i>';
        print '<div class="seup-stat-number">' . $doc_count . '</div>';
        print '<div class="seup-stat-label">Dokumenata</div>';
        print '</div>';

        print '<div class="seup-stat-card">';
        print '<i class="fas fa-calendar seup-stat-icon"></i>';
        print '<div class="seup-stat-number">' . $predmet->datum_otvaranja . '</div>';
        print '<div class="seup-stat-label">Datum Otvaranja</div>';
        print '</div>';

        print '<div class="seup-stat-card">';
        print '<i class="fas fa-clock seup-stat-icon"></i>';
        print '<div class="seup-stat-number">' . $vrijeme_text . '</div>';
        print '<div class="seup-stat-label">Vrijeme Čuvanja</div>';
        print '</div>';

        print '<div class="seup-stat-card">';
        print '<i class="fas fa-user seup-stat-icon"></i>';
        print '<div class="seup-stat-number">' . ($predmet->korisnik_rbr ?: 'N/A') . '</div>';
        print '<div class="seup-stat-label">Oznaka Korisnika</div>';
        print '</div>';

        print '</div>';
        print '</div>';
        print '</div>';
    }

    public static function printModals($caseId, $availableAkti)
    {
        self::printOmotPreviewModal();
        self::printPrintInstructionsModal();
        self::printDeleteDocumentModal();
        self::printAktUploadModal($caseId);
        self::printPrilogUploadModal($caseId, $availableAkti);
        self::printRegistrirajOtpremuModal($caseId);
        self::printZaprimiDokumentModal($caseId, $availableAkti);
        self::printZaprimanjeDetailsModal();
    }

    private static function printOmotPreviewModal()
    {
        print '<div class="seup-modal" id="omotPreviewModal">';
        print '<div class="seup-modal-content" style="max-width: 800px; max-height: 90vh;">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-eye me-2"></i>Prepregled Omota Spisa</h5>';
        print '<button type="button" class="seup-modal-close" id="closeOmotModal">&times;</button>';
        print '</div>';
        print '<div class="seup-modal-body" style="max-height: 70vh; overflow-y: auto;">';
        print '<div id="omotPreviewContent">';
        print '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Učitavam prepregled...</div>';
        print '</div>';
        print '</div>';
        print '<div class="seup-modal-footer">';
        print '<button type="button" class="seup-btn seup-btn-secondary" id="closePreviewBtn">Zatvori</button>';
        print '<button type="button" class="seup-btn seup-btn-primary" id="generateFromPreviewBtn">';
        print '<i class="fas fa-file-pdf me-2"></i>Generiraj PDF';
        print '</button>';
        print '</div>';
        print '</div>';
        print '</div>';
    }

    private static function printPrintInstructionsModal()
    {
        print '<div class="seup-modal" id="printInstructionsModal">';
        print '<div class="seup-modal-content" style="max-width: 600px;">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-print me-2"></i>Upute za Ispis Omota Spisa</h5>';
        print '<button type="button" class="seup-modal-close" id="closePrintModal">&times;</button>';
        print '</div>';
        print '<div class="seup-modal-body">';
        print '<div class="seup-print-instructions">';
        print '<div class="seup-print-warning">';
        print '<div class="seup-warning-icon"><i class="fas fa-exclamation-triangle"></i></div>';
        print '<div class="seup-warning-content">';
        print '<h4>Važne upute za ispis</h4>';
        print '<p>Molimo pažljivo pročitajte upute prije ispisa omota spisa</p>';
        print '</div>';
        print '</div>';

        print '<div class="seup-print-steps">';
        print '<div class="seup-print-step">';
        print '<div class="seup-step-number">1</div>';
        print '<div class="seup-step-content">';
        print '<h5>Postavke printera</h5>';
        print '<p>Postavite printer na <strong>A3 format papira</strong> (297 x 420 mm)</p>';
        print '</div>';
        print '</div>';

        print '<div class="seup-print-step">';
        print '<div class="seup-step-number">2</div>';
        print '<div class="seup-step-content">';
        print '<h5>Orijentacija</h5>';
        print '<p>Odaberite <strong>Portrait</strong> (uspravnu) orijentaciju</p>';
        print '</div>';
        print '</div>';

        print '<div class="seup-print-step">';
        print '<div class="seup-step-number">3</div>';
        print '<div class="seup-step-content">';
        print '<h5>Margine</h5>';
        print '<p>Postavite margine na <strong>minimum</strong> ili koristite "Fit to page"</p>';
        print '</div>';
        print '</div>';

        print '<div class="seup-print-step">';
        print '<div class="seup-step-number">4</div>';
        print '<div class="seup-step-content">';
        print '<h5>Preklapanje</h5>';
        print '<p>Nakon ispisa, <strong>preklopite papir na pola</strong> da formirate omot</p>';
        print '</div>';
        print '</div>';
        print '</div>';

        print '<div class="seup-print-note">';
        print '<div class="seup-note-icon"><i class="fas fa-lightbulb"></i></div>';
        print '<div class="seup-note-content">';
        print '<h5>Napomena</h5>';
        print '<p>Omot spisa je dizajniran za A3 papir koji se preklapa na pola. ';
        print 'Stranica 1 je naslovnica, stranice 2-3 su unutarnje (popis privitaka), a stranica 4 je zadnja.</p>';
        print '</div>';
        print '</div>';

        print '</div>';
        print '</div>';
        print '<div class="seup-modal-footer">';
        print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelPrintBtn">Odustani</button>';
        print '<button type="button" class="seup-btn seup-btn-primary" id="confirmPrintBtn">';
        print '<i class="fas fa-print me-2"></i>Ispiši Omot';
        print '</button>';
        print '</div>';
        print '</div>';
        print '</div>';
    }

    private static function printDeleteDocumentModal()
    {
        print '<div class="seup-modal" id="deleteDocModal">';
        print '<div class="seup-modal-content">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-trash me-2"></i>Brisanje Dokumenta</h5>';
        print '<button type="button" class="seup-modal-close" id="closeDeleteModal">&times;</button>';
        print '</div>';
        print '<div class="seup-modal-body">';
        print '<div class="seup-delete-doc-info">';
        print '<div class="seup-delete-doc-icon"><i class="fas fa-file-alt"></i></div>';
        print '<div class="seup-delete-doc-details">';
        print '<div class="seup-delete-doc-name" id="deleteDocName">document.pdf</div>';
        print '<div class="seup-delete-doc-warning">';
        print '<i class="fas fa-exclamation-triangle"></i>';
        print 'Jeste li sigurni da želite obrisati ovaj dokument? Ova akcija je nepovratna.';
        print '</div>';
        print '</div>';
        print '</div>';
        print '</div>';
        print '<div class="seup-modal-footer">';
        print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelDeleteBtn">Odustani</button>';
        print '<button type="button" class="seup-btn seup-btn-danger" id="confirmDeleteBtn">';
        print '<i class="fas fa-trash me-2"></i>Obriši';
        print '</button>';
        print '</div>';
        print '</div>';
        print '</div>';
    }

    private static function printAktUploadModal($caseId)
    {
        print '<div class="seup-modal" id="aktUploadModal">';
        print '<div class="seup-modal-content">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-file-alt me-2"></i>Dodaj Akt</h5>';
        print '<button type="button" class="seup-modal-close" id="closeAktModal">&times;</button>';
        print '</div>';
        print '<div class="seup-modal-body">';
        print '<div class="seup-akt-upload-info">';
        print '<div class="seup-akt-info-icon"><i class="fas fa-info-circle"></i></div>';
        print '<div class="seup-akt-info-content">';
        print '<h6>Dodavanje novog akta</h6>';
        print '<p>Akt će automatski dobiti sljedeći urb broj u nizu</p>';
        print '</div>';
        print '</div>';
        print '<form id="aktUploadForm" enctype="multipart/form-data">';
        print '<input type="hidden" name="action" value="upload_akt">';
        print '<input type="hidden" name="case_id" value="' . $caseId . '">';
        print '<div class="seup-form-group">';
        print '<label for="aktFile" class="seup-label"><i class="fas fa-file me-2"></i>Odaberite datoteku</label>';
        print '<input type="file" id="aktFile" name="akt_file" class="seup-input" accept=".pdf,.docx,.xlsx,.doc,.xls,.jpg,.jpeg,.png" required>';
        print '<div class="seup-help-text">Podržani formati: PDF, DOCX, XLSX, DOC, XLS, JPG, PNG</div>';
        print '</div>';
        print '</form>';
        print '</div>';
        print '<div class="seup-modal-footer">';
        print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelAktBtn">Odustani</button>';
        print '<button type="button" class="seup-btn seup-btn-primary" id="uploadAktBtn">';
        print '<i class="fas fa-upload me-2"></i>Upload Akt';
        print '</button>';
        print '</div>';
        print '</div>';
        print '</div>';
    }

    private static function printPrilogUploadModal($caseId, $availableAkti)
    {
        print '<div class="seup-modal" id="prilogUploadModal">';
        print '<div class="seup-modal-content">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-paperclip me-2"></i>Dodaj Prilog</h5>';
        print '<button type="button" class="seup-modal-close" id="closePrilogModal">&times;</button>';
        print '</div>';
        print '<div class="seup-modal-body">';
        print '<div class="seup-prilog-upload-info">';
        print '<div class="seup-prilog-info-icon"><i class="fas fa-info-circle"></i></div>';
        print '<div class="seup-prilog-info-content">';
        print '<h6>Dodavanje novog priloga</h6>';
        print '<p>Prilog će biti dodan pod odabrani akt s automatskim brojem</p>';
        print '</div>';
        print '</div>';

        if (count($availableAkti) > 0) {
            print '<form id="prilogUploadForm" enctype="multipart/form-data">';
            print '<input type="hidden" name="action" value="upload_prilog">';
            print '<input type="hidden" name="case_id" value="' . $caseId . '">';

            print '<div class="seup-form-group">';
            print '<label for="aktSelect" class="seup-label"><i class="fas fa-file-alt me-2"></i>Odaberite akt</label>';
            print '<select id="aktSelect" name="akt_id" class="seup-select" required>';
            print '<option value="">-- Odaberite akt --</option>';
            foreach ($availableAkti as $akt) {
                print '<option value="' . $akt->ID_akta . '">';
                print 'Akt ' . $akt->urb_broj . ' - ' . htmlspecialchars($akt->filename);
                print '</option>';
            }
            print '</select>';
            print '</div>';

            print '<div class="seup-form-group">';
            print '<label for="prilogFile" class="seup-label"><i class="fas fa-file me-2"></i>Odaberite datoteku</label>';
            print '<input type="file" id="prilogFile" name="prilog_file" class="seup-input" accept=".pdf,.docx,.xlsx,.doc,.xls,.jpg,.jpeg,.png" required>';
            print '<div class="seup-help-text">Podržani formati: PDF, DOCX, XLSX, DOC, XLS, JPG, PNG</div>';
            print '</div>';
            print '</form>';
        } else {
            print '<div class="seup-alert seup-alert-warning">';
            print '<i class="fas fa-exclamation-triangle me-2"></i>';
            print 'Nema dostupnih akata. Prvo dodajte akt prije dodavanja priloga.';
            print '</div>';
        }

        print '</div>';
        print '<div class="seup-modal-footer">';
        if (count($availableAkti) > 0) {
            print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelPrilogBtn">Odustani</button>';
            print '<button type="button" class="seup-btn seup-btn-success" id="uploadPrilogBtn">';
            print '<i class="fas fa-upload me-2"></i>Upload Prilog';
            print '</button>';
        } else {
            print '<button type="button" class="seup-btn seup-btn-primary" id="dodajAktPrviBtn">';
            print '<i class="fas fa-file-alt me-2"></i>Dodaj Prvi Akt';
            print '</button>';
        }
        print '</div>';
        print '</div>';
        print '</div>';
    }

    private static function printRegistrirajOtpremuModal($caseId)
    {
        print '<div class="seup-modal" id="registrirajOtpremuModal">';
        print '<div class="seup-modal-content" style="max-width: 700px;">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-paper-plane me-2"></i>Registriraj Otpremu Dokumenta</h5>';
        print '<button type="button" class="seup-modal-close" id="closeOtpremaModal">&times;</button>';
        print '</div>';
        print '<div class="seup-modal-body">';
        print '<form id="otpremaForm" enctype="multipart/form-data">';
        print '<input type="hidden" name="action" value="registriraj_otpremu">';
        print '<input type="hidden" name="case_id" value="' . $caseId . '">';
        print '<input type="hidden" name="fk_ecm_file" id="otprema_fk_ecm_file">';
        print '<input type="hidden" name="tip_dokumenta" id="otprema_tip_dokumenta">';

        print '<div class="seup-alert seup-alert-info">';
        print '<i class="fas fa-info-circle me-2"></i>';
        print 'Dokument: <strong id="otprema_doc_name"></strong>';
        print '</div>';

        print '<h6 class="seup-section-title"><i class="fas fa-user"></i> Primatelj *</h6>';
        print '<div class="seup-form-group">';
        print '<label for="primatelj_naziv" class="seup-label">Ime/Naziv primatelja *</label>';
        print '<input type="text" id="primatelj_naziv" name="primatelj_naziv" class="seup-input" required>';
        print '</div>';

        print '<div class="seup-form-row">';
        print '<div class="seup-form-group">';
        print '<label for="primatelj_adresa" class="seup-label">Adresa</label>';
        print '<input type="text" id="primatelj_adresa" name="primatelj_adresa" class="seup-input">';
        print '</div>';
        print '</div>';

        print '<div class="seup-form-row">';
        print '<div class="seup-form-group">';
        print '<label for="primatelj_email" class="seup-label">Email</label>';
        print '<input type="email" id="primatelj_email" name="primatelj_email" class="seup-input">';
        print '</div>';
        print '<div class="seup-form-group">';
        print '<label for="primatelj_telefon" class="seup-label">Telefon</label>';
        print '<input type="text" id="primatelj_telefon" name="primatelj_telefon" class="seup-input">';
        print '</div>';
        print '</div>';

        print '<h6 class="seup-section-title"><i class="fas fa-shipping-fast"></i> Otprema *</h6>';
        print '<div class="seup-form-row">';
        print '<div class="seup-form-group">';
        print '<label for="datum_otpreme" class="seup-label">Datum otpreme *</label>';
        print '<input type="date" id="datum_otpreme" name="datum_otpreme" class="seup-input" required>';
        print '</div>';
        print '<div class="seup-form-group">';
        print '<label for="nacin_otpreme" class="seup-label">Način otpreme *</label>';
        print '<select id="nacin_otpreme" name="nacin_otpreme" class="seup-select" required>';
        print '<option value="">-- Odaberite --</option>';
        print '<option value="posta">Pošta</option>';
        print '<option value="email">E-mail</option>';
        print '<option value="rucno">Na ruke</option>';
        print '<option value="ostalo">Ostalo</option>';
        print '</select>';
        print '</div>';
        print '</div>';

        print '<h6 class="seup-section-title"><i class="fas fa-info-circle"></i> Dodatne Informacije (opciono)</h6>';
        print '<div class="seup-form-row">';
        print '<div class="seup-form-group">';
        print '<label for="naziv_predmeta" class="seup-label">Naziv predmeta</label>';
        print '<input type="text" id="naziv_predmeta" name="naziv_predmeta" class="seup-input">';
        print '</div>';
        print '<div class="seup-form-group">';
        print '<label for="klasifikacijska_oznaka" class="seup-label">Klasifikacijska oznaka</label>';
        print '<input type="text" id="klasifikacijska_oznaka" name="klasifikacijska_oznaka" class="seup-input">';
        print '</div>';
        print '</div>';

        print '<h6 class="seup-section-title"><i class="fas fa-paperclip"></i> Potvrda (opciono)</h6>';
        print '<div class="seup-form-group">';
        print '<label for="potvrda_file" class="seup-label">Upload potvrde pošiljke</label>';
        print '<input type="file" id="potvrda_file" name="potvrda_file" class="seup-input" accept=".pdf,.jpg,.jpeg,.png">';
        print '<div class="seup-help-text"><i class="fas fa-info-circle"></i> Sprema se u: /Otpreme/' . date('Y') . '/' . date('m') . '/</div>';
        print '</div>';

        print '<div class="seup-form-group">';
        print '<label for="napomena" class="seup-label">Napomena</label>';
        print '<textarea id="napomena" name="napomena" class="seup-textarea" rows="3"></textarea>';
        print '</div>';

        print '</form>';
        print '</div>';
        print '<div class="seup-modal-footer">';
        print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelOtpremaBtn">Odustani</button>';
        print '<button type="button" class="seup-btn seup-btn-primary" id="submitOtpremaBtn">';
        print '<i class="fas fa-save me-2"></i>Spremi Otpremu';
        print '</button>';
        print '</div>';
        print '</div>';
        print '</div>';
    }

    private static function printZaprimiDokumentModal($caseId, $availableAkti)
    {
        global $db;
        require_once __DIR__ . '/zaprimanje_helper.class.php';

        print '<div class="seup-modal" id="zaprimiDokumentModal">';
        print '<div class="seup-modal-content" style="max-width: 700px;">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-inbox me-2"></i>Zaprimi Dokument</h5>';
        print '<button type="button" class="seup-modal-close" id="closeZaprimanjeModal">&times;</button>';
        print '</div>';
        print '<div class="seup-modal-body">';
        print '<form id="zaprimanjeForm" enctype="multipart/form-data">';
        print '<input type="hidden" name="action" value="registriraj_zaprimanje">';
        print '<input type="hidden" name="case_id" value="' . $caseId . '">';
        print '<input type="hidden" name="fk_posiljatelj" id="fk_posiljatelj">';

        print '<h6 class="seup-section-title"><i class="fas fa-file"></i> Dokument *</h6>';
        print '<div class="seup-form-group">';
        print '<label for="dokument_file" class="seup-label">Odaberite datoteku *</label>';
        print '<input type="file" id="dokument_file" name="dokument_file" class="seup-input" accept=".pdf,.docx,.xlsx,.doc,.xls,.jpg,.jpeg,.png" required>';
        print '<div class="seup-help-text">Podržani formati: PDF, DOCX, XLSX, DOC, XLS, JPG, PNG</div>';
        print '</div>';

        print '<div class="seup-form-group">';
        print '<label for="tip_dokumenta" class="seup-label">Tip dokumenta *</label>';
        print '<select id="tip_dokumenta" name="tip_dokumenta" class="seup-select" required>';
        print '<option value="">-- Odaberite tip --</option>';
        print '<option value="akt">Novi Akt</option>';
        print '<option value="prilog_postojecem">Prilog postojećem aktu</option>';
        print '<option value="nedodjeljeno">Nedodjeljeno</option>';
        print '</select>';
        print '</div>';

        print '<div class="seup-form-group" id="akt_za_prilog_wrapper" style="display: none;">';
        print '<label for="fk_akt_za_prilog" class="seup-label">Odaberite akt *</label>';
        print '<select id="fk_akt_za_prilog" name="fk_akt_za_prilog" class="seup-select">';
        print '<option value="">-- Odaberite akt --</option>';
        if (!empty($availableAkti)) {
            foreach ($availableAkti as $akt) {
                print '<option value="' . $akt->ID_akta . '">';
                print 'Akt ' . $akt->urb_broj . ' - ' . htmlspecialchars($akt->filename);
                print '</option>';
            }
        }
        print '</select>';
        print '</div>';

        print '<h6 class="seup-section-title"><i class="fas fa-user"></i> Pošiljatelj</h6>';
        print '<div class="seup-form-group seup-autocomplete-wrapper">';
        print '<label for="posiljatelj_search" class="seup-label">Tražite ili unesite pošiljatelja</label>';
        print '<input type="text" id="posiljatelj_search" name="posiljatelj_search" class="seup-input" placeholder="Unesite naziv pošiljatelja...">';
        print '<div class="seup-autocomplete-dropdown" id="posiljatelj_dropdown"></div>';
        print '<div class="seup-help-text"><i class="fas fa-info-circle"></i> Počnite tipkati za pretragu postojećih pošiljatelja</div>';
        print '</div>';

        print '<h6 class="seup-section-title"><i class="fas fa-info-circle"></i> Dodatne Informacije *</h6>';
        print '<div class="seup-form-row">';
        print '<div class="seup-form-group">';
        print '<label for="datum_zaprimanja" class="seup-label">Datum zaprimanja *</label>';
        print '<input type="date" id="datum_zaprimanja" name="datum_zaprimanja" class="seup-input" required>';
        print '</div>';
        print '<div class="seup-form-group">';
        print '<label for="nacin_zaprimanja" class="seup-label">Način zaprimanja *</label>';
        print '<select id="nacin_zaprimanja" name="nacin_zaprimanja" class="seup-select" required>';
        print '<option value="">-- Odaberite --</option>';
        print '<option value="posta">Pošta</option>';
        print '<option value="email">E-mail</option>';
        print '<option value="rucno">Na ruke</option>';
        print '<option value="ostalo">Ostalo</option>';
        print '</select>';
        print '</div>';
        print '</div>';

        print '<h6 class="seup-section-title"><i class="fas fa-paperclip"></i> Potvrda (opciono)</h6>';
        print '<div class="seup-form-group">';
        print '<label for="potvrda_file" class="seup-label">Upload potvrde zaprimanja</label>';
        print '<input type="file" id="potvrda_file" name="potvrda_file" class="seup-input" accept=".pdf,.jpg,.jpeg,.png">';
        print '<div class="seup-help-text"><i class="fas fa-info-circle"></i> Sprema se u: /Zaprimanja/' . date('Y') . '/</div>';
        print '</div>';

        print '<div class="seup-form-group">';
        print '<label for="napomena" class="seup-label">Napomena</label>';
        print '<textarea id="napomena" name="napomena" class="seup-textarea" rows="3"></textarea>';
        print '</div>';

        print '</form>';
        print '</div>';
        print '<div class="seup-modal-footer">';
        print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelZaprimanjeBtn">Odustani</button>';
        print '<button type="button" class="seup-btn seup-btn-primary" id="submitZaprimanjeBtn">';
        print '<i class="fas fa-save me-2"></i>Zaprimi Dokument';
        print '</button>';
        print '</div>';
        print '</div>';
        print '</div>';
    }

    private static function printZaprimanjeDetailsModal()
    {
        print '<div class="seup-modal" id="zaprimanjeDetailsModal">';
        print '<div class="seup-modal-content" style="max-width: 600px;">';
        print '<div class="seup-modal-header">';
        print '<h5 class="seup-modal-title"><i class="fas fa-inbox me-2"></i>Detalji Zaprimanja</h5>';
        print '<button type="button" class="seup-modal-close" id="closeZaprimanjeDetailsModal">&times;</button>';
        print '</div>';
        print '<div class="seup-modal-body" id="zaprimanjeDetailsContent">';
        print '<div class="seup-loading"><i class="fas fa-spinner fa-spin"></i> Učitavam...</div>';
        print '</div>';
        print '</div>';
        print '</div>';
    }

    public static function printScripts()
    {
        print '<script src="/custom/seup/js/seup-modern.js"></script>';
        print '<script src="/custom/seup/js/predmet-sortiranje.js"></script>';
        print '<script src="/custom/seup/js/predmet.js"></script>';
        print '<script src="/custom/seup/js/zaprimanja.js"></script>';
    }
}
