from pathlib import Path
import secrets,base64,subprocess,sys
root=Path(__file__).resolve().parents[2]
p=root/'performance-testing/runtime';p.mkdir(exist_ok=True)
env=root/'.env'
if env.exists():
    if 'APP_ENV=performance' not in env.read_text():sys.exit('Refusing to change a non-performance environment')
else:
    env.write_text('APP_NAME=PerformanceLab\nAPP_ENV=performance\nAPP_KEY=base64:'+base64.b64encode(secrets.token_bytes(32)).decode()+'\nAPP_DEBUG=false\nAPP_URL=http://127.0.0.1:8765\nDB_CONNECTION=sqlite\nDB_DATABASE='+str(p/'performance.sqlite').replace('\\','/')+'\nSESSION_DRIVER=file\nCACHE_STORE=file\nQUEUE_CONNECTION=sync\nMAIL_MAILER=log\nLOG_CHANNEL=single\nBCRYPT_ROUNDS=12\n',encoding='utf-8')
db=p/'performance.sqlite'
if db.exists() and db.stat().st_size:sys.exit('Database already prepared. To start again, stop server and rename runtime/performance.sqlite as a backup first.')
db.touch()
subprocess.run(['php','artisan','migrate','--force'],cwd=root,check=True)
subprocess.run(['php','artisan','db:seed','--class=PerformanceSeeder','--force'],cwd=root,check=True)
