'use strict';

// Pins the behavior of the Grunt build (`gruntfile.js`) and of the compiled files it writes.
// Builds run in a scratch copy, never in the checkout. Run with `npm test`, before `grunt`
// rewrites the tracked compiled files.

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const { ROOT, readCommitted, sha256, scratch, dispose, grunt, read, loadGruntfile } = require('./helpers');

const config = loadGruntfile();

// Resolves the `src` array of concat.dist (nested arrays flattened).
function concatSources() {
  return config.get("concat.dist.src").flat(Infinity);
}

// Less files the build reads that no css watch glob covers (the globs stop at one directory
// level, so `less/bootstrap/mixins/` and `less/flat/modules/` are not watched). Recorded, not
// hidden: adding a glob to the watch target means deleting the entries it covers.
const UNWATCHED_LESS = [
  'less/bootstrap/mixins/alerts.less',
  'less/bootstrap/mixins/background-variant.less',
  'less/bootstrap/mixins/border-radius.less',
  'less/bootstrap/mixins/buttons.less',
  'less/bootstrap/mixins/center-block.less',
  'less/bootstrap/mixins/clearfix.less',
  'less/bootstrap/mixins/forms.less',
  'less/bootstrap/mixins/gradients.less',
  'less/bootstrap/mixins/grid-framework.less',
  'less/bootstrap/mixins/grid.less',
  'less/bootstrap/mixins/hide-text.less',
  'less/bootstrap/mixins/image.less',
  'less/bootstrap/mixins/labels.less',
  'less/bootstrap/mixins/list-group.less',
  'less/bootstrap/mixins/nav-divider.less',
  'less/bootstrap/mixins/nav-vertical-align.less',
  'less/bootstrap/mixins/opacity.less',
  'less/bootstrap/mixins/pagination.less',
  'less/bootstrap/mixins/panels.less',
  'less/bootstrap/mixins/progress-bar.less',
  'less/bootstrap/mixins/reset-filter.less',
  'less/bootstrap/mixins/resize.less',
  'less/bootstrap/mixins/responsive-visibility.less',
  'less/bootstrap/mixins/size.less',
  'less/bootstrap/mixins/tab-focus.less',
  'less/bootstrap/mixins/table-row.less',
  'less/bootstrap/mixins/text-emphasis.less',
  'less/bootstrap/mixins/text-overflow.less',
  'less/bootstrap/mixins/vendor-prefixes.less',
  'less/flat/modules/checkbox-and-radio.less',
  'less/flat/modules/scaffolding.less',
  'less/flat/modules/type.less',
];

