from pathlib import Path
import subprocess,time
from playwright.sync_api import sync_playwright
R=Path(__file__).resolve().parents[2];P=R/'performance-testing'
with (P/'runtime/presentation-server.txt').open('w') as log:
    server=subprocess.Popen(['php','-S','127.0.0.1:8767','../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],cwd=R/'public',stdout=log,stderr=subprocess.STDOUT,creationflags=subprocess.CREATE_NO_WINDOW)
    try:
        time.sleep(1)
        with sync_playwright() as p:
            browser=p.chromium.launch(channel='msedge',headless=True)
            page=browser.new_page(viewport={'width':1440,'height':1000},device_scale_factor=1.5)
            page.goto('http://127.0.0.1:8767/login',wait_until='networkidle')
            page.locator('#email').fill('teacher@performance.test')
            page.locator('#password').fill('Perf-local-2026!')
            page.locator('[data-test="login-button"]').click()
            page.wait_for_url('**/dashboard')
            page.goto('http://127.0.0.1:8767/teacher/courses/3/manage',wait_until='networkidle')
            page.locator('h2').first.wait_for()
            page.screenshot(path=str(P/'screenshots/application-analytics.png'))
            browser.close()
    finally:server.terminate();server.wait(timeout=10)
