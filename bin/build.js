import esbuild from 'esbuild';

await esbuild.build({
    bundle: true,
    entryPoints: ['resources/js/request-snippet.js'],
    format: 'esm',
    minify: true,
    outfile: 'resources/js/dist/request-snippet.js',
    platform: 'browser',
    target: ['es2020'],
});
