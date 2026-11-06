'use strict';

// Checks that the minified theme scripts (`js/site.js`, `uams.js`) behave like the unminified
// source they are built from (`js/site.dev.js`). Each script runs in its own jsdom page built
// from one fixture, through document ready, and what it leaves behind is compared: the globals
// it defines, the event handlers it binds, and the changes it makes to the DOM. The comparison
// is between source and minified output, so a later build-tool update is checked the same way.
// Run with `npm test`.

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM, VirtualConsole } = require('jsdom');

const { ROOT, loadGruntfile, sha256 } = require('./helpers');

const config = loadGruntfile();

// The scripts under test: the unminified concatenation first, as the reference.
const VARIANTS = [
  { name: 'js/site.dev.js', file: 'js/site.dev.js' },
  { name: 'js/site.js', file: 'js/site.js' },
  { name: 'uams.js', file: 'uams.js' },
];

// Third-party globals the theme expects the page to provide: WordPress core supplies
// underscore and Backbone, and the theme enqueues jQuery from a CDN (setup/class.uams-scripts.php).
const PROVIDED = [
  require.resolve('jquery/dist/jquery.js'),
  require.resolve('underscore/underscore-umd.js'),
  require.resolve('backbone/backbone.js'),
];

// One entry per module in `concat.dist.theme`. `globals` are the properties the module defines
// on `UAMS`; `root` is the fixture markup the module acts on; `acts` counts what it produced
// there, so a module that finds nothing to act on fails instead of passing with no behavior.
const MODULES = {
  'js/uams.core.js': {
    globals: ['VERSION', 'KEYCODES', 'getBaseUrl', 'wpinstance', 'sources'],
    root: null,
    acts: (w) => (w.UAMS.sources.search ? 1 : 0),
  },
  'js/uams.init.js': {
    globals: ['elements', 'initialize', '$body', '$window', 'baseUrl'],
    root: 'table, pre',
    acts: (w) => w.document.querySelectorAll('table.table-striped, pre.prettyprint').length,
  },
  'js/uams.searchtoggle.js': {
    globals: ['SearchToggle'],
    root: 'button.uams-search',
    acts: (w) => w.document.querySelectorAll('button.uams-search').length,
  },
  'js/uams.search.js': {
    globals: ['Search', 'search'],
    root: '#uamssearcharea',
    acts: (w) => w.UAMS.search.length,
  },
  'js/uams.quicklinks.js': {
    globals: ['QuickLinks', 'quicklinks'],
    root: '.uams-quicklinks, #quicklinks',
    acts: (w) => w.UAMS.quicklinks.length + w.document.querySelectorAll('#quicklinks').length,
  },
  'js/uams.slideshow.js': {
    globals: ['Slideshow', 'slideshows'],
    root: '.uams-slideshow',
    acts: (w) => w.UAMS.slideshows.length,
  },
  'js/uams.youtube.js': {
    globals: ['YouTube', 'youtube'],
    root: '.uams-youtube',
    acts: (w) => w.UAMS.youtube.length,
  },
  'js/uams.vimeo.js': {
    globals: ['Vimeo', 'vimeo'],
    root: '.uams-vimeo',
    acts: (w) => w.UAMS.vimeo.length,
  },
  'js/uams.radio-checkbox.js': {
    globals: ['Radio'],
    root: '#radio-group',
    acts: (w) => w.document.querySelectorAll('#radio-group .icons').length,
  },
  'js/uams.checkbox.js': {
    globals: ['Checkbox', 'checkbox'],
    root: '#checkbox-group',
    acts: (w) => w.UAMS.checkbox.length,
  },
  'js/uams.dropdowns.js': {
    globals: ['Dropdowns', 'dropdowns'],
    root: '#reddiedrops',
    acts: (w) => w.UAMS.dropdowns.length,
  },
  'js/uams.mobile-menu.js': {
    globals: ['MobileMenu', 'mobilemenu'],
    root: '#mobile-reddiedrops',
    acts: (w) => w.UAMS.mobilemenu.length,
  },
  'js/uams.mobile-sidebar-menu.js': {
    globals: ['ToggleSidebarMenu', 'togglemobile'],
    root: '#mobile-sidebar',
    acts: (w) => w.UAMS.togglemobile.length,
  },
  'js/uams.accordion.js': {
    globals: ['Accordion', 'accordion'],
    root: '.uams-accordion',
    acts: (w) => w.UAMS.accordion.length,
  },
  'js/uams.select.js': {
    globals: ['Select', 'select'],
    root: '.uams-select, .uams-select-mask',
    acts: (w) => w.UAMS.select.length,
  },
  'js/uams.images.js': {
    globals: ['Image', 'images'],
    root: 'a > img',
    acts: (w) => w.UAMS.images.length,
  },
  'js/uams.player.js': {
    globals: ['Player'],
    root: '.uams-player',
    acts: (w) => w.UAMS.players.length,
  },
  'js/uams.social.js': {
    globals: ['Social', 'social'],
    root: '.uams-social',
    acts: (w) => w.UAMS.social.length + w.document.querySelectorAll('.uams-social li').length,
  },
};

