from pathlib import Path
import json, subprocess, platform, hashlib
p=Path('performance-testing')
lock=json.loads(Path('composer.lock').read_text())
info={'os':platform.platform(),'python':platform.python_version(),'php':subprocess.check_output(['php','-v']).decode(),'packages':{x['name']:x['version'] for x in lock['packages'] if x['name'] in ['laravel/framework','inertiajs/inertia-laravel']},'workers':1,'session':'file','database':'SQLite dedicated','authentication':'Real teacher login with CSRF; setup cookie copied to VUs; shared teacher session, GET-only analytics','k6':subprocess.check_output([str(next((p/'tools').glob('*/k6.exe'))),'version']).decode(),'before_commit':'80aaabefbdfa268f1ed6a10395db905e0f63a89c','database_sha256':hashlib.sha256((p/'runtime/performance.sqlite').read_bytes()).hexdigest()}
(p/'reports/environment.json').write_text(json.dumps(info,indent=2),encoding='utf-8')
