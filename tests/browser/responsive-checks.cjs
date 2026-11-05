const assert = require('node:assert/strict');
const path = require('node:path');
const { navigate, reload, resize } = require('./browser-runtime.cjs');

const viewports = [
    { width: 320, height: 740 }, { width: 390, height: 844 },
    { width: 768, height: 1024 }, { width: 813, height: 772 },
    { width: 1024, height: 768 }, { width: 1366, height: 768 },
    { width: 667, height: 320 },
];

async function auditBounds(page, label) {
    const problems = await page.evaluate(() => {
        const outside = [];
        for (const element of document.querySelectorAll('input:not([type="hidden"]), select, textarea, button, a, h1, h2, canvas')) {
            if (!element.checkVisibility({ checkVisibilityCSS: true }) || element.closest('details:not([open])') && !element.closest('summary')) continue;
            const box = element.getBoundingClientRect();
            if (!box.width || !box.height || getComputedStyle(element).visibility === 'hidden') continue;
            let clipped = false;
            for (let parent = element.parentElement; parent && parent !== document.body; parent = parent.parentElement) {
                const css = getComputedStyle(parent);
                if (['auto', 'scroll'].includes(css.overflowX) && parent.scrollWidth > parent.clientWidth + 1) { clipped = true; break; }
            }
            if (clipped) continue;
            if (box.left < -1 || box.right > innerWidth + 1) outside.push(`${element.tagName}.${element.className}#${element.id}: ${Math.round(box.left)}..${Math.round(box.right)}`);
        }
        if (document.documentElement.scrollWidth > innerWidth + 1) outside.push('Document horizontal overflow');
        return outside;
    });
    if (problems.length) await page.screenshot({ path: path.resolve(__dirname, '../../storage/app/test-results', 'overflow-' + label.replace(/[^a-z0-9-]/gi, '-') + '.png'), fullPage: true });
    assert.deepEqual(problems, [], `${label}: controls must stay within the screen`);
}

async function auditChartMotion(page) {
    const target = page.locator('[data-chart-ring], .rating-fill, .feedback-rating-track > span').first();
    const values = await target.evaluate(element => {
        delete element.dataset.motionReady;
        window.dispatchEvent(new Event('cfs:panel-updated'));
        const animation = element.getAnimations().at(-1);
        if (!animation) return null;
        animation.pause();
        animation.currentTime = 0;
        const start = getComputedStyle(element).transform;
        animation.currentTime = 400;
        const middle = getComputedStyle(element).transform;
        animation.finish();
        return { start, middle, width: element.style.width };
    });
    assert.ok(values && values.start !== values.middle, 'Charts must animate without changing stored values');
    await reload(page, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => document.querySelector('[data-motion-ready]'));
    assert.ok(await page.locator('[data-motion-ready]').count() > 0, 'Refresh must initialize chart motion');
    if (await page.locator('.feedback-donut').count()) {
        assert.equal(await page.locator('.feedback-donut-motion-ring').count(), 1, 'Pie motion must not duplicate the ring');
        assert.ok(await page.locator('.feedback-donut-motion-ring').evaluate(element => getComputedStyle(element).backgroundImage.includes('conic-gradient')));
    }
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await reload(page, { waitUntil: 'domcontentloaded' });
    assert.equal(await page.locator('[data-live-url]').evaluate(element => element.getAnimations({ subtree: true }).length), 0, 'Reduced motion preference must be respected');
    await page.emulateMedia({ reducedMotion: 'no-preference' });
}

