CHANGELOG – SEUP (Sustav Elektroničkog Uredskog Poslovanja)
1.0.0 – Initial Release

Prva funkcionalna verzija SEUP modula.

Osnovna struktura modula generirana putem Dolibarr ModuleBuilder-a.

Dodani početni modeli za Predmete, Akte i Priloge.

Postavljeni temeljni SQL predlošci i osnovna navigacija.

Hardkodirani testni sadržaji za interne potrebe razvoja.

2.0.0 – Core Stabilizacija

Potpuna reorganizacija direktorija (class/, pages/, lib/, sql/, langs/ itd.).

Implementirani modeli:

Predmet

Akt_helper

Prilog_helper

Suradnici_helper

Sortiranje_helper

Dodan osnovni workflow za kreiranje, prikaz i uređivanje predmeta.

Dodani backend alati za sortiranje, pretragu i filtriranje.

Počeci Nextcloud integracije – priprema API klase.

Prvi draft OnlyOffice integracije (bez potpune implementacije).

Dodan sustav tagova i osnovne administracijske stranice.

2.5.0 – DMS Ekspanzija

Uvedena napredna podrška za rad s prilozima i dokumentima.

Dovršena Nextcloud API integracija: kreiranje foldera, upload, strukture.

Nadograđen interface za rad s aktima, povezivanje akata na predmete.

Uvedeni helperi za generiranje dokumenata (PDF, DOCX).

Dodane interne klase za digitalni potpis i provjeru potpisa.

Dodan "Plan klasifikacijskih oznaka".

Prvi stabilni importer podataka.

3.0.0 – „Production Ready“ Refactor

Veliko čišćenje i refaktor kodne baze.

Uklanjanje starih placeholder datoteka i nepotrebnih skeleton fajlova.

Usklađivanje strukture s Dolibarr 22 standardima.

Optimiziran rad s bazom: novi SQL predlošci, bolja organizacija tablica.

Uređivanje svih stranica (pages/) – UX poboljšanja, layout stabilizacija.

Ujednačavanje PHP klasa i naming conventiona.

Uvedene dodatne funkcije za korisničke uloge i interne workflowe.

Dodano više sigurnosnih provjera i sanitizacije inputa.

Značajno brže učitavanje većih listi predmeta i akata.

3.0.1 – Licensing & Packaging Cleanup (CURRENT)

Uklonjene sve GPL datoteke i naslijeđeni ModuleBuilder headeri.

Dodan novi proprietary LICENSE.md (8Core).

Kreiran novi info.xml kompatibilan s Dolibarr 22.

Usklađeni brojevi verzija i modul identificatori.

Čišćenje vendor-a: uklanjanje duplih JWT implementacija.

Priprema za stabilno izdanje i distribuciju prema klijentima.

Dokumentacija ažurirana: README, struktura, changelog.
