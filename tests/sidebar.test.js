// Run: node tests/sidebar.test.js
const assert = require('assert');
const vm = require('vm');
const fs = require('fs');
const path = require('path');
const listeners = {};
const toggleHandlers = {};
let open = false;
const attributes = {};
const toggle = {
  addEventListener(type, handler) { toggleHandlers[type] = handler; },
  setAttribute(key, value) { attributes[key] = value; },
  contains(target) { return target === this; },
};
const sidebar = {
  classList: { contains() { return open; }, toggle(_, value) { open = value; } },
  contains(target) { return target === this; },
};
const document = {
  querySelectorAll() { return []; },
  querySelector(selector) { return selector === '.cp-sidebar-toggle' ? toggle : sidebar; },
  addEventListener(type, handler) { listeners[type] = handler; },
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../assets/js/cashpe.js'), 'utf8'), {
  document, location: { pathname: '/Dashboard' }, window: {}, navigator: {},
});
toggleHandlers.click();
assert.strictEqual(open, true);
assert.strictEqual(attributes['aria-expanded'], 'true');
listeners.keydown({ key: 'Escape' });
assert.strictEqual(open, false);
toggleHandlers.click();
listeners.click({ target: {} });
assert.strictEqual(open, false);
console.log('PASS: sidebar opens and closes with Escape and outside click');
