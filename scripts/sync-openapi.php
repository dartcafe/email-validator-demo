<?php
declare(strict_types=1);

// Run from demo root: php scripts/sync-openapi.php
$demoRoot = dirname(__DIR__);
$demoVendor = $demoRoot . '/vendor';
$demoPublic = $demoRoot . '/public';
$target = $demoPublic . '/openapi.json';

require $demoVendor . '/autoload.php';

/**
 * Try to locate the package install path using Composer 2 API.
 * @return string|null absolute path to dartcafe/email-validator or null
 */
function findLibPath(string $package = 'dartcafe/email-validator'): ?string {
    if (class_exists(\Composer\InstalledVersions::class)) {
        try {
            $p = \Composer\InstalledVersions::getInstallPath($package);
            if (is_string($p) && $p !== '' && is_dir($p)) {
                return realpath($p) ?: $p;
            }
        } catch (\Throwable $e) {
            // package not installed
        }
    }
    // fallback guess
    $guess = __DIR__ . '/../vendor/' . $package;
    return is_dir($guess) ? realpath($guess) ?: $guess : null;
}

$libPath = findLibPath();
if ($libPath === null) {
    fwrite(STDERR, "[docs:sync] Package dartcafe/email-validator not found in vendor.\n");
    exit(0); // don't fail CI; demo can run without docs
}

// 1) copy if package already ships openapi.json
$ship = $libPath . '/public/openapi.json';
if (is_file($ship)) {
    @mkdir($demoPublic, 0777, true);
    if (!copy($ship, $target)) {
        fwrite(STDERR, "[docs:sync] Failed to copy $ship -> $target\n");
        exit(1);
    }
    echo "[docs:sync] Copied OpenAPI from package public/openapi.json\n";
    exit(0);
}

// 2) otherwise: generate from package docs + demo docs using swagger-php CLI
$libDocs  = $libPath . '/docs';
$demoDocs = $demoRoot . '/docs';
if (!is_dir($libDocs) && !is_dir($demoDocs)) {
    fwrite(STDERR, "[docs:sync] No docs/ in package; nothing to generate.\n");
    exit(0);
}

// find CLI
$openapiBin = $demoVendor . '/bin/openapi';
if (!is_file($openapiBin)) {
    fwrite(STDERR, "[docs:sync] zircote/swagger-php not installed in demo (vendor/bin/openapi missing).\n");
    fwrite(STDERR, "           Run: composer install --dev\n");
    exit(0);
}

@mkdir($demoPublic, 0777, true);

// Build command: scan lib docs + demo docs with demo autoload as bootstrap
$cmd = escapeshellcmd($openapiBin)
     . ' --bootstrap ' . escapeshellarg($demoVendor . '/autoload.php')
     . ' --format json'
     . ' --output ' . escapeshellarg($target)
     . (is_dir($libDocs)  ? ' ' . escapeshellarg($libDocs)  : '')
     . (is_dir($demoDocs) ? ' ' . escapeshellarg($demoDocs) : '');

echo "[docs:sync] Generating OpenAPI from $docsDir ...\n";
exec($cmd, $out, $code);
if ($code !== 0 || !is_file($target)) {
    fwrite(STDERR, "[docs:sync] Generation failed (exit $code). Command:\n$cmd\n");
    exit(1);
}
echo "[docs:sync] Generated $target\n";