// The markup each module in `concat.dist.theme` acts on.
const FIXTURE = `<!DOCTYPE html>
<html lang="en"><head><title>Fixture</title></head>
<body>
  <div id="uams-container">
    <div id="uamssearcharea" class="uams-search-area">
      <form action="/" method="get">
        <label class="radio"><input class="radiobtn" type="radio" name="feature" value="site" checked> Site</label>
        <label class="radio"><input class="radiobtn" type="radio" name="feature" value="people"> People</label>
        <select name="scope"><option value="a">A</option><option value="b">B</option></select>
        <input class="search" type="text" name="s">
        <button class="search" type="button">Search</button>
      </form>
    </div>
    <button class="uams-search" type="button" aria-label="open search area" aria-expanded="false"></button>
    <div class="uams-quicklinks"></div>
    <div id="main-content" tabindex="-1">
      <table><tr><td>cell</td></tr></table>
      <pre>code</pre>
      <div class="uams-accordion">
        <h3>First</h3><div><p>One</p></div>
        <h3>Second</h3><div><p>Two</p></div>
      </div>
      <div class="uams-select">
        <select name="choice"><option value="x">Ex</option><option value="y" selected>Why</option><option value="z">Zed</option></select>
      </div>
      <div class="uams-slideshow photo-slider">
        <div class="slide"><img src="a.jpg" alt="A"></div>
        <div class="slide"><img src="b.jpg" alt="B"></div>
        <div class="slide"><img src="c.jpg" alt="C"></div>
        <ul class="slider-dots"><li></li><li></li><li></li></ul>
      </div>
      <div id="yt-single" class="uams-youtube" data-uams-youtube="abc123" data-uams-youtube-type="single"></div>
      <div class="uams-vimeo" data-video="76979871" data-width="400" data-height="300"></div>
      <div class="uams-player"></div>
      <div class="uams-social" data-facebook="" data-twitter="" data-reddit=""></div>
      <div id="checkbox-group">
        <label><input type="checkbox" name="boxes" value="1" checked> One</label>
        <label><input type="checkbox" name="boxes" value="2" disabled> Two</label>
      </div>
      <div id="radio-group">
        <label><input type="radio" name="pick" value="1" checked> One</label>
        <label><input type="radio" name="pick" value="2"> Two</label>
      </div>
      <p class="wp-caption"><a href="full.jpg" rel="lightbox"><img src="thumb.jpg" alt="Thumb"></a></p>
    </div>
  </div>
  <div id="reddiedrops">
    <ul class="reddiedrops-menu">
      <li class="reddiedrops-item"><a href="/one">One</a>
        <ul class="sub-menu"><li><a href="/one/a">A</a></li><li><a href="/one/b">B</a></li></ul>
      </li>
      <li class="reddiedrops-item"><a href="/two">Two</a></li>
    </ul>
  </div>
  <div id="mobile-reddiedrops">
    <button type="button">Menu</button>
    <ul class="uams-mobile-menu">
      <li class="menu-item-has-children"><a href="/m">Parent</a>
        <ul class="sub-menu"><li><a href="/m/c">Child</a></li></ul>
      </li>
    </ul>
  </div>
  <div id="mobile-sidebar">
    <button type="button">Section</button>
    <div id="mobile-sidebar-menu"><div id="mobile-sidebar-links"><a href="/s">Link</a></div></div>
  </div>
</body></html>`;

