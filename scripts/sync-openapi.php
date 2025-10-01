<?php
declare(strict_types=1);

// Run from demo root: php scripts/sync-openapi.php
$demoRoot   = dirname(__DIR__);
$demoVendor = $demoRoot . '/vendor';
$demoPublic = $demoRoot . '/public';
$target     = $demoPublic . '/openapi.json';

require $demoVendor . '/autoload.php';

/** Find installed path of a composer package */
function findLibPath(string $package = 'dartcafe/email-validator'): ?string {
    if (class_exists(\Composer\InstalledVersions::class)) {
        try {
            $p = \Composer\InstalledVersions::getInstallPath($package);
            if (is_string($p) && $p !== '' && is_dir($p)) {
                return realpath($p) ?: $p;
            }
        } catch (\Throwable) {}
    }
    $guess = __DIR__ . '/../vendor/' . $package;
    return is_dir($guess) ? (realpath($guess) ?: $guess) : null;
}

$libPath  = findLibPath();
$libDocs  = $libPath ? $libPath . '/docs' : null;
$demoDocs = $demoRoot . '/src/Demo/Docs';

$openapiBin = $demoVendor . '/bin/openapi';
if (!is_file($openapiBin)) {
    fwrite(STDERR, "[docs:sync] swagger-php (vendor/bin/openapi) missing. Run: composer install --dev\n");
    exit(1);
}

$scanDirs = [];
if ($libDocs && is_dir($libDocs))  { $scanDirs[] = $libDocs; }
if (is_dir($demoDocs))             { $scanDirs[] = $demoDocs; }
if (!$scanDirs) {
    fwrite(STDERR, "[docs:sync] No docs directories found.\n");
    exit(1);
}

// Collect all PHP files to force-load (attributes need reflection)
$files = [];
foreach ($scanDirs as $dir) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    /** @var SplFileInfo $f */
    foreach ($it as $f) {
        if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
            $files[] = $f->getPathname();
        }
    }
}

// Build a temporary bootstrap that loads composer autoload + all doc files
@mkdir($demoPublic, 0777, true);
$tmpBootstrap = $demoPublic . '/.openapi_bootstrap.php';
$bootstrapCode = <<<'PHP'
<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
$__OPENAPI_DOC_FILES = %s;
foreach ($__OPENAPI_DOC_FILES as $__f) {
    require_once $__f;
}
unset($__OPENAPI_DOC_FILES, $__f);
PHP;

file_put_contents(
    $tmpBootstrap,
    sprintf($bootstrapCode, var_export($files, true))
);

// Run swagger-php scan over both dirs
$scanArg = implode(' ', array_map('escapeshellarg', $scanDirs));
$cmd = escapeshellcmd($openapiBin)
     . ' --bootstrap ' . escapeshellarg($tmpBootstrap)
     . ' --format json'
     . ' --output '   . escapeshellarg($target)
     . ' ' . $scanArg;

echo "[docs:sync] Scanning: " . implode(', ', $scanDirs) . "\n";
exec($cmd, $out, $code);

// Clean bootstrap (optional)
@unlink($tmpBootstrap);

if ($code !== 0 || !is_file($target)) {
    fwrite(STDERR, "[docs:sync] Generation failed (exit $code).\n$cmd\n");
    exit(1);
}
echo "[docs:sync] Generated $target\n";
