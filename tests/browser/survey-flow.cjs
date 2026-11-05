const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn, spawnSync } = require('node:child_process');
const { launchBrowser, deviceOptions, navigate, reload, resize } = require('./browser-runtime.cjs');
const decodeQr = require('jsqr');
const { smtpInbox } = require('./smtp-inbox.cjs');
const php = process.env.CFS_PHP || 'php';

const root = path.resolve(__dirname, '../..');
const temporary = fs.mkdtempSync(path.join(os.tmpdir(), 'cfs-survey-'));
const database = path.join(temporary, 'browser.sqlite');
const screenshots = path.join(root, 'storage/app/test-results');
fs.writeFileSync(database, '');
fs.mkdirSync(screenshots, { recursive: true });

async function main() {
    const socket = net.createServer();
    await new Promise((resolve) => socket.listen(0, '127.0.0.1', resolve));
    const port = socket.address().port;
    await new Promise((resolve) => socket.close(resolve));
    const base = `http://127.0.0.1:${port}`;
    const inbox = process.env.CFS_SMTP_TEST === '1' ? await smtpInbox() : null;
    const env = { ...process.env, APP_ENV: 'testing', APP_URL: base, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
        MAIL_MAILER: 'array', CACHE_DRIVER: 'array', SESSION_DRIVER: 'file', SESSION_COOKIE: 'cfs_browser_test' };
    if (inbox) Object.assign(env, { MAIL_MAILER: 'smtp', MAIL_HOST: '127.0.0.1', MAIL_PORT: String(inbox.port),
        MAIL_USERNAME: 'null', MAIL_PASSWORD: 'null', MAIL_ENCRYPTION: 'null', MAIL_FROM_ADDRESS: 'cfs@example.test' });
    const migrate = spawnSync(php, ['artisan', 'migrate', '--seed', '--force'], { cwd: root, env, encoding: 'utf8', windowsHide: true });
    if (migrate.status !== 0 && inbox) await inbox.close();
    assert.equal(migrate.status, 0, String(migrate.error || '') + migrate.stdout + migrate.stderr);
    const server = spawn(php, ['-S', `127.0.0.1:${port}`, '-t', 'public', 'server.php'], {
        cwd: root, env, stdio: 'ignore', windowsHide: true,
    });
    let browser;
    try {
        for (let attempt = 0; attempt < 60; attempt++) {
            try { if ((await fetch(base + '/home', { headers: { Connection: 'close' } })).ok) break; } catch (error) { /* Wait for PHP startup. */ }
            await new Promise((resolve) => setTimeout(resolve, 100));
        }
        browser = await launchBrowser();
        const context = await browser.newContext({ ...deviceOptions, viewport: { width: 1920, height: 945 }, extraHTTPHeaders: { Connection: 'close' } });
        context.setDefaultTimeout(60000);
        context.setDefaultNavigationTimeout(60000);
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('dialog', (dialog) => dialog.dismiss());
        const adminContext = await browser.newContext({ ...deviceOptions, viewport: { width: 1440, height: 900 }, extraHTTPHeaders: { Connection: 'close' } });
        adminContext.setDefaultTimeout(60000);
        adminContext.setDefaultNavigationTimeout(60000);
        const admin = await adminContext.newPage();
        admin.on('pageerror', (error) => errors.push(error.message));
        admin.on('dialog', (dialog) => dialog.accept());
        await navigate(admin, base + '/admin-login');
        await admin.locator('input[name="login"]').fill('admin@example.com');
        await admin.locator('#password_display').pressSequentially('password');
        await admin.locator('#password_display').press('Enter', { noWaitAfter: true });
        await admin.waitForURL('**/dashboard', { waitUntil: 'domcontentloaded' });
        await navigate(admin, base + '/questions');
        await admin.getByRole('button', { name: 'Add Question', exact: true }).click();
        await admin.locator('#modal_question').fill('Visit note');
        await admin.locator('#modal_question_type').selectOption('comment');
        assert.equal(await admin.locator('[data-choice-section]').isVisible(), false);
        await admin.locator('#modal_help_text').fill('Share a short visit note');
        await admin.locator('#modal_step').selectOption('order');
        await admin.locator('#modal_sort').fill('100');
        await admin.screenshot({ path: path.join(screenshots, 'question-modal-desktop.png') });
        await admin.getByRole('button', { name: 'Save', exact: true }).click();
        await admin.waitForURL('**/questions');
        const questionRow = admin.locator('.questions-table tbody tr').filter({ hasText: 'Visit note' });
        const customQuestionId = await questionRow.locator('[data-question-edit]').getAttribute('data-question-id');
        assert.ok(customQuestionId, 'New question must be saved');
        await questionRow.getByRole('button', { name: 'Edit', exact: true }).click();
        await admin.locator('#modal_help_text').fill('Updated visit note hint');
        await admin.getByRole('button', { name: 'Save', exact: true }).click();
        await admin.waitForURL('**/questions');
        let extraStepKey;
        let extraQuestionId;
        if (process.env.CFS_EXTRA_STEPS === '1') {
            await admin.getByRole('button', { name: 'Add Step', exact: true }).press('Enter');
            await admin.locator('#step-title').fill('Additional Feedback');
            await admin.locator('#step-description').fill('Share additional feedback.');
            await admin.locator('#step-description').press('Control+Enter');
            await admin.waitForURL('**/questions');
            const stepRow = admin.locator('[data-step-row]').filter({ hasText: 'Additional Feedback' });
            extraStepKey = await stepRow.getAttribute('data-step-row');
            assert.equal(await stepRow.getByRole('button', { name: 'Publish', exact: true }).isDisabled(), true);
            await admin.getByRole('button', { name: 'Add Question', exact: true }).press('Enter');
            await admin.locator('#modal_question').fill('Additional response');
            await admin.locator('#modal_question_type').selectOption('comment');
            await admin.locator('#modal_step').selectOption(extraStepKey);
            await admin.getByRole('button', { name: 'Save', exact: true }).press('Enter');
            await admin.waitForURL('**/questions');
            extraQuestionId = await admin.locator('.questions-table tbody tr').filter({ hasText: 'Additional response' }).locator('[data-question-edit]').getAttribute('data-question-id');
            await admin.locator('[data-step-row]').filter({ hasText: 'Additional Feedback' }).getByRole('button', { name: 'Publish', exact: true }).press('Enter');
            await admin.waitForFunction(() => document.body.innerText.includes('Step published for new survey responses.'));
        }
        await navigate(admin, base + '/settings');
        await admin.locator('#notification_email').fill('browser-alerts@example.test');
        await admin.locator('#notification_email').press('Enter');
        await admin.waitForFunction(() => document.body.innerText.includes('Notification settings saved.'));
        await admin.getByRole('button', { name: 'Send Test Email', exact: true }).click();
        await admin.waitForFunction(smtp => document.body.innerText.includes(smtp ? 'accepted by the mail provider' : 'development mail output'), !!inbox);
        await admin.waitForLoadState('load');
        await admin.waitForFunction(() => document.querySelector('[data-survey-qr]')?.width > 100);
        const surveyLink = await admin.locator('#survey_link').inputValue();
        const pixels = await admin.locator('[data-survey-qr]').evaluate(canvas => ({ width: canvas.width, height: canvas.height, data: Array.from(canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data) }));
        assert.equal(decodeQr(new Uint8ClampedArray(pixels.data), pixels.width, pixels.height).data, surveyLink);
        await admin.evaluate(() => Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: async text => { window.testCopiedLink = text; } } }));
        await admin.getByRole('button', { name: 'Copy Link', exact: true }).press('Enter');
        assert.equal(await admin.evaluate(() => window.testCopiedLink), surveyLink);
        await admin.waitForFunction(() => document.querySelector('[data-download-qr]')?.dataset.qrReady === '1');
        const downloadEvent = admin.waitForEvent('download');
        await admin.getByRole('link', { name: 'Download QR', exact: true }).press('Enter');
        const download = await downloadEvent;
        assert.equal(download.suggestedFilename(), 'cfs-survey-qr.png');
        const chunks = [];
        for await (const chunk of await download.createReadStream()) chunks.push(chunk);
        assert.deepEqual([...Buffer.concat(chunks).subarray(0, 8)], [137, 80, 78, 71, 13, 10, 26, 10]);
        const settingControls = await admin.locator('.settings-input, .settings-section .compact-button').evaluateAll(elements => elements.map(element => element.getBoundingClientRect().height));
        assert.ok(settingControls.every(height => height === 28), 'Settings controls must use compact consistent heights');
        await admin.screenshot({ path: path.join(screenshots, 'settings-desktop.png'), fullPage: true });
        await resize(admin, { width: 390, height: 844 });
        const settingsBounds = await admin.evaluate(() => ({ viewport: innerWidth, client: document.documentElement.clientWidth, scroll: document.documentElement.scrollWidth, scale: visualViewport?.scale }));
        if (settingsBounds.scroll > settingsBounds.viewport + 1) {
            console.log('Settings mobile bounds', settingsBounds);
            await admin.screenshot({ path: path.join(screenshots, 'settings-mobile-overflow.png'), fullPage: true });
        }
        assert.ok(settingsBounds.scroll <= settingsBounds.viewport + 1, JSON.stringify(settingsBounds));
        await admin.screenshot({ path: path.join(screenshots, 'settings-mobile.png'), fullPage: true });
        await resize(admin, { width: 1440, height: 900 });
        await admin.locator('#account_name').fill('Verified Owner');
        await admin.locator('#account_current_password').fill('password');
        await admin.getByRole('button', { name: 'Show current password', exact: true }).press('Enter');
        assert.equal(await admin.locator('#account_current_password').getAttribute('type'), 'text');
        await admin.getByRole('button', { name: 'Hide current password', exact: true }).press('Enter');
        await admin.locator('#account_current_password').press('Enter');
        await admin.waitForFunction(() => document.body.innerText.includes('Account details updated.'));
        assert.equal(await admin.locator('#account_name').inputValue(), 'Verified Owner');
        await admin.waitForFunction(() => window.cfsKeyboardActions);
        await admin.locator('#survey_enabled').focus();
        await admin.keyboard.press('Enter');
        assert.equal(await admin.locator('#survey_enabled').isChecked(), false);
        await admin.locator('#closed_survey_message').fill('The survey is temporarily closed for this test.');
        await admin.getByRole('button', { name: 'Preview', exact: true }).press('Enter');
        assert.equal(await admin.locator('[data-preview-message]').innerText(), 'The survey is temporarily closed for this test.');
        await admin.keyboard.press('Escape');
        await admin.getByRole('button', { name: 'Save Survey Settings', exact: true }).press('Enter');
        await admin.waitForFunction(() => document.body.innerText.includes('Survey access settings saved.'));
        await navigate(page, surveyLink);
        assert.ok((await page.locator('.closed-survey-message').innerText()).includes('temporarily closed'));
        await admin.bringToFront();
        await admin.waitForFunction(() => window.cfsKeyboardActions);
        await admin.locator('#survey_enabled').focus();
        await admin.keyboard.press('Enter');
        await admin.getByRole('button', { name: 'Save Survey Settings', exact: true }).press('Enter');
        await admin.waitForFunction(() => document.body.innerText.includes('Survey access settings saved.'));
        console.log('Settings, QR decoding/download, keyboard controls and survey access checks passed.');
        const observer = await adminContext.newPage();
        await navigate(observer, base + '/dashboard');
        await observer.waitForTimeout(3300);
        const settingsLink = observer.locator('.admin-side-nav a[href$="/settings"]');
        assert.equal(await settingsLink.count(), 1);
        assert.equal(await observer.locator('[data-dashboard-chart]').count(), 2);
        assert.equal(await observer.locator('[data-dashboard-distribution]').count(), 1);
        if (process.env.CFS_LAYOUT_ONLY === '1') {
            await observer.screenshot({ path: path.join(screenshots, 'dashboard-desktop.png'), fullPage: true });
            await resize(observer, { width: 390, height: 844 });
            assert.equal(await observer.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
            await observer.screenshot({ path: path.join(screenshots, 'dashboard-mobile.png'), fullPage: true });
            console.log('Admin desktop/mobile layout checks passed.');
            return;
        }
        const widths = [];
        const footerPositions = [];

        async function checkLayout(step) {
            console.log(`Checking survey step ${step} across desktop, tablet and phone widths.`);
            const card = await page.locator('.survey-card').boundingBox();
            const action = await page.locator('.survey-actions button').boundingBox();
            widths.push(card.width);
            assert.ok(action.y + action.height <= card.y + card.height + 1, `Step ${step} clips its action button`);
            footerPositions.push((await page.locator('.footer').boundingBox()).y);
            await page.screenshot({ path: path.join(screenshots, `step-${step}-desktop.png`), fullPage: true });
            await resize(page, { width: 390, height: 844 });
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
            assert.equal(overflow, false, `Step ${step} overflows mobile width`);
            const mobileCard = await page.locator('.survey-card').boundingBox();
            const mobileAction = await page.locator('.survey-actions button').boundingBox();
            assert.ok(mobileAction.y + mobileAction.height + 8 <= mobileCard.y + mobileCard.height, `Step ${step} clips mobile actions`);
            await page.screenshot({ path: path.join(screenshots, `step-${step}-mobile.png`), fullPage: true });
            const { auditBounds, viewports } = require('./responsive-checks.cjs');
            for (const viewport of viewports) {
                await resize(page, viewport);
                await auditBounds(page, `Survey step ${step}, ${viewport.width}px`);
                const button = await page.locator('.survey-actions button').boundingBox();
                const card = await page.locator('.survey-card').boundingBox();
                assert.ok(button.y + button.height <= card.y + card.height, 'Survey actions must stay inside the card');
            }
            await resize(page, { width: 1920, height: 945 });
        }

        await page.bringToFront();
        await navigate(page, base + '/home');
        assert.equal(await page.locator('[data-start-survey]').isDisabled(), true);
        await page.locator('input[value="decline"]').check();
        assert.equal(await page.locator('[data-start-survey]').isDisabled(), true);
        await page.getByRole('link', { name: 'Terms & Conditions', exact: true }).first().click();
        await page.waitForURL('**/terms?**');
        await page.waitForFunction(() => document.querySelector('[data-home-terms-dialog]')?.open);
        await page.locator('.terms-agree').click();
        for (let index = 0; index < 5; index++) await page.locator('[data-terms-next]').click();
        await page.waitForURL('**/home');
        assert.equal(await page.locator('input[value="agree"]').isChecked(), true);
        assert.equal(await page.locator('[data-start-survey]').isEnabled(), true);
        await page.locator('[data-start-survey]').click();
        await page.waitForURL('**/feedback/date-time');
        const visitAudit = require('./responsive-checks.cjs');
        for (const viewport of visitAudit.viewports) {
            await resize(page, viewport);
            await visitAudit.auditBounds(page, `Date and Time, ${viewport.width}px`);
        }
        await resize(page, { width: 1920, height: 945 });
        await page.locator('#date_of_visit').fill('2026-01-01');
        await page.locator('#time_of_order').fill('10:00');
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.waitForURL('**/feedback/order');
        await checkLayout(2);
        assert.ok((await page.locator('.survey-card').innerText()).includes('Updated visit note hint'), 'Updated questions must reach the customer survey');
        await page.locator('input[value="Dine In"]').check();
        await page.locator('#order_details').fill('Browser test coffee');
        await page.locator(`[name="extra[${customQuestionId}]"]`).fill('Browser custom answer');
        await reload(page, { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('#order_details').inputValue(), 'Browser test coffee');
        assert.equal(await page.locator('input[value="Dine In"]').isChecked(), true);
        assert.equal(await page.locator(`[name="extra[${customQuestionId}]"]`).inputValue(), 'Browser custom answer');
        assert.equal(await page.locator('.topbar').count(), 1);
        assert.equal(await page.locator('.footer').count(), 1);
        assert.equal(await page.locator('.survey-card').count(), 1);
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.waitForURL('**/feedback/store-rating');
        await checkLayout(3);
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        assert.ok(page.url().endsWith('/store-rating'), 'Untouched ratings must block Next');
        await page.locator('#cleanliness').focus();
        await page.keyboard.press('ArrowRight');
        await page.locator('#speed_of_service').focus();
        await page.keyboard.press('ArrowRight');
        await page.locator('#staff').focus();
        await page.keyboard.press('ArrowRight');
        await reload(page, { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('#staff').inputValue(), '6');
        assert.equal(await page.locator('#staff').isDisabled(), false);
        await page.locator('[data-staff-na]').check();
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.waitForURL('**/feedback/food-rating');
        await checkLayout(4);
        await page.locator('#food_taste').focus();
        await page.keyboard.press('ArrowRight');
        await page.getByRole('link', { name: 'Back', exact: true }).click();
        await page.waitForURL('**/feedback/store-rating');
        assert.equal(await page.locator('[data-staff-na]').isChecked(), true);
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.waitForURL('**/feedback/food-rating');
        assert.equal(await page.locator('#food_taste').inputValue(), '6');
        for (const key of ['freshness', 'temperature', 'presentation', 'value_for_money']) {
            await page.locator('#food_' + key).focus();
            await page.keyboard.press('ArrowRight');
        }
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.waitForURL('**/feedback/final');
        await checkLayout(5);
        assert.ok(widths.every((width) => width === widths[0]), `Unequal card widths: ${widths}`);
        assert.ok(footerPositions.every((position) => position === footerPositions[0]), `Footer moved: ${footerPositions}`);
        await page.locator('label:has(input[name="order_accurate"][value="Like Up"])').click();
        await page.locator('label:has(input[name="food_satisfied"][value="Like Up"])').click();
        await page.locator('input[name="problem_today"][value="Yes"]').check();
        assert.equal(await page.locator('#problem_comment').evaluate((element) => element.required), true);
        await page.locator('#problem_comment').fill('Browser test concern');
        await page.getByRole('link', { name: 'Back', exact: true }).click();
        await page.waitForURL('**/feedback/food-rating');
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.waitForURL('**/feedback/final');
        assert.equal(await page.locator('#problem_comment').inputValue(), 'Browser test concern');
        await reload(page, { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('input[name="order_accurate"][value="Like Up"]').isChecked(), true);
        if (extraStepKey) {
            await page.getByRole('button', { name: 'Next', exact: true }).press('Enter');
            await page.waitForURL(`**/feedback/steps/${extraStepKey}`);
            await checkLayout(6);
            assert.equal(await page.locator('.survey-pill').innerText(), 'Step 6 of 6');
            await page.locator(`[name="extra[${extraQuestionId}]"]`).fill('Additional step answer');
            await page.locator(`[name="extra[${extraQuestionId}]"]`).press('Control+Enter');
        } else await page.getByRole('button', { name: 'Review Answers', exact: true }).press('Enter');
        await page.waitForURL('**/feedback/review');
        assert.ok((await page.locator('body').innerText()).includes('Browser test concern'));
        assert.ok((await page.locator('body').innerText()).includes('Browser custom answer'));
        if (extraStepKey) assert.ok((await page.locator('body').innerText()).includes('Additional step answer'));
        assert.ok((await page.locator('.survey-card').boundingBox()).height <= 480, 'Review must use the shorter card');
        assert.ok((await page.locator('.review-list').boundingBox()).height <= 320, 'Answers must stay in their compact scroll area');
        const reviewCard = await page.locator('.review-card').boundingBox();
        const reviewActions = await page.locator('.survey-actions').boundingBox();
        assert.ok(reviewActions.y + reviewActions.height < reviewCard.y + reviewCard.height, 'Review actions must not be clipped');
        const followUpEmail = 'survey-customer@example.test';
        const wantsFollowUp = process.env.CFS_FOLLOW_UP === '1';
        const followUp = page.locator('#follow_up_requested');
        const followUpFields = page.locator('[data-follow-up-fields]');
        const email = page.locator('#follow_up_email');
        const consent = page.locator('#follow_up_consent');
        assert.equal(await followUp.isChecked(), false);
        assert.equal(await followUpFields.isVisible(), false);
        assert.equal(await email.isDisabled(), true);
        await followUp.check();
        assert.equal(await followUpFields.isVisible(), true);
        await page.getByRole('button', { name: 'Submit Feedback', exact: true }).click();
        assert.ok(page.url().endsWith('/feedback/review'), 'A selected follow-up needs an email');
        assert.equal(await email.evaluate(element => element.validity.valueMissing), true);
        await email.fill('not-an-email');
        assert.equal(await email.evaluate(element => element.validity.typeMismatch), true);
        await email.fill(followUpEmail);
        await page.getByRole('button', { name: 'Submit Feedback', exact: true }).click();
        assert.equal(await consent.evaluate(element => element.validity.valueMissing), true);
        await consent.check();
        await email.fill('changed@example.test');
        assert.equal(await consent.isChecked(), false, 'Changing the email requires consent again');
        await email.fill(followUpEmail);
        await consent.check();
        await reload(page, { waitUntil: 'load' });
        assert.equal(await followUp.isChecked(), true);
        assert.equal(await email.inputValue(), followUpEmail);
        assert.equal(await consent.isChecked(), true);
        await page.getByRole('link', { name: 'Back', exact: true }).click();
        if (extraStepKey) {
            await page.waitForURL(`**/feedback/steps/${extraStepKey}`);
            await page.getByRole('button', { name: 'Review Answers', exact: true }).click();
        } else {
            await page.waitForURL('**/feedback/final');
            await page.getByRole('button', { name: 'Review Answers', exact: true }).click();
        }
        await page.waitForURL('**/feedback/review');
        assert.equal(await email.inputValue(), followUpEmail, 'Back/Next must retain the follow-up draft');
        assert.equal(await consent.isChecked(), true);
        await followUp.uncheck();
        assert.equal(await email.inputValue(), '');
        assert.equal(await consent.isChecked(), false);
        assert.equal(await followUpFields.isVisible(), false);
        assert.equal(await page.evaluate(() => Object.entries(sessionStorage).some(([key, value]) => key.startsWith('cfs-survey:') && value.includes('survey-customer@example.test'))), false, 'Opting out must remove the local email draft');
        await reload(page, { waitUntil: 'load' });
        assert.equal(await followUp.isChecked(), false);
        assert.equal(await email.inputValue(), '');
        if (wantsFollowUp) {
            await followUp.check();
            await email.fill(followUpEmail);
            await consent.check();
        }
        await page.screenshot({ path: path.join(screenshots, 'review-desktop.png'), fullPage: true });
        await resize(page, { width: 390, height: 844 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        await page.screenshot({ path: path.join(screenshots, 'review-mobile.png'), fullPage: true });
        await resize(page, { width: 1920, height: 945 });
        const reviewAudit = require('./responsive-checks.cjs');
        for (const viewport of reviewAudit.viewports) {
            await resize(page, viewport);
            await reviewAudit.auditBounds(page, `Review follow-up, ${viewport.width}px`);
            await followUp.scrollIntoViewIfNeeded();
            if (wantsFollowUp) await email.scrollIntoViewIfNeeded();
            const card = await page.locator('.review-card').boundingBox();
            const button = await page.locator('.survey-actions button').boundingBox();
            assert.ok(button.y + button.height <= card.y + card.height, 'Follow-up must not clip Submit');
        }
        await resize(page, { width: 1920, height: 945 });
        await page.getByRole('button', { name: 'Submit Feedback', exact: true }).click();
        await page.waitForURL('**/thank-you');
        if (inbox) {
            const notice = inbox.messages.find(message => message.raw.includes('FB-000002'));
            assert.ok(notice, 'A successful survey must send a real SMTP admin notification');
            assert.deepEqual(notice.recipients, ['browser-alerts@example.test']);
            assert.ok(notice.raw.includes('Needs Action'));
        }
        assert.ok(!(await page.locator('body').innerText()).includes(followUpEmail), 'Thank You must not disclose the follow-up email');
        await page.waitForTimeout(250);
        const firstFrame = await page.locator('canvas').evaluate((canvas) => canvas.toDataURL());
        await page.waitForTimeout(350);
        const secondFrame = await page.locator('canvas').evaluate((canvas) => canvas.toDataURL());
        assert.notEqual(firstFrame, secondFrame, 'Confetti must animate');
        await reload(page, { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('.thank-you-card').count(), 1);
        await page.screenshot({ path: path.join(screenshots, 'thank-you-desktop.png') });
        const { auditBounds, viewports } = require('./responsive-checks.cjs');
        for (const viewport of viewports) {
            await resize(page, viewport);
            await auditBounds(page, `Thank You, ${viewport.width}px`);
        }
        await resize(page, { width: 1920, height: 945 });
        await observer.bringToFront();
        await observer.waitForFunction(() => document.querySelector('#total-feedback')?.textContent.trim() === '2', null, { timeout: 15000 });
        assert.equal(await observer.locator('.dashboard-panel').count(), 1);
        assert.equal(await observer.locator('.footer').count(), 1);
        await observer.screenshot({ path: path.join(screenshots, 'dashboard-desktop.png'), fullPage: true });
        await page.bringToFront();
        await page.getByRole('link', { name: 'Back to Home', exact: true }).click();
        assert.equal(await page.evaluate(() => Object.keys(sessionStorage).filter((key) => key.startsWith('cfs-survey:')).length), 0);
        await navigate(page, base + '/admin-login');
        await page.locator('input[name="login"]').fill('admin@example.com');
        await page.locator('#password_display').pressSequentially('password');
        await page.locator('button[type="submit"]').click();
        await page.waitForURL('**/dashboard');
        await reload(page, { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('.admin-workspace').count(), 1);
        assert.equal(await page.locator('.topbar').count(), 1);
        assert.equal(await page.locator('.footer').count(), 1);
        await navigate(page, base + '/admin-feedback');
        await page.getByRole('link', { name: /View/ }).first().click();
        await page.waitForURL(/\/admin-feedback\/\d+$/);
        if (wantsFollowUp) {
            assert.ok((await page.locator('.feedback-detail-meta').innerText()).includes('Follow-up requested'));
            assert.equal(await page.locator(`a[href="mailto:${followUpEmail}"]`).count(), 1);
            assert.ok((await page.locator('.feedback-follow-up').innerText()).includes('Email follow-up consent given'));
        } else {
            assert.ok((await page.locator('.feedback-detail-meta').innerText()).includes('Anonymous'));
            assert.equal(await page.getByText('No follow-up requested.', { exact: true }).count(), 1);
        }
        assert.ok((await page.locator('body').innerText()).includes('FB-000002'));
        assert.ok((await page.locator('body').innerText()).includes('Browser custom answer'));
        const detailUrl = page.url();
        await page.locator('#status').selectOption('In Progress');
        await page.locator('#action_taken').fill('Checking browser test concern');
        await page.getByRole('button', { name: 'Save Admin Action', exact: true }).click();
        await page.waitForFunction(() => document.querySelector('.action-history')?.innerText.includes('Needs Action to In Progress'));
        await page.locator('#status').selectOption('Resolved');
        await page.locator('#action_taken').fill('Browser concern resolved');
        await page.getByRole('button', { name: 'Save Admin Action', exact: true }).click();
        await page.waitForFunction(() => document.querySelector('.action-history')?.innerText.includes('In Progress to Resolved'));
        assert.ok((await page.locator('.action-history').innerText()).includes('In Progress to Resolved'));
        await admin.bringToFront();
        await navigate(admin, base + '/questions');
        await admin.locator('.questions-table tbody tr').filter({ hasText: 'Visit note' }).getByRole('button', { name: 'Archive', exact: true }).click();
        await admin.waitForFunction(() => document.body.innerText.includes('Question archived.'));
        assert.equal(await admin.locator('.questions-table tbody tr').filter({ hasText: 'Visit note' }).count(), 0);
        await admin.getByRole('link', { name: 'Archived Questions', exact: true }).click();
        await admin.locator('.questions-table tbody tr').filter({ hasText: 'Visit note' }).getByRole('button', { name: 'Restore', exact: true }).click();
        await admin.waitForFunction(() => document.body.innerText.includes('Question restored.'));
        await admin.waitForURL('**/questions');
        assert.equal(await admin.locator('.questions-table tbody tr').filter({ hasText: 'Visit note' }).count(), 1);
        await navigate(page, detailUrl);
        assert.ok((await page.locator('body').innerText()).includes('Visit note'));
        assert.ok((await page.locator('body').innerText()).includes('Browser custom answer'));
        await navigate(admin, base + '/admin-feedback?status=Resolved');
        assert.ok((await admin.locator('body').innerText()).includes('FB-000002'));
        const csv = await adminContext.request.get(base + '/dashboard/export?status=Resolved');
        assert.equal(csv.status(), 200);
        assert.ok((await csv.text()).includes('FB-000002'));
        assert.ok(!(await csv.text()).includes(followUpEmail));
        if (extraStepKey) assert.ok((await csv.text()).includes('Additional step answer'));
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({ result: 'passed', cardWidths: widths, footerPositions, screenshots }, null, 2));
    } finally {
        if (browser) await browser.close();
        server.kill();
        if (inbox) await inbox.close();
    }
}

main().catch((error) => { console.error(error); process.exitCode = 1; });
