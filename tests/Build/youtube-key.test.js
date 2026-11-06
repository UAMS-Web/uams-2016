'use strict';

// Pins how `js/uams.youtube.js` gets the YouTube Data API key: from `uams_youtube.apiKey`, which
// the theme localizes from the UAMS_YOUTUBE_API_KEY constant (setup/class.youtube-shortcode.php).
// The script runs in a vm context with stand-ins for Backbone, underscore and jQuery, so the
// test needs no browser and no network. Run with `npm test`.

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const { ROOT } = require('./helpers');

const SOURCE = fs.readFileSync(path.join(ROOT, 'js/uams.youtube.js'), 'utf8');

// A made-up value, never a real key.
const KEY = 'test-key-123';

// Loads the script. `apiKey` undefined leaves `uams_youtube` undefined on the page.
function load(apiKey) {
  const calls = { fetch: 0, makeView: 0, errors: 0, requests: 0 };

  // Backbone's `extend` reduced to what the script needs: a constructor that runs `initialize`.
  const extend = (base) => (proto) => {
    function Ctor(options) {
      this.options = options;
      // Backbone.View copies these options onto the view.
      if (options && options.collection) this.collection = options.collection;
      this.el = options && options.el;
      this.$el = sandbox.$(this.el);
      if (typeof this.initialize === 'function') this.initialize(options);
    }
    Ctor.prototype = Object.assign(Object.create(base), proto);
    Ctor.extend = extend(Ctor.prototype);
    return Ctor;
  };
  const collectionBase = {
    fetch() {
      calls.fetch += 1;
      calls.requests += 1;
    },
  };

  const sandbox = {
    UAMS: {},
    Backbone: {
      Model: { extend: extend({}) },
      View: { extend: extend({}) },
      Collection: { extend: extend(collectionBase) },
    },
    _: Object.assign((target) => ({ bindAll() {}, map: () => [] }), {
      template: () => () => '',
      extend: Object.assign,
      bindAll() {},
    }),
    // jQuery reduced to a chainable stand-in: `data` reads the element's attributes, every other
    // method returns the same object, and a read of `length` finds nothing.
    $: (el) => {
      const chain = new Proxy(function chained() {}, {
        get(target, name) {
          if (name === 'data') return (attribute) => (el && el.data ? el.data[attribute] : undefined);
          if (name === 'length') return 0;
          if (name === Symbol.toPrimitive) return () => '';
          return () => chain;
        },
        apply: () => chain,
      });
      return chain;
    },
    console: { error: () => { calls.errors += 1; }, log() {}, warn() {} },
    window: {},
  };
  // `UAMS.$body` is cached by UAMS.initialize() on a real page.
  sandbox.UAMS.$body = sandbox.$();
  if (apiKey !== undefined) sandbox.uams_youtube = { apiKey };

  vm.createContext(sandbox);
  vm.runInContext(SOURCE, sandbox);

  // Counts view construction, which is what renders into the placeholder.
  const original = sandbox.UAMS.YouTube.Collection.prototype.make_view;
  sandbox.UAMS.YouTube.Collection.prototype.make_view = function make_view(...args) {
    calls.makeView += 1;
    return original.apply(this, args);
  };

  return { UAMS: sandbox.UAMS, calls };
}

function playlist(extra = {}) {
  return { data: { 'uams-youtube': 'PL123', 'uams-youtube-type': 'playlist', ...extra } };
}

function single() {
  return { data: { 'uams-youtube': 'vid456', 'uams-youtube-type': 'single' } };
}

test.describe('with a key', () => {
  test('a playlist request carries the key', () => {
    const { UAMS, calls } = load(KEY);
    const collection = new UAMS.YouTube.Collection({ el: playlist() });
    assert.equal(
      collection.url,
      `https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=PL123&key=${KEY}&maxResults=20`
    );
    assert.equal(calls.fetch, 1);
  });

  test('a playlist request keeps the max-results attribute', () => {
    const { UAMS } = load(KEY);
    const collection = new UAMS.YouTube.Collection({ el: playlist({ 'max-results': '5' }) });
    assert.ok(collection.url.endsWith(`&key=${KEY}&maxResults=5`), collection.url);
  });

  test('a single video request carries the key', () => {
    const { UAMS, calls } = load(KEY);
    const collection = new UAMS.YouTube.Collection({ el: single() });
    assert.equal(
      collection.url,
      `https://www.googleapis.com/youtube/v3/videos?part=snippet&id=vid456&key=${KEY}`
    );
    assert.equal(calls.fetch, 1);
  });

  test('the key is encoded for the query string', () => {
    const { UAMS } = load('a b&c=d');
    const collection = new UAMS.YouTube.Collection({ el: single() });
    assert.ok(collection.url.endsWith('&key=a%20b%26c%3Dd'), collection.url);
  });
});

test.describe('without a key', () => {
  for (const [label, apiKey] of [
    ['an undefined uams_youtube', undefined],
    ['an empty key', ''],
    ['a key that is not a string', 12345],
  ]) {
    test(`${label} makes no request and renders nothing`, () => {
      const { UAMS, calls } = load(apiKey);
      for (const el of [playlist(), single()]) {
        const collection = new UAMS.YouTube.Collection({ el });
        assert.equal(collection.url, undefined);
      }
      assert.equal(calls.fetch, 0);
      assert.equal(calls.makeView, 0);
      assert.equal(calls.errors, 0);
    });
  }
});

test('the control: the same scan finds a key-shaped string', () => {
  assert.match(`x=AIza${'A'.repeat(35)}`, /AIza[0-9A-Za-z_-]{35}/);
});

test('no shipped theme script holds a Google API key', () => {
  const files = ['js/uams.youtube.js', 'js/site.dev.js', 'js/site.js', 'uams.js', 'js/uams.js', '_uams.js'];
  const holding = files.filter((file) => /AIza[0-9A-Za-z_-]{35}/.test(fs.readFileSync(path.join(ROOT, file), 'utf8')));
  // Names the files only: the match itself is never printed.
  assert.deepEqual(holding, []);
});
