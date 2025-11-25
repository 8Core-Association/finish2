SEUP – Sustav Elektroničkog Uredskog Poslovanja

Dolibarr DMS + Uredsko poslovanje modul
Verzija: 3.0.1
Autor: Informatička Udruga 8Core
Web: https://8core.hr

📌 Opis

SEUP je napredni modul za Dolibarr ERP/CRM koji implementira kompletan sustav elektroničkog uredskog poslovanja.
Pruža funkcionalnosti za upravljanje predmetima, aktima, dokumentima, prilozima, klasifikacijskim oznakama te integraciju s vanjskim sustavima.

SEUP pretvara Dolibarr u moćan DMS sustav namijenjen javnim ustanovama, uredima i organizacijama koje vode službeno uredsko poslovanje.

🔑 Glavne funkcionalnosti
📁 Predmeti

Otvaranje predmeta iz pošte, zahtjeva ili ručno

Upravljanje sadržajem, urudžbenim brojem i klasifikacijskim oznakama

Povezivanje predmeta s djelatnicima, odjelima i suradnicima

Statusi i workflow podrška

📝 Akti

Dodavanje jednog ili više akata predmetu

Generiranje akata iz predložaka

Automatsko kreiranje pripadajućih priloga i metapodataka

Evidentiranje slanja i zaprimanja

📄 Dokumenti i Prilozi (DMS)

Upravljanje svim vrstama dokumenata i datoteka

Integracija s Nextcloud-om (upload, folderi, strukture)

Pregled, uređivanje i povijest dokumenata

Podrška za OnlyOffice (ovisno o instalaciji)

🔒 Digitalni potpis

Detekcija potpisa iz PDF dokumenata

Priprema za FINA e-Potpis i PKI integraciju

Prikaz podataka o potpisnicima

🏷️ Tagovi, klasifikacije i sadržaji

Kompletan sustav tagiranja

Evidencija klasifikacijskih oznaka

Plan klasifikacijskih oznaka integriran u module

🔍 Pretraga i sortiranje

Napredna filtracija predmeta i akata

Pretraga po svim relevantnim poljima

Interni helper za pametno sortiranje većih lista

⚙️ Administracija

Postavke modula

Upravljanje sadržajima, dosjeima, tagovima

Kontrola nad strukturama direktorija i zapisima

📐 Zahtjevi

Dolibarr 22.x ili noviji

PHP 8.0 – 8.2

MySQL/MariaDB

(Opcionalno) Nextcloud 27+

(Opcionalno) OnlyOffice Document Server

📦 Instalacija

Preuzmite SEUP paket i raspakirajte ga u:

/custom/seup/


Provjerite da se datoteka info.xml nalazi u root direktoriju modula.

U Dolibarru idite na:
Setup → Modules/Applications → Izlistaj neslužbene module

Pronađite SEUP i kliknite Enable.

Pokrenite instalaciju baze ako modul to zatraži.

Nakon aktivacije podesite postavke u izborniku SEUP Postavke.

🔧 Struktura direktorija
seup/
│── class/        # PHP klase (Predmeti, Akti, helperi, integracije...)
│── pages/        # Stranice modula (UI)
│── lib/          # Pomoćne biblioteke i funkcije
│── sql/          # SQL skripte za instalaciju i nadogradnje
│── langs/        # Jezične datoteke (hr_HR, en_GB)
│── img/          # Ikone i grafika
│── vendor/       # Dodatne vanjske biblioteke
│── LICENSE.md    # Proprietary licenca (8Core)
│── README.md     # Dokumentacija modula
│── info.xml      # Metapodaci modula (Dolibarr)

🔒 Licenca

SEUP je vlasnički (proprietary) softver.
Distribucija, kopiranje ili izmjene nisu dopuštene bez pismenog odobrenja Informatičke Udruge 8Core.
Detalji u datoteci LICENSE.md.

📬 Podrška

Kontakt: info@8core.hr

Web: https://8core.hr

Za korisnike s ugovorom, podrška se pruža prema individualnom SLA.