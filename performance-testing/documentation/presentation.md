## 1. Výkonnostné testovanie



Odporúčaný čas: 35 s.

Predstavujem tému výkonnostného testovania. Aplikácia môže správne vykonať požadovanú operáciu, no používateľ na ňu môže príliš dlho čakať. Najprv vysvetlím, čo pri výkone sledujeme a ako sa testuje. Potom postup ukážem na aplikácii z mojej bakalárskej práce, ktorá tu slúži ako praktický príklad.

## 2. Správna odpoveď môže prísť príliš neskoro.



Odporúčaný čas: 45 s.

Ide o ilustráciu otázky, nie o výsledok merania. Funkčné testovanie overuje, či aplikácia vracia správne výsledky. Výkonnostné testovanie dopĺňa otázku, ako sa správa pri rôznom počte požiadaviek alebo pri rastúcom objeme údajov. Pomalá odpoveď môže používateľa prinútiť opakovať akciu a zhoršiť jeho skúsenosť.

## 3. Tri otázky, ktorým rozumie aj používateľ.



Odporúčaný čas: 50 s.

Rýchlosť opisuje čas odpovede. Spoľahlivosť sledujeme cez chyby a kontroly správnosti odpovedí; samotná absencia chybového kódu nestačí. Stabilita znamená, či sa správanie počas testu výrazne nezhoršuje. V dokumentácii je tiež priepustnosť, teda počet vybavených požiadaviek za sekundu. Pri meraní možno sledovať aj využitie procesora a pamäte.

## 4. Každý typ testu odpovedá na inú otázku.



Odporúčaný čas: 55 s.

Odborné názvy sú záťažový test (load), stresový test (stress) a vytrvalostný test (endurance alebo soak). Prvý overuje očakávanú prevádzku, druhý hľadá hranice a tretí sleduje vývoj v čase. Diagramy sú len schémy. Neskoršia praktická ukážka používa pri treťom type skrátený trojminútový beh, ktorý nenahrádza dlhodobý test.

## 5. Najprv cieľ. Potom meranie a porovnanie.



Odporúčaný čas: 45 s.

Požiadavky stanovíme pred meraním, aby sme ich dodatočne neprispôsobovali výsledkom. Pri opakovaní zachováme rovnaké údaje, scenár a podmienky. Hľadanie príčiny má vychádzať z merania jednotlivých častí spracovania. Po úprave znovu overíme výkon aj správnosť. Tento postup teraz prenesiem do konkrétnej aplikácie.

## 6. Ukážka na aplikácii z bakalárskej práce

Webová aplikácia na online vzdelávanie

Odporúčaný čas: 55 s.

Aplikácia vznikla v mojej bakalárskej práci a v tomto projekte slúži ako testovaný príklad. Nejde o prezentáciu celej bakalárskej práce. Vybral som učiteľský prehľad kurzu, pretože spracúva údaje o študentoch, pokroku a výsledkoch. Screenshot je zo skutočnej aplikácie so syntetickými údajmi. Technicky ide o Laravel, Vue/Inertia a SQLite. Meria sa odpoveď servera, nie dokončenie vykreslenia v prehliadači.

## 7. Zvládne prehľad kurzu rast počtu študentov?



Odporúčaný čas: 65 s.

Veľkosť kurzu a súčasná záťaž sú dve rozdielne veci. Kurzy obsahovali 50, 250 a 1000 študentov, každý s piatimi pokusmi o test a kurzom s 20 lekciami. Bežná záťaž mala 20 virtuálnych používateľov; 1000 študentov teda neznamená 1000 ľudí online naraz. Automatizované požiadavky vytváral nástroj k6. Cieľ PR-01 bol p95 pod 800 ms pri 20 používateľoch a PR-02 pod 1500 ms pri 50 používateľoch, vždy v stabilnej fáze pre najväčší kurz. Ďalej sa overovali chyby pod 1 %, stabilita a správne výsledky. Testy bežali lokálne, na vývojovom serveri s jedným PHP workerom.

## 8. Pri väčšom kurze používateľ dlho čakal.



Odporúčaný čas: 60 s.

