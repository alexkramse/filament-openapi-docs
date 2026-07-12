import esbuild from 'esbuild';
import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import { existsSync, readFileSync, watch } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const isWatch = process.argv.includes('--watch');
const shouldPublish = process.argv.includes('--publish');
const packageRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const appRoot = findAppRoot(packageRoot);
const jsPath = resolve(packageRoot, 'resources/js/request-snippet.js');
const cssPath = resolve(packageRoot, 'resources/css/openapi-docs.css');
let publishProcess = null;
let publishQueued = false;
let buildProcess = null;
let buildQueued = false;

const buildOptions = {
    bundle: true,
    entryPoints: ['resources/js/request-snippet.js'],
    format: 'esm',
    minify: true,
    outfile: 'resources/js/dist/request-snippet.js',
    platform: 'browser',
    target: ['es2020'],
};

if (! isWatch) {
    await buildAndPublish();
} else {
    runBuildAndPublish();

    watchSourceFile(jsPath, () => {
        runBuildAndPublish();
    });

    if (shouldPublish && existsSync(cssPath)) {
        watchSourceFile(cssPath, () => {
            publishFilamentAssets();
        });
    }

    console.log('Watching Filament OpenAPI docs assets...');
}

function findAppRoot(startPath) {
    let currentPath = startPath;

    while (currentPath !== dirname(currentPath)) {
        if (existsSync(resolve(currentPath, 'artisan'))) {
            return currentPath;
        }

        currentPath = dirname(currentPath);
    }

    return null;
}

async function buildAndPublish() {
    await esbuild.build(buildOptions);
    await publishFilamentAssets();
}

async function runBuildAndPublish() {
    if (buildProcess) {
        buildQueued = true;

        return;
    }

    buildProcess = buildAndPublish()
        .catch((error) => {
            console.error(error);
        })
        .finally(() => {
            buildProcess = null;

            if (buildQueued) {
                buildQueued = false;
                runBuildAndPublish();
            }
        });

    await buildProcess;
}

function watchSourceFile(path, callback) {
    let timeout;
    let currentHash = hashFile(path);

    watch(path, () => {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            const nextHash = hashFile(path);

            if (nextHash === currentHash) {
                return;
            }

            currentHash = nextHash;
            callback();
        }, 100);
    });
}

function hashFile(path) {
    return createHash('sha256')
        .update(readFileSync(path))
        .digest('hex');
}

async function publishFilamentAssets() {
    if (! shouldPublish) {
        return;
    }

    if (! appRoot) {
        console.warn('Could not find Laravel artisan file; skipping filament:assets.');

        return;
    }

    if (publishProcess) {
        publishQueued = true;

        return;
    }

    await new Promise((resolvePromise) => {
        publishProcess = spawn('php', ['artisan', 'filament:assets', '--no-interaction'], {
            cwd: appRoot,
            stdio: 'inherit',
        });

        publishProcess.on('close', () => {
            publishProcess = null;
            resolvePromise();

            if (publishQueued) {
                publishQueued = false;
                publishFilamentAssets();
            }
        });
    });
}