// Reduces any value to plain data so two runs can be compared with `deepEqual`. Functions
// compare by arity (the minifier renames parameters but keeps their count), DOM nodes by
// their tag, id and class, and cycles are cut.
function plain(value, seen = new Set(), depth = 0) {
  if (value === null || value === undefined) return value === null ? null : '[undefined]';
  const type = typeof value;
  if (type === 'function') return `[function/${value.length}]`;
  if (type === 'number' && Number.isNaN(value)) return '[NaN]';
  if (type !== 'object') return value;
  if (seen.has(value)) return '[cycle]';
  if (depth > 6) return '[deep]';
  if (typeof value.nodeType === 'number' && typeof value.nodeName === 'string') {
    return `[node ${value.nodeName}#${value.id || ''}.${value.className || ''}]`;
  }
  if (value === value.window) return '[window]';
  seen.add(value);
  let out;
  if (Array.isArray(value)) {
    out = value.map((item) => plain(item, seen, depth + 1));
  } else if (value.jquery) {
    out = { jquery: value.length };
  } else {
    out = {};
    for (const key of Object.keys(value).sort()) {
      // `cid` and `_listenId` embed a per-page counter that is the same for every run.
      out[key] = plain(value[key], seen, depth + 1);
    }
    const proto = Object.getPrototypeOf(value);
    if (proto && proto !== Object.prototype && proto !== Array.prototype) {
      out['[prototype]'] = Object.keys(proto).sort().map((key) => `${key}:${plain(proto[key], seen, depth + 1)}`);
    }
  }
  seen.delete(value);
  return out;
}

// The handlers jQuery holds for an element: type, namespace, selector and arity.
function handlers(window, element) {
  const events = window.jQuery._data(element, 'events') || {};
  const out = {};
  for (const type of Object.keys(events).sort()) {
    out[type] = events[type].map((h) => `${String(h.namespace || "").replace(/delegateEvents\d+/, 'delegateEvents')}|${h.selector || ''}|${h.handler.length}`).sort();
  }
  return out;
}

function describeNode(node) {
  return `${node.nodeName.toLowerCase()}#${node.id || ''}.${String(node.className || '').trim().replace(/\s+/g, ' ')}`;
}

// What the scripts left in the region a module acts on: its markup and the handlers bound on
// the elements in it (and on their ancestors, where Backbone delegates).
function region(window, selector) {
  if (!selector) return { markup: null, handlers: null };
  const nodes = [...window.document.querySelectorAll(selector)];
  const bound = {};
  const reach = new Set();
  for (const node of nodes) {
    for (let n = node; n && n.nodeType === 1; n = n.parentNode) reach.add(n);
    node.querySelectorAll('*').forEach((child) => reach.add(child));
  }
  for (const node of reach) {
    const found = handlers(window, node);
    if (Object.keys(found).length) bound[describeNode(node)] = found;
  }
  return { markup: nodes.map((node) => node.outerHTML), handlers: bound };
}

// Clicks every element in the page, in document order, and records the page after each click.
// Backbone binds most handlers by delegation on an ancestor, so the click has to land on the
// descendant a handler selects; clicking everything reaches them all. A handler that does
// something different shows up as a different page after the same click.
function clicks(window) {
  const out = [];
  for (const node of [...window.document.body.querySelectorAll('*')]) {
    let threw = false;
    try {
      node.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));
    } catch (error) {
      threw = true;
    }
    out.push({ node: describeNode(node), threw, page: sha256(window.document.body.innerHTML) });
  }
  return out;
}

