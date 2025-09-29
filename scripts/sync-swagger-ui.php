<?php
declare(strict_types=1);

$demoRoot   = dirname(__DIR__);
$vendorDist = $demoRoot . '/vendor/swagger-api/swagger-ui/dist';
$targetDir  = $demoRoot . '/public/docs/assets';

if (!is_dir($vendorDist)) {
    fwrite(STDERR, "[docs:assets] swagger-ui dist not found in vendor. Run composer install.\n");
    exit(0); // nicht hart failen
}

@mkdir($targetDir, 0777, true);

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($vendorDist, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $src) {
    $rel = substr($src->getPathname(), strlen($vendorDist) + 1);
    $dst = $targetDir . DIRECTORY_SEPARATOR . $rel;

    if ($src->isDir()) {
        if (!is_dir($dst)) @mkdir($dst, 0777, true);
        continue;
    }
    if (!is_dir(dirname($dst))) @mkdir(dirname($dst), 0777, true);
    if (!copy($src->getPathname(), $dst)) {
        fwrite(STDERR, "[docs:assets] Failed to copy {$rel}\n");
    }
}
echo "[docs:assets] Copied Swagger UI assets to public/docs/assets\n";