async function auditResponsive(page, base, screenshots, admin = true) {
    const routes = admin
        ? ['/dashboard', '/dashboard/total', '/dashboard/average', '/dashboard/today', '/dashboard/unread', '/dashboard/inbox', '/settings', '/questions', '/questions?archived=1', '/business-profile', '/business-profile?edit=1', '/admin-feedback', '/admin-feedback/1', '/admin-messages']
        : ['/home', '/terms', '/privacy-policy', '/faqs', '/contact', '/admin-login', '/forgot-password', '/reset-password/layout-check'];
    for (const route of routes) {
        if (process.env.CFS_RESPONSIVE_FROM && !route.includes(process.env.CFS_RESPONSIVE_FROM)) continue;
        console.log(`Checking responsive ${route}`);
        const response = await navigate(page, base + route, { waitUntil: 'load' });
        assert.equal(response.status(), 200, route);
        for (const viewport of viewports) {
            await resize(page, viewport);
            await page.evaluate(() => document.fonts.ready);
            const label = `${admin ? 'admin' : 'public'}-${route.slice(1).replace(/[^a-z0-9-]/g, '-')}-${viewport.width}`;
            if (process.env.CFS_BROWSER_ENGINE !== 'webkit' || [390, 1366].includes(viewport.width)) {
                await page.screenshot({ path: path.join(screenshots, `responsive-${label}.png`), fullPage: process.env.CFS_BROWSER_ENGINE !== 'webkit' });
            }
            await auditBounds(page, label);
            if (route === '/dashboard') {
                await page.locator('[data-export-menu] summary').click();
                await auditBounds(page, label + '-export-menu');
                await page.locator('[data-export-menu] summary').click();
            }
            const header = await page.locator('.topbar').boundingBox();
            assert.ok(header.height <= 68, `${label}: header must remain one compact row`);
            if (await page.locator('[data-recovery-dialog][open]').count()) {
                const modal = await page.locator('[data-recovery-dialog]').boundingBox();
                assert.ok(modal.x >= 0 && modal.x + modal.width <= viewport.width + 1);
                assert.ok(modal.y >= 0 && modal.y + modal.height <= viewport.height + 1);
                continue; // Recovery is modal; the background navigation is intentionally inert.
            }
            if (viewport.width <= 1050) {
                assert.equal(await page.locator('#site-navigation').isVisible(), false);
                await page.locator('[data-site-menu-toggle]').press('Enter', { noWaitAfter: true });
                assert.equal(await page.locator('#site-navigation').isVisible(), true);
                await auditBounds(page, label + '-navigation');
                await page.keyboard.press('Escape');
                assert.equal(await page.locator('#site-navigation').isVisible(), false);
            }
            if (admin) {
                if (viewport.width <= 1050) {
                    assert.equal(await page.locator('#admin-navigation').isVisible(), false);
                    assert.ok((await page.locator('.admin-sidebar').boundingBox()).height <= 46);
                    await page.locator('[data-admin-menu-toggle]').press('Enter');
                    assert.equal(await page.locator('#admin-navigation').isVisible(), true);
                    await auditBounds(page, label + '-admin-menu');
                    await page.keyboard.press('Escape');
                }
                await page.locator('[data-notification-trigger]').click();
                const popup = await page.locator('[data-notification-popover]').boundingBox();
                assert.ok(popup.x >= 0 && popup.x + popup.width <= viewport.width + 1);
                assert.ok(popup.y >= 0 && popup.y + popup.height <= viewport.height + 1);
                await page.screenshot({ path: path.join(screenshots, `responsive-notifications-${viewport.width}.png`) });
                await page.getByRole('button', { name: 'Close notifications', exact: true }).press('Enter');
                assert.equal(await page.locator('[data-notification-popover]').isVisible(), false);
                if (route.startsWith('/questions')) {
                    const addStep = await page.locator('[data-step-add]').boundingBox();
                    const addQuestion = await page.locator('[data-question-modal-open]').boundingBox();
                    assert.equal(addStep.y, addQuestion.y, 'Add buttons must align');
                    assert.equal(addStep.width, addQuestion.width);
                    assert.equal(addStep.height, addQuestion.height);
                    assert.ok((await page.locator('.admin-main').boundingBox()).height >= 500, 'Short admin pages must retain workspace height');
                    await page.locator('[data-question-modal-open]').press('Enter');
                    await auditBounds(page, label + '-question-dialog');
                    const questionWidth = (await page.locator('.question-modal').boundingBox()).width;
                    await page.keyboard.press('Escape');
                    await page.locator('[data-step-add]').press('Enter');
                    await auditBounds(page, label + '-step-dialog');
                    assert.equal((await page.locator('[data-step-dialog]').boundingBox()).width, questionWidth, 'Add Step and Add Question dialogs must have equal widths');
                    await page.keyboard.press('Escape');
                }
                if (route === '/settings') {
                    const spacing = await page.locator('.settings-grid').evaluate(el => ({ row: parseFloat(getComputedStyle(el).rowGap), column: parseFloat(getComputedStyle(el).columnGap) }));
                    assert.ok(spacing.row <= 16 && spacing.column <= 24, 'Settings groups must remain compact');
                    const fonts = await page.locator('.settings-section button, .settings-section input').evaluateAll(elements => [...new Set(elements.map(el => getComputedStyle(el).fontFamily))]);
                    assert.equal(fonts.length, 1, 'Form controls must share the system font across browsers');
                    const sections = await page.locator('.settings-section').evaluateAll(elements => elements.map(element => getComputedStyle(element).borderLeftWidth));
                    assert.ok(sections.every(border => border === '0px'), 'Settings must not contain inner cards');
                    const sizes = await page.locator('.settings-section .compact-button').evaluateAll(elements => elements.map(element => [element.offsetWidth, element.offsetHeight, element.scrollWidth <= element.clientWidth]));
                    assert.ok(sizes.every(([width, height, fits]) => width === 104 && height === 28 && fits), JSON.stringify(sizes));
                }
            }
        }
    }
}

module.exports = { auditBounds, auditResponsive, auditChartMotion, viewports };
