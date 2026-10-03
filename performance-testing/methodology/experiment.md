# Metodika a rozhodnutia

Pred meraním boli preskúmané routes/web.php, tri kontroléry CourseController, TeacherCourseController a TestController, modely, migrácie, seedery, existujúce testy, Composer/npm konfigurácia, .env.example a databázová konfigurácia. Zadanie a hodnotenie vyžadujú PDF najviac 10 A4 a prezentáciu najviac 15 minút.

## Zachovanie východiskového stavu

Samostatný Git worktree vetvy performance-testing vychádza z commit-u 80aaabefbdfa268f1ed6a10395db905e0f63a89c. V pôvodnom pracovnom strome bol iba neversionovaný BP.zip; nebol zmenený ani pridaný do Git. Zdrojový archív BEFORE je uložený vedľa pracovnej kópie. Baseline kontrolér zostáva v baseline/TeacherCourseController.php. Existujúca používateľská databáza ani .env neboli kopírované.

## Rozsah a konfigurácia

Hlavný endpoint: GET /teacher/courses/{id}/manage, s plnou Inertia JSON odpoveďou. Vykresľovanie Vue v prehliadači, sieť WAN a sťahovanie statických assetov nie sú merané. Účty sú syntetické. VU reprezentujú súbežné čítania rovnakého kurzu pod oprávneným učiteľom, nie odlišných vlastníkov kurzu. VU zdieľajú prihlásenú session zo setup; toto obmedzenie treba uviesť pri interpretácii.

SQLite, file sessions, debug=false, rovnaký frontend build a jeden PHP development worker vo Windows. Požiadavky sú uzavretý model s 1 s think time. Pri spomalení klesá ponúkaná záťaž. Throughput preto nie je nezávislým odhadom maximálnej produkčnej kapacity.

SMALL/MEDIUM/LARGE sa líšia počtom študentov a súvisiacich záznamov; databáza obsahuje všetky tri kurzy naraz. V každom kurze 20 lekcií, 10 otázok, 40 možností odpovede a 5 pokusov na študenta. Seed 202627, fixné časové značky. Presné počty sú v reports/dataset.json.

## Meranie

k6 vykoná reálny login s CSRF a zahrievací GET; setup sa nezapočítava do analytics_ms. Každá odpoveď musí mať HTTP 200 a správny počet zapísaných študentov, pokusov a posledných výsledkov. JSON summary obsahuje avg/median/p90/p95/p99, NDJSON umožňuje nezávislý prepočet. RPS v celkovom porovnaní je počet dokončených analytických požiadaviek delený celkovým časom behu k6, vrátane setup a grace period; RPS stabilnej fázy používa presné 30 s okno.

Load: 5 s nábeh, 30 s pri 20 VUs, 5 s pokles. Stress: 10/20/50/100 VUs, každá fáza 30 s a 10 s grace period. Pri preťažení môžu zostať prerušené iterácie a nevybavená fronta. Percentily vychádzajú z dokončených HTTP volaní vrátane zaznamenaných timeoutov; nezaznamenané prerušené požiadavky nie sú úspešné odpovede. Stabilné okná sa vyhodnocujú podľa času dokončenia požiadavky.

Soak: 5 VUs, 180 s, porovnanie troch 60 s okien. Plná 1800 s verzia je pripravená, ale nie je súčasťou vykonaného experimentu. Z krátkeho testu nemožno usudzovať na neprítomnosť dlhodobých únikov pamäte. Pamäť počas soak nebola kontinuálne profilovaná.

## Neúspešný pomocný beh

Po BEFORE stress teste zostala vo vývojovom serveri fronta. Prvý soak skončil timeoutom GET /login ešte počas setup. Je zachovaný v results/before/setup-failure; nie je zahrnutý do výsledkov soak. Server sa pred platným soak reštartoval; rovnaké pravidlo platí pre AFTER. Nejde o zamlčanú chybu systému, ale o zdokumentované oddelenie experimentov.

## Funkčná ekvivalencia a interpretácia

Pred zmenou boli doplnené regresné testy posledného výsledku, priemeru, počtov, dokončených lekcií, chybných otázok a prístupových práv. Celý výstup analytics na troch datasetoch sa uloží pred zmenou a porovná po zmene. Zlepšenie latencie = (BEFORE − AFTER) / BEFORE × 100 %. Zmena throughputu = (AFTER − BEFORE) / BEFORE × 100 %. Pri nulovej východiskovej chybovosti sa uvádzajú percentuálne body.

Jeden beh každej konfigurácie predstavuje lokálny prieskumný experiment; neuvádzajú sa intervaly spoľahlivosti. CLI profil používa šesť opakovaní s prvým zahrievacím. Nepridávajú sa indexy ani cache bez dôkazu, že riešia zistenú príčinu.

Počas práce bola relácia prerušená a procesy boli zastavené. Čiastočný soak je uložený v interrupted-soak; do porovnania vstupuje až úplný opakovaný beh. BEFORE load/stress a neskorší soak/AFTER preto nie sú jedným neprerušeným meracím blokom. Tento časový odstup obmedzuje kontrolu vedľajších procesov a teplotného stavu CPU.
