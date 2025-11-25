-- =============================================================================
-- TABLE: a_otprema
-- Purpose: Document shipment/dispatch tracking (one document can have multiple shipments)
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_otprema (
    ID_otpreme int(11) NOT NULL AUTO_INCREMENT,

    -- Link na dokument (AKT ili PRILOG)
    fk_ecm_file int(11) NOT NULL COMMENT 'Reference to llx_ecm_files',
    tip_dokumenta enum('akt','prilog','nedodijeljeni') NOT NULL COMMENT 'Document type: act, attachment, or unassigned',
    ID_predmeta int(11) NOT NULL COMMENT 'Subject/case reference',

    -- PRIMATELJ (recipient - free text entry, not linked to a_posiljatelji)
    primatelj_naziv varchar(255) NOT NULL COMMENT 'Recipient name',
    primatelj_adresa varchar(500) DEFAULT NULL COMMENT 'Recipient address',
    primatelj_email varchar(100) DEFAULT NULL COMMENT 'Recipient email',
    primatelj_telefon varchar(50) DEFAULT NULL COMMENT 'Recipient phone',

    -- Otprema metadata
    datum_otpreme date NOT NULL COMMENT 'Shipment date',
    nacin_otpreme enum('posta','email','rucno','ostalo') NOT NULL COMMENT 'Shipment method',

    -- Dodatne informacije (OPCIONO)
    naziv_predmeta varchar(255) DEFAULT NULL COMMENT 'Subject name (optional)',
    klasifikacijska_oznaka varchar(100) DEFAULT NULL COMMENT 'Classification mark (optional)',

    -- Potvrda otpreme (stored in /documents/ecm/SEUP/Otpreme/YYYY/MM/)
    fk_potvrda_ecm_file int(11) DEFAULT NULL COMMENT 'Shipment confirmation file reference',

    napomena text DEFAULT NULL COMMENT 'Additional notes',
    fk_user_creat int(11) NOT NULL COMMENT 'User who created shipment record',
    datum_kreiranja timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',

    PRIMARY KEY (ID_otpreme),
    KEY fk_ecm_file_otprema (fk_ecm_file),
    KEY fk_predmet_otprema (ID_predmeta),
    KEY fk_potvrda_otprema (fk_potvrda_ecm_file),
    KEY fk_user_otprema (fk_user_creat),
    KEY idx_datum_otpreme (datum_otpreme),
    KEY idx_tip_dokumenta (tip_dokumenta),

    CONSTRAINT fk_otprema_ecm FOREIGN KEY (fk_ecm_file)
        REFERENCES llx_ecm_files(rowid) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_otprema_predmet FOREIGN KEY (ID_predmeta)
        REFERENCES llx_a_predmet(ID_predmeta) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_otprema_potvrda FOREIGN KEY (fk_potvrda_ecm_file)
        REFERENCES llx_ecm_files(rowid) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_otprema_user FOREIGN KEY (fk_user_creat)
        REFERENCES llx_user(rowid) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
