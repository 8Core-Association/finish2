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
 * U skladu sa Zakonom o autorskom pravu i srodnim pravima 
 * (NN 167/03, 79/07, 80/11, 125/17), a osobito člancima 32. (pravo na umnožavanje), 35. 
 * (pravo na preradu i distribuciju) i 76. (kaznene odredbe), 
 * svako neovlašteno umnožavanje ili prerada ovog softvera smatra se prekršajem. 
 * Prema Kaznenom zakonu (NN 125/11, 144/12, 56/15), članak 228., stavak 1., 
 * prekršitelj se može kazniti novčanom kaznom ili zatvorom do jedne godine, 
 * a sud može izreći i dodatne mjere oduzimanja protivpravne imovinske koristi.
 * Bilo kakve izmjene, prijevodi, integracije ili dijeljenje koda bez izričitog pismenog 
 * odobrenja autora smatraju se kršenjem ugovora i zakona te će se pravno sankcionirati. 
 * Za sva pitanja, zahtjeve za licenciranjem ili dodatne informacije obratite se na info@8core.hr.
 */
/**
 *    \file       seup/pages/postavke.php
 *    \ingroup    seup
 *    \brief      Postavke SEUP sustava
 */

// === Dolibarr bootstrap (bez filozofije) ===
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
if (!$res) {
  $tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
  $tmp2 = realpath(__FILE__);
  $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
  while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
  if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
  if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
  if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
  if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
  if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
}
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Učitaj prijevode
$langs->loadLangs(array("seup@seup"));

// Klase modula
require_once __DIR__ . '/../class/klasifikacijska_oznaka.class.php';
require_once __DIR__ . '/../class/oznaka_ustanove.class.php';
require_once __DIR__ . '/../class/interna_oznaka_korisnika.class.php';
require_once __DIR__ . '/../class/changelog_sistem.class.php';

// === Helpers ===
function seup_db_prefix($db) {
  if (method_exists($db, 'prefix')) return $db->prefix();
  if (defined('MAIN_DB_PREFIX')) return MAIN_DB_PREFIX;
  return '';
}
$TABLE_POS = seup_db_prefix($db) . 'a_posiljatelji';

// === Create-if-not-exists tablica za Treće Osobe (a_posiljatelji) ===
$db->query("CREATE TABLE IF NOT EXISTS `".$db->escape($TABLE_POS)."`(
  `rowid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `naziv` VARCHAR(255) NOT NULL,
  `adresa` VARCHAR(255) DEFAULT NULL,
  `oib` VARCHAR(32) DEFAULT NULL,
  `telefon` VARCHAR(64) DEFAULT NULL,
  `kontakt_osoba` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `datec` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tms` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(`rowid`),
  UNIQUE KEY `uq_oib` (`oib`)
) ENGINE=InnoDB");

// === Create-if-not-exists tablica za Vrste Arhivskog Gradiva (a_arhivska_gradiva) ===
$TABLE_ARH = seup_db_prefix($db) . 'a_arhivska_gradiva';
$db->query("CREATE TABLE IF NOT EXISTS `".$db->escape($TABLE_ARH)."`(
  `rowid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `oznaka` VARCHAR(100) NOT NULL,
  `vrsta_gradiva` VARCHAR(255) NOT NULL,
  `opisi_napomene` TEXT DEFAULT NULL,
  `datec` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tms` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(`rowid`),
  UNIQUE KEY `uq_oznaka` (`oznaka`)
) ENGINE=InnoDB");

// === Osn. varijable ===
$action = GETPOST('action', 'aZ09');
$form = new Form($db);
$formfile = new FormFile($db);
$now = dol_now();
ob_start();

// Sigurnost thirdparty ograničenja
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) { $action=''; $socid=$user->socid; }

// Header i assets
llxHeader("", "SEUP - Postavke", '', '', 0, 0, '', '', '', 'mod-seup page-postavke');
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
print '<script src="' . DOL_URL_ROOT . '/custom/seup/js/messages.js"></script>';

// === Data: Oznaka ustanove (load) ===
$podaci_postoje = null;
$sql = "SELECT ID_ustanove, singleton, code_ustanova, name_ustanova FROM " . MAIN_DB_PREFIX . "a_oznaka_ustanove WHERE singleton = 1 LIMIT 1";
$resql = $db->query($sql);
$ID_ustanove = 0;
if ($resql && $db->num_rows($resql) > 0) {
  $podaci_postoje = $db->fetch_object($resql);
  $ID_ustanove = $podaci_postoje->ID_ustanove;
}

// === Data: svi aktivni korisnici (za interne oznake) ===
$listUsers = [];
$userStatic = new User($db);
$resql = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1 ORDER BY lastname ASC");
if ($resql) { while ($o = $db->fetch_object($resql)) { $userStatic->fetch($o->rowid); $listUsers[] = clone $userStatic; } }

/*************************************
 *  POST SUBMIT HANDLERS
 *************************************/

