--
-- SEUP Module Database Schema
-- Script run when an upgrade of Dolibarr is done. Whatever is the Dolibarr version.
-- (c) 2025 8Core Association
--

-- =============================================================================
-- TABLE: a_oznaka_ustanove
-- Purpose: Organization/Institution identification (singleton pattern)
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_oznaka_ustanove (
    ID_ustanove int(11) NOT NULL AUTO_INCREMENT,
    singleton tinyint(1) DEFAULT 1,
    code_ustanova varchar(20) NOT NULL,
    name_ustanova varchar(255) NOT NULL,
    PRIMARY KEY (ID_ustanove),
    UNIQUE KEY singleton (singleton)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_klasifikacijska_oznaka
-- Purpose: Classification marks (klasa) for document categorization
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_klasifikacijska_oznaka (
    ID_klasifikacijske_oznake int(11) NOT NULL AUTO_INCREMENT,
    ID_ustanove int(11) NOT NULL,
    klasa_broj varchar(10) NOT NULL,
    sadrzaj varchar(10) NOT NULL,
    dosje_broj varchar(10) NOT NULL,
    vrijeme_cuvanja int(11) NOT NULL DEFAULT 0 COMMENT '0 = permanent, >0 = years',
    opis_klasifikacijske_oznake text,
    PRIMARY KEY (ID_klasifikacijske_oznake),
    UNIQUE KEY unique_combination (klasa_broj, sadrzaj, dosje_broj),
    KEY fk_ustanova (ID_ustanove),
    CONSTRAINT fk_klasifikacija_ustanova FOREIGN KEY (ID_ustanove)
        REFERENCES llx_a_oznaka_ustanove(ID_ustanove) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_interna_oznaka_korisnika
-- Purpose: Internal user marks/codes for document organization
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_interna_oznaka_korisnika (
    ID int(11) NOT NULL AUTO_INCREMENT,
    ID_ustanove int(11) NOT NULL,
    ime_prezime varchar(255) NOT NULL,
    rbr int(11) NOT NULL,
    naziv varchar(255) NOT NULL,
    PRIMARY KEY (ID),
    UNIQUE KEY unique_rbr (rbr),
    KEY fk_ustanova_korisnik (ID_ustanove),
    CONSTRAINT fk_interna_oznaka_ustanova FOREIGN KEY (ID_ustanove)
        REFERENCES llx_a_oznaka_ustanove(ID_ustanove) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_predmet
-- Purpose: Main subjects/cases (uredski predmeti)
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_predmet (
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
    vrijeme_cuvanja int(11) NOT NULL DEFAULT 0,
    tstamp_created timestamp DEFAULT CURRENT_TIMESTAMP,
    naziv varchar(255) DEFAULT NULL COMMENT 'Naziv pošiljatelja/sender name',
    zaprimljeno_datum datetime DEFAULT NULL COMMENT 'Datum zaprimanja predmeta/received date',
    PRIMARY KEY (ID_predmeta),
    UNIQUE KEY unique_predmet (klasa_br, sadrzaj, dosje_broj, godina, predmet_rbr),
    KEY fk_ustanova_predmet (ID_ustanove),
    KEY fk_korisnik_predmet (ID_interna_oznaka_korisnika),
    KEY fk_klasifikacija_predmet (ID_klasifikacijske_oznake),
    KEY idx_godina (godina),
    KEY idx_created (tstamp_created),
    CONSTRAINT fk_predmet_ustanova FOREIGN KEY (ID_ustanove)
        REFERENCES llx_a_oznaka_ustanove(ID_ustanove) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_predmet_korisnik FOREIGN KEY (ID_interna_oznaka_korisnika)
        REFERENCES llx_a_interna_oznaka_korisnika(ID) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_predmet_klasifikacija FOREIGN KEY (ID_klasifikacijske_oznake)
        REFERENCES llx_a_klasifikacijska_oznaka(ID_klasifikacijske_oznake) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_akti
-- Purpose: Acts/Documents (akti) linked to subjects
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_akti (
    ID_akta int(11) NOT NULL AUTO_INCREMENT,
    ID_predmeta int(11) NOT NULL,
    urb_broj varchar(10) NOT NULL COMMENT 'Urudžbeni broj/Registry number',
    fk_ecm_file int(11) NOT NULL COMMENT 'Reference to llx_ecm_files',
    datum_kreiranja timestamp DEFAULT CURRENT_TIMESTAMP,
    fk_user_creat int(11) NOT NULL,
    PRIMARY KEY (ID_akta),
    UNIQUE KEY unique_predmet_urb (ID_predmeta, urb_broj),
    KEY fk_predmet_akt (ID_predmeta),
    KEY fk_ecm_file_akt (fk_ecm_file),
    KEY fk_user_akt (fk_user_creat),
    CONSTRAINT fk_akt_predmet FOREIGN KEY (ID_predmeta)
        REFERENCES llx_a_predmet(ID_predmeta) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_akt_ecm FOREIGN KEY (fk_ecm_file)
        REFERENCES llx_ecm_files(rowid) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_akt_user FOREIGN KEY (fk_user_creat)
        REFERENCES llx_user(rowid) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_prilozi
-- Purpose: Attachments/Appendices (prilozi) linked to acts
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_prilozi (
    ID_priloga int(11) NOT NULL AUTO_INCREMENT,
    ID_akta int(11) NOT NULL,
    ID_predmeta int(11) NOT NULL,
    prilog_rbr varchar(10) NOT NULL COMMENT 'Attachment number',
    fk_ecm_file int(11) NOT NULL COMMENT 'Reference to llx_ecm_files',
    datum_kreiranja timestamp DEFAULT CURRENT_TIMESTAMP,
    fk_user_creat int(11) NOT NULL,
    PRIMARY KEY (ID_priloga),
    UNIQUE KEY unique_akt_prilog (ID_akta, prilog_rbr),
    KEY fk_akt_prilog (ID_akta),
    KEY fk_predmet_prilog (ID_predmeta),
    KEY fk_ecm_file_prilog (fk_ecm_file),
    KEY fk_user_prilog (fk_user_creat),
    CONSTRAINT fk_prilog_akt FOREIGN KEY (ID_akta)
        REFERENCES llx_a_akti(ID_akta) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prilog_predmet FOREIGN KEY (ID_predmeta)
        REFERENCES llx_a_predmet(ID_predmeta) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prilog_ecm FOREIGN KEY (fk_ecm_file)
        REFERENCES llx_ecm_files(rowid) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prilog_user FOREIGN KEY (fk_user_creat)
        REFERENCES llx_user(rowid) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_tagovi
-- Purpose: Tags system for categorization
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_tagovi (
    rowid int(11) NOT NULL AUTO_INCREMENT,
    tag varchar(100) NOT NULL,
    color varchar(20) DEFAULT 'blue',
    entity int(11) NOT NULL DEFAULT 1,
    date_creation datetime NOT NULL,
    fk_user_creat int(11) NOT NULL,
    PRIMARY KEY (rowid),
    UNIQUE KEY unique_tag_entity (tag, entity),
    KEY idx_entity (entity),
    KEY fk_user_tag (fk_user_creat),
    CONSTRAINT fk_tag_user FOREIGN KEY (fk_user_creat)
        REFERENCES llx_user(rowid) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_predmet_tagovi
-- Purpose: Many-to-many relationship between subjects and tags
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_predmet_tagovi (
    rowid int(11) NOT NULL AUTO_INCREMENT,
    fk_predmet int(11) NOT NULL,
    fk_tag int(11) NOT NULL,
    PRIMARY KEY (rowid),
    UNIQUE KEY unique_predmet_tag (fk_predmet, fk_tag),
    KEY fk_predmet_idx (fk_predmet),
    KEY fk_tag_idx (fk_tag),
    CONSTRAINT fk_predmet_tag_predmet FOREIGN KEY (fk_predmet)
        REFERENCES llx_a_predmet(ID_predmeta) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_predmet_tag_tag FOREIGN KEY (fk_tag)
        REFERENCES llx_a_tagovi(rowid) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_predmet_stranka
-- Purpose: Relationship between subjects and third parties (societe)
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_predmet_stranka (
    rowid int(11) NOT NULL AUTO_INCREMENT,
    ID_predmeta int(11) NOT NULL,
    fk_soc int(11) NOT NULL COMMENT 'Reference to llx_societe',
    role varchar(50) DEFAULT 'creator' COMMENT 'Role: creator, sender, receiver, etc.',
    date_stranka_opened datetime DEFAULT NULL,
    PRIMARY KEY (rowid),
    KEY fk_predmet_stranka (ID_predmeta),
    KEY fk_soc_stranka (fk_soc),
    CONSTRAINT fk_stranka_predmet FOREIGN KEY (ID_predmeta)
        REFERENCES llx_a_predmet(ID_predmeta) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_stranka_soc FOREIGN KEY (fk_soc)
        REFERENCES llx_societe(rowid) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- TABLE: a_arhiva
-- Purpose: Archive of closed/completed subjects
-- =============================================================================
CREATE TABLE IF NOT EXISTS llx_a_arhiva (
    ID_arhive int(11) NOT NULL AUTO_INCREMENT,
    ID_predmeta int(11) NOT NULL,
    klasa_predmeta varchar(50) NOT NULL,
    naziv_predmeta text NOT NULL,
    lokacija_arhive varchar(500) NOT NULL COMMENT 'Physical or digital archive location',
    broj_dokumenata int(11) DEFAULT 0,
    razlog_arhiviranja text,
    datum_arhiviranja timestamp DEFAULT CURRENT_TIMESTAMP,
    fk_user_arhivirao int(11) NOT NULL,
    status_arhive enum('active','deleted') DEFAULT 'active',
    PRIMARY KEY (ID_arhive),
    KEY fk_predmet_arhiva (ID_predmeta),
    KEY fk_user_arhiva (fk_user_arhivirao),
    KEY idx_status (status_arhive),
    KEY idx_datum (datum_arhiviranja),
    CONSTRAINT fk_arhiva_predmet FOREIGN KEY (ID_predmeta)
        REFERENCES llx_a_predmet(ID_predmeta) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_arhiva_user FOREIGN KEY (fk_user_arhivirao)
        REFERENCES llx_user(rowid) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- EXTEND EXISTING TABLE: llx_ecm_files
-- Purpose: Add digital signature detection columns
-- =============================================================================
-- Note: These ALTER statements check for column existence before adding
-- MySQL/MariaDB compatible approach using procedural blocks

-- Add digital_signature column
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'llx_ecm_files'
               AND COLUMN_NAME = 'digital_signature');
SET @sqlstmt := IF(@exist = 0,
                   'ALTER TABLE llx_ecm_files ADD COLUMN digital_signature TINYINT(1) DEFAULT 0 COMMENT ''Has digital signature (0=no, 1=yes)''',
                   'SELECT ''Column digital_signature already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add signature_info column
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'llx_ecm_files'
               AND COLUMN_NAME = 'signature_info');
SET @sqlstmt := IF(@exist = 0,
                   'ALTER TABLE llx_ecm_files ADD COLUMN signature_info JSON DEFAULT NULL COMMENT ''Signature metadata (JSON format)''',
                   'SELECT ''Column signature_info already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add signature_date column
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'llx_ecm_files'
               AND COLUMN_NAME = 'signature_date');
SET @sqlstmt := IF(@exist = 0,
                   'ALTER TABLE llx_ecm_files ADD COLUMN signature_date DATETIME DEFAULT NULL COMMENT ''Digital signature date''',
                   'SELECT ''Column signature_date already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add signer_name column
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'llx_ecm_files'
               AND COLUMN_NAME = 'signer_name');
SET @sqlstmt := IF(@exist = 0,
                   'ALTER TABLE llx_ecm_files ADD COLUMN signer_name VARCHAR(255) DEFAULT NULL COMMENT ''Name of the signer''',
                   'SELECT ''Column signer_name already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add signature_status column
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'llx_ecm_files'
               AND COLUMN_NAME = 'signature_status');
SET @sqlstmt := IF(@exist = 0,
                   'ALTER TABLE llx_ecm_files ADD COLUMN signature_status ENUM(''valid'',''invalid'',''expired'',''unknown'') DEFAULT ''unknown'' COMMENT ''Signature validation status''',
                   'SELECT ''Column signature_status already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for digital signature queries
SET @exist := (SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'llx_ecm_files'
               AND INDEX_NAME = 'idx_digital_signature');
SET @sqlstmt := IF(@exist = 0,
                   'ALTER TABLE llx_ecm_files ADD KEY idx_digital_signature (digital_signature)',
                   'SELECT ''Index idx_digital_signature already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


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
    nacin_otpreme enum('posta','email','rucno','courier') NOT NULL COMMENT 'Shipment method',

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


-- =============================================================================
-- INITIAL DATA (Optional)
-- =============================================================================
-- You can add initial/default data here if needed
-- Example: Default organization entry, default classification marks, etc.

-- =============================================================================
-- END OF SEUP DATABASE SCHEMA
-- =============================================================================
