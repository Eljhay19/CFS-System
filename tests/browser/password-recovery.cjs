const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn, spawnSync } = require('node:child_process');
const { launchBrowser, deviceOptions, navigate, reload, resize } = require('./browser-runtime.cjs');
const { auditBounds, viewports } = require('./responsive-checks.cjs');
const { smtpInbox } = require('./smtp-inbox.cjs');

async function main() {
    const root = path.resolve(__dirname, '../..');
    const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'cfs-otp-test-'));
    const database = path.join(temp, 'test.sqlite');
    fs.writeFileSync(database, '');
    const socket = net.createServer();
    await new Promise(resolve => socket.listen(0, '127.0.0.1', resolve));
    const port = socket.address().port;
    await new Promise(resolve => socket.close(resolve));
    const base = 'http://127.0.0.1:' + port;
    const php = process.env.CFS_PHP || 'php';
    const inbox = await smtpInbox();
    const env = { ...process.env, APP_ENV: 'testing', APP_URL: base, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
        MAIL_MAILER: 'smtp', MAIL_HOST: '127.0.0.1', MAIL_PORT: String(inbox.port), MAIL_ENCRYPTION: 'null',
        MAIL_USERNAME: 'null', MAIL_PASSWORD: 'null', MAIL_FROM_ADDRESS: 'cfs@example.test',
        LOG_CHANNEL: 'stderr', CACHE_DRIVER: 'array', SESSION_DRIVER: 'file', SESSION_COOKIE: 'cfs_password_browser_test' };
    let server, browser;
    const screenshots = path.join(root, 'storage/app/test-results');
    fs.mkdirSync(screenshots, { recursive: true });
    const codes = () => inbox.messages.map(message => message.raw.replace(/=\r?\n/g, '').match(/Your CFS verification code is: (\d{6})/)?.[1]).filter(Boolean);
    try {
        const migrate = spawnSync(php, ['artisan', 'migrate', '--seed', '--force'], { cwd: root, env, encoding: 'utf8', windowsHide: true });
        assert.equal(migrate.status, 0, String(migrate.error || '') + migrate.stdout + migrate.stderr);
        server = spawn(php, ['-S', '127.0.0.1:' + port, '-t', 'public', 'server.php'], { cwd: root, env, stdio: 'ignore', windowsHide: true });
        for (let attempt = 0; attempt < 60; attempt++) {
            try { if ((await fetch(base + '/home', { headers: { Connection: 'close' } })).ok) break; } catch {}
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        browser = await launchBrowser();
        const context = await browser.newContext({ ...deviceOptions, viewport: { width: 1366, height: 768 }, extraHTTPHeaders: { Connection: 'close' } });
        const page = await context.newPage();
        page.setDefaultTimeout(60000); page.setDefaultNavigationTimeout(60000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await navigate(page, base + '/admin-login', { waitUntil: 'load' });
        const loginBox = await page.locator('[data-admin-login-panel]').boundingBox();
        const loginInput = await page.locator('.input-shell').first().boundingBox();
        const loginButton = await page.locator('.login-actions button').boundingBox();
        async function auditPanel(state) {
            assert.equal(await page.locator('.topbar').count(), 1);
            assert.equal(await page.locator('.footer').count(), 1);
            assert.equal(await page.locator('dialog').count(), 0);
            assert.equal(await page.locator('[data-admin-login-panel]').count(), 0);
            assert.equal(await page.locator('[data-recovery-panel]').count(), 1);
            assert.equal(await page.locator('.nav a.active').innerText(), 'Admin Login');
            for (const viewport of viewports) {
                await resize(page, viewport);
                await auditBounds(page, state + '-' + viewport.width);
                const panel = page.locator('[data-recovery-panel]');
                const box = await panel.boundingBox();
                assert.equal(Math.round(box.width), viewport.width === 320 ? 296 : 332);
                if (viewport.width === 1366) assert.ok(Math.abs(box.x - loginBox.x) < 1, 'Recovery replaces the Login card in the same position');
                if (state === 'sent') assert.equal(await panel.evaluate(el => el.scrollHeight <= el.clientHeight + 1), true, 'OTP panel must not scroll internally');
                assert.equal(await page.locator('.login-actions .button').first().evaluate(el => el.offsetHeight), Math.round(loginButton.height));
            }
            await resize(page, { width: 1366, height: 768 });
            await page.screenshot({ path: path.join(screenshots, 'password-' + state + '-desktop.png'), fullPage: true });
            await resize(page, { width: 390, height: 844 });
            await page.screenshot({ path: path.join(screenshots, 'password-' + state + '-mobile.png'), fullPage: true });
            await resize(page, { width: 1366, height: 768 });
        }
        await page.getByRole('link', { name: 'Forgot Password?', exact: true }).press('Enter');
        await page.waitForURL('**/forgot-password', { waitUntil: 'load' });
        await auditPanel('forgot');
        const input = await page.locator('.input-shell').boundingBox();
        assert.equal(input.width, loginInput.width); assert.equal(input.height, loginInput.height);
        await reload(page, { waitUntil: 'load' });
        await page.locator('#email').fill('admin@example.com');
        await page.locator('#email').press('Enter');
        await page.waitForURL('**/check-your-email', { waitUntil: 'load' });
        assert.equal(inbox.messages.length, 1);
        assert.deepEqual(inbox.messages[0].recipients, ['admin@example.com']);
        assert.equal(codes().length, 1, 'An actual SMTP code email must be received by the test inbox');
        assert.equal(await page.locator('[data-resend]').isDisabled(), true);
        assert.equal(await page.locator('[data-primary-submit]').isEnabled(), true, 'Resend cooldown must not disable Verify Code');
        await auditPanel('sent');
        await reload(page, { waitUntil: 'load' });
        assert.equal(inbox.messages.length, 1, 'Reload must not send mail again');
        await page.waitForFunction(() => !document.querySelector('[data-resend]').disabled, null, { timeout: 65000 });
        await page.getByRole('button', { name: 'Resend Code', exact: true }).press('Enter');
        await page.waitForURL('**/check-your-email', { waitUntil: 'load' });
        await page.waitForFunction(() => document.querySelector('.recovery-verify-form')?.dataset.ready === '1');
        assert.equal(codes().length, 2);
        assert.notEqual(codes()[0], codes()[1]);
        const fillCode = async code => { await page.locator('[data-otp-digit]').first().fill(code); };
        await fillCode(codes()[0]);
        await page.getByRole('button', { name: 'Verify Code', exact: true }).press('Enter');
        await page.waitForFunction(() => document.querySelector('.alert.error')?.textContent.includes('invalid or expired'));
        assert.equal(await page.locator('[data-otp-source]').inputValue(), '', 'Invalid codes must never be restored from old input');
        await fillCode(codes()[1]);
        const digits = await page.locator('[data-otp-digit]').evaluateAll(inputs => inputs.map(input => input.value));
        assert.equal(digits.join(''), codes()[1], 'Six-digit paste/autofill must distribute across the inputs');
        await page.locator('[data-otp-digit]').last().press('Enter');
        await page.waitForURL('**/reset-password/**', { waitUntil: 'load' });
        const resetUrl = page.url();
        await auditPanel('reset');
        await page.locator('#password').fill('Verified-reset-2026');
        await page.locator('[data-password-toggle]').first().click();
        assert.equal(await page.locator('#password').getAttribute('type'), 'text');
        await page.locator('[data-password-toggle]').first().click();
        await page.locator('#password_confirmation').fill('mismatch');
        await page.locator('#password_confirmation').press('Enter');
        await page.waitForFunction(() => document.querySelector('.alert.error'));
        assert.equal(await page.locator('#password').inputValue(), '');
        await page.locator('#password').fill('Verified-reset-2026');
        await page.locator('#password_confirmation').fill('Verified-reset-2026');
        await page.locator('#password_confirmation').press('Enter');
        await page.waitForURL('**/admin-login', { waitUntil: 'load' });
        await page.locator('#login').fill('admin@example.com');
        await page.locator('#password_display').pressSequentially('password');
        await page.locator('#password_display').press('Enter');
        await page.waitForFunction(() => document.querySelector('.alert.error')?.textContent.includes('Invalid'));
        await page.locator('#password_display').pressSequentially('Verified-reset-2026');
        await page.locator('#password_display').press('Enter');
        await page.waitForURL('**/dashboard', { waitUntil: 'load' });
        await page.locator('.logout-form button').press('Enter');
        await page.waitForURL('**/admin-login', { waitUntil: 'load' });
        await navigate(page, resetUrl, { waitUntil: 'load' });
        await page.locator('#password').fill('Another-password-2026');
        await page.locator('#password_confirmation').fill('Another-password-2026');
        await page.locator('#password_confirmation').press('Enter');
        await page.waitForFunction(() => document.querySelector('.alert.error')?.textContent.includes('token'));
        assert.deepEqual(errors, []);
        console.log('OTP recovery passed: real local SMTP delivery, resend cooldown, expired-code rejection, six inputs/paste, verified reset, old/new login, single-use token, inline panels and seven responsive viewports.');
    } finally {
        if (browser) await browser.close();
        server?.kill();
        await inbox.close();
    }
}
main().catch(error => { console.error(error); process.exitCode = 1; });
