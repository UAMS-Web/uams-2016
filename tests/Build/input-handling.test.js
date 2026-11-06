'use strict';

// Pins how three theme scripts treat page-supplied strings: the hostname check in
// `UAMS.getBaseUrl`, the accordion button label, and the alert close target. Each source file
// runs in a jsdom page with the real jQuery, underscore and Backbone. Run with `npm test`.

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM, VirtualConsole } = require('jsdom');

const { ROOT } = require('./helpers');

const LIBS = [
  require.resolve('jquery/dist/jquery.js'),
  require.resolve('underscore/underscore-umd.js'),
  require.resolve('backbone/backbone.js'),
].map((file) => fs.readFileSync(file, 'utf8'));

function read(relativePath) {
  return fs.readFileSync(path.join(ROOT, relativePath), 'utf8');
}

// Builds a page at `url` with the libraries and then the given theme sources.
function page(url, html, sources) {
  const dom = new JSDOM(`<!doctype html><body>${html}</body>`, {
    url,
    runScripts: 'outside-only',
    virtualConsole: new VirtualConsole(),
  });
  [...LIBS, ...sources.map(read)].forEach((code) => dom.window.eval(code));
  return dom.window;
}

// `UAMS.getBaseUrl` appends the first path segment only on the UAMS hosts.
const SITE_PATH = '/site/page/';

function baseUrl(host) {
  const win = page(`https://${host}${SITE_PATH}`, '', ['js/uams.core.js', 'js/uams.init.js']);
  return win.UAMS.getBaseUrl();
}

test('a UAMS host and its subdomains get the site segment', () => {
  for (const host of ['www.uams.edu', 'uamsonline.com', 'www.uamsonline.com', 'inside.uams.edu', 'insidedev.uamsonlinedev.com']) {
    assert.equal(baseUrl(host), `https://${host}/site/`, host);
  }
});

test('a host that only contains a UAMS name does not', () => {
  for (const host of ['www.uams.edu.example.com', 'notuamsonline.com', 'example.com', 'uamsonline.com.example.org', 'xinside.uams.edu']) {
    assert.equal(baseUrl(host), `https://${host}/`, host);
  }
});

test('an accordion header with markup in its text becomes a plain text label', async () => {
  const html = '<div class="js-accordion" data-accordion-prefix-classes="a">'
    + '<h2 class="js-accordion__header">&lt;img src=x onerror=window.pwned=1&gt;Title &amp; more</h2>'
    + '<div class="js-accordion__panel">body</div></div>';
  const win = page('https://example.com/', html, ['js/uams.accordionmodule.js']);
  // jQuery runs the ready callback on a later tick.
  await new Promise((resolve) => setTimeout(resolve, 50));
  const button = win.document.querySelector('button.js-accordion__header');
  assert.ok(button, 'the button exists');
  assert.equal(button.querySelector('img'), null, 'no element was created from the text');
  assert.equal(button.textContent, '<img src=x onerror=window.pwned=1>Title & more');
});

function alertPage(attrs) {
  const html = '<div id="keep">kept</div><div class="alert" id="the-alert">'
    + `<a ${attrs} data-dismiss="alert">x</a></div>`;
  const win = page('https://example.com/', html, ['js/libraries/bs.alert.js']);
  win.jQuery('#the-alert').alert();
  return win;
}

test('an alert closes the element named by an id target', () => {
  const win = alertPage('data-target="#keep"');
  win.document.querySelector('[data-dismiss]').click();
  assert.equal(win.document.getElementById('keep'), null);
  assert.ok(win.document.getElementById('the-alert'));
});

test('an alert closes itself when the target is not an id', () => {
  for (const attrs of ['data-target="div"', 'data-target="<img src=x>"', 'href="#keep, div"', 'data-target=".alert, #keep"']) {
    const win = alertPage(attrs);
    win.document.querySelector('[data-dismiss]').click();
    assert.ok(win.document.getElementById('keep'), `${attrs}: #keep stays`);
    assert.equal(win.document.getElementById('the-alert'), null, `${attrs}: the alert closed`);
  }
});

test('an alert with an href id strips the URL part and closes that element', () => {
  const win = alertPage('href="/page#keep"');
  win.document.querySelector('[data-dismiss]').click();
  assert.equal(win.document.getElementById('keep'), null);
});
