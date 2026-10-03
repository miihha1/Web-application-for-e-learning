# Reprodukcia experimentu – Téma 14
Autor: Mykhailo Adamenko. Pracujte v samostatnej kópii aplikácie.

## Príprava (PowerShell v koreni aplikácie)
PHP 8.2+, Composer, Node/npm, Python 3.9+ a k6. Merané verzie: reports/environment.json.

```powershell
composer install
npm ci
py -3.9 performance-testing/scripts/prepare.py
npm run build
```

prepare.py vytvorí iba samostatnú databázu runtime/performance.sqlite. Odmietne prepísať neprázdnu databázu alebo bežné .env. Seeder používa seed 202627; presné počty sú v reports/dataset.json. Heslo Perf-local-2026! patrí výhradne syntetickým lokálnym účtom.

k6 2.3.0: rozbaľte oficiálny windows-amd64 ZIP z https://github.com/grafana/k6/releases/tag/v2.3.0 do performance-testing/tools/ (podpriečinok obsahuje k6.exe).

## Terminál A – server

```powershell
cd public
php -S 127.0.0.1:8765 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Na Windows má tento vývojový server jeden worker.

## Terminál B – testy
Pred BEFORE použite controller z performance-testing/baseline/TeacherCourseController.php, pred AFTER finálny controller. Aktuálnu verziu si najprv odložte. Počas testovania controller nemeňte. Pôvodný commit: 80aaabefbdfa268f1ed6a10395db905e0f63a89c. Diff: reports/optimization.patch.

```powershell
$env:PERF_OUTPUT_ROOT="performance-testing/runtime/replay-$(Get-Date -Format yyyyMMdd-HHmmss)"
Copy-Item app/Http/Controllers/TeacherCourseController.php performance-testing/runtime/controller-work-backup.php
Copy-Item performance-testing/baseline/TeacherCourseController.php app/Http/Controllers/TeacherCourseController.php
py -3.9 performance-testing/scripts/run_suite.py before load
py -3.9 performance-testing/scripts/run_suite.py before stress
# V termináli A: Ctrl+C a znovu rovnaký príkaz php -S (vyprázdni frontu).
py -3.9 performance-testing/scripts/run_suite.py before soak
# Po optimalizácii:
Copy-Item performance-testing/baseline/TeacherCourseController.after.php app/Http/Controllers/TeacherCourseController.php
php artisan test
py -3.9 performance-testing/scripts/run_suite.py after load
py -3.9 performance-testing/scripts/run_suite.py after stress
# V termináli A opäť reštartujte server.
py -3.9 performance-testing/scripts/run_suite.py after soak
```

Sada vykoná load na SMALL/MEDIUM/LARGE, stress a krátky soak na LARGE. Referenčné výsledky: results/before a results/after. Nové behy sa uložia pod PERF_OUTPUT_ROOT. Existujúce výsledky skript odmietne prepísať; kontroluje aj správnu verziu kontroléra. K6 exit 99 označuje nesplnený threshold a je platným výsledkom testu.

Samostatný test:

```powershell
$k6 = (Get-ChildItem performance-testing/tools -Recurse -Filter k6.exe | Select-Object -First 1).FullName
$env:COURSE='3'
$env:MODE='load' # alebo stress / soak
$env:SUMMARY='performance-testing/results/manual-load.json'
& $k6 run --out json=performance-testing/results/manual-load.ndjson performance-testing/scripts/scenario.js
# Plný 30-minútový soak:
$env:MODE='soak'
$env:SOAK_SECONDS='1800'
$env:SUMMARY='performance-testing/results/manual-soak.json'
& $k6 run --out json=performance-testing/results/manual-soak.ndjson performance-testing/scripts/scenario.js
```

Autentifikácia: GET /login a POST /login s CSRF. Setup session učiteľa je zdieľaná VUs: ide o súbežné čítanie rovnakého dashboardu, nie 100 rôznych účtov. Auth a CSRF ostávajú aktívne; login nepatrí do latencie analytiky. k6 posiela Inertia verziu a overuje počty v odpovedi.

Profil bez HTTP fronty:

```powershell
php performance-testing/scripts/profile.php 3
php performance-testing/scripts/profile_sections.php 3
```

Druhý príkaz používa instrumentovanú BEFORE kópiu. CLI profil zahŕňa controller a serializáciu, nie celú HTTP cestu. Prvý zo šiestich behov je zahrievací. PHP peak memory je maximum procesu, nie živá pamäť servera počas soak testu.

## Kontrolné toky a dokumenty

`controls.js` meria zoznam kurzov a odoslanie testu (1 VU, 20 iterácií). `run_controls.py` dočasne prepína kontrolér a používa dve kópie databázy na porte 8766. Spúšťajte ho iba po skončení hlavnej sady; uložené referenčné kontroly najprv zálohujte. Po skončení obnoví pôvodný kontrolér a overí hash hlavnej databázy.

```powershell
py -3.9 performance-testing/scripts/run_controls.py
py -3.9 -m pip install -r performance-testing/scripts/report-requirements.txt
py -3.9 performance-testing/scripts/analyze.py
py -3.9 performance-testing/scripts/build_documents.py
```

Posledné dva príkazy regenerujú referenčné CSV, grafy, PDF a PPTX z results/. PDF má 10 A4, prezentácia 12 slidov s poznámkami (približne 11 minút). Zdroje dokumentácie sú v scripts/build_documents.py. Pre PDF sú použité systémové fonty Times New Roman vo Windows. Samotný report možno obnoviť príkazom `py -3.9 performance-testing/scripts/build_documents.py --report-only`.

Samostatná aktualizácia vizuálnej prezentácie:

```powershell
py -3.9 performance-testing/scripts/build_presentation.py
powershell -ExecutionPolicy Bypass -File performance-testing/scripts/export_presentation.ps1
```

Export PDF a PNG náhľadov používa nainštalovaný Microsoft PowerPoint. Prezentácia obsahuje skutočný screenshot z screenshots/application-analytics.png a editovateľné schémy a grafy. Screenshot možno obnoviť pomocou capture_presentation.py (voliteľne Playwright 1.48.0 a Microsoft Edge). Skript používa lokálny server a syntetický učiteľský účet.
