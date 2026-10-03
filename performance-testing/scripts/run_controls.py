"""Run serial control flows against isolated copies of the performance database.

Run only after the main suite is finished: the controller is switched temporarily.
"""
from pathlib import Path
import subprocess,os,time,shutil,json,hashlib
R=Path(__file__).resolve().parents[2];P=R/'performance-testing'
controller=R/'app/Http/Controllers/TeacherCourseController.php'
original=controller.read_bytes();db=P/'runtime/performance.sqlite';dbhash=hashlib.sha256(db.read_bytes()).hexdigest()
k6=next((P/'tools').glob('*/k6.exe'))
output=Path(os.environ.get('PERF_OUTPUT_ROOT',str(P/'results')))
for phase in ['before','after']:
    (output/phase).mkdir(parents=True,exist_ok=True)
    if (output/phase/'controls.json').exists():raise RuntimeError('Existing controls preserved. Set PERF_OUTPUT_ROOT to a new directory.')
try:
    for phase in ['before','after']:
        controller.write_bytes((P/'baseline'/('TeacherCourseController.php' if phase=='before' else 'TeacherCourseController.after.php')).read_bytes())
        target=P/'runtime'/('control-'+phase+'.sqlite');shutil.copy2(db,target)
        env=dict(os.environ,DB_DATABASE=str(target).replace('\\','/'),APP_URL='http://127.0.0.1:8766',BASE_URL='http://127.0.0.1:8766',SUMMARY=str((output/phase/'controls.json').resolve()))
        with (P/'runtime'/('control-server-'+phase+'.txt')).open('w') as serverlog:
            server=subprocess.Popen(['php','-S','127.0.0.1:8766','../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],cwd=R/'public',env=env,stdout=serverlog,stderr=subprocess.STDOUT,creationflags=subprocess.CREATE_NO_WINDOW)
            try:
                time.sleep(1)
                if server.poll() is not None:raise RuntimeError('Control server did not start')
                start=time.time()
                with (output/phase/'controls.txt').open('w',encoding='utf-8') as log:
                    result=subprocess.run([str(k6),'run','--out','json='+str((output/phase/'controls.ndjson').resolve()),str(P/'scripts/controls.js')],env=env,cwd=R,stdout=log,stderr=subprocess.STDOUT)
                (output/phase/'controls-meta.json').write_text(json.dumps({'started_utc':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime(start)),'seconds':time.time()-start,'returncode':result.returncode,'initial_database_sha256':dbhash,'controller_sha256':hashlib.sha256(controller.read_bytes()).hexdigest(),'script_sha256':hashlib.sha256((P/'scripts/controls.js').read_bytes()).hexdigest(),'iterations':20,'vus':1},indent=2),encoding='utf-8')
                print(phase,result.returncode,flush=True)
                if result.returncode:raise RuntimeError('Control checks failed; inspect log')
            finally:
                server.terminate();server.wait(timeout=10)
finally:
    controller.write_bytes(original)
    assert hashlib.sha256(db.read_bytes()).hexdigest()==dbhash,'Main performance database unexpectedly changed'
