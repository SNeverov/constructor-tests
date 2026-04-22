<?php
declare(strict_types=1);

// Max input file size in bytes (8 MB)
const UPLOAD_MAX_BYTES = 8 * 1024 * 1024;

// WebP quality per image type
const UPLOAD_QUALITY_COVER    = 85;
const UPLOAD_QUALITY_QUESTION = 83;
const UPLOAD_QUALITY_OPTION   = 83;

// Output dimensions (max side, aspect preserved)
const UPLOAD_DIM_COVER    = ['w' => 900, 'h' => 506];
const UPLOAD_DIM_QUESTION = ['w' => 900, 'h' => 600];
const UPLOAD_DIM_OPTION   = ['w' => 800, 'h' => 600];

$GLOBALS['_upload_type_config'] = [
    'cover'    => ['dir' => 'covers',    'dim' => UPLOAD_DIM_COVER,    'quality' => UPLOAD_QUALITY_COVER],
    'question' => ['dir' => 'questions', 'dim' => UPLOAD_DIM_QUESTION, 'quality' => UPLOAD_QUALITY_QUESTION],
    'option'   => ['dir' => 'options',   'dim' => UPLOAD_DIM_OPTION,   'quality' => UPLOAD_QUALITY_OPTION],
];

function upload_image(array $file, string $type): string
{
    $config = $GLOBALS['_upload_type_config'][$type] ?? null;
    if ($config === null) {
        throw new RuntimeException('Неизвестный тип изображения');
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки файла (код ' . ($file['error'] ?? '?') . ')');
    }

    $size = (int)($file['size'] ?? 0);
    if ($size === 0 || $size > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Файл слишком большой. Максимум 8 МБ');
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Файл не найден');
    }

    $mime = mime_content_type($tmpPath);
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Допустимые форматы: JPEG, PNG, GIF, WebP');
    }

    $src = upload_gd_load($tmpPath, $mime);
    if ($src === false) {
        throw new RuntimeException('Не удалось открыть изображение');
    }

    $srcW = (int)imagesx($src);
    $srcH = (int)imagesy($src);
    [$dstW, $dstH] = upload_calc_dimensions($srcW, $srcH, $config['dim']['w'], $config['dim']['h']);

    if ($dstW !== $srcW || $dstH !== $srcH) {
        $dst = imagecreatetruecolor($dstW, $dstH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($src);
    } else {
        $dst = $src;
    }

    $uploadDir = __DIR__ . '/../../public/uploads/' . $config['dir'];
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.webp';
    $filePath = $uploadDir . '/' . $filename;

    $ok = imagewebp($dst, $filePath, $config['quality']);
    imagedestroy($dst);

    if (!$ok) {
        throw new RuntimeException('Не удалось сохранить изображение');
    }

    return '/uploads/' . $config['dir'] . '/' . $filename;
}

function upload_delete_file(string $webPath): void
{
    if ($webPath === '' || str_starts_with($webPath, '/assets/')) {
        return;
    }

    $abs = __DIR__ . '/../../public' . $webPath;
    $real = realpath($abs);
    $base = realpath(__DIR__ . '/../../public/uploads');

    if ($real !== false && $base !== false && str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
        @unlink($real);
    }
}

function upload_gd_load(string $path, string $mime): \GdImage|false
{
    return match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png'  => imagecreatefrompng($path),
        'image/gif'  => imagecreatefromgif($path),
        'image/webp' => imagecreatefromwebp($path),
        default      => false,
    };
}

function upload_calc_dimensions(int $srcW, int $srcH, int $maxW, int $maxH): array
{
    if ($srcW <= $maxW && $srcH <= $maxH) {
        return [$srcW, $srcH];
    }

    $ratio = min($maxW / $srcW, $maxH / $srcH);
    return [max(1, (int)round($srcW * $ratio)), max(1, (int)round($srcH * $ratio))];
}