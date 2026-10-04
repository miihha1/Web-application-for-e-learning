from pathlib import Path
import json,subprocess,zipfile,shutil,hashlib,html
R=Path(__file__).resolve().parents[2];P=R/'performance-testing';destination=R.parent
d=json.loads((P/'reports/comparison.json').read_text(encoding='utf-8'))
table='<table><tr>'+''.join('<th>'+x+'</th>' for x in ['Stav','Test','p95 ms','RPS','Chyby %','Prerušené iterácie'])+'</tr>'
for r in d['rows']:
    vals=[r['phase'],r['test'],f"{r['p(95)']:.2f}",f"{r['rps']:.3f}",f"{r['error']*100:.2f}",r['interrupted_iterations']]
    table+='<tr>'+''.join('<td>'+html.escape(str(v))+'</td>' for v in vals)+'</tr>'
table+='</table>'
report='<!doctype html><html lang="sk"><meta charset="utf-8"><title>Performance BEFORE / AFTER</title><style>body{font:16px system-ui;max-width:1100px;margin:40px auto;padding:20px;color:#172c38}table{border-collapse:collapse;width:100%}td,th{padding:10px;border-bottom:1px solid #ccc;text-align:right}th{background:#edf2f5}img{width:100%;max-width:850px}</style><h1>Výkonnostné testovanie – BEFORE / AFTER</h1><p>Mykhailo Adamenko · 2. 10. 2026. Skutočné lokálne výsledky; jeden PHP worker, SQLite. Chybovosť je z dokončených volaní, prerušené iterácie sú uvedené osobitne.</p>'+table+''.join('<p><img src="'+name+'" alt="'+name+'"></p>' for name in ['p95.png','throughput.png','stress.png'])+'<p>PR-01 a PR-02 nesplnené. PR-03 BEFORE nepreukázaná pre prerušenia, AFTER splnená. PR-04 iba skrátený soak; PR-05 splnená. Podrobnosti: <a href="../documentation/KS_Semestralny_projekt_Tema14_Mykhailo_Adamenko.pdf">PDF</a>.</p></html>'
(P/'reports/index.html').write_text(report,encoding='utf-8')
# Do not include live authentication sessions in the distributable summaries.
for path in (P/'results').rglob('*.json'):
    try:obj=json.loads(path.read_text(encoding='utf-8-sig'))
    except (UnicodeError,ValueError):continue
    if isinstance(obj,dict) and 'setup_data' in obj:
        del obj['setup_data'];path.write_text(json.dumps(obj,indent=2),encoding='utf-8')
files={R/x for x in subprocess.check_output(['git','ls-files'],cwd=R).decode().splitlines()}
files.update([R/'database/seeders/PerformanceSeeder.php',R/'tests/Feature/PerformanceAnalyticsTest.php'])
files.update(x for x in P.rglob('*') if x.is_file() and not any(part in ['runtime','tools','__pycache__'] for part in x.relative_to(P).parts))
assert not any(x.name=='.env' or 'vendor' in x.relative_to(R).parts or 'node_modules' in x.relative_to(R).parts for x in files)
archive=destination/'KS_Tema14_Mykhailo_Adamenko_projekt.zip'
with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED) as z:
    for path in sorted(files):
        if path.is_file():z.write(path,'elearn/'+path.relative_to(R).as_posix())
deliverables=[archive]
for name in ['KS_Semestralny_projekt_Tema14_Mykhailo_Adamenko.pdf','KS_Tema14_Prezentacia_Mykhailo_Adamenko.pptx','KS_Tema14_Prezentacia_Mykhailo_Adamenko.pdf']:
    source=P/'documentation'/name;target=destination/name
    target=target.with_name(target.stem+('_v3' if 'Prezentacia' in name else '_v2')+target.suffix)
    if not target.exists() or source.read_bytes()!=target.read_bytes():shutil.copy2(source,target)
    deliverables.append(target)
manifest={p.name:{'bytes':p.stat().st_size,'sha256':hashlib.sha256(p.read_bytes()).hexdigest()} for p in deliverables}
(destination/'ODOVZDANIE.json').write_text(json.dumps(manifest,indent=2),encoding='utf-8')
print(json.dumps(manifest,indent=2))
