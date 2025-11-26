import esbuild from "esbuild";

let devMode = true;

const ignoreWarnings = new Set([
    "'TYPO3' is not defined"
])

const buildConfig = {
    entryPoints: [
        "Resources/Private/TypeScript/category-tree-element.ts",
    ],
    mainFields: ["browser", "module", "main"],
    conditions: ["browser"],
    bundle: true,
    outdir: "Resources/Public/JavaScript/",
    format: "esm",
    plugins: [],
    logLevel: "info",
    sourcemap: true,
    external: ["@typo3/*", "interactjs"],
};

if (process.argv.includes('--build')) {
    await build()
} else {
    await watch()
}

async function build() {
    devMode = false;
    buildConfig.sourcemap = false
    buildConfig.minify = true
    await esbuild.build(buildConfig)
}

async function watch() {
    let ctx = await esbuild.context(buildConfig)
    await ctx.watch()
}
