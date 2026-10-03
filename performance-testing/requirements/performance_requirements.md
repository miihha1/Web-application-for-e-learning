# Požiadavky pred meraním

Rozsah: HTTP GET /teacher/courses/{course}/manage, Inertia JSON, dataset LARGE.

- PR-01: 20 VUs, p95 < 800 ms v stabilnej 30 s fáze load testu.
- PR-02: 50 VUs, p95 < 1500 ms v stabilnej 30 s fáze stress testu.
- PR-03: HTTP error rate < 1 % a úspešné obsahové kontroly >= 99 % počas load testu.
- PR-04: pri 5 VUs počas 180 s skráteného soak testu p95 poslednej tretiny <= 1,25 × p95 prvej tretiny; error rate každej tretiny < 1 %. Plná verzia 1800 s je konfigurovateľná; krátky test nedokazuje dlhodobú stabilitu.
- PR-05: pri 1000 študentoch analytika vráti HTTP 200 a správne počty 1000 študentov, 5000 pokusov a 1000 posledných výsledkov.

Ide o navrhnuté lokálne akceptačné ciele, nie existujúcu SLA. Limity sa nemenia podľa výsledkov. Pri finalizácii bola opravená diakritika poškodená kódovaním terminálu; numerické kritériá zostali nezmenené.

Windows PHP development server má jediný worker. Meria sa aj čakanie vo fronte. File session driver je rovnaký BEFORE/AFTER. Spresnenie autentifikácie: VUs zdieľajú session učiteľa zo setup (súbežné čítanie jedného dashboardu). Databáza SQLite je samostatná.

Load: 5 s nábeh + 30 s stabilná fáza + 5 s pokles, 20 VUs. Stress: fázy 10/20/50/100 VUs po 30 s, medzi nimi 10 s grace period. Horný limit 100 VUs bol zvolený pre lokálny jednovláknový server. Soak: 5 VUs, 180 s, think time 1 s. Request timeout 30 s. Hlavné testy na LARGE; SMALL/MEDIUM sú doplnkové load merania. Z výsledkov sa neodvodzuje univerzálna produkčná kapacita.
