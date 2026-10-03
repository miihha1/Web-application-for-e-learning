import subprocess,os,json,time,hashlib,sys
from pathlib import Path
root=Path(__file__).resolve().parents[2];os.chdir(root)
if len(sys.argv)!=3 or sys.argv[1] not in ['before','after'] or sys.argv[2] not in ['load','stress','soak']:
 sys.exit('Usage: run_suite.py before|after load|stress|soak. Restart the dedicated server before soak.')
phase=sys.argv[1];p=root/'performance-testing';k6=next((p/'tools').glob('*/k6.exe'))
expected=p/'baseline'/('TeacherCourseController.php' if phase=='before' else 'TeacherCourseController.after.php')
controller=root/'app/Http/Controllers/TeacherCourseController.php'
if controller.read_bytes()!=expected.read_bytes():sys.exit('Controller does not match requested phase. Restore the matching baseline copy first.')
for mode,cid in [('load',1),('load',2),('load',3),('stress',3),('soak',3)]:
 if len(sys.argv)>2 and mode!=sys.argv[2]:continue
 stem=f'{mode}-{cid}';out=Path(os.environ.get('PERF_OUTPUT_ROOT',str(p/'results')))/phase;out.mkdir(parents=True,exist_ok=True)
 if any((out/(stem+suffix)).exists() for suffix in ['.json','.ndjson','.txt']):sys.exit('Existing results preserved. Set PERF_OUTPUT_ROOT to a new directory.')
 env=dict(os.environ,MODE=mode,COURSE=str(cid),SUMMARY=str(out/(stem+'.json')))
 cmd=[str(k6),'run','--out','json='+str(out/(stem+'.ndjson')),'performance-testing/scripts/scenario.js']
 t=time.time()
 with (out/(stem+'.txt')).open('w',encoding='utf-8') as log:r=subprocess.run(cmd,env=env,stdout=log,stderr=subprocess.STDOUT)
 meta={'started_utc':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime(t)),'seconds':time.time()-t,'exit_code':r.returncode,'mode':mode,'course':cid,'script_sha256':hashlib.sha256((p/'scripts/scenario.js').read_bytes()).hexdigest(),'controller_sha256':hashlib.sha256((root/'app/Http/Controllers/TeacherCourseController.php').read_bytes()).hexdigest(),'command':cmd}
 (out/(stem+'-meta.json')).write_text(json.dumps(meta,indent=2),encoding='utf-8')
 print(stem,r.returncode,flush=True)
 if r.returncode not in [0,99]:sys.exit(r.returncode)
