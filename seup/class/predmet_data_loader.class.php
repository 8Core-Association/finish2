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

require_once __DIR__ . '/predmet_helper.class.php';
require_once __DIR__ . '/prilog_helper.class.php';

class Predmet_Data_Loader
{
    public static function loadPredmetDetails($db, $caseId)
    {
        $sql = "SELECT
                    p.ID_predmeta,
                    p.klasa_br,
                    p.sadrzaj,
                    p.dosje_broj,
                    p.godina,
                    p.predmet_rbr,
                    p.naziv_predmeta,
                    DATE_FORMAT(p.tstamp_created, '%d.%m.%Y') as datum_otvaranja,
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
                WHERE p.ID_predmeta = " . (int)$caseId;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            $obj->klasa_format = $obj->klasa_br . '-' . $obj->sadrzaj . '/' .
                                $obj->godina . '-' . $obj->dosje_broj . '/' .
                                $obj->predmet_rbr;
            return $obj;
        }

        return null;
    }

    public static function loadDocuments($db, $conf, $langs, $caseId)
    {
        $documentTableHTML = '';
        Predmet_helper::fetchUploadedDocuments($db, $conf, $documentTableHTML, $langs, $caseId);
        return $documentTableHTML;
    }

    public static function loadAvailableAkti($db, $caseId)
    {
        return Prilog_Helper::getAvailableAkti($db, $caseId);
    }

    public static function countDocuments($db, $conf, $caseId)
    {
        $relative_path = Predmet_helper::getPredmetFolderPath($caseId, $db);
        $ecm_filepath = rtrim($relative_path, '/');

        $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ecm_files
                WHERE filepath = '" . $db->escape($ecm_filepath) . "'
                AND entity = " . $conf->entity;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return $obj->count;
        }

        return 0;
    }
}
