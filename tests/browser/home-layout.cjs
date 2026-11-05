const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

async function main() {
    const screenshots = path.resolve(__dirname, '../../storage/app/test-results');
    fs.mkdirSync(screenshots, { recursive: true });
    const browser = await chromium.launch({ headless: true, channel: 'chrome' });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.addInitScript(() => {
            window.addEventListener('pagehide', () => {
                const form = document.querySelector('.hero-form');
                if (form) sessionStorage.setItem('home-leaving-state', JSON.stringify({
                    agree: form.querySelector('[value="agree"]').checked,
                    disabled: form.querySelector('[data-start-survey]').disabled,
                }));
            });
        });
        await page.goto((process.env.CFS_BASE_URL || 'http://127.0.0.1:8000') + '/home', { waitUntil: 'domcontentloaded' });
        const measurements = [];
        for (const viewport of [{ width: 1920, height: 945 }, { width: 1440, height: 900 }, { width: 390, height: 844 }]) {
            await page.setViewportSize(viewport);
            const result = await page.evaluate(() => {
                const bounds = (selector) => document.querySelector(selector).getBoundingClientRect().toJSON();
                const label = document.querySelector('.consent-option > span');
                const range = document.createRange();
                range.selectNodeContents(label);
                return {
                    viewport: innerWidth, hero: bounds('.hero'), agree: bounds('.consent-option'),
                    decline: bounds('.consent-option.last'), button: bounds('[data-start-survey]'),
                    topLogo: bounds('.topbar > .brand'), bottomLogo: bounds('.footer .brand'),
                    topNav: bounds('.topbar .nav'), bottomNav: bounds('.footer-links'),
                    agreeLines: new Set([...range.getClientRects()].map((rect) => Math.round(rect.top))).size,
                    featureGap: bounds('.feature-row').top - bounds('.hero').bottom,
                    featureIcon: bounds('.feature-icon').width,
                    featureTitle: parseFloat(getComputedStyle(document.querySelector('.feature strong')).fontSize),
                    featureText: parseFloat(getComputedStyle(document.querySelector('.feature-text')).fontSize),
                    overflow: document.documentElement.scrollWidth > innerWidth + 1,
                };
            });
            assert.equal(result.overflow, false);
            assert.equal(result.agreeLines, 1, 'Consent must stay on one line');
            assert.equal(result.agree.width, result.decline.width);
            assert.equal(result.agree.height, result.decline.height);
            assert.ok(result.agree.width <= 322 && result.agree.height <= 34);
            assert.ok(result.button.width <= 125 && result.button.height <= 34);
            assert.equal(result.featureGap, viewport.width <= 640 ? 18 : 24);
            assert.equal(result.featureIcon, 22);
            assert.equal(result.featureTitle, 13);
            assert.equal(result.featureText, 12);
            if (viewport.width > 900) {
                assert.equal(result.hero.width, 880);
                assert.ok(result.hero.height <= 420, 'Home card must be compact');
                assert.ok(Math.abs(result.topLogo.x - result.bottomLogo.x) < 1, 'Header/footer logos must align');
                assert.ok(Math.abs(result.topNav.right - result.bottomNav.right) < 1, 'Header/footer links must align');
            }
            assert.equal(await page.locator('.survey-business').count(), 0);
            assert.equal(await page.locator('.consent-option .inline-policy-link').count(), 1);
            assert.equal(await page.locator('[data-start-survey]').isDisabled(), true);
            await page.locator('input[value="decline"]').check();
            assert.equal(await page.locator('[data-start-survey]').isDisabled(), true);
            assert.equal(await page.locator('input[value="agree"]').isChecked(), false);
            await page.screenshot({ path: path.join(screenshots, `home-${viewport.width}.png`), fullPage: true });
            await page.reload({ waitUntil: 'domcontentloaded' });
            measurements.push({ viewport: result.viewport, card: [result.hero.width, result.hero.height], consent: [result.agree.width, result.agree.height] });
        }

        const infoPage = await browser.newPage({ viewport: { width: 1920, height: 945 } });
        const infoSizes = [];
        for (const section of ['terms', 'privacy-policy', 'faqs']) {
            await infoPage.goto((process.env.CFS_BASE_URL || 'http://127.0.0.1:8000') + '/' + section, { waitUntil: 'domcontentloaded' });
            const box = await infoPage.locator('.terms-card').boundingBox();
            infoSizes.push({ section, width: box.width, height: box.height });
        }
        assert.equal(new Set(infoSizes.map((box) => box.width)).size, 1, 'Information cards must share their width');
        assert.ok(Math.max(...infoSizes.map((box) => box.height)) - Math.min(...infoSizes.map((box) => box.height)) <= 2, 'Information cards must share their compact height');
        await infoPage.goto((process.env.CFS_BASE_URL || 'http://127.0.0.1:8000') + '/contact', { waitUntil: 'domcontentloaded' });
        assert.equal(await infoPage.locator('.contact-card').count(), 1);
        assert.equal((await infoPage.locator('.contact-card').boundingBox()).width, 480);
        assert.equal((await infoPage.locator('.contact-card').boundingBox()).height, 360);
        assert.equal(await infoPage.getByRole('button', { name: 'Send Message', exact: true }).count(), 1);
        assert.equal(await infoPage.locator('.terms-nav').count(), 0);
        await infoPage.screenshot({ path: path.join(screenshots, 'contact-desktop.png') });
        await infoPage.setViewportSize({ width: 390, height: 844 });
        assert.equal(await infoPage.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        await infoPage.screenshot({ path: path.join(screenshots, 'contact-mobile.png'), fullPage: true });
        await infoPage.getByRole('link', { name: 'Back to Home', exact: true }).click();
        await infoPage.waitForURL('**/home', { waitUntil: 'domcontentloaded' });
        await infoPage.close();

        let startRequests = 0;
        page.on('request', (request) => { if (request.method() === 'POST' && new URL(request.url()).pathname === '/feedback/start') startRequests++; });
        const assertLocked = async () => {
            assert.equal(await page.locator('input[value="agree"]').isChecked(), false);
            assert.equal(await page.locator('[data-start-survey]').isDisabled(), true);
        };
        const assertConfirmed = async () => {
            assert.equal(await page.locator('input[value="agree"]').isChecked(), true);
            assert.equal(await page.locator('[data-start-survey]').isEnabled(), true);
        };
        await assertLocked();
        await page.locator('input[value="agree"]').click();
        await page.waitForURL(/\/terms(?:\?.*)?$/, { waitUntil: 'domcontentloaded' });
        assert.equal(new URL(page.url()).searchParams.get('from'), 'home');
        assert.equal(await page.locator('[data-home-terms-dialog][open]').count(), 1);
        assert.equal(await page.locator('.hero').count(), 1);
        assert.equal(await page.locator('[data-terms-card]').count(), 1);
        assert.equal(await page.locator('.topbar').count(), 1);
        assert.equal(await page.locator('.footer').count(), 1);
        assert.equal(await page.locator('[data-home-terms-dialog] .terms-card').evaluate((card) => getComputedStyle(card).backgroundColor), 'rgb(255, 255, 255)');
        const popupContent = await page.locator('[data-terms-panel]').allTextContents();
        await page.locator('[data-home-terms-dialog]').evaluate((dialog) => Promise.all(dialog.getAnimations().map((animation) => animation.finished)));
        await page.setViewportSize({ width: 1920, height: 945 });
        assert.equal((await page.locator('.hero').boundingBox()).width, 880);
        assert.equal((await page.locator('[data-terms-card]').boundingBox()).width, 580);
        await page.screenshot({ path: path.join(screenshots, 'terms-home-desktop.png') });
        await page.setViewportSize({ width: 390, height: 844 });
        const modalBounds = await page.locator('[data-home-terms-dialog]').boundingBox();
        assert.ok(modalBounds.x >= 0 && modalBounds.x + modalBounds.width <= 390 && modalBounds.y >= 0 && modalBounds.y + modalBounds.height <= 844);
        await page.screenshot({ path: path.join(screenshots, 'terms-home-mobile.png') });
        assert.deepEqual(await page.evaluate(() => JSON.parse(sessionStorage.getItem('home-leaving-state'))), { agree: false, disabled: true });
        await page.locator('[data-terms-close]').click();
        await page.waitForURL('**/home', { waitUntil: 'domcontentloaded' });
        await assertLocked();
        await page.locator('.footer-links').getByRole('link', { name: 'Terms & Conditions', exact: true }).click();
        await page.waitForURL('**/terms', { waitUntil: 'domcontentloaded' });
        assert.equal(new URL(page.url()).search, '');
        assert.equal(await page.locator('[data-home-terms-dialog]').count(), 0);
        assert.equal(await page.locator('.hero').count(), 0);
        assert.deepEqual(await page.locator('[data-terms-panel]').allTextContents(), popupContent);
        await page.screenshot({ path: path.join(screenshots, 'terms-standalone-mobile.png') });
        await page.locator('[data-terms-close]').click();
        await page.waitForURL('**/home', { waitUntil: 'domcontentloaded' });
        await assertLocked();
        await page.locator('[data-home-agree-option] > span').click();
        await page.waitForURL(/\/terms(?:\?.*)?$/, { waitUntil: 'domcontentloaded' });
        await page.reload({ waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('[data-home-terms-dialog][open]').count(), 1, 'Refresh must preserve the Home popup');
        await page.keyboard.press('Escape');
        await page.waitForURL('**/home', { waitUntil: 'domcontentloaded' });
        await assertLocked();
        await page.locator('input[value="agree"]').click();
        await page.waitForURL(/\/terms(?:\?.*)?$/, { waitUntil: 'domcontentloaded' });
        await page.goBack({ waitUntil: 'domcontentloaded' });
        await assertLocked();
        await page.locator('input[value="agree"]').focus();
        await page.keyboard.press('Space');
        await page.waitForURL(/\/terms(?:\?.*)?$/, { waitUntil: 'domcontentloaded' });
        for (let index = 0; index < 4; index++) await page.locator('[data-terms-next]').click();
        await page.locator('[data-terms-next]').click();
        assert.ok(new URL(page.url()).pathname === '/terms', 'Unchecked agreement must not confirm');
        await page.locator('.terms-agree').click();
        assert.equal(await page.locator('[data-terms-agree]').isChecked(), true);
        await page.locator('[data-terms-next]').click();
        await page.waitForURL('**/home', { waitUntil: 'domcontentloaded' });
        assert.equal(startRequests, 0, 'Terms confirmation must not start the survey');
        await assertConfirmed();
        await page.locator('input[value="decline"]').check();
        assert.equal(await page.locator('[data-start-survey]').isDisabled(), true);
        await page.locator('input[value="agree"]').check();
        assert.equal(await page.locator('[data-start-survey]').isEnabled(), true);
        await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: false })));
        assert.equal(await page.locator('[data-start-survey]').isEnabled(), true, 'Late page loading must not reset a customer selection');
        await page.reload({ waitUntil: 'domcontentloaded' });
        await assertConfirmed();
        await page.locator('.inline-policy-link').click();
        await page.waitForURL(/\/terms(?:\?.*)?$/, { waitUntil: 'domcontentloaded' });
        await page.locator('[data-terms-close]').click();
        await page.waitForURL('**/home', { waitUntil: 'domcontentloaded' });
        await assertLocked();
        assert.equal(await page.locator('.hero-form').getAttribute('data-terms-confirmed'), '0');
        await page.locator('input[value="agree"]').click();
        await page.waitForURL(/\/terms(?:\?.*)?$/, { waitUntil: 'domcontentloaded' });
        await page.locator('[data-terms-tab]').last().click();
        await page.locator('.terms-agree').click();
        await page.getByRole('button', { name: 'Confirm Agreement' }).click();
        await page.waitForURL('**/home', { waitUntil: 'domcontentloaded' });
        await assertConfirmed();
        await page.locator('[data-start-survey]').click();
        await page.waitForURL('**/feedback/date-time', { waitUntil: 'domcontentloaded' });
        assert.equal(startRequests, 1);
        await page.goto((process.env.CFS_BASE_URL || 'http://127.0.0.1:8000') + '/home', { waitUntil: 'domcontentloaded' });
        for (const viewport of [{ width: 1920, height: 945 }, { width: 390, height: 844 }]) {
            await page.setViewportSize(viewport);
            const buttons = await page.locator('.draft-actions .button').evaluateAll((elements) => elements.map((element) => {
                const box = element.getBoundingClientRect();
                return [box.width, box.height];
            }));
            assert.deepEqual(buttons, [[120, 30], [120, 30]], 'Continue and Start Over must match the compact Begin button');
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
            await page.screenshot({ path: path.join(screenshots, `home-draft-${viewport.width}.png`), fullPage: true });
        }
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({ result: 'passed', measurements, infoSizes }, null, 2));
    } finally {
        await browser.close();
    }
}

main().catch((error) => { console.error(error); process.exitCode = 1; });
