'use strict';

// Shared helpers for the build tests. Every build runs in a scratch copy of the sources,
// so a test never rewrites a tracked file.

const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const crypto = require('node:crypto');
const { spawnSync } = require('node:child_process');

const ROOT = path.resolve(__dirname, '..', '..');
const GRUNT_BIN = path.join(ROOT, 'node_modules', 'grunt', 'bin', 'grunt');

// Normalization used by every comparison in these tests: CRLF becomes LF. A Windows checkout
// without the repository's `* text=auto eol=lf` attribute applied can hold CRLF, and Grunt
// itself normalizes line feeds when it concatenates. Nothing else is normalized.
function norm(text) {
  return text.replace(/\r\n/g, '\n');
}

function readCommitted(relativePath) {
  return norm(fs.readFileSync(path.join(ROOT, relativePath), 'utf8'));
}

function sha256(text) {
  return crypto.createHash('sha256').update(text, 'utf8').digest('hex');
}

// A scratch directory holding the files the build reads (`gruntfile.js`, `package.json`,
// `less/`, `js/`) and a junction to this checkout's `node_modules`.
function scratch() {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'uams-build-'));
  for (const entry of ['gruntfile.js', 'package.json']) {
    fs.copyFileSync(path.join(ROOT, entry), path.join(dir, entry));
  }
  for (const entry of ['less', 'js']) {
    fs.cpSync(path.join(ROOT, entry), path.join(dir, entry), { recursive: true });
  }
  fs.symlinkSync(path.join(ROOT, 'node_modules'), path.join(dir, 'node_modules'), 'junction');
  return dir;
}

function dispose(dir) {
  // Remove the junction first, as a link, so the recursive delete never follows it into
  // this checkout's node_modules.
  try {
    fs.unlinkSync(path.join(dir, 'node_modules'));
  } catch (error) {
    if (error.code !== 'ENOENT') {
      throw error;
    }
  }
  // Best effort: on Windows a directory can stay locked for a moment after a failed Grunt
  // run, and a leftover scratch directory in the system temp folder is not a test failure.
  try {
    fs.rmSync(dir, { recursive: true, force: true, maxRetries: 10, retryDelay: 300 });
  } catch (error) {
    if (!['EBUSY', 'EPERM', 'ENOTEMPTY'].includes(error.code)) {
      throw error;
    }
  }
}

// Runs Grunt tasks in a directory and returns the exit status and both output streams.
function grunt(dir, tasks) {
  const result = spawnSync(process.execPath, [GRUNT_BIN, ...tasks, '--no-color'], {
    cwd: dir,
    encoding: 'utf8',
    timeout: 240000,
  });
  return { status: result.status, stdout: result.stdout, stderr: result.stderr };
}

function read(dir, relativePath) {
  return norm(fs.readFileSync(path.join(dir, relativePath), 'utf8'));
}

// Loads `gruntfile.js` against the real Grunt API from the repository root and returns its
// resolved configuration (templates expanded) plus the tasks it registers and loads.
function loadGruntfile() {
  const realGrunt = require('grunt');
  const registered = {};
  const loaded = [];
  const recorder = Object.create(realGrunt);
  recorder.registerTask = (name, list) => {
    registered[name] = list;
  };
  recorder.loadNpmTasks = (name) => {
    loaded.push(name);
  };
  const previous = process.cwd();
  process.chdir(ROOT);
  try {
    require(path.join(ROOT, 'gruntfile.js'))(recorder);
    const get = (key) => realGrunt.config.get(key);
    return { get, registered, loaded, grunt: realGrunt };
  } finally {
    process.chdir(previous);
  }
}

module.exports = { ROOT, norm, readCommitted, sha256, scratch, dispose, grunt, read, loadGruntfile };
