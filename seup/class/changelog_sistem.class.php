<?php

/**
 * SEUP – Sustav Elektroničkog Uredskog Poslovanja
 *
 * Plaćena (proprietary) licenca
 * (c) 2025 Informatička Udruga 8Core / 8Core Association
 *
 * Autori:
 *  - Tomislav Galić <tomislav@8core.hr>
 *  - Marko Šimunović <marko@8core.hr>
 *
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 *
 * Sva prava pridržana.
 * Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima.
 * Zabranjeno je umnožavanje, distribucija, mijenjanje, objavljivanje ili
 * bilo koji oblik eksploatacije bez prethodnog pismenog odobrenja nositelja prava.
 *
 * Detalji licence: vidi datoteku LICENSE.md u root direktoriju modula.
 */

/**
 * \file       class/changelog_sistem.class.php
 * \ingroup    seup
 * \brief      Jednostavan sistem za upravljanje verzijom SEUP sustava.
 */

class Changelog_Sistem
{
    /**
     * Trenutna verzija SEUP sustava.
     *
     * @var string
     */
    const VERSION = '3.0.1';

    /**
     * Datum izdanja trenutne verzije (ISO format YYYY-MM-DD).
     *
     * @var string
     */
    const RELEASE_DATE = '2025-10-15';

    /**
     * Dohvaća verziju formatiranu za footer.
     *
     * @return string
     */
    public static function getVersion()
    {
        return 'SEUP v. ' . self::VERSION;
    }

    /**
     * Dohvaća datum izdanja u ISO formatu (YYYY-MM-DD).
     *
     * @return string
     */
    public static function getReleaseDateIso()
    {
        return self::RELEASE_DATE;
    }

    /**
     * Dohvaća datum izdanja u formatu pogodnom za prikaz (dd.mm.YYYY).
     *
     * @return string
     */
    public static function getReleaseDateHuman()
    {
        $timestamp = strtotime(self::RELEASE_DATE);
        if ($timestamp === false) {
            // Ako je netko sjebao format, vratimo originalnu vrijednost
            return self::RELEASE_DATE;
        }

        return date('d.m.Y', $timestamp);
    }

    /**
     * Dohvaća kombinaciju verzije i datuma (npr. "SEUP v. 3.0.1 (15.11.2025)").
     *
     * @return string
     */
    public static function getVersionWithDate()
    {
        return 'SEUP v. ' . self::VERSION . ' (' . self::getReleaseDateHuman() . ')';
    }
}