Porovnávame celé behy pri rovnakej bežnej záťaži a troch veľkostiach kurzu. Ide o p95, nie priemernú odpoveď; päť percent zaznamenaných odpovedí môže byť pomalších. Pri najväčšom kurze bolo 11 prerušených iterácií, preto nulová chybovosť dokončených volaní sama osebe nepotvrdila spoľahlivosť. Lokálny server vybavoval požiadavky jedným workerom, čo vytváralo čakanie vo fronte. Výsledky nevyjadrujú kapacitu produkčného nasadenia.

## 9. Rovnaké údaje sme spracúvali zbytočne opakovane.



Odporúčaný čas: 60 s.

Meranie častí kódu ukázalo problém hlavne v spracovaní údajov v PHP. Opakovane sa dekódovali odpovede pri prechode všetkými otázkami a pokusmi. Po zmene sa odpovede pripravia raz na pokus. Vybrali sme iba potrebné stĺpce, odstránili nepotrebné načítanie používateľa pri výsledku aj ďalšie triedenie už zoradených pokusov. História odpovedí sa zachovala. Porovnanie úplných výstupov analytiky sa zhodovalo pre všetky tri kurzy. Prešlo 51 automatizovaných testov s 244 kontrolami. Samostatný profil kontroléra klesol z mediánu 1028,5 ms na 412,7 ms; ide o iné meranie než čas odpovede pri záťaži.

## 10. Čakanie sa výrazne skrátilo.



Odporúčaný čas: 55 s.

Hodnoty p95 za celý bežný záťažový beh sú 20,595 s pred úpravou a 9,069 s po úprave, zobrazené zaokrúhlene. Pokles je 55,97 %. Nejde o priemer ani o garanciu času každej odpovede. Dáta a testovací scenár boli rovnaké. Pri vyhodnotení požiadavky používame iba stabilnú fázu: po úprave 9,102 s, čo je stále výrazne nad cieľom 0,8 s. Zlepšenie výkonu preto automaticky neznamená splnenie požiadaviek.

## 11. Rýchlejšie spracovanie. Zachovaná správnosť.



Odporúčaný čas: 55 s.

Na tomto slajde zhrniem dosiahnuté výsledky. Pri kurze s 1000 študentmi a 20 súčasných používateľoch sa sledovaný čas odozvy skrátil približne o 56 %. Ide o hranicu pre 95 zo 100 dokončených odpovedí, vysvetlenú na predchádzajúcom slajde. Celé výstupy prehľadu boli zhodné pred úpravou aj po nej vo všetkých troch veľkostiach kurzu. Prešlo 51 automatizovaných testov s 244 kontrolami. V nameraných behoch bežnej záťaže po úprave neboli zaznamenané chyby ani prerušené iterácie. Tieto výsledky sa vzťahujú na vykonané lokálne merania.

## 12. Od merania ku konkrétnemu zlepšeniu.



Odporúčaný čas: 45 s.

Hlavným prínosom projektu je prepojenie merania s konkrétnou úpravou aplikácie. Najprv sme identifikovali opakované spracovanie údajov, potom ho obmedzili a rovnakými testami overili účinok. Výsledkom je namerané zrýchlenie pri zachovaní správnosti prehľadu. Aplikácia z bakalárskej práce tak poslúžila ako praktický príklad využitia výkonnostného testovania. Ďakujem za pozornosť.

Použité zdroje:
1. KS_ZS_202627_semestralny_projekt_temy.pdf, téma 14; i-ks_semestralne_temy_hodnotenie.pdf.
2. Mykhailo Adamenko: bakalárska práca, BP_FINAL.pdf.
3. Grafana k6: https://grafana.com/docs/k6/latest/using-k6/metrics/ ; https://grafana.com/docs/k6/latest/using-k6/scenarios/ ; https://grafana.com/docs/k6/latest/using-k6/cookies/ .
4. Laravel: https://laravel.com/docs/12.x/database ; https://laravel.com/docs/12.x/eloquent-mutators .
5. PHP: https://www.php.net/manual/en/features.commandline.webserver.php .