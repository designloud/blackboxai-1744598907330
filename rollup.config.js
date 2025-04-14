import babel from '@rollup/plugin-babel';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import { terser } from 'rollup-plugin-terser';

const production = !process.env.ROLLUP_WATCH;

export default {
    input: 'resources/js/conversa.js',
    output: [
        // UMD build for browsers
        {
            file: 'dist/conversa.umd.js',
            format: 'umd',
            name: 'Conversa',
            sourcemap: !production,
        },
        // ES module build for bundlers
        {
            file: 'dist/conversa.esm.js',
            format: 'es',
            sourcemap: !production,
        },
        // CommonJS build for Node.js
        {
            file: 'dist/conversa.cjs.js',
            format: 'cjs',
            sourcemap: !production,
        },
        // Minified UMD build for production
        {
            file: 'dist/conversa.min.js',
            format: 'umd',
            name: 'Conversa',
            plugins: [terser()],
            sourcemap: true,
        },
    ],
    plugins: [
        // Resolve node_modules dependencies
        resolve({
            browser: true,
            preferBuiltins: false,
        }),
        // Convert CommonJS modules to ES6
        commonjs({
            include: 'node_modules/**',
        }),
        // Transpile with Babel
        babel({
            babelHelpers: 'bundled',
            exclude: 'node_modules/**',
            presets: [
                ['@babel/preset-env', {
                    targets: {
                        browsers: [
                            'last 2 versions',
                            '> 1%',
                            'not dead',
                        ],
                    },
                    modules: false,
                    loose: true,
                }],
            ],
        }),
    ],
    // External dependencies that shouldn't be bundled
    external: [
        'laravel-echo',
        'pusher-js',
    ],
    // Global variables for external dependencies in UMD build
    globals: {
        'laravel-echo': 'Echo',
        'pusher-js': 'Pusher',
    },
    // Watch configuration
    watch: {
        include: 'resources/js/**',
        clearScreen: false,
    },
    // Show warnings about circular dependencies
    onwarn(warning, warn) {
        // Skip circular dependency warnings
        if (warning.code === 'CIRCULAR_DEPENDENCY') return;
        warn(warning);
    },
}
