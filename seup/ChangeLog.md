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

3.0.1 – Licensing & Packaging Cleanup

Uklonjene sve GPL datoteke i naslijeđeni ModuleBuilder headeri.

Dodan novi proprietary LICENSE.md (8Core).

Kreiran novi info.xml kompatibilan s Dolibarr 22.

Usklađeni brojevi verzija i modul identificatori.

Čišćenje vendor-a: uklanjanje duplih JWT implementacija.

Priprema za stabilno izdanje i distribuciju prema klijentima.

Dokumentacija ažurirana: README, struktura, changelog.

4.0.0 – Zaprimanja i Otpreme

Implementiran kompletan sustav za zaprimanje dokumenata:
  - Novi `zaprimanje_helper.class.php` za upravljanje zaprimanjima
  - Dodana tablica `llx_a_zaprimanje` za evidenciju zaprimljenih dokumenata
  - UI za pregled, kreiranje i uređivanje zaprimanja (zaprimanja.php)
  - Automatsko generiranje brojeva zaprimanja prema standardima
  - Povezivanje zaprimljenih dokumenata s predmetima

Implementiran sustav otprema:
  - Novi `otprema_helper.class.php` za upravljanje otpremama
  - Dodana tablica `llx_a_otprema` za evidenciju otpremljenih dokumenata
  - UI za pregled i upravljanje otpremama (otpreme.php)
  - Praćenje statusa otprema i histoire promjena
  - Povezivanje otprema s aktima i predmetima

Napredna detekcija digitalnih potpisa:
  - Potpuna implementacija `digital_signature_detector.class.php`
  - Automatsko skeniranje PDF dokumenata pri uploadu
  - Detekcija FINA e-potpisa i općih PDF potpisa
  - Spremanje metapodataka potpisa (potpisnik, datum, status)
  - Vizualna indikacija potpisa u listi dokumenata

Poboljšanja u modularizaciji koda:
  - Razdvojeni helperi za različite funkcionalnosti
  - `predmet_helper.class.php` - upravljanje predmetima
  - `predmet_action_handler.class.php` - procesiranje akcija
  - `predmet_data_loader.class.php` - učitavanje podataka
  - `predmet_view.class.php` - renderiranje pogleda
  - `akt_helper.class.php` - upravljanje aktima
  - `prilog_helper.class.php` - upravljanje prilozima

Nove funkcionalnosti za klasifikaciju:
  - `klasifikacijska_oznaka.class.php` - upravljanje KO
  - `oznaka_ustanove.class.php` - institucijske oznake
  - `interna_oznaka_korisnika.class.php` - korisničke oznake

Dodatni sustavi:
  - `omat_generator.class.php` - generiranje OMAT brojeva
  - `ecm_scanner.class.php` - skeniranje ECM repositorija
  - `cloud_helper.class.php` - integracije s cloud servisima
  - `changelog_sistem.class.php` - praćenje izmjena

UI/UX poboljšanja:
  - Modernizirani CSS stilovi (seup-modern.css)
  - Responzivni dizajn za zaprimanja i otpreme
  - Poboljšan prikaz dokumenata s novim stilovima
  - Dodani custom stilovi za tagove, suradnike, priloge

JavaScript optimizacije:
  - Modularni JS za različite stranice
  - Poboljšano sortiranje i filtriranje
  - Real-time validacija formi
  - AJAX integracije za brže učitavanje

4.1.0 – Refaktor Predmeta i Performance

Velika reorganizacija `predmet.php` stranice:
  - Razbijanje monolitnog fajla na više helpera
  - Odvojena logika za prikaz (view), akcije (action handler) i učitavanje podataka (data loader)
  - Smanjena veličina datoteke za ~60% i poboljšana čitljivost
  - Brže učitavanje i jednostavnije održavanje

Optimizacija rada s dokumentima:
  - Poboljšan prikaz dokumenata u tabličnom formatu
  - Bulk operacije nad dokumentima (označavanje, brisanje)
  - Dodana podrška za selekciju više dokumenata odjednom
  - Napredni filtering i sorting opcije

Poboljšanja u prikazivanju metapodataka:
  - Prikaz created_by (tko je kreirao dokument)
  - Prikaz datuma kreiranja s lokalnim formatom
  - Prikaz statusa potpisa s vizualnim indikatorima
  - Tooltip na hover s detaljima potpisa

4.1.1 – Stilska Unifikacija i UX

Moderan i ujednačen dizajn:
  - Nova Bootstrap-like komponenta biblioteka
  - Ujednačeni form elementi (input, select, textarea, button)
  - Konzistentna paletna boja kroz cijeli modul
  - Dodani custom stilovi za kartice, tabele i modalne dijaloge

Poboljšanja u tabličnim prikazima:
  - Responzivne tablice s horizontal scrollom na mobilnim uređajima
  - Zebra striping i hover efekti za bolju čitljivost
  - Sticky header za velike tablice
  - Poboljšano formatiranje stupaca

Akcijski gumbi i ikone:
  - Dodane Font Awesome ikone kroz cijeli interface
  - Vizualno konzistentni action buttoni
  - Tooltipovi i kontekstualne poruke
  - Disabled states za nedostupne akcije

Modal dialozi:
  - Custom modal komponente za forme
  - Animirani prijelazi i overlay efekti
  - Responsivan dizajn za različite veličine ekrana
  - Keyboard shortcuts (ESC za zatvaranje)

4.1.2 – Finalna Dorađivanja (CURRENT)

Proširenje digitalnog potpisa:
  - Dodavanje datuma potpisa u tooltip prikazu
  - Prikaz "Potpisao: [Ime] (Datum: dd.mm.yyyy HH:mm)"
  - Automatsko formatiranje datuma u hrvatski format
  - Vizualno pregledno prikazivanje informacija o potpisu

Optimizacija prikaza dokumenata:
  - Prošireni stupci za bolje prikazivanje dugih naziva datoteka
  - Optimizirana širina stupaca za bolju čitljivost
  - Smanjeno preklapanje teksta u stupcima
  - Responzivna tablica s boljim breakpointovima

Refaktoring helpera:
  - Dodatna dokumentacija u PHP DocBlock formatu
  - Popravljeni edge case-ovi u upload logici
  - Sigurnosne provjere za sve file operacije
  - Bolji error handling i logging

Poboljšanja performansi:
  - Optimizirani SQL upiti za brže učitavanje
  - Smanjeno dupliranje poziva baze podataka
  - Cache-iranje često korištenih podataka
  - Lazy loading za velike liste dokumenata

Razno:
  - Ispravke pravopisnih grešaka u sučelju
  - Ažurirana dokumentacija (README.md, info.xml)
  - Dodani primjeri i sample dokumenti
  - Priprema za sljedeći release cycle
