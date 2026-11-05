const assert = require('node:assert/strict');
const path = require('node:path');
const { navigate, reload, resize } = require('./browser-runtime.cjs');
const { auditBounds, auditChartMotion, viewports } = require('./responsive-checks.cjs');

async function auditDashboard(page, context, base, run, screenshots, fixture) {
    await reload(page, { waitUntil: 'load' });
    const apply = async () => {
        await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.locator('.filter-apply').press('Enter')]);
        await page.waitForFunction(() => window.cfsDateFilters);
    };
    const expectRecords = async records => {
        assert.equal(Number(await page.locator('#total-feedback').innerText()), records.length);
        const plotted = await page.locator('[data-chart-bar]').evaluateAll(bars => bars.reduce((sum, bar) => sum + Number(bar.dataset.count), 0));
        assert.equal(plotted, records.length, 'Bars must sum to the filtered database total');
        assert.equal(Number(await page.locator('[data-rated-count]').textContent()), records.length);
        await page.locator('[data-export-menu] summary').click();
        const csv = await (await context.request.get(await page.getByRole('link', { name: 'Export Filtered Results', exact: true }).getAttribute('href'))).text();
        for (const record of fixture.records) assert.equal(csv.includes(record.code), records.includes(record), 'CSV must use the applied filters');
        await page.locator('[data-export-menu] summary').click();
    };
    for (const [preset, dates] of Object.entries(fixture.ranges)) {
        await page.locator('[data-date-preset]').selectOption(preset);
        assert.equal(await page.locator('[data-date-from]').inputValue(), dates.from);
        assert.equal(await page.locator('[data-date-to]').inputValue(), dates.to);
        await apply();
        const expected = fixture.records.filter(record => (!dates.from || record.date >= dates.from) && (!dates.to || record.date <= dates.to));
        await expectRecords(expected);
        await reload(page, { waitUntil: 'load' });
        assert.equal(await page.locator('[data-date-preset]').inputValue(), preset);
        assert.equal(await page.locator('[data-date-from]').inputValue(), dates.from);
    }
    await page.getByRole('link', { name: 'Clear', exact: true }).click();
    await page.waitForURL('**/dashboard', { waitUntil: 'load' });
    const first = fixture.records.find(record => record.status === 'Needs Action');
    await page.locator('[data-date-from]').fill(first.date);
    await page.locator('[data-date-to]').fill(first.date);
    await page.locator('[name="status"]').selectOption('Needs Action');
    assert.equal(await page.locator('[data-date-preset]').inputValue(), 'custom');
    await apply();
    await expectRecords(fixture.records.filter(record => record.date === first.date && record.status === 'Needs Action'));
    for (const period of ['weekly', 'monthly', 'daily']) {
        await page.locator('[name="period"]').selectOption(period);
        await apply();
        assert.equal(await page.locator('[data-dashboard-chart="volume"]').getAttribute('data-period'), period);
    }
    await page.getByRole('link', { name: 'Clear', exact: true }).click();
    await page.waitForURL('**/dashboard', { waitUntil: 'load' });
    await auditChartMotion(page);
    await reload(page, { waitUntil: 'load' });
    for (const selector of ['[data-chart-line]', '[data-chart-bar]', '[data-chart-ring]']) {
        assert.ok(await page.locator(selector).count() > 0, selector);
        const animated = await page.locator(selector).first().evaluate(element => {
            delete element.dataset.motionReady;
            window.dispatchEvent(new Event('cfs:panel-updated'));
            const animation = element.getAnimations().at(-1);
            if (!animation) return false;
            animation.pause(); animation.currentTime = 0;
            const start = getComputedStyle(element).cssText + getComputedStyle(element).transform + getComputedStyle(element).strokeDashoffset;
            animation.currentTime = 450;
            const middle = getComputedStyle(element).cssText + getComputedStyle(element).transform + getComputedStyle(element).strokeDashoffset;
            animation.finish();
            return start !== middle;
        });
        assert.equal(animated, true, `${selector} must visibly animate`);
    }
    for (const icon of ['chart-no-axes-column', 'chart-no-axes-column-increasing', 'trending-up', 'chevron-right', 'triangle-alert', 'refresh-cw']) {
        assert.equal((await context.request.get(base + '/icons/lucide/' + icon + '.svg')).status(), 200, icon);
    }
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: path.join(screenshots, 'dashboard-three-charts-desktop.png'), fullPage: true });
    await page.locator('[data-notification-trigger]').click();
    await page.locator('.notification-popup-row').first().waitFor();
    const iconStyles = await page.locator('.notification-popup-icon').evaluateAll(elements => elements.map(element => {
        const css = getComputedStyle(element);
        return { width: element.getBoundingClientRect().width, background: css.backgroundColor, mask: css.maskImage || css.webkitMaskImage };
    }));
    assert.ok(iconStyles.every(icon => icon.width >= 16 && icon.background !== 'rgba(0, 0, 0, 0)' && icon.mask.includes('/icons/lucide/')), JSON.stringify(iconStyles));
    await page.locator('.notification-popup-icon').evaluateAll(elements => Promise.all(elements.map(element => {
        const url = getComputedStyle(element).getPropertyValue('--icon-url').match(/url\(["']?(.*?)["']?\)/)[1];
        const image = new Image(); image.src = url; return image.decode();
    })));
    const popup = await page.locator('[data-notification-popover]').boundingBox();
    assert.equal(popup.width, 336);
    assert.ok((await page.locator('.notification-popup-copy p').first().textContent()).includes('FB-'));
    const dot = await page.locator('.notification-popup-row.unread .notification-unread-dot').first().boundingBox();
    assert.ok(dot.x > popup.x + popup.width / 2, 'Unread dots belong on the right');
    await page.screenshot({ path: path.join(screenshots, 'notification-reference-desktop.png'), fullPage: true });
    await page.locator('[data-notification-read-all]').press('Enter');
    await page.waitForFunction(() => document.querySelector('[data-notification-badge]').textContent === '0');
    assert.equal(await page.locator('.notification-unread-dot').count(), 0);
    await page.locator('.notification-footer-link').click();
    await page.waitForURL('**/dashboard/inbox', { waitUntil: 'load' });
    assert.ok(await page.locator('.metric-list-row').count() >= 5, 'All notifications must include read records');
    await navigate(page, base + '/dashboard', { waitUntil: 'load' });
    await page.locator('[data-date-preset]').selectOption('last30');
    await page.locator('.dashboard-title h1').click();
    const oldRevision = await page.locator('[data-live-url]').getAttribute('data-live-revision');
    run(['tests/browser/live-feedback.php', 'Submitted']);
    await page.waitForTimeout(4000);
    assert.equal(await page.locator('[data-date-preset]').inputValue(), 'last30', 'Polling must not erase pending filters after blur');
    assert.equal(await page.locator('[data-live-url]').getAttribute('data-live-revision'), oldRevision);
    await page.locator('[data-date-preset]').selectOption('all');
    await page.waitForFunction(revision => document.querySelector('[data-live-url]').dataset.liveRevision !== revision, oldRevision, { timeout: 20000 });
    assert.equal(Number(await page.locator('#total-feedback').textContent()), fixture.records.length + 1);
    assert.equal(await page.locator('.topbar').count(), 1);
    assert.equal(await page.locator('.footer').count(), 1);
    for (const viewport of viewports) {
        await resize(page, viewport);
        await auditBounds(page, `three-charts-${viewport.width}`);
        const copyWidths = await page.locator('.dashboard-notifications .notification-copy').evaluateAll(elements => elements.map(element => element.getBoundingClientRect().width));
        assert.ok(copyWidths.every(width => width >= 100), `Notification text must stay readable at ${viewport.width}: ${copyWidths}`);
        const panels = await page.locator('.dashboard-chart-panel').evaluateAll(elements => elements.map(element => element.getBoundingClientRect().toJSON()));
        if (viewport.width >= 1251) assert.ok(panels.every(panel => panel.y === panels[0].y));
        await page.locator('[data-notification-trigger]').click();
        const bounds = await page.locator('[data-notification-popover]').boundingBox();
        assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= viewport.width + 1 && bounds.y + bounds.height <= viewport.height + 1);
        await page.keyboard.press('Escape');
        if ([390, 813].includes(viewport.width)) await page.screenshot({ path: path.join(screenshots, `dashboard-three-charts-${viewport.width}.png`), fullPage: true });
    }
}

module.exports = { auditDashboard };
