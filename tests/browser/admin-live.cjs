const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn, spawnSync } = require('node:child_process');
const { launchBrowser, deviceOptions, navigate, reload, resize } = require('./browser-runtime.cjs');

async function main() {
    const root = path.resolve(__dirname, '../..');
    const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'cfs-admin-live-'));
    const database = path.join(temp, 'browser.sqlite');
    fs.writeFileSync(database, '');
    const socket = net.createServer();
    await new Promise((resolve) => socket.listen(0, '127.0.0.1', resolve));
    const port = socket.address().port;
    await new Promise((resolve) => socket.close(resolve));
    const base = `http://127.0.0.1:${port}`;
    const php = process.env.CFS_PHP || 'php';
    const env = { ...process.env, APP_ENV: 'testing', APP_URL: base, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
        MAIL_MAILER: 'array', CACHE_DRIVER: 'array', SESSION_COOKIE: 'cfs_admin_live_test', CFS_BROWSER_FIXTURE: '1' };
    const run = (args) => {
        const result = spawnSync(php, args, { cwd: root, env, encoding: 'utf8', windowsHide: true });
        assert.equal(result.status, 0, String(result.error || '') + result.stdout + result.stderr);
        return result.stdout;
    };
    run(['artisan', 'migrate', '--seed', '--force']);
    const server = spawn(php, ['-S', `127.0.0.1:${port}`, '-t', 'public', 'server.php'], { cwd: root, env, stdio: 'ignore', windowsHide: true });
    let browser;
    let uploaded;
    const screenshots = path.join(root, 'storage/app/test-results');
    fs.mkdirSync(screenshots, { recursive: true });
    try {
        for (let attempt = 0; attempt < 60; attempt++) {
            try { if ((await fetch(base + '/home', { headers: { Connection: 'close' } })).ok) break; } catch (error) { /* Wait for the test server. */ }
            await new Promise((resolve) => setTimeout(resolve, 100));
        }
        browser = await launchBrowser();
        const context = await browser.newContext({ ...deviceOptions, viewport: { width: 1440, height: 900 }, extraHTTPHeaders: { Connection: 'close' } });
        const page = await context.newPage();
        async function checkCompactButton(locator, width = 120) {
            await page.evaluate(() => document.fonts.ready);
            await locator.evaluate(() => document.fonts.ready);
            const box = await locator.boundingBox();
            assert.equal(box.height, 30);
            assert.equal(box.width, width);
            assert.ok(await locator.evaluate((button) => button.scrollWidth <= button.clientWidth), 'Button text must fit');
            await locator.hover();
            await page.waitForFunction((selector) => getComputedStyle(document.querySelector(selector)).backgroundColor === 'rgb(0, 0, 0)', await locator.evaluate((button) => '.' + [...button.classList].join('.')));
            assert.deepEqual(await locator.boundingBox(), box, 'Hover must not move or resize buttons');
            await page.mouse.move(0, 0);
        }
        page.setDefaultNavigationTimeout(60000);
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('requestfailed', (request) => {
            if (request.resourceType() === 'stylesheet') errors.push('Stylesheet failed: ' + request.url());
        });
        await navigate(page, base + '/admin-login', { waitUntil: 'domcontentloaded' });
        await page.locator('[name="login"]').fill('admin@example.com');
        await page.locator('#password_display').pressSequentially('password');
        await page.locator('#password_display').press('Enter', { noWaitAfter: true });
        await page.waitForURL('**/dashboard', { waitUntil: 'domcontentloaded' });
        if (process.env.CFS_FEEDBACK_DETAILS_ONLY === '1') {
            await require('./feedback-details-checks.cjs').auditFeedbackDetails(page, context, base, run, screenshots);
            assert.deepEqual(errors, []);
            console.log('Feedback Details layout, action validation/save/history/retry, live metrics, and equal-width Add Step/Question dialogs passed.');
            return;
        }
        if (process.env.CFS_DASHBOARD_ONLY === '1') {
            const fixture = JSON.parse(run(['tests/browser/dashboard-data.php']));
            await require('./dashboard-checks.cjs').auditDashboard(page, context, base, run, screenshots, fixture);
            assert.deepEqual(errors, []);
            console.log('Dashboard charts, all presets/custom filters, filtered CSV, live dirty-filter retention, notification actions and responsive checks passed.');
            return;
        }
        if (process.env.CFS_CHART_MOTION_ONLY === '1') {
            const { auditChartMotion } = require('./responsive-checks.cjs');
            await auditChartMotion(page);
            await navigate(page, base + '/admin-feedback', { waitUntil: 'domcontentloaded' });
            await auditChartMotion(page);
            const nav = await page.locator('.admin-side-nav').evaluate(element => ({ gap: getComputedStyle(element).gap, heights: [...element.querySelectorAll('a, button')].map(item => item.offsetHeight) }));
            assert.equal(nav.gap, '4px');
            assert.ok(nav.heights.every(height => height === 38));
            assert.deepEqual(errors, []);
            console.log('Chart motion, reload initialization, live panel events, reduced-motion behavior, and compact sidebar checks passed.');
            return;
        }
        if (process.env.CFS_RESPONSIVE_ONLY === '1') {
            const { auditResponsive } = require('./responsive-checks.cjs');
            await auditResponsive(page, base, screenshots);
            const visitor = await browser.newPage({ ...deviceOptions, extraHTTPHeaders: { Connection: 'close' } });
            visitor.on('pageerror', error => errors.push(error.message));
            await auditResponsive(visitor, base, screenshots, false);
            assert.deepEqual(errors, []);
            console.log('Responsive checks passed: admin and public pages at 320, 390, 768, 813, 1024, 1366 pixels.');
            return;
        }
        if (process.env.CFS_NOTIFICATION_LAYOUT_ONLY === '1') {
            await page.locator('[data-notification-trigger]').click();
            await resize(page, { width: 390, height: 844 });
            await page.screenshot({ path: path.join(screenshots, 'notification-position-debug.png'), fullPage: true });
            console.log(await page.evaluate(() => {
                const popup = document.querySelector('[data-notification-popover]');
                const css = getComputedStyle(popup);
                return { viewport: innerWidth, rect: popup.getBoundingClientRect().toJSON(), hidden: popup.hidden, parent: popup.parentElement.tagName,
                    inline: popup.getAttribute('style'), position: css.position, left: css.left, right: css.right, width: css.width, transform: css.transform,
                    cssFiles: [...document.styleSheets].map((sheet) => sheet.href) };
            }));
            return;
        }
        await navigate(page, base + '/business-profile', { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('#business_name').isDisabled(), true);
        assert.equal(await page.locator('.business-profile-panel').evaluate((panel) => getComputedStyle(panel).borderTopWidth), '0px');
        assert.equal((await page.locator('#business_name').boundingBox()).height, 32);
        assert.ok((await page.locator('#business_name').boundingBox()).width < 280);
        assert.equal(await page.getByRole('button', { name: 'Save Information', exact: true }).count(), 0);
        await checkCompactButton(page.getByRole('link', { name: 'Edit Information', exact: true }));
        await page.getByRole('link', { name: 'Edit Information', exact: true }).click();
        assert.equal(await page.locator('#business_name').isEnabled(), true);
        await checkCompactButton(page.getByRole('button', { name: 'Save Information', exact: true }));
        await page.locator('#email').fill('profile-qa@example.test');
        await page.locator('#logo').setInputFiles(path.join(root, 'tests/fixtures/business-logo-upload.png'));
        await page.locator('#email').press('Enter');
        await page.waitForURL('**/business-profile', { waitUntil: 'domcontentloaded' });
        assert.equal(await page.getByRole('link', { name: 'Edit Information', exact: true }).count(), 1);
        assert.equal(await page.locator('#email').isDisabled(), true);
        const avatar = page.locator('.profile-avatar img');
        const avatarUrl = await avatar.getAttribute('src');
        uploaded = path.resolve(root, 'public', new URL(avatarUrl).pathname.slice(1));
        assert.equal(await page.locator('[data-logo-preview] img').getAttribute('src'), avatarUrl);
        await avatar.evaluate((image) => image.decode());
        assert.equal((await avatar.boundingBox()).width, 32);
        assert.equal(await page.locator('[data-header-dashboard]').count(), 0);
        await page.screenshot({ path: path.join(screenshots, 'profile-saved-desktop.png'), fullPage: true });
        await resize(page, { width: 390, height: 844 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        await page.screenshot({ path: path.join(screenshots, 'profile-saved-mobile.png'), fullPage: true });
        await resize(page, { width: 1440, height: 900 });
        await navigate(page, base + '/settings', { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('#notification_email').inputValue(), 'profile-qa@example.test');
        await page.screenshot({ path: path.join(screenshots, 'settings-compact-desktop.png'), fullPage: true });
        await navigate(page, base + '/questions', { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('.questions-panel').evaluate((panel) => getComputedStyle(panel).borderTopWidth), '0px');
        await checkCompactButton(page.getByRole('button', { name: 'Add Question', exact: true }));
        assert.equal((await context.request.get(base + '/icons/lucide/plus.svg')).status(), 200);
        await page.screenshot({ path: path.join(screenshots, 'questions-single-surface.png'), fullPage: true });
        await resize(page, { width: 390, height: 844 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        await checkCompactButton(page.getByRole('button', { name: 'Add Question', exact: true }));
        await page.screenshot({ path: path.join(screenshots, 'questions-single-surface-mobile.png'), fullPage: true });
        await resize(page, { width: 1440, height: 900 });
        await navigate(page, base + '/home', { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('[data-header-dashboard]').count(), 1);
        await navigate(page, base + '/dashboard', { waitUntil: 'domcontentloaded' });
        const dateFields = await page.locator('[data-date-from], [data-date-to]').evaluateAll((fields) => fields.map((field) => field.getBoundingClientRect().toJSON()));
        assert.equal(dateFields[0].height, 30);
        assert.ok(dateFields[0].width <= 145);
        assert.equal(dateFields[0].y, dateFields[1].y);
        assert.ok(Math.abs(dateFields[0].width - dateFields[1].width) < 1);
        assert.equal((await page.locator('.filter-apply').boundingBox()).y, dateFields[0].y);
        assert.equal((await page.locator('.filter-clear').boundingBox()).y, dateFields[0].y);
        await checkCompactButton(page.locator('.filter-apply'), 64);
        await checkCompactButton(page.locator('.filter-clear'), 64);
        await checkCompactButton(page.locator('.dashboard-export'), 128);
        await page.locator('[data-date-preset]').selectOption('last7');
        const ranges = JSON.parse(await page.locator('[data-date-ranges]').getAttribute('data-date-ranges'));
        assert.equal(await page.locator('[data-date-from]').inputValue(), ranges.last7.from);
        assert.equal(await page.locator('[data-date-to]').inputValue(), ranges.last7.to);
        await page.getByRole('button', { name: 'Apply', exact: true }).click();
        await page.waitForURL('**/dashboard?**', { waitUntil: 'domcontentloaded' });
        assert.equal(new URL(page.url()).searchParams.get('range'), 'last7');
        await page.getByRole('link', { name: 'Clear', exact: true }).click();
        await page.waitForURL('**/dashboard', { waitUntil: 'domcontentloaded' });
        for (const period of ['weekly', 'monthly']) {
            await page.locator('#feedback-filters [name="period"]').selectOption(period);
            await page.getByRole('button', { name: 'Apply', exact: true }).click();
            await page.waitForURL(url => url.searchParams.get('period') === period, { waitUntil: 'domcontentloaded' });
            const stats = await (await context.request.get(base + '/dashboard/stats?period=' + period)).json();
            assert.equal(Number(await page.locator('#total-feedback').innerText()), stats.total);
        }
        await page.getByRole('link', { name: 'Clear', exact: true }).click();
        await page.waitForURL('**/dashboard', { waitUntil: 'domcontentloaded' });
        await page.locator('[data-export-menu] summary').click();
        assert.equal(await page.getByRole('link', { name: 'Export Filtered Results', exact: true }).isVisible(), true);
        const exportResponse = await context.request.get(await page.getByRole('link', { name: 'Export Filtered Results', exact: true }).getAttribute('href'));
        assert.equal(exportResponse.status(), 200);
        assert.ok(exportResponse.headers()['content-disposition'].includes('attachment'));
        assert.ok((await exportResponse.text()).includes('FB-000001'));
        const csvDownloadEvent = page.waitForEvent('download');
        await page.getByRole('link', { name: 'Export Filtered Results', exact: true }).press('Enter');
        const csvDownload = await csvDownloadEvent;
        assert.ok(csvDownload.suggestedFilename().endsWith('.csv'));
        const csvChunks = [];
        for await (const chunk of await csvDownload.createReadStream()) csvChunks.push(chunk);
        assert.ok(Buffer.concat(csvChunks).toString().includes('FB-000001'));
        await page.locator('[data-export-menu] summary').click();
        await page.mouse.move(0, 0);
        await page.evaluate(() => document.fonts.ready);
        const dashboardIcons = await page.locator('.filter-preset-shell .ui-icon, .dashboard-export .ui-icon').evaluateAll((icons) => icons.map((icon) => {
            const style = getComputedStyle(icon);
            return { width: icon.getBoundingClientRect().width, height: icon.getBoundingClientRect().height,
                background: style.backgroundColor, mask: style.maskImage };
        }));
        assert.ok(dashboardIcons.every((icon) => icon.width === 14 && icon.height === 14 && icon.background !== 'rgba(0, 0, 0, 0)' && icon.mask !== 'none'), JSON.stringify(dashboardIcons));
        assert.ok(await page.locator('#average-rating').evaluate((rating) => parseFloat(getComputedStyle(rating).fontSize) >= 25));
        await page.screenshot({ path: path.join(screenshots, 'dashboard-reference-desktop.png'), fullPage: true });
        await resize(page, { width: 390, height: 844 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        await page.screenshot({ path: path.join(screenshots, 'dashboard-reference-mobile.png'), fullPage: true });
        await resize(page, { width: 1440, height: 900 });

        const visitor = await browser.newPage({ viewport: { width: 1440, height: 900 }, extraHTTPHeaders: { Connection: 'close' } });
        visitor.on('pageerror', (error) => errors.push(error.message));
        await navigate(visitor, base + '/contact', { waitUntil: 'domcontentloaded' });
        assert.equal(await visitor.locator('#contact-name').getAttribute('required'), null);
        assert.equal(await visitor.locator('#contact-email').getAttribute('required'), '');
        const contactBox = await visitor.locator('.contact-card').boundingBox();
        assert.equal(contactBox.width, 480);
        assert.equal(contactBox.height, 360);
        const contactHeading = await visitor.locator('.contact-card .terms-heading').boundingBox();
        const contactForm = await visitor.locator('.contact-form').boundingBox();
        assert.ok(contactHeading.width > 400);
        assert.equal(contactHeading.x, contactForm.x);
        assert.ok(contactHeading.y + contactHeading.height <= contactForm.y);
        const contactFields = await visitor.locator('.contact-input:has(input)').evaluateAll((fields) => fields.map((field) => field.getBoundingClientRect().toJSON()));
        assert.ok(contactFields.every((field) => field.height === 30 && field.width <= 220));
        assert.equal(contactFields[0].width, contactFields[1].width);
        const sendButton = await visitor.locator('.contact-send').boundingBox();
        assert.equal(sendButton.width, 120);
        assert.equal(sendButton.height, 30);
        const messageField = await visitor.locator('#contact-message').boundingBox();
        const desktopActions = await visitor.locator('.contact-actions').boundingBox();
        assert.ok(messageField.y + messageField.height <= desktopActions.y);
        assert.equal((await context.request.get(base + '/icons/lucide/send.svg')).status(), 200);
        await visitor.evaluate(() => document.fonts.ready);
        await visitor.screenshot({ path: path.join(screenshots, 'contact-form-desktop.png'), fullPage: true });
        await resize(visitor, { width: 390, height: 844 });
        assert.equal(await visitor.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        await visitor.screenshot({ path: path.join(screenshots, 'contact-form-mobile.png'), fullPage: true });
        const contactAction = await visitor.locator('.contact-actions').boundingBox();
        const mobileContact = await visitor.locator('.contact-card').boundingBox();
        assert.ok(contactAction.y + contactAction.height < mobileContact.y + mobileContact.height);
        await visitor.locator('#contact-email').fill('visitor-qa@example.test');
        await visitor.locator('#contact-message').fill('Please help with the CFS survey.');
        await visitor.locator('#contact-message').press('Control+Enter');
        await visitor.waitForFunction(() => document.querySelector('.contact-status')?.textContent.includes('Your message has been received.'));
        assert.equal(await visitor.locator('#contact-message').inputValue(), '');
        const contactFeed = await (await context.request.get(base + '/dashboard/notifications')).json();
        const contactNotification = contactFeed.notifications.find(notification => notification.url.includes('/admin-messages/'));
        assert.ok(contactNotification);
        assert.equal((await context.request.get(contactNotification.url)).status(), 200);
        await visitor.close();
        await page.bringToFront();
        await navigate(page, base + '/dashboard/total', { waitUntil: 'domcontentloaded' });
        const firstRevision = await page.locator('[data-live-url]').getAttribute('data-live-revision');
        const first = JSON.parse(run(['tests/browser/live-feedback.php', 'Needs Action']));
        await page.waitForFunction(() => document.querySelector('#total-feedback')?.textContent.trim() === '2', null, { timeout: 20000 });
        assert.notEqual(await page.locator('[data-live-url]').getAttribute('data-live-revision'), firstRevision);
        assert.equal(await page.locator('.feedback-kpi-spark').count(), 4);
        await page.screenshot({ path: path.join(screenshots, 'live-dashboard-desktop.png'), fullPage: true });
        await navigate(page, base + '/admin-feedback', { waitUntil: 'domcontentloaded' });
        const donutImage = () => page.locator('.feedback-donut').evaluate(donut => getComputedStyle(donut.querySelector('.feedback-donut-motion-ring') || donut).backgroundImage);
        const firstDonut = await donutImage();
        const second = JSON.parse(run(['tests/browser/live-feedback.php', 'Submitted']));
        await page.waitForFunction(() => document.querySelector('.feedback-donut span')?.textContent.trim() === '3', null, { timeout: 20000 });
        assert.notEqual(await donutImage(), firstDonut, 'The visible chart segments must follow new database counts');
        await page.waitForFunction(() => document.querySelector('[data-notification-badge]')?.textContent.trim() === '2', null, { timeout: 15000 });
        await page.locator('[data-notification-trigger]').click();
        await page.locator('.notification-popup-row').first().waitFor();
        const ids = await page.locator('.notification-popup-row').evaluateAll((rows) => rows.map((row) => row.dataset.notificationId));
        assert.equal(ids.length, 3);
        assert.equal(new Set(ids).size, 3);
        assert.ok((await page.locator('[data-notification-list]').innerText()).includes(first.code));
        assert.ok((await page.locator('[data-notification-list]').innerText()).includes(second.code));
        await resize(page, { width: 390, height: 844 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        await page.waitForFunction(() => {
            const bounds = document.querySelector('[data-notification-popover]').getBoundingClientRect();
            return bounds.x >= 0 && bounds.right <= innerWidth;
        }, null, { timeout: 5000 });
        await page.screenshot({ path: path.join(screenshots, 'live-notifications-mobile.png'), fullPage: true });
        await resize(page, { width: 1440, height: 900 });
        await page.locator(`.notification-popup-row[href$="/${first.id}"]`).click();
        await page.waitForURL(`**/admin-feedback/${first.id}`, { waitUntil: 'domcontentloaded' });
        await page.waitForFunction(() => document.querySelector('[data-notification-badge]')?.textContent.trim() === '1');
        const monitor = await context.newPage();
        monitor.on('pageerror', (error) => errors.push(error.message));
        await navigate(monitor, base + '/admin-feedback', { waitUntil: 'domcontentloaded' });
        await page.bringToFront();
        await page.locator('#status').selectOption('Resolved');
        await page.locator('#action_taken').fill('Resolved during isolated browser test');
        await page.getByRole('button', { name: 'Save Admin Action', exact: true }).click();
        await page.waitForFunction(() => document.querySelector('.action-history')?.innerText.includes('Needs Action to Resolved'));
        await monitor.bringToFront();
        try {
            await monitor.waitForFunction(() => document.querySelector('.feedback-legend')?.textContent.includes('Resolved: 1'), null, { timeout: 30000 });
        } catch (error) {
            console.log(await monitor.evaluate(() => ({ hidden: document.hidden, live: window.cfsAdminLive, focus: document.activeElement?.outerHTML,
                revision: document.querySelector('[data-live-url]')?.dataset.liveRevision, legend: document.querySelector('.feedback-legend')?.textContent })));
            console.log('Stats', await (await context.request.get(base + '/dashboard/stats')).json(), 'Errors', errors);
            await monitor.screenshot({ path: path.join(screenshots, 'live-refresh-failure.png'), fullPage: true });
            throw error;
        }
        await monitor.locator('[data-notification-trigger]').click();
        await monitor.locator('[data-notification-read-all]').click();
        await monitor.waitForFunction(() => document.querySelector('[data-notification-badge]')?.textContent.trim() === '0');
        await monitor.keyboard.press('Escape');
        assert.equal(await monitor.locator('.feedback-dashboard').count(), 1);
        assert.equal(await monitor.locator('.topbar').count(), 1);
        assert.equal(await monitor.locator('.footer').count(), 1);
        await monitor.screenshot({ path: path.join(screenshots, 'live-feedback-desktop.png'), fullPage: true });
        await resize(monitor, { width: 390, height: 844 });
        assert.equal(await monitor.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        await monitor.screenshot({ path: path.join(screenshots, 'live-feedback-mobile.png'), fullPage: true });
        assert.deepEqual(errors, []);
        console.log('Compact button sizes/hover, date filters/CSV export, profile save/edit, Contact submission, live charts/notifications, and desktop/mobile checks passed.');
    } finally {
        if (browser) await browser.close();
        server.kill();
        const logoRoot = path.resolve(root, 'public/uploads/logos') + path.sep;
        if (uploaded && uploaded.startsWith(logoRoot) && fs.existsSync(uploaded)) fs.unlinkSync(uploaded);
    }
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
