<?php
declare(strict_types=1);

$demoRoot   = dirname(__DIR__);
$vendorDist = $demoRoot . '/vendor/swagger-api/swagger-ui/dist';
$assetsDst  = $demoRoot . '/public/docs/assets';
$pkgRoot    = dirname($vendorDist);
$noticeDst  = $demoRoot . '/public/docs/THIRD-PARTY-NOTICES';

if (!is_dir($vendorDist)) {
    fwrite(STDERR, "[docs:assets] swagger-ui dist not found in vendor. Run composer install.\n");
    exit(0);
}

@mkdir($assetsDst, 0777, true);
@mkdir($noticeDst, 0777, true);

// Apache-2.0 requires to include LICENSE + NOTICE
foreach ([$pkgRoot . '/LICENSE', $pkgRoot . '/NOTICE'] as $f) {
    if (is_file($f)) {
        @copy($f, $noticeDst . '/swagger-ui-' . basename($f));
    }
}

// copy complete dist structure to public/docs/assets
$it = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($vendorDist, \FilesystemIterator::SKIP_DOTS),
    \RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $src) {
    $rel = substr($src->getPathname(), strlen($vendorDist) + 1);
    $dst = $assetsDst . DIRECTORY_SEPARATOR . $rel;

    if ($src->isDir()) {
        if (!is_dir($dst)) { @mkdir($dst, 0777, true); }
        continue;
    }
    if (!is_dir(dirname($dst))) { @mkdir(dirname($dst), 0777, true); }
    if (!@copy($src->getPathname(), $dst)) {
        fwrite(STDERR, "[docs:assets] Failed to copy {$rel}\n");
    }
}

echo "[docs:assets] Copied Swagger UI assets to public/docs/assets and LICENSE/NOTICE to public/docs/THIRD-PARTY-NOTICES\n";