// Loads one script into a fresh page, runs document ready, and returns what it left behind.
async function run(variant) {
  const dom = new JSDOM(FIXTURE, {
    runScripts: 'outside-only',
    pretendToBeVisual: true,
    url: 'https://www.uams.edu/fixture/',
    // Navigation and other unimplemented browser features are reported to the page's console,
    // which is dropped: the clicks below reach links and form buttons.
    virtualConsole: new VirtualConsole(),
  });
  const { window } = dom;
  await new Promise((resolve) => (window.document.readyState === 'complete' ? resolve() : window.addEventListener('load', resolve)));
  for (const file of PROVIDED) window.eval(fs.readFileSync(file, 'utf8'));
  const before = new Set(Object.getOwnPropertyNames(window));
  // The admin-ajax and YouTube requests fail the same way for every script, with no network.
  window.Backbone.ajax = (options) => {
    if (options && options.error) options.error({ status: 0 }, 'error', 'fixture');
    return {};
  };
  window.eval(fs.readFileSync(path.join(ROOT, variant.file), 'utf8'));
  await new Promise((resolve) => window.setTimeout(resolve, 50));

  // Classes with no instance made at ready are made here the same way for every script.
  window.UAMS.players = [...window.document.querySelectorAll('.uams-player')].map((el) => new window.UAMS.Player({ el }));
  window.UAMS.radios = [...window.document.querySelectorAll('#radio-group input')].map((el) => new window.UAMS.Radio({ el }));
  window.UAMS.radio = window.UAMS.radios;

  // A library names a global after a random number on every page load (`jQuery351060...`).
  const globals = Object.getOwnPropertyNames(window)
    .filter((key) => !before.has(key))
    .map((key) => key.replace(/^(jQuery\d{3})\d+$/, '$1<random>'))
    .sort();
  const result = {
    globals,
    uams: {},
    modules: {},
  };
  for (const key of Object.keys(window.UAMS).sort()) result.uams[key] = plain(window.UAMS[key]);
  for (const [file, module] of Object.entries(MODULES)) {
    result.modules[file] = {
      defined: module.globals.map((name) => `${name}:${typeof window.UAMS[name]}`),
      region: region(window, module.root),
      acts: module.acts(window),
    };
  }
  result.document = window.document.documentElement.outerHTML;
  result.clicks = clicks(window);
  window.close();
  // Arrays built by page code belong to the page's realm; a JSON round trip makes them plain.
  return JSON.parse(JSON.stringify(result));
}

test.describe('minified theme scripts behave like their source', () => {
  const results = {};

  test.before(async () => {
    for (const variant of VARIANTS) results[variant.name] = await run(variant);
  });

  test('the fixture names every module in concat.dist.theme', () => {
    const theme = config.get('concat.dist.theme').flat(Infinity);
    assert.deepEqual(Object.keys(MODULES).sort(), [...theme].sort());
  });

  for (const file of Object.keys(MODULES)) {
    test.describe(file, () => {
      test('acts on the fixture in the unminified source', () => {
        assert.ok(results['js/site.dev.js'].modules[file].acts > 0, `${file} found nothing to act on in the fixture`);
      });

      test('defines its globals', () => {
        for (const variant of VARIANTS) {
          for (const entry of results[variant.name].modules[file].defined) {
            assert.ok(!entry.endsWith(':undefined'), `${variant.name} does not define UAMS.${entry}`);
          }
        }
      });

      for (const variant of VARIANTS.slice(1)) {
        test(`${variant.name} defines, binds and changes what js/site.dev.js does`, () => {
          assert.deepEqual(results[variant.name].modules[file], results['js/site.dev.js'].modules[file]);
        });
      }
    });
  }

  for (const variant of VARIANTS.slice(1)) {
    test(`${variant.name} leaves the same globals as js/site.dev.js`, () => {
      assert.deepEqual(results[variant.name].globals, results['js/site.dev.js'].globals);
      assert.deepEqual(results[variant.name].uams, results['js/site.dev.js'].uams);
    });

    test(`${variant.name} leaves the same page as js/site.dev.js after ready and after clicks`, () => {
      assert.equal(results[variant.name].document, results['js/site.dev.js'].document);
      assert.deepEqual(results[variant.name].clicks, results['js/site.dev.js'].clicks);
    });
  }

  test('the clicks changed the page', () => {
    const pages = new Set(results['js/site.dev.js'].clicks.map((click) => click.page));
    assert.ok(pages.size > 1, 'no click changed the fixture page');
  });
});
