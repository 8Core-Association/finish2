-- =====================================================
-- SEUP Modul - Tablica za Zaprimanje Dokumentacije
-- =====================================================
-- Tablica za evidenciju zaprimljene dokumentacije
-- od trećih osoba (ustanova, pošiljatelja)
-- =====================================================

CREATE TABLE IF NOT EXISTS a_zaprimanja (
  ID_zaprimanja INT(11) NOT NULL AUTO_INCREMENT,

  -- Veza s predmetom
  ID_predmeta INT(11) NOT NULL COMMENT 'Veza na a_predmet',

  -- Veza s dokumentom (akt ili prilog)
  fk_ecm_file INT(11) DEFAULT NULL COMMENT 'Link na zaprimljeni dokument (akt/prilog)',
  tip_dokumenta ENUM('akt', 'prilog', 'nedodjeljeni') DEFAULT 'nedodjeljeni' COMMENT 'Tip zaprimljenog dokumenta',

  -- Pošiljatelj (veza na a_posiljatelji)
  fk_posiljatelj INT(11) UNSIGNED DEFAULT NULL COMMENT 'Link na a_posiljatelji',
  posiljatelj_naziv VARCHAR(255) DEFAULT NULL COMMENT 'Naziv pošiljatelja (fallback ako nije u a_posiljatelji)',

  -- Datum i način zaprimanja
  datum_zaprimanja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Datum i vrijeme zaprimanja',
  nacin_zaprimanja ENUM('posta', 'email', 'rucno', 'fax', 'web', 'sluzben_put') DEFAULT 'posta' COMMENT 'Način zaprimanja',

  -- Broj priloga
  broj_priloga INT(11) DEFAULT 1 COMMENT 'Broj fizičkih priloga',

  -- Potvrda zaprimanja (ECM)
  fk_potvrda_ecm_file INT(11) DEFAULT NULL COMMENT 'Link na potvrdu zaprimanja (povratnica, potpis)',

  -- Opis
  opis_zaprimanja TEXT COMMENT 'Kratak opis zaprimljenog sadržaja',
  napomena TEXT COMMENT 'Interna napomena',

  -- Metapodaci
  fk_user_zaprimio INT(11) NOT NULL COMMENT 'Korisnik koji je zaprimio',
  datum_kreiranja DATETIME DEFAULT CURRENT_TIMESTAMP,
  entity INT(11) NOT NULL DEFAULT 1,

  PRIMARY KEY (ID_zaprimanja),
  KEY idx_predmet (ID_predmeta),
  KEY idx_posiljatelj (fk_posiljatelj),
  KEY idx_ecm_file (fk_ecm_file),
  KEY idx_datum (datum_zaprimanja),
  KEY fk_user (fk_user_zaprimio),
  KEY fk_potvrda (fk_potvrda_ecm_file)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Evidencija zaprimljene dokumentacije';
