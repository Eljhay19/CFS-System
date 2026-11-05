const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const listeners = { document: {}, window: {} };
const fire = (surface, type, event = {}) => (listeners[surface][type] || []).forEach(handler => handler(event));
const add = surface => (type, handler) => (listeners[surface][type] ||= []).push(handler);
const window = { addEventListener: add('window'), dispatchEvent: event => fire('window', event.type) };
const context = vm.createContext({
    window, document: { addEventListener: add('document') }, AbortController, URL, Event,
    location: new URL('http://127.0.0.1/dashboard'),
});
const source = fs.readFileSync(path.resolve(__dirname, '../../public/js/page-lifecycle.js'), 'utf8');
vm.runInContext(source, context);
vm.runInContext(source, context);
assert.equal(listeners.window.pagehide.length, 1, 'Initialization must not duplicate listeners');
const lifecycle = window.cfsPageLifecycle;
const completed = lifecycle.controller();
lifecycle.release(completed);
const active = lifecycle.controller();
lifecycle.suspend();
assert.equal(active.signal.aborted, true);
assert.equal(completed.signal.aborted, false, 'Completed requests must be released');
assert.equal(lifecycle.controller().signal.aborted, true, 'Suspended pages must not start new polling');
let resumes = 0;
window.addEventListener('cfs:page-resumed', () => resumes++);
fire('window', 'pageshow');
fire('window', 'pageshow');
assert.equal(lifecycle.suspended, false);
assert.equal(resumes, 1, 'Back/forward restoration must resume exactly once');

function click(href, options = {}) {
    const link = {
        href, target: options.target,
        hasAttribute: name => name === 'download' && Boolean(options.download),
        closest: selector => selector === '[data-export-menu]' && options.exportMenu ? {} : null,
    };
    fire('document', 'click', { button: 0, ...options, target: { closest: () => link } });
}
for (const [href, options] of [
    ['http://127.0.0.1/dashboard#ratings', {}],
    ['http://127.0.0.1/dashboard/export', { exportMenu: true }],
    ['http://127.0.0.1/download.csv', { download: true }],
    ['http://127.0.0.1/settings', { target: '_blank' }],
    ['http://127.0.0.1/settings', { ctrlKey: true }],
    ['http://127.0.0.1/settings', { defaultPrevented: true }],
    ['mailto:admin@example.test', {}],
]) {
    click(href, options);
    assert.equal(lifecycle.suspended, false, 'In-page links, downloads, new tabs and handled clicks must keep live updates running');
}
const nextRequest = lifecycle.controller();
fire('document', 'click', { button: 0, target: {} });
assert.equal(lifecycle.suspended, false);
click('http://127.0.0.1/settings');
assert.equal(nextRequest.signal.aborted, true, 'Full-page navigation must cancel old requests');
lifecycle.resume();
fire('document', 'submit', { defaultPrevented: true });
assert.equal(lifecycle.suspended, false, 'AJAX forms and rejected validation must not suspend polling');
fire('document', 'submit', { defaultPrevented: false });
assert.equal(lifecycle.suspended, true);
lifecycle.resume();
const beforeHide = lifecycle.controller();
fire('window', 'pagehide');
assert.equal(beforeHide.signal.aborted, true);
fire('window', 'pageshow');
assert.equal(lifecycle.suspended, false);
console.log('Page lifecycle passed: cancellation, Back/Forward resume, no duplicate handlers, and export/hash/new-tab/AJAX exceptions.');