test.describe('less', () => {
  let dir;
  let production;
  let development;

  test.before(() => {
    dir = scratch();
    production = grunt(dir, ['less:production']);
    development = grunt(dir, ['less:development']);
  });
  test.after(() => dispose(dir));

  test('production and development targets succeed', () => {
    assert.equal(production.status, 0, production.stdout + production.stderr);
    assert.equal(development.status, 0, development.stdout + development.stderr);
  });

  test('production writes the same stylesheet to style.css and uams.css', () => {
    assert.equal(read(dir, 'style.css'), read(dir, 'uams.css'));
  });

  test('development writes the same stylesheet as production (the cleancss option does not minify)', () => {
    // Pins today's behavior: `cleancss: true` has no effect with the installed
    // grunt-contrib-less, so the three outputs are identical.
    assert.equal(read(dir, 'style.dev.css'), read(dir, 'style.css'));
  });

  test('the stylesheet carries the theme header from less/style.less', () => {
    const css = read(dir, 'style.css');
    assert.match(css, /^\/\*!\nTheme Name: UAMS 2016\n/);
    assert.match(css, /\nVersion: 0\.9\nRequires PHP: 8\.2\n \*\/\n/);
  });

  // The committed stylesheets differ from a fresh build today. Each difference is written out
  // below as an exact edit to the fresh build, so any other change to a committed stylesheet
  // or to the build output fails, and fixing a difference means deleting its entry.
  test('style.dev.css and uams.css lack only the Requires PHP header line', () => {
    const built = read(dir, 'style.css');
    assert.equal(built.split('Requires PHP: 8.2\n').length, 2, 'one header line expected');
    const expected = built.replace('Requires PHP: 8.2\n', '');
    assert.equal(readCommitted('style.dev.css'), expected);
    assert.equal(readCommitted('uams.css'), expected);
  });

  test('style.css differs from a fresh build only by three recorded hand edits', () => {
    const built = read(dir, 'style.css');
    const alertRule = '\n    background: url(assets/images/alert.png) no-repeat 0 -10px transparent;\n';
    assert.equal(built.split('Version: 0.9\n').length, 2, 'one version line expected');
    assert.equal(built.split(alertRule).length, 2, 'one alert background rule expected');

    const edited = built
      .replace('Version: 0.9\n', 'Version: 1.3.0.1\n')
      .replace(alertRule, '\n    /* background: url(assets/images/alert.png) no-repeat 0 -10px transparent; */\n');

    const committed = readCommitted('style.css');
    assert.ok(committed.startsWith(edited), 'the committed file starts with the edited fresh build');

    // The appended block of 48 lines the less sources do not produce.
    const appended = committed.slice(edited.length);
    assert.ok(appended.startsWith('/* Added for uamsalert */\n'));
    assert.equal(appended.split('\n').length, 49);
    assert.equal(sha256(appended), '38a794e0b725ce366a1b15f49ece92a5dc022babc33b67c79ac93e5a432ddc79');
  });

  test('a changed less source changes the output', () => {
    const changed = scratch();
    try {
      fs.appendFileSync(path.join(changed, 'less', 'uams.accessibility.less'), '\n.build-test-marker { color: red; }\n');
      const result = grunt(changed, ['less:development']);
      assert.equal(result.status, 0, result.stdout + result.stderr);
      assert.match(read(changed, 'style.dev.css'), /\.build-test-marker\s*\{/);
    } finally {
      dispose(changed);
    }
  });

  test('the css watch target covers the less files the build reads', async () => {
    const less = require(path.join(ROOT, 'node_modules', 'less'));
    const entry = path.join(ROOT, 'less', 'style.less');
    const output = await less.render(fs.readFileSync(entry, 'utf8'), { filename: entry });
    const watched = new Set(
      config.grunt.file.expand({ cwd: ROOT }, config.get('watch.css.files')).map((f) => path.resolve(ROOT, f))
    );
    watched.add(entry);
    const unwatched = output.imports
      .map((f) => path.resolve(f))
      .filter((f) => !watched.has(f))
      .map((f) => path.relative(ROOT, f).replace(/\\/g, '/'))
      .sort();
    assert.deepEqual(unwatched, UNWATCHED_LESS);
  });
});

test.describe('concat and uglify', () => {
  let dir;
  let result;

  test.before(() => {
    dir = scratch();
    result = grunt(dir, ['concat', 'uglify']);
  });
  test.after(() => dispose(dir));

  test('both tasks succeed', () => {
    assert.equal(result.status, 0, result.stdout + result.stderr);
  });

  test('concat.dist.src runs from the intro, libraries and theme modules to the outro', () => {
    const src = concatSources();
    assert.equal(src[0], 'js/uams.intro.js');
    assert.equal(src[src.length - 1], 'js/uams.outro.js');
    assert.deepEqual(src.slice(1, -1), [...config.get('concat.dist.libraries'), ...config.get('concat.dist.theme')]);
  });

  test('concat joins the sources in order with a semicolon', () => {
    const expected = concatSources()
      .map((file) => read(dir, file))
      .join(';');
    assert.equal(read(dir, 'js/site.dev.js'), expected);
  });

  test('js/site.dev.js matches the committed file', () => {
    assert.equal(read(dir, 'js/site.dev.js'), readCommitted('js/site.dev.js'));
  });

  test('uglify writes js/site.js and uams.js, both matching the committed files', () => {
    assert.equal(read(dir, 'js/site.js'), readCommitted('js/site.js'));
    assert.equal(read(dir, 'uams.js'), readCommitted('uams.js'));
    assert.equal(read(dir, 'js/site.js'), read(dir, 'uams.js'));
  });

  test('uams.js parses and holds every module in concat.dist.theme', () => {
    const code = read(dir, 'uams.js');
    assert.doesNotThrow(() => new vm.Script(code));
    const modules = config.get('concat.dist.theme');
    assert.ok(modules.length > 0);
    for (const file of modules) {
      const source = read(dir, file);
      const assigned = source.match(/^UAMS\.(\w+)\s*=/m);
      const marker = assigned ? `UAMS.${assigned[1]}=` : 'UAMS=function';
      assert.ok(code.includes(marker), `${file}: expected ${marker} in uams.js`);
    }
  });

  test('a changed theme module changes site.dev.js and uams.js', () => {
    const changed = scratch();
    try {
      fs.appendFileSync(path.join(changed, 'js', 'uams.social.js'), '\nUAMS.BuildTestMarker = true\n');
      const built = grunt(changed, ['concat', 'uglify']);
      assert.equal(built.status, 0, built.stdout + built.stderr);
      assert.ok(read(changed, 'js/site.dev.js').includes('UAMS.BuildTestMarker = true'));
      assert.ok(read(changed, 'uams.js').includes('UAMS.BuildTestMarker=!0'));
    } finally {
      dispose(changed);
    }
  });
});

test.describe('jshint', () => {
  test('lints gruntfile.js and the theme modules with the configured options', () => {
    const dir = scratch();
    try {
      const files = config.get("jshint.files").flat(Infinity);
      assert.equal(files[0], 'gruntfile.js');
      assert.deepEqual(files.slice(1), config.get('concat.dist.theme'));
      const result = grunt(dir, ['jshint']);
      assert.equal(result.status, 0, result.stdout + result.stderr);
      assert.match(result.stdout, /\d+ files lint free\./);
    } finally {
      dispose(dir);
    }
  });

  test('a planted syntax error fails the task', () => {
    const dir = scratch();
    try {
      fs.appendFileSync(path.join(dir, 'js', 'uams.social.js'), '\nvar planted = ;\n');
      const result = grunt(dir, ['jshint']);
      assert.notEqual(result.status, 0, result.stdout);
      assert.match(result.stdout, /uams\.social\.js/);
    } finally {
      dispose(dir);
    }
  });
});

test.describe('registered tasks', () => {
  test('default runs jshint, concat, uglify, notify and less in order', () => {
    assert.deepEqual(config.registered.default, ['jshint', 'concat', 'uglify', 'notify', 'less']);
  });

  test('js runs jshint, concat, uglify and notify in order', () => {
    assert.deepEqual(config.registered.js, ['jshint', 'concat', 'uglify', 'notify']);
  });

  test('css runs less and notify in order', () => {
    assert.deepEqual(config.registered.css, ['less', 'notify']);
  });

  test('only default, js and css are registered, and the six plugins are loaded', () => {
    assert.deepEqual(Object.keys(config.registered).sort(), ['css', 'default', 'js']);
    assert.deepEqual(config.loaded, [
      'grunt-notify',
      'grunt-contrib-less',
      'grunt-contrib-jshint',
      'grunt-contrib-concat',
      'grunt-contrib-uglify',
      'grunt-contrib-watch',
    ]);
  });
});

test.describe('watch', () => {
  test('the js target watches the concat sources and runs the js task', () => {
    assert.deepEqual(config.get("watch.js.files").flat(Infinity), concatSources());
    assert.deepEqual(config.get('watch.js.tasks'), ['js']);
  });

  test('the css target watches the less directories and runs the css task', () => {
    assert.deepEqual(config.get('watch.css.files'), [
      'less/*.less',
      'less/flat/*.less',
      'less/bootstrap/*.less',
      'less/widgets/*.less',
    ]);
    assert.deepEqual(config.get('watch.css.tasks'), ['css']);
  });

  test('the config target watches gruntfile.js and reloads', () => {
    assert.deepEqual(config.get('watch.config.files'), ['gruntfile.js']);
    assert.equal(config.get('watch.config.options.reload'), true);
  });

  test('every file the js watch lists exists', () => {
    for (const file of concatSources()) {
      assert.ok(fs.existsSync(path.join(ROOT, file)), `${file} is listed and missing`);
    }
  });
});
