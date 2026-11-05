const assert = require('node:assert/strict');
const path = require('node:path');
const { navigate, reload, resize } = require('./browser-runtime.cjs');
const { auditBounds, viewports } = require('./responsive-checks.cjs');

async function auditFeedbackDetails(page, context, base, run, screenshots) {
    await navigate(page, base + '/questions', { waitUntil: 'load' });
    for (const viewport of viewports) {
        await resize(page, viewport);
        await page.locator('[data-question-modal-open]').press('Enter');
        const width = (await page.locator('.question-modal').boundingBox()).width;
        await page.keyboard.press('Escape');
        await page.locator('[data-step-add]').press('Enter');
        const step = await page.locator('[data-step-dialog]').boundingBox();
        assert.equal(step.width, width, `Equal dialog widths at ${viewport.width}`);
        await auditBounds(page, `step-equal-width-${viewport.width}`);
        if (viewport.width === 1366) await page.screenshot({ path: path.join(screenshots, 'add-survey-step-equal-width.png') });
        await page.keyboard.press('Escape');
    }
    await resize(page, { width: 1440, height: 900 });
    await page.locator('[data-step-add]').press('Enter');
    await page.locator('#step-title').fill('Browser test additional section');
    await page.locator('#step-description').fill('A draft step created in an isolated test.');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.locator('#step-title').press('Enter')]);
    assert.ok((await page.locator('.additional-steps').innerText()).includes('Browser test additional section'));
    assert.equal(await page.locator('.additional-steps button:has-text("Publish")').isDisabled(), true);
    await navigate(page, base + '/admin-feedback/1', { waitUntil: 'load' });
    assert.equal(await page.locator('.feedback-detail .panel').count(), 0);
    assert.equal(await page.locator('.feedback-visit-details > div').count(), 4);
    const visitLabels = await page.locator('.feedback-visit-details dt').allTextContents();
    assert.ok(visitLabels[0].toLowerCase().includes('date') && visitLabels[1].toLowerCase().includes('time'), 'Visit date/time must be grouped first');
    assert.equal(await page.locator('.feedback-rating-answers > div').count(), 8);
    assert.equal(await page.locator('.feedback-customer dd').count(), 16, 'Every saved answer must be visible');
    const controls = await page.locator('.feedback-control-shell').evaluateAll(elements => elements.map(element => element.getBoundingClientRect().toJSON()));
    assert.ok(controls.every(control => control.width <= 310));
    assert.equal(controls[0].height, 30);
    assert.equal(controls[1].height, 80);
    const button = await page.locator('.feedback-action-save').boundingBox();
    assert.equal(button.width, 120); assert.equal(button.height, 30);
    for (const viewport of viewports) {
        await resize(page, viewport);
        await auditBounds(page, `feedback-details-full-${viewport.width}`);
        const customer = await page.locator('.feedback-customer').boundingBox();
        const action = await page.locator('.feedback-admin-action').boundingBox();
        const history = await page.locator('.feedback-history').boundingBox();
        const save = await page.locator('.feedback-action-save').boundingBox();
        const footer = await page.locator('.feedback-action-footer').boundingBox();
        assert.ok(history.y >= action.y + action.height, 'History belongs below the admin action form');
        assert.equal(history.x, action.x, 'History and action form share the same column');
        assert.ok(Math.abs(save.x + save.width - footer.x - footer.width) <= 1, 'Save button must align right');
        assert.equal(await page.locator('#status').evaluate(element => getComputedStyle(element).lineHeight), '28px');
        if (viewport.width <= 1050) assert.ok(action.y >= customer.y + customer.height);
        else assert.equal(action.y, customer.y);
        if ([390, 1366].includes(viewport.width)) await page.screenshot({ path: path.join(screenshots, `feedback-details-full-${viewport.width}.png`), fullPage: true });
    }
    await resize(page, { width: 1440, height: 900 });
    const feedback = JSON.parse(run(['tests/browser/live-feedback.php', 'Needs Action']));
    await navigate(page, base + '/admin-feedback/' + feedback.id, { waitUntil: 'load' });
    assert.equal(await page.getByRole('heading', { name: 'Admin Action Required' }).count(), 1);
    assert.equal(await page.getByText('No follow-up requested.', { exact: true }).count(), 1);
    assert.ok((await page.locator('.action-history').innerText()).includes('No admin actions recorded yet.'));
    for (const icon of ['history', 'arrow-left', 'triangle-alert', 'clipboard-list', 'mail', 'message-square']) {
        assert.equal((await context.request.get(base + '/icons/lucide/' + icon + '.svg')).status(), 200, icon);
    }
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: path.join(screenshots, 'feedback-details-compact-desktop.png'), fullPage: true });
    await page.locator('#status').selectOption('Resolved');
    assert.equal(await page.locator('#action_taken').getAttribute('required'), '');
    await page.locator('.feedback-action-save').click();
    assert.equal(await page.locator('#action_taken').evaluate(element => element.validity.valueMissing), true);
    assert.equal(await page.locator('.feedback-action-save').isEnabled(), true);
    await page.locator('#status').selectOption('In Progress');
    assert.equal(await page.locator('#action_taken').getAttribute('required'), null);
    await page.locator('#action_taken').fill('Reviewing the customer concern.');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.locator('#action_taken').press('Control+Enter')]);
    assert.ok((await page.locator('.action-history').innerText()).includes('Needs Action to In Progress'));
    assert.equal(await page.locator('#action_taken').inputValue(), 'Reviewing the customer concern.');
    await page.locator('#status').selectOption('Resolved');
    await page.locator('#action_taken').fill('Concern resolved and checked.');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.locator('#action_taken').press('Control+Enter')]);
    assert.equal(await page.locator('.action-history li').count(), 2);
    assert.ok((await page.locator('.action-history').innerText()).includes('In Progress to Resolved'));
    await reload(page, { waitUntil: 'load' });
    assert.equal(await page.locator('#status').inputValue(), 'Resolved');
    assert.equal(await page.locator('#action_taken').inputValue(), 'Concern resolved and checked.');
    const csrf = await page.locator('[data-admin-action-form] [name="_token"]').inputValue();
    const actionUrl = await page.locator('[data-admin-action-form]').getAttribute('action');
    const duplicate = await context.request.post(actionUrl, { form: { _token: csrf, status: 'Resolved', action_taken: 'Concern resolved and checked.' } });
    assert.equal(duplicate.status(), 200);
    await reload(page, { waitUntil: 'load' });
    assert.equal(await page.locator('.action-history li').count(), 2, 'Identical retries must not duplicate audit entries');
    const stats = await (await context.request.get(base + '/dashboard/stats')).json();
    assert.equal(stats.resolved, 1);
    const invalid = await context.request.post(actionUrl, { form: { _token: csrf, status: 'Resolved', action_taken: '' }, headers: { Referer: base + '/admin-feedback/' + feedback.id } });
    assert.equal(invalid.status(), 200);
    await navigate(page, base + '/admin-feedback/' + feedback.id, { waitUntil: 'load' });
    // The direct request consumed the validation flash; persisted values must be unchanged.
    assert.equal(await page.locator('#action_taken').inputValue(), 'Concern resolved and checked.');
    const emailRetry = page.getByRole('button', { name: 'Retry Email', exact: true });
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), emailRetry.press('Enter')]);
    assert.ok((await page.locator('.feedback-detail-meta').innerText()).includes('Logged (development)'));
    for (const viewport of viewports) {
        await resize(page, viewport);
        await auditBounds(page, `feedback-details-history-${viewport.width}`);
        if (viewport.width === 390) await page.screenshot({ path: path.join(screenshots, 'feedback-details-compact-mobile.png'), fullPage: true });
    }
    await page.getByRole('link', { name: 'Back to Feedback', exact: true }).click();
    await page.waitForURL('**/admin-feedback', { waitUntil: 'load' });
}

module.exports = { auditFeedbackDetails };
