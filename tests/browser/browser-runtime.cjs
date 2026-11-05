const { chromium, webkit, devices } = require('playwright');

const engine = process.env.CFS_BROWSER_ENGINE || 'chrome';
const device = process.env.CFS_TOUCH === '1'
    ? { ...devices[engine === 'webkit' ? 'iPhone 13' : 'Pixel 7'], deviceScaleFactor: 1 }
    : {};
// A fixed device screen prevents WebKit from relaying out when these tests change viewport size.
const { viewport, screen, defaultBrowserType, ...deviceOptions } = device;

module.exports = {
    deviceOptions,
    resize: async (page, viewport) => {
        await page.setViewportSize(viewport);
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    },
    navigate: async (page, url, options) => {
        await page.evaluate(() => window.cfsPageLifecycle?.suspend());
        return page.goto(url, options);
    },
    reload: async (page, options) => {
        await page.evaluate(() => window.cfsPageLifecycle?.suspend());
        return page.reload(options);
    },
    launchBrowser: () => engine === 'webkit'
        ? webkit.launch({ headless: true })
        : chromium.launch({ channel: 'chrome', headless: true }),
};