// 1) INTERNA OZNAKA KORISNIKA (ADD/UPDATE je već u tvojem kodu – ostavljeno)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1.a Add interna oznaka
  if (isset($_POST['action_oznaka']) && $_POST['action_oznaka'] === 'add') {
    $interna_oznaka_korisnika = new Interna_oznaka_korisnika();
    $interna_oznaka_korisnika->setIme_prezime(GETPOST('ime_user', 'alphanohtml'));
    $interna_oznaka_korisnika->setRbr_korisnika(GETPOST('redni_broj', 'int'));
    $interna_oznaka_korisnika->setRadno_mjesto_korisnika(GETPOST('radno_mjesto_korisnika', 'alphanohtml'));

    if (empty($interna_oznaka_korisnika->getIme_prezime()) || $interna_oznaka_korisnika->getRbr_korisnika()==='' || empty($interna_oznaka_korisnika->getRadno_mjesto_korisnika())) {
      setEventMessages($langs->trans("All fields are required"), null, 'errors');
    } elseif (!preg_match('/^\d{1,2}$/', (string)$interna_oznaka_korisnika->getRbr_korisnika())) {
      setEventMessages($langs->trans("Invalid serial number (vrijednosti moraju biti u rasponu 0 - 99)"), null, 'errors');
    } else {
      $resCheck = $db->query("SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika WHERE rbr = '" . $db->escape($interna_oznaka_korisnika->getRbr_korisnika()) . "'");
      if ($resCheck) {
        $obj = $db->fetch_object($resCheck);
        if ($obj->cnt > 0) setEventMessages($langs->trans("Korisnik s tim rednim brojem vec postoji u bazi"), null, 'errors');
        else {
          $db->begin();
          $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika (ID_ustanove, ime_prezime, rbr, naziv) VALUES ("
                . (int)$ID_ustanove . ", '"
                . $db->escape($interna_oznaka_korisnika->getIme_prezime()) . "', '"
                . $db->escape($interna_oznaka_korisnika->getRbr_korisnika()) . "', '"
                . $db->escape($interna_oznaka_korisnika->getRadno_mjesto_korisnika()) . "')";
          if ($db->query($sql)) { $db->commit(); setEventMessages($langs->trans("Intena Oznaka Korisnika uspjesno dodana"), null, 'mesgs'); }
          else setEventMessages($langs->trans("Database error: ") . $db->lasterror(), null, 'errors');
        }
      }
    }
  }

  // 2) OZNAKA USTANOVE (AJAX JSON kao u tvom kodu)
  if (isset($_POST['action_ustanova'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if (function_exists('ob_get_level') && ob_get_level() > 0) { ob_end_clean(); }

    $oznaka_ustanove = new Oznaka_ustanove();
    try {
      $db->begin();
      if ($podaci_postoje) $oznaka_ustanove->setID_oznaka_ustanove($podaci_postoje->singleton);
      $oznaka_ustanove->setOznaka_ustanove(GETPOST('code_ustanova', 'alphanohtml'));
      if (!preg_match('/^\d{4}-\d-\d$/', $oznaka_ustanove->getOznaka_ustanove())) throw new Exception($langs->trans("Neispravan format Oznake Ustanove"));
      $oznaka_ustanove->setNaziv_ustanove(GETPOST('name_ustanova', 'alphanohtml'));
      $act = GETPOST('action_ustanova', 'alpha');
      if ($act === 'add' && !$podaci_postoje) {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_oznaka_ustanove (code_ustanova, name_ustanova) VALUES ('".$db->escape($oznaka_ustanove->getOznaka_ustanove())."','".$db->escape($oznaka_ustanove->getNaziv_ustanove())."')";
      } else {
        if (!is_object($podaci_postoje) || empty($podaci_postoje->singleton)) throw new Exception($langs->trans('RecordNotFound'));
        $oznaka_ustanove->setID_oznaka_ustanove($podaci_postoje->singleton);
        $sql = "UPDATE " . MAIN_DB_PREFIX . "a_oznaka_ustanove SET code_ustanova='".$db->escape($oznaka_ustanove->getOznaka_ustanove())."', name_ustanova='".$db->escape($oznaka_ustanove->getNaziv_ustanove())."' WHERE ID_ustanove='".$db->escape($oznaka_ustanove->getID_oznaka_ustanove())."'";
      }
      if (!$db->query($sql)) throw new Exception($db->lasterror());
      $db->commit();
      echo json_encode(['success'=>true,'message'=>$langs->trans($act==='add'?'Oznaka Ustanove Uspjesno dodana':'Oznaka Ustanove uspjesno azurirana'),'data'=>['code_ustanova'=>$oznaka_ustanove->getOznaka_ustanove(),'name_ustanova'=>$oznaka_ustanove->getNaziv_ustanove()]]); exit;
    } catch (Exception $e) {
      $db->rollback(); http_response_code(500); echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit;
    }
  }

  // 3) KLASIFIKACIJSKA OZNAKA (ostavljeno prema tvom kodu – skraćeno, bez izmjena logike)
  if (isset($_POST['action_klasifikacija'])) {
    $klasifikacijska_oznaka = new Klasifikacijska_oznaka();
    $klasifikacijska_oznaka->setKlasa_br(GETPOST('klasa_br', 'int'));
    if (!preg_match('/^\d{3}$/', (string)$klasifikacijska_oznaka->getKlasa_br())) { setEventMessages($langs->trans("ErrorKlasaBrFormat"), null, 'errors'); $error++; }
    $klasifikacijska_oznaka->setSadrzaj(GETPOST('sadrzaj', 'int'));
    if (!preg_match('/^\d{2}$/', (string)$klasifikacijska_oznaka->getSadrzaj()) || $klasifikacijska_oznaka->getSadrzaj() > 99) { setEventMessages($langs->trans("ErrorSadrzajFormat"), null, 'errors'); $error++; }
    $klasifikacijska_oznaka->setDosjeBroj(GETPOST('dosje_br', 'int'));
    if (!preg_match('/^\d{2}$/', (string)$klasifikacijska_oznaka->getDosjeBroj()) || $klasifikacijska_oznaka->getDosjeBroj() > 50) { setEventMessages($langs->trans("ErrorDosjeBrojFormat"), null, 'errors'); $error++; }
    $klasifikacijska_oznaka->setVrijemeCuvanja($klasifikacijska_oznaka->CastVrijemeCuvanjaToInt(GETPOST('vrijeme_cuvanja', 'int')));
    if (!preg_match('/^\d{1,2}$/', (string)$klasifikacijska_oznaka->getVrijemeCuvanja()) || $klasifikacijska_oznaka->getVrijemeCuvanja() > 10) { setEventMessages($langs->trans("ErrorVrijemeCuvanjaFormat"), null, 'errors'); $error++; }
    $klasifikacijska_oznaka->setOpisKlasifikacijskeOznake(GETPOST('opis_klasifikacije', 'alphanohtml'));

    if ($_POST['action_klasifikacija'] === 'add') {
      $klasa_br = $db->escape($klasifikacijska_oznaka->getKlasa_br()); $sadrzaj=$db->escape($klasifikacijska_oznaka->getSadrzaj()); $dosje_br=$db->escape($klasifikacijska_oznaka->getDosjeBroj());
      $rez = $db->query("SELECT ID_klasifikacijske_oznake FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka WHERE klasa_broj='$klasa_br' AND sadrzaj='$sadrzaj' AND dosje_broj='$dosje_br'");
      if ($rez && $db->num_rows($rez)>0) { setEventMessages($langs->trans("KombinacijaKlaseSadrzajaDosjeaVecPostoji"), null, 'errors'); $error++; }
      else {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka (ID_ustanove, klasa_broj, sadrzaj, dosje_broj, vrijeme_cuvanja, opis_klasifikacijske_oznake) VALUES ("
            . (int)$ID_ustanove . ", '".$db->escape($klasifikacijska_oznaka->getKlasa_br())."','".$db->escape($klasifikacijska_oznaka->getSadrzaj())."','".$db->escape($klasifikacijska_oznaka->getDosjeBroj())."','".$db->escape($klasifikacijska_oznaka->getVrijemeCuvanja())."','".$db->escape($klasifikacijska_oznaka->getOpisKlasifikacijskeOznake())."')";
        if (!$db->query($sql)) setEventMessages($langs->trans("ErrorDatabase") . ": " . $db->lasterror(), null, 'errors'); else setEventMessages($langs->trans("Uspjesno pohranjena klasifikacijska oznaka"), null, 'mesgs');
      }
    } elseif ($_POST['action_klasifikacija'] === 'delete') {
      $id_oznake = GETPOST('id_klasifikacijske_oznake', 'int');
      if (!$id_oznake) setEventMessages($langs->trans("ErrorMissingRecordID"), null, 'errors');
      else {
        $db->begin();
        $ok=$db->query("DELETE FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka WHERE ID_klasifikacijske_oznake=".(int)$id_oznake);
        if ($ok) { $db->commit(); setEventMessages($langs->trans("KlasifikacijskaOznakaUspjesnoObrisana"), null, 'mesgs'); header('Location: '.$_SERVER['PHP_SELF']); exit; }
        else { $db->rollback(); setEventMessages($langs->trans("ErrorDeleteFailed") . ": " . $db->lasterror(), null, 'errors'); }
      }
    }
  }

  // 4) TREĆE OSOBE – NOVI HANDLER (a_posiljatelji, hard delete, + email)
  if (isset($_POST['action_treca_osoba'])) {
    $act = GETPOST('action_treca_osoba','alpha'); // add|update|delete
    $rowid = (int) GETPOST('rowid','int');
    $naziv = trim(GETPOST('to_naziv','restricthtml'));
    $adresa = trim(GETPOST('to_adresa','restricthtml'));
    $oib = trim(GETPOST('to_oib','alphanohtml'));
    $telefon = trim(GETPOST('to_telefon','alphanohtml'));
    $kontakt_osoba = trim(GETPOST('to_kontakt_osoba','restricthtml'));
    $email = trim(GETPOST('to_email','alphanohtml'));

    // Validacije
    $errs = array();
    if ($act==='add' || $act==='update') {
      if ($naziv==='') $errs[] = "Naziv je obavezan.";
      if ($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = "E-mail nije valjan.";
    }
    if (empty($errs)) {
      if ($act==='add') {
        $db->begin();
        if ($oib!=='') {
          $chk = $db->query("SELECT rowid FROM `".$db->escape($TABLE_POS)."` WHERE oib='".$db->escape($oib)."' LIMIT 1");
          if ($chk && $db->num_rows($chk)>0) { $db->rollback(); setEventMessages("Postoji zapis s istim OIB-om.", null, 'errors'); }
          else {
            $sql = "INSERT INTO `".$db->escape($TABLE_POS)."` (naziv,adresa,oib,telefon,kontakt_osoba,email,datec) VALUES ('".$db->escape($naziv)."','".$db->escape($adresa)."',".($oib!==''?"'".$db->escape($oib)."'":"NULL").",'".$db->escape($telefon)."','".$db->escape($kontakt_osoba)."',".($email!==''?"'".$db->escape($email)."'":"NULL").",NOW())";
            $ok = $db->query($sql);
            if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=trece_osobe&msg=created"); exit; }
            else { $db->rollback(); setEventMessages("Greška pri spremanju.", null, 'errors'); }
          }
        } else {
          $sql = "INSERT INTO `".$db->escape($TABLE_POS)."` (naziv,adresa,oib,telefon,kontakt_osoba,email,datec) VALUES ('".$db->escape($naziv)."','".$db->escape($adresa)."',NULL,'".$db->escape($telefon)."','".$db->escape($kontakt_osoba)."',".($email!==''?"'".$db->escape($email)."'":"NULL").",NOW())";
          $ok = $db->query($sql);
          if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=trece_osobe&msg=created"); exit; }
          else { $db->rollback(); setEventMessages("Greška pri spremanju.", null, 'errors'); }
        }
      } elseif ($act==='update') {
        if ($rowid<=0) setEventMessages("Nedostaje ID zapisa.", null, 'errors');
        else {
          $db->begin();
          if ($oib!=='') {
            $chk = $db->query("SELECT rowid FROM `".$db->escape($TABLE_POS)."` WHERE oib='".$db->escape($oib)."' AND rowid!=".(int)$rowid." LIMIT 1");
            if ($chk && $db->num_rows($chk)>0) { $db->rollback(); setEventMessages("OIB već postoji na drugom zapisu.", null, 'errors'); $rowid=0; }
          }
          if ($rowid>0) {
            $sql = "UPDATE `".$db->escape($TABLE_POS)."` SET
                    naziv='".$db->escape($naziv)."',
                    adresa='".$db->escape($adresa)."',
                    oib=".($oib!==''?"'".$db->escape($oib)."'":"NULL").",
                    telefon='".$db->escape($telefon)."',
                    kontakt_osoba='".$db->escape($kontakt_osoba)."',
                    email=".($email!==''?"'".$db->escape($email)."'":"NULL")."
                    WHERE rowid=".(int)$rowid." LIMIT 1";
            $ok = $db->query($sql);
            if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=trece_osobe&msg=updated"); exit; }
            else { $db->rollback(); setEventMessages("Greška pri ažuriranju.", null, 'errors'); }
          }
        }
      } elseif ($act==='delete') {
        $id = (int) GETPOST('id','int');
        if ($id>0) {
          $db->begin();
          $ok = $db->query("DELETE FROM `".$db->escape($TABLE_POS)."` WHERE rowid=".$id." LIMIT 1");
          if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=trece_osobe&msg=deleted"); exit; }
          else { $db->rollback(); setEventMessages("Brisanje nije uspjelo.", null, 'errors'); }
        } else setEventMessages("Nedostaje ID za brisanje.", null, 'errors');
      }
    } else {
      setEventMessages(implode(' ',$errs), null, 'errors');
    }
  }

  // 5) VRSTE ARHIVSKOG GRADIVA – NOVI HANDLER
  if (isset($_POST['action_arhivska_gradiva'])) {
    $act = GETPOST('action_arhivska_gradiva','alpha'); // add|update|delete
    $rowid = (int) GETPOST('rowid','int');
    $oznaka = trim(GETPOST('ag_oznaka','alphanohtml'));
    $vrsta_gradiva = trim(GETPOST('ag_vrsta_gradiva','restricthtml'));
    $opisi_napomene = trim(GETPOST('ag_opisi_napomene','restricthtml'));

    // Validacije
    $errs = array();
    if ($act==='add' || $act==='update') {
      if ($oznaka==='') $errs[] = "Oznaka je obavezna.";
      if ($vrsta_gradiva==='') $errs[] = "Vrsta gradiva je obavezna.";
    }
    if (empty($errs)) {
      if ($act==='add') {
        $db->begin();
        $chk = $db->query("SELECT rowid FROM `".$db->escape($TABLE_ARH)."` WHERE oznaka='".$db->escape($oznaka)."' LIMIT 1");
        if ($chk && $db->num_rows($chk)>0) { $db->rollback(); setEventMessages("Postoji zapis s istom oznakom.", null, 'errors'); }
        else {
          $sql = "INSERT INTO `".$db->escape($TABLE_ARH)."` (oznaka,vrsta_gradiva,opisi_napomene,datec) VALUES ('".$db->escape($oznaka)."','".$db->escape($vrsta_gradiva)."',".($opisi_napomene!==''?"'".$db->escape($opisi_napomene)."'":"NULL").",NOW())";
          $ok = $db->query($sql);
          if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=arhivska_gradiva&msg=created"); exit; }
          else { $db->rollback(); setEventMessages("Greška pri spremanju.", null, 'errors'); }
        }
      } elseif ($act==='update') {
        if ($rowid<=0) setEventMessages("Nedostaje ID zapisa.", null, 'errors');
        else {
          $db->begin();
          $chk = $db->query("SELECT rowid FROM `".$db->escape($TABLE_ARH)."` WHERE oznaka='".$db->escape($oznaka)."' AND rowid!=".(int)$rowid." LIMIT 1");
          if ($chk && $db->num_rows($chk)>0) { $db->rollback(); setEventMessages("Oznaka već postoji na drugom zapisu.", null, 'errors'); $rowid=0; }
          if ($rowid>0) {
            $sql = "UPDATE `".$db->escape($TABLE_ARH)."` SET
                    oznaka='".$db->escape($oznaka)."',
                    vrsta_gradiva='".$db->escape($vrsta_gradiva)."',
                    opisi_napomene=".($opisi_napomene!==''?"'".$db->escape($opisi_napomene)."'":"NULL")."
                    WHERE rowid=".(int)$rowid." LIMIT 1";
            $ok = $db->query($sql);
            if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=arhivska_gradiva&msg=updated"); exit; }
            else { $db->rollback(); setEventMessages("Greška pri ažuriranju.", null, 'errors'); }
          }
        }
      } elseif ($act==='delete') {
        $id = (int) GETPOST('id','int');
        if ($id>0) {
          $db->begin();
          $ok = $db->query("DELETE FROM `".$db->escape($TABLE_ARH)."` WHERE rowid=".$id." LIMIT 1");
          if ($ok) { $db->commit(); header("Location: ".$_SERVER['PHP_SELF']."?tab=arhivska_gradiva&msg=deleted"); exit; }
          else { $db->rollback(); setEventMessages("Brisanje nije uspjelo.", null, 'errors'); }
        } else setEventMessages("Nedostaje ID za brisanje.", null, 'errors');
      }
    } else {
      setEventMessages(implode(' ',$errs), null, 'errors');
    }
  }
}

// Flash poruke – veži na aktivni tab
$tab   = GETPOST('tab','alphanohtml');
$flash = GETPOST('msg','alphanohtml');

if ($tab === 'trece_osobe') {
    if ($flash === 'created') setEventMessages('Zapis je dodan.', null, 'mesgs');
    elseif ($flash === 'updated') setEventMessages('Zapis je ažuriran.', null, 'mesgs');
    elseif ($flash === 'deleted') setEventMessages('Zapis je obrisan.', null, 'mesgs');
}

if ($tab === 'arhivska_gradiva') {
    if ($flash === 'created') setEventMessages('Vrsta arhivskog gradiva je dodana.', null, 'mesgs');
    elseif ($flash === 'updated') setEventMessages('Vrsta arhivskog gradiva je ažurirana.', null, 'mesgs');
    elseif ($flash === 'deleted') setEventMessages('Vrsta arhivskog gradiva je obrisana.', null, 'mesgs');
}
// === UI ===
print '<main class="seup-settings-hero">';
print '<div class="seup-floating-elements">'; for ($i=1;$i<=5;$i++) print '<div class="seup-floating-element"></div>'; print '</div>';
print '<div class="seup-settings-content">';
print '<div class="seup-settings-header">';
print '<h1 class="seup-settings-title">Postavke Sustava</h1>';
print '<p class="seup-settings-subtitle">Konfigurirajte osnovne parametre, korisničke oznake i klasifikacijski sustav</p>';
print '</div>';

// Settings Cards Grid - 4 columns, 2 rows
print '<div class="seup-settings-cards-grid">';

// Card 1: Klasifikacijske oznake
print '<div class="seup-settings-card-trigger" data-modal="klasifikacijskeOznakeModal">';
print '<div class="seup-card-icon"><i class="fas fa-sitemap"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Klasifikacijske Oznake</h3>';
print '<p class="seup-card-description">Upravljanje sustavom klasifikacije dokumentacije</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 2: Interne oznake korisnika
print '<div class="seup-settings-card-trigger" data-modal="interneOznakeModal">';
print '<div class="seup-card-icon"><i class="fas fa-users"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Interne Oznake Korisnika</h3>';
print '<p class="seup-card-description">Upravljanje korisničkim oznakama i radnim mjestima</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 3: Oznaka ustanove
print '<div class="seup-settings-card-trigger" data-modal="oznakaUstanoveModal">';
print '<div class="seup-card-icon"><i class="fas fa-building"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Oznaka Ustanove</h3>';
print '<p class="seup-card-description">Osnovni podaci o ustanovi i identifikacijska oznaka</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 4: Treće Osobe
print '<div class="seup-settings-card-trigger" data-modal="treceOsobeModal">';
print '<div class="seup-card-icon"><i class="fas fa-handshake"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Treće Osobe</h3>';
print '<p class="seup-card-description">Suradnici i vanjski partneri</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 5: Vrste Arhivskog Gradiva
print '<div class="seup-settings-card-trigger" data-modal="arhivskaGradivaModal">';
print '<div class="seup-card-icon"><i class="fas fa-archive"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Vrste Arhivskog Gradiva</h3>';
print '<p class="seup-card-description">Upravljanje vrstama arhivskog gradiva</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</div>';

// Card 6: Zaposlenici (Admin)
print '<a href="' . DOL_URL_ROOT . '/user/list.php" class="seup-settings-card-trigger">';
print '<div class="seup-card-icon"><i class="fas fa-users-cog"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Zaposlenici (Admin)</h3>';
print '<p class="seup-card-description">Upravljanje zaposlenicima ustanove</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</a>';

// Card 7: Podaci o Ustanovi (Admin)
print '<a href="' . DOL_URL_ROOT . '/admin/company.php" class="seup-settings-card-trigger">';
print '<div class="seup-card-icon"><i class="fas fa-building-shield"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Podaci o Ustanovi (Admin)</h3>';
print '<p class="seup-card-description">Osnovni podaci pravne osobe</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</a>';

// Card 8: Backup i Izvoz (Admin)
print '<a href="' . DOL_URL_ROOT . '/admin/tools/dolibarr_export.php" class="seup-settings-card-trigger">';
print '<div class="seup-card-icon"><i class="fas fa-download"></i></div>';
print '<div class="seup-card-content">';
print '<h3 class="seup-card-title">Backup i Izvoz (Admin)</h3>';
print '<p class="seup-card-description">Izvoz i sigurnosne kopije podataka</p>';
print '</div>';
print '<i class="fas fa-chevron-right seup-card-arrow"></i>';
print '</a>';

print '</div>'; // seup-settings-cards-grid

// Modal 1: Klasifikacijske oznake
print '<div class="seup-modal" id="klasifikacijskeOznakeModal">';
print '<div class="seup-modal-content seup-modal-large">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-sitemap me-2"></i>Klasifikacijske Oznake</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form">';
print '<input type="hidden" id="hidden_id_klasifikacijske_oznake" name="id_klasifikacijske_oznake" value="">';
print '<div class="seup-form-grid seup-grid-3">';
print '<div class="seup-form-group seup-autocomplete-container"><label class="seup-label">Klasa broj (000)</label><input type="text" id="klasa_br" name="klasa_br" class="seup-input" pattern="\\d{3}" maxlength="3" placeholder="000" autocomplete="off"><div id="autocomplete-results" class="seup-autocomplete-dropdown"></div></div>';
print '<div class="seup-form-group"><label class="seup-label">Sadržaj (00)</label><input type="text" id="sadrzaj" name="sadrzaj" class="seup-input" pattern="\\d{2}" maxlength="2" placeholder="00"></div>';
print '<div class="seup-form-group"><label class="seup-label">Dosje broj</label><select id="dosje_br" name="dosje_br" class="seup-select" required><option value="">Odaberite dosje</option>';
for ($i=1;$i<=50;$i++){ $val=sprintf('%02d',$i); print '<option value="'.$val.'">'.$val.'</option>'; }
print '</select></div></div>';
print '<div class="seup-form-grid"><div class="seup-form-group"><label class="seup-label">Vrijeme čuvanja</label><select id="vrijeme_cuvanja" name="vrijeme_cuvanja" class="seup-select" required><option value="permanent">Trajno</option>';
for ($g=1;$g<=10;$g++) print '<option value="'.$g.'">'.$g.' godina</option>';
print '</select></div><div class="seup-form-group"><label class="seup-label">Opis klasifikacije</label><textarea id="opis_klasifikacije" name="opis_klasifikacije" class="seup-textarea" rows="3"></textarea></div></div>';
print '<div class="seup-form-actions"><button type="submit" name="action_klasifikacija" value="add" class="seup-btn seup-btn-primary"><i class="fas fa-plus"></i> Dodaj</button><button type="submit" name="action_klasifikacija" value="update" class="seup-btn seup-btn-secondary"><i class="fas fa-edit"></i> Ažuriraj</button><button type="submit" name="action_klasifikacija" value="delete" class="seup-btn seup-btn-danger"><i class="fas fa-trash"></i> Obriši</button></div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Modal 2: Interne oznake korisnika
print '<div class="seup-modal" id="interneOznakeModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-users me-2"></i>Interne Oznake Korisnika</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form">';
print '<div class="seup-form-grid"><div class="seup-form-group"><label class="seup-label">Korisnik</label><select name="ime_user" id="ime_user" class="seup-select" required><option value="">Odaberite korisnika</option>';
foreach ($listUsers as $u) { print '<option value="'.htmlspecialchars($u->getFullName($langs)).'">'.htmlspecialchars($u->getFullName($langs)).'</option>'; }
print '</select></div><div class="seup-form-group"><label class="seup-label">Redni broj (0-99)</label><input type="number" name="redni_broj" id="redni_broj" class="seup-input" min="0" max="99" required></div></div>';
print '<div class="seup-form-group"><label class="seup-label">Radno mjesto</label><input type="text" name="radno_mjesto_korisnika" id="radno_mjesto_korisnika" class="seup-input" required></div>';
print '<div class="seup-form-actions"><button type="submit" name="action_oznaka" value="add" class="seup-btn seup-btn-primary"><i class="fas fa-plus"></i> Dodaj</button><button type="submit" name="action_oznaka" value="update" class="seup-btn seup-btn-secondary"><i class="fas fa-edit"></i> Ažuriraj</button><button type="submit" name="action_oznaka" value="delete" class="seup-btn seup-btn-danger"><i class="fas fa-trash"></i> Obriši</button></div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Modal 3: Oznaka ustanove
print '<div class="seup-modal" id="oznakaUstanoveModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-building me-2"></i>Oznaka Ustanove</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" id="ustanova-form" class="seup-form">';
print '<input type="hidden" name="action_ustanova" id="form-action" value="'.($podaci_postoje ? 'update' : 'add').'">';
print '<div id="messageDiv" class="seup-alert d-none" role="alert"></div>';
print '<div class="seup-form-grid"><div class="seup-form-group"><label class="seup-label">Oznaka (format: 0000-0-0)</label><input type="text" id="code_ustanova" name="code_ustanova" class="seup-input" pattern="^\\d{4}-\\d-\\d$" placeholder="0000-0-0" required value="'.($podaci_postoje?htmlspecialchars($podaci_postoje->code_ustanova):'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">Naziv ustanove</label><input type="text" id="name_ustanova" name="name_ustanova" class="seup-input" placeholder="Unesite naziv ustanove" required value="'.($podaci_postoje?htmlspecialchars($podaci_postoje->name_ustanova):'').'"></div></div>';
print '<div class="seup-form-actions"><button type="submit" id="ustanova-submit" class="seup-btn seup-btn-primary"><i class="fas fa-'.($podaci_postoje?'edit':'plus').'"></i> '.($podaci_postoje?'Ažuriraj':'Dodaj').'</button></div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Modal 4: Treće Osobe
print '<div class="seup-modal" id="treceOsobeModal">';
print '<div class="seup-modal-content seup-modal-large">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-handshake me-2"></i>Treće Osobe</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';

// Edit fetch
$E = null; $edit = (int) GETPOST('edit','int');
if ($edit>0) { $res=$db->query("SELECT * FROM `".$db->escape($TABLE_POS)."` WHERE rowid=".$edit." LIMIT 1"); if ($res) $E=$db->fetch_object($res); }
$V = function($x){ return $x?htmlspecialchars($x,ENT_QUOTES,'UTF-8'):''; };

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form">';
if ($E) print '<input type="hidden" name="rowid" value="'.(int)$E->rowid.'">';
print '<div class="seup-form-grid seup-grid-2">';
print '<div class="seup-form-group"><label class="seup-label">Naziv / Ime i prezime *</label><input type="text" name="to_naziv" class="seup-input" required value="'.$V($E?$E->naziv:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">OIB</label><input type="text" name="to_oib" class="seup-input" pattern="\\d{11}" maxlength="11" value="'.$V($E?$E->oib:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">Adresa</label><input type="text" name="to_adresa" class="seup-input" value="'.$V($E?$E->adresa:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">Kontakt osoba</label><input type="text" name="to_kontakt_osoba" class="seup-input" value="'.$V($E?$E->kontakt_osoba:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">Kontakt telefon</label><input type="text" name="to_telefon" class="seup-input" value="'.$V($E?$E->telefon:'').'"></div>';
print '<div class="seup-form-group"><label class="seup-label">E-mail</label><input type="email" name="to_email" class="seup-input" value="'.$V($E?$E->email:'').'"></div>';
print '</div>';
print '<div class="seup-form-actions">';
print '<button type="submit" name="action_treca_osoba" value="'.($E?'update':'add').'" class="seup-btn seup-btn-'.($E?'secondary':'primary').'"><i class="fas fa-'.($E?'edit':'plus').'"></i> '.($E?'Ažuriraj':'Dodaj').'</button>'; 
print ' <button type="reset" class="seup-btn seup-btn-secondary" id="btnPonisti">Poništi</button>';
if ($E) print ' <a class="seup-btn" href="'.$_SERVER['PHP_SELF'].'?tab=trece_osobe">Odustani</a>';
print '</div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

// Modal 5: Vrste Arhivskog Gradiva
print '<div class="seup-modal" id="arhivskaGradivaModal">';
print '<div class="seup-modal-content seup-modal-large">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-archive me-2"></i>Vrste Arhivskog Gradiva</h5>';
print '<button type="button" class="seup-modal-close">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';

// Edit fetch za arhivska gradiva
$A = null; $edit_arh = (int) GETPOST('edit_arh','int');
if ($edit_arh>0) { $res=$db->query("SELECT * FROM `".$db->escape($TABLE_ARH)."` WHERE rowid=".$edit_arh." LIMIT 1"); if ($res) $A=$db->fetch_object($res); }

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" class="seup-form">';
if ($A) print '<input type="hidden" name="rowid" value="'.(int)$A->rowid.'">';
print '<div class="seup-form-grid seup-grid-2">';
print '<div class="seup-form-group"><label class="seup-label">Oznaka *</label><input type="text" name="ag_oznaka" class="seup-input" required value="'.$V($A?$A->oznaka:'').'" placeholder="Unesite oznaku"></div>';
print '<div class="seup-form-group"><label class="seup-label">Vrsta Gradiva *</label><input type="text" name="ag_vrsta_gradiva" class="seup-input" required value="'.$V($A?$A->vrsta_gradiva:'').'" placeholder="Unesite vrstu gradiva"></div>';
print '</div>';
print '<div class="seup-form-group"><label class="seup-label">Opisi/Napomene</label><textarea name="ag_opisi_napomene" class="seup-textarea" rows="4" placeholder="Unesite opise ili napomene...">'.$V($A?$A->opisi_napomene:'').'</textarea></div>';
print '<div class="seup-form-actions">';
print '<button type="submit" name="action_arhivska_gradiva" value="'.($A?'update':'add').'" class="seup-btn seup-btn-'.($A?'secondary':'primary').'"><i class="fas fa-'.($A?'edit':'plus').'"></i> '.($A?'Ažuriraj':'Dodaj').'</button>';
print ' <button type="reset" class="seup-btn seup-btn-secondary" id="btnPonistiArh">Poništi</button>';
if ($A) print ' <a class="seup-btn" href="'.$_SERVER['PHP_SELF'].'?tab=arhivska_gradiva">Odustani</a>';
print '</div>';
print '</form>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-modal-close">Zatvori</button>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // content

// Copyright footer
print '<footer class="seup-footer">';
print '<div class="seup-footer-content">';
print '<div class="seup-footer-left">';
print '<p>Sva prava pridržana © <a href="https://8core.hr" target="_blank" rel="noopener">8Core Association</a> 2014 - ' . date('Y') . '</p>';
print '</div>';
print '<div class="seup-footer-right">';
print '<p class="seup-version">' . Changelog_Sistem::getVersion() . '</p>';
print '</div>';
print '</div>';
print '</footer>';

// JS
print '<script src="/custom/seup/js/seup-modern.js"></script>';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Modal functionality
  const modalTriggers = document.querySelectorAll('.seup-settings-card-trigger');
  const modals = document.querySelectorAll('.seup-modal');
  const modalCloses = document.querySelectorAll('.seup-modal-close');

  // Open modal
  modalTriggers.forEach(trigger => {
    trigger.addEventListener('click', function() {
      const modalId = this.getAttribute('data-modal');
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('show');
      }
    });
  });

  // Close modal
  modalCloses.forEach(closeBtn => {
    closeBtn.addEventListener('click', function() {
      const modal = this.closest('.seup-modal');
      if (modal) {
        modal.classList.remove('show');
      }
    });
  });

  // Close modal when clicking outside
  modals.forEach(modal => {
    modal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.remove('show');
      }
    });
  });

  // Ustanova AJAX
  const form = document.getElementById('ustanova-form');
  const actionField = document.getElementById('form-action');
  const btnSubmit = document.getElementById('ustanova-submit');
  if (form && btnSubmit) {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      btnSubmit.classList.add('seup-loading'); btnSubmit.disabled = true;
      const formData = new FormData(this);
      formData.append('action_ustanova', btnSubmit.textContent.includes('Dodaj') ? 'add' : 'update');
      try {
        const response = await fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', { method:'POST', body:formData, headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'} });
        if (!response.ok) throw new Error('HTTP '+response.status);
        const result = await response.json();
        if (result.success) {
          actionField.value = 'update';
          btnSubmit.innerHTML = '<i class="fas fa-edit"></i> Ažuriraj';
          btnSubmit.classList.remove('seup-btn-primary'); btnSubmit.classList.add('seup-btn-secondary');
          document.getElementById('code_ustanova').value = result.data.code_ustanova;
          document.getElementById('name_ustanova').value = result.data.name_ustanova;
          showMessage(result.message, 'success');
        } else { showMessage(result.error || 'Greška pri spremanju', 'error'); }
      } catch (e) { showMessage('Došlo je do greške: '+e.message, 'error'); }
      finally { btnSubmit.classList.remove('seup-loading'); btnSubmit.disabled = false; }
    });
  }

  // Autocomplete minimal (placeholder)
  const input = document.getElementById('klasa_br');
  const resultsContainer = document.getElementById('autocomplete-results');
  if (input && resultsContainer) {
    function clearResults(){ resultsContainer.innerHTML=''; resultsContainer.style.display='none'; }
    document.addEventListener('click', function(e){ if(!e.target.closest('.seup-autocomplete-container')) clearResults(); });
  }

  // Toast poruke
  window.showMessage = function(message, type='success', duration=5000){
    let el = document.querySelector('.seup-message-toast');
    if (!el) { el = document.createElement('div'); el.className='seup-message-toast'; document.body.appendChild(el); }
    el.className = `seup-message-toast seup-message-${type} show`;
    el.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-triangle'}"></i> ${message}`;
    setTimeout(()=>{ el.classList.remove('show'); }, duration);
  };
});
</script>

<style>
.seup-message-toast{position:fixed;top:20px;right:20px;padding:12px 18px;border-radius:10px;color:#fff;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.15);transform:translateX(400px);transition:transform .25s;z-index:9999;max-width:400px}
.seup-message-toast.show{transform:translateX(0)}
.seup-message-success{background:linear-gradient(135deg,#16a34a,#15803d)}
.seup-message-error{background:linear-gradient(135deg,#ef4444,#dc2626)}
.seup-btn.seup-loading{position:relative;color:transparent}
.seup-btn.seup-loading::after{content:'';position:absolute;top:50%;left:50%;width:16px;height:16px;margin:-8px 0 0 -8px;border:2px solid transparent;border-top:2px solid currentColor;border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>

<?php llxFooter(); $db->close(); ?>
