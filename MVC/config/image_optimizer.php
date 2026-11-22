<?php
/*
 * Otimização centralizada de imagens do CashFY usando PHP GD.
 *
 * Responsabilidades:
 *  - Armazenamento fora do public_html em ../cashfy-uploads/ (CASHFY_UPLOADS_DIR);
 *  - Redimensionamento proporcional (sem upscale) e compressão automática por perfil (perfil/products/icones);
 *  - Prioridade para conversão em WebP com fallback automático para JPEG progressivo / PNG;
 *  - Correção de orientação EXIF (fotos de celulares) e preservação de transparência;
 *  - Geração de miniaturas em ../cashfy-uploads/<sub>/thumbs/ no upload e sob demanda para imagens existentes;
 *  - Entrega segura via MVC/controller/media.php (funciona em localhost e Hostinger);
 *  - Compatibilidade total com caminhos legados ("uploads/...") e novos ("cashfy-uploads/..." / "../cashfy-uploads/...").
 */
require_once __DIR__ . '/init.php';

if (!defined('CASHFY_MAX_IMAGE_PIXELS')) {
    define('CASHFY_MAX_IMAGE_PIXELS', 40000000); // ~40 MP: evita estouro de memória (decompression bomb)
}

if (!defined('CASHFY_MAX_ONDEMAND_THUMBS')) {
    define('CASHFY_MAX_ONDEMAND_THUMBS', 8); // Limite por requisição ao gerar thumbs de imagens legadas
}

/* Caminho físico absoluto da pasta externa ../cashfy-uploads/ com barra final */
function cashfy_upload_root(): string
{
    $dir = defined('CASHFY_UPLOADS_DIR')
        ? CASHFY_UPLOADS_DIR
        : (rtrim(str_replace('\\', '/', dirname(__DIR__, 3)), '/') . '/cashfy-uploads');

    return rtrim(str_replace('\\', '/', $dir), '/') . '/';
}

/*
 * Normaliza qualquer caminho salvo no banco ou passado pela view para o caminho interno
 * dentro de cashfy-uploads/ (ex.: "perfil/abc.webp", "products/6afbd.jpg", "icones/andrei.jpg").
 * Aceita tanto o formato legado ("uploads/...") quanto "../cashfy-uploads/..." e "cashfy-uploads/...".
 */
function cashfy_normalize_upload_path(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    $clean = str_replace('\\', '/', trim($path));

    if ($clean === '' || strpos($clean, "\0") !== false) {
        return null;
    }

    $clean = preg_replace('#^(?:\.\./)+#', '', $clean);
    $clean = ltrim((string) $clean, '/');

    if (strpos($clean, 'cashfy-uploads/') === 0) {
        $clean = substr($clean, strlen('cashfy-uploads/'));
    } elseif (strpos($clean, 'uploads/') === 0) {
        $clean = substr($clean, strlen('uploads/'));
    }

    if ($clean === '' || strpos($clean, '..') !== false) {
        return null;
    }

    if (!preg_match('#^(perfil|products|icones)/(?:thumbs/)?[A-Za-z0-9._-]+$#', $clean)) {
        return null;
    }

    return $clean;
}

/* Garante ../cashfy-uploads/<sub>/ (ou subpasta) existente e gravável. Devolve o caminho ou null. */
function cashfy_prepare_upload_dir(string $sub): ?string
{
    $sub = trim(str_replace('\\', '/', $sub), '/');

    if ($sub === '' || strpos($sub, '..') !== false) {
        return null;
    }

    $dir = cashfy_upload_root() . $sub;

    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }

    if (!is_writable($dir)) {
        @chmod($dir, 0755);

        if (!is_writable($dir)) {
            return null;
        }
    }

    return $dir;
}

/* Perfis centralizados por tipo de upload */
function cashfy_image_profile(string $sub): array
{
    $profiles = [
        'perfil' => [
            'main' => [
                'max_width' => 600,
                'max_height' => 600,
                'mode' => 'contain',
                'webp_quality' => 82,
                'jpeg_quality' => 85,
                'png_level' => 6,
            ],
            'thumb' => [
                'max_width' => 280,
                'max_height' => 280,
                'mode' => 'cover',
                'webp_quality' => 80,
                'jpeg_quality' => 82,
                'png_level' => 6,
            ],
        ],
        'products' => [
            'main' => [
                'max_width' => 1200,
                'max_height' => 1200,
                'mode' => 'contain',
                'webp_quality' => 82,
                'jpeg_quality' => 85,
                'png_level' => 6,
            ],
            'thumb' => [
                // Proporcional (sem crop forçado) para manter nitidez em fotos verticais e horizontais
                'max_width' => 560,
                'max_height' => 760,
                'mode' => 'contain',
                'webp_quality' => 78,
                'jpeg_quality' => 82,
                'png_level' => 6,
            ],
        ],
        'icones' => [
            'main' => [
                'max_width' => 600,
                'max_height' => 600,
                'mode' => 'contain',
                'webp_quality' => 82,
                'jpeg_quality' => 85,
                'png_level' => 6,
            ],
            'thumb' => [
                // Fotos circulares da equipe (130x130px -> 280x280px Retina 2x)
                'max_width' => 280,
                'max_height' => 280,
                'mode' => 'cover',
                'webp_quality' => 80,
                'jpeg_quality' => 82,
                'png_level' => 6,
            ],
        ],
    ];

    return $profiles[$sub] ?? $profiles['products'];
}

function cashfy_gd_available(): bool
{
    return extension_loaded('gd')
        && function_exists('imagecreatetruecolor')
        && function_exists('imagecopyresampled');
}

function cashfy_gd_supports_webp(): bool
{
    return cashfy_gd_available()
        && function_exists('imagewebp')
        && ((imagetypes() & IMG_WEBP) === IMG_WEBP);
}

/* Compatível com PHP 7.4 (resource) e PHP 8.0–8.5+ (GdImage sem deprecation warning) */
function cashfy_gd_free($image): void
{
    if ($image instanceof GdImage || is_resource($image)) {
        imagedestroy($image);
    }
}

/* Abre imagem a partir do disco via GD */
function cashfy_gd_load(string $file_path, int $image_type)
{
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($file_path) : false;
        case IMAGETYPE_PNG:
            return function_exists('imagecreatefrompng') ? @imagecreatefrompng($file_path) : false;
        case IMAGETYPE_GIF:
            return function_exists('imagecreatefromgif') ? @imagecreatefromgif($file_path) : false;
        case IMAGETYPE_WEBP:
            return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file_path) : false;
        default:
            return false;
    }
}

/* Corrige orientação EXIF em fotos JPEG vindas de celulares antes de regravar sem metadados */
function cashfy_gd_fix_orientation($image, string $source_path, int $image_type)
{
    if ($image_type !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
        return $image;
    }

    $exif = @exif_read_data($source_path);
    $orientation = (int) ($exif['Orientation'] ?? 1);

    if ($orientation <= 1) {
        return $image;
    }

    $rotated = $image;

    switch ($orientation) {
        case 2:
            if (function_exists('imageflip')) {
                @imageflip($rotated, IMG_FLIP_HORIZONTAL);
            }
            break;
        case 3:
            $rotated = @imagerotate($image, 180, 0) ?: $image;
            break;
        case 4:
            if (function_exists('imageflip')) {
                @imageflip($rotated, IMG_FLIP_VERTICAL);
            }
            break;
        case 5:
            $rotated = @imagerotate($image, -90, 0) ?: $image;
            if (function_exists('imageflip')) {
                @imageflip($rotated, IMG_FLIP_HORIZONTAL);
            }
            break;
        case 6:
            $rotated = @imagerotate($image, -90, 0) ?: $image;
            break;
        case 7:
            $rotated = @imagerotate($image, 90, 0) ?: $image;
            if (function_exists('imageflip')) {
                @imageflip($rotated, IMG_FLIP_HORIZONTAL);
            }
            break;
        case 8:
            $rotated = @imagerotate($image, 90, 0) ?: $image;
            break;
    }

    if ($rotated !== $image) {
        cashfy_gd_free($image);
    }

    return $rotated;
}

/* Cria uma versão redimensionada em canvas truecolor preservando transparência (ou fundo branco para JPEG) */
function cashfy_gd_render_variant($src, array $spec, bool $flatten_white = false)
{
    $src_w = imagesx($src);
    $src_h = imagesy($src);

    if ($src_w <= 0 || $src_h <= 0) {
        return false;
    }

    $max_w = max(1, (int) ($spec['max_width'] ?? $src_w));
    $max_h = max(1, (int) ($spec['max_height'] ?? $src_h));
    $mode = (string) ($spec['mode'] ?? 'contain');

    $src_x = 0;
    $src_y = 0;
    $crop_w = $src_w;
    $crop_h = $src_h;

    if ($mode === 'cover') {
        $target_ratio = $max_w / $max_h;
        $src_ratio = $src_w / $src_h;

        if ($src_ratio > $target_ratio) {
            $crop_w = (int) round($src_h * $target_ratio);
            $src_x = (int) floor(($src_w - $crop_w) / 2);
        } elseif ($src_ratio < $target_ratio) {
            $crop_h = (int) round($src_w / $target_ratio);
            $src_y = (int) floor(($src_h - $crop_h) / 2);
        }

        // Nunca faz upscale além da área recortada disponível
        $scale = min(1.0, $max_w / max(1, $crop_w), $max_h / max(1, $crop_h));
        $dst_w = max(1, (int) round($crop_w * $scale));
        $dst_h = max(1, (int) round($crop_h * $scale));
    } else {
        $scale = min(1.0, $max_w / $src_w, $max_h / $src_h);
        $dst_w = max(1, (int) round($src_w * $scale));
        $dst_h = max(1, (int) round($src_h * $scale));
    }

    $dst = @imagecreatetruecolor($dst_w, $dst_h);

    if ($dst === false) {
        return false;
    }

    if ($flatten_white) {
        imagealphablending($dst, true);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $dst_w, $dst_h, $white);
    } else {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $dst_w, $dst_h, $transparent);
    }

    $ok = @imagecopyresampled($dst, $src, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $crop_w, $crop_h);

    if (!$ok) {
        cashfy_gd_free($dst);
        return false;
    }

    return $dst;
}

/* Define o formato de saída priorizando WebP e caindo para PNG/JPEG caso necessário */
function cashfy_gd_target_format(int $source_type): string
{
    if (cashfy_gd_supports_webp()) {
        return 'webp';
    }

    if ($source_type === IMAGETYPE_PNG || $source_type === IMAGETYPE_GIF) {
        return 'png';
    }

    return 'jpg';
}

/* Salva uma imagem GD de forma atômica (temp + rename) e aplica permissão 0644 (compatível com Hostinger Linux) */
function cashfy_gd_write_variant($src, string $dest_path, string $format, array $spec): bool
{
    $flatten = ($format === 'jpg');
    $canvas = cashfy_gd_render_variant($src, $spec, $flatten);

    if ($canvas === false) {
        return false;
    }

    $tmp_dest = $dest_path . '.tmp.' . getmypid() . '.' . mt_rand(1000, 9999);
    $saved = false;

    if ($format === 'webp' && function_exists('imagewebp')) {
        $saved = @imagewebp($canvas, $tmp_dest, (int) ($spec['webp_quality'] ?? 82));
    } elseif ($format === 'png' && function_exists('imagepng')) {
        $saved = @imagepng($canvas, $tmp_dest, (int) ($spec['png_level'] ?? 6));
    } elseif (function_exists('imagejpeg')) {
        @imageinterlace($canvas, true); // JPEG progressivo
        $saved = @imagejpeg($canvas, $tmp_dest, (int) ($spec['jpeg_quality'] ?? 84));
    }

    cashfy_gd_free($canvas);

    if (!$saved || !is_file($tmp_dest) || filesize($tmp_dest) === 0) {
        if (is_file($tmp_dest)) {
            @unlink($tmp_dest);
        }
        return false;
    }

    @chmod($tmp_dest, 0644);

    if (!@rename($tmp_dest, $dest_path)) {
        if (!@copy($tmp_dest, $dest_path)) {
            @unlink($tmp_dest);
            return false;
        }
        @unlink($tmp_dest);
    }

    @chmod($dest_path, 0644);
    return true;
}

/*
 * Otimiza o arquivo temporário enviado no upload, gera a imagem principal + miniatura
 * em ../cashfy-uploads/<sub>/ e devolve o caminho para salvar no banco
 * (ex.: "cashfy-uploads/products/ab12cd34ef567890.webp").
 */
function cashfy_optimize_uploaded_image(string $tmp_path, array $info, string $sub, ?string &$error = null): ?string
{
    $error = null;
    $width = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    $source_type = (int) ($info[2] ?? 0);

    if ($width <= 0 || $height <= 0 || ($width * $height) > CASHFY_MAX_IMAGE_PIXELS) {
        $error = 'As dimensões da imagem são grandes demais. Escolha uma foto menor.';
        return null;
    }

    $dir = cashfy_prepare_upload_dir($sub);

    if ($dir === null) {
        error_log('cashfy upload: pasta ../cashfy-uploads/' . $sub . ' ausente ou sem permissão de escrita');
        $error = 'O servidor não conseguiu salvar a imagem. Tente novamente em instantes.';
        return null;
    }

    $stem = bin2hex(random_bytes(8));

    // Fallback seguro caso GD não esteja habilitado no servidor
    if (!cashfy_gd_available()) {
        $fallback_exts = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];
        $ext = $fallback_exts[$source_type] ?? 'jpg';
        $dest = $dir . '/' . $stem . '.' . $ext;

        if (!@move_uploaded_file($tmp_path, $dest)) {
            error_log('cashfy upload: fallback move_uploaded_file falhou para ' . $dest);
            $error = 'O servidor não conseguiu salvar a imagem. Tente novamente em instantes.';
            return null;
        }

        @chmod($dest, 0644);
        error_log('cashfy upload: extensão PHP GD indisponível; imagem salva sem otimização.');
        return 'cashfy-uploads/' . $sub . '/' . $stem . '.' . $ext;
    }

    $src = cashfy_gd_load($tmp_path, $source_type);

    if ($src === false) {
        $error = 'Não foi possível processar a imagem enviada. Tente outro arquivo JPG, PNG ou WEBP.';
        return null;
    }

    $src = cashfy_gd_fix_orientation($src, $tmp_path, $source_type);

    $profile = cashfy_image_profile($sub);
    $format = cashfy_gd_target_format($source_type);
    $main_filename = $stem . '.' . $format;
    $main_dest = $dir . '/' . $main_filename;

    $main_ok = cashfy_gd_write_variant($src, $main_dest, $format, $profile['main']);

    // Se WebP falhar por algum motivo pontual no servidor, tenta fallback em JPEG/PNG
    if (!$main_ok && $format === 'webp') {
        $format = ($source_type === IMAGETYPE_PNG || $source_type === IMAGETYPE_GIF) ? 'png' : 'jpg';
        $main_filename = $stem . '.' . $format;
        $main_dest = $dir . '/' . $main_filename;
        $main_ok = cashfy_gd_write_variant($src, $main_dest, $format, $profile['main']);
    }

    if (!$main_ok) {
        cashfy_gd_free($src);
        error_log('cashfy upload: falha ao gravar imagem otimizada em ' . $main_dest);
        $error = 'O servidor não conseguiu salvar a imagem. Tente novamente em instantes.';
        return null;
    }

    // Se o usuário já enviou um WebP dentro das dimensões máximas e menor que a recompressão, mantém o menor
    if (
        $source_type === IMAGETYPE_WEBP
        && $format === 'webp'
        && imagesx($src) <= $profile['main']['max_width']
        && imagesy($src) <= $profile['main']['max_height']
        && is_file($tmp_path)
        && filesize($tmp_path) > 0
        && filesize($tmp_path) < filesize($main_dest)
    ) {
        @copy($tmp_path, $main_dest);
        @chmod($main_dest, 0644);
    }

    // Gera miniatura dedicada (falha na miniatura é não-fatal; imagem principal continua válida)
    if (!empty($profile['thumb'])) {
        $thumb_dir = cashfy_prepare_upload_dir($sub . '/thumbs');
        if ($thumb_dir !== null) {
            $thumb_dest = $thumb_dir . '/' . $stem . '.' . $format;
            if (!cashfy_gd_write_variant($src, $thumb_dest, $format, $profile['thumb'])) {
                error_log('cashfy upload: não foi possível gerar miniatura em ' . $thumb_dest);
            }
        }
    }

    cashfy_gd_free($src);

    return 'cashfy-uploads/' . $sub . '/' . $main_filename;
}

/*
 * Gera sob demanda a miniatura WebP/fallback dentro de ../cashfy-uploads/<sub>/thumbs/
 * para uma imagem já existente (ex.: .jpg legadas).
 * $rel_internal é o caminho interno normalizado (ex.: "products/6afbd04813493.jpg").
 * Devolve o caminho interno da miniatura (ex.: "products/thumbs/6afbd04813493.webp") ou null.
 */
function cashfy_ensure_thumbnail(string $rel_internal, string $sub): ?string
{
    static $generated_this_request = 0;

    $stem = pathinfo($rel_internal, PATHINFO_FILENAME);

    if ($stem === '' || strpos($stem, '..') !== false) {
        return null;
    }

    $root = cashfy_upload_root();

    // 1. Verifica se a miniatura já existe em disco (priorizando WebP)
    foreach (['webp', 'jpg', 'png', 'gif'] as $ext) {
        $rel_thumb = $sub . '/thumbs/' . $stem . '.' . $ext;
        if (is_file($root . $rel_thumb)) {
            return $rel_thumb;
        }
    }

    // 2. Não existe ainda: gera sob demanda se GD estiver disponível e dentro do teto da requisição
    if (!cashfy_gd_available() || $generated_this_request >= CASHFY_MAX_ONDEMAND_THUMBS) {
        return null;
    }

    $source_abs = $root . $rel_internal;

    if (!is_file($source_abs)) {
        return null;
    }

    $info = @getimagesize($source_abs);

    if ($info === false) {
        return null;
    }

    $width = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    $source_type = (int) ($info[2] ?? 0);

    if ($width <= 0 || $height <= 0 || ($width * $height) > CASHFY_MAX_IMAGE_PIXELS) {
        return null;
    }

    $profile = cashfy_image_profile($sub);

    if (empty($profile['thumb'])) {
        return null;
    }

    $thumb_dir = cashfy_prepare_upload_dir($sub . '/thumbs');

    if ($thumb_dir === null) {
        return null;
    }

    $src = cashfy_gd_load($source_abs, $source_type);

    if ($src === false) {
        return null;
    }

    $src = cashfy_gd_fix_orientation($src, $source_abs, $source_type);

    $format = cashfy_gd_target_format($source_type);
    $rel_thumb = $sub . '/thumbs/' . $stem . '.' . $format;
    $abs_thumb = $root . $rel_thumb;

    $ok = cashfy_gd_write_variant($src, $abs_thumb, $format, $profile['thumb']);

    if (!$ok && $format === 'webp') {
        $format = ($source_type === IMAGETYPE_PNG || $source_type === IMAGETYPE_GIF) ? 'png' : 'jpg';
        $rel_thumb = $sub . '/thumbs/' . $stem . '.' . $format;
        $abs_thumb = $root . $rel_thumb;
        $ok = cashfy_gd_write_variant($src, $abs_thumb, $format, $profile['thumb']);
    }

    cashfy_gd_free($src);

    if (!$ok) {
        return null;
    }

    $generated_this_request++;
    return $rel_thumb;
}

/*
 * Resolve a URL de exibição nas views.
 *  - $variant = 'thumb'    -> entrega a miniatura otimizada (gerando sob demanda para imagens legadas)
 *  - $variant = 'original' -> entrega a imagem principal otimizada (ou original legada)
 *  - $prefix               -> prefixo relativo da view ('' no index.php, '../../' em MVC/view/*.php)
 *
 * Como ../cashfy-uploads/ fica fora do public_html, as imagens são servidas pelo endpoint
 * leve MVC/controller/media.php (compatível tanto com Hostinger quanto com localhost).
 */
function cashfy_image_url(?string $db_path, string $variant = 'original', string $prefix = ''): string
{
    if (empty($db_path)) {
        return '';
    }

    $rel = cashfy_normalize_upload_path($db_path);

    if ($rel !== null) {
        if ($variant === 'thumb') {
            foreach (['perfil', 'products', 'icones'] as $sub) {
                if (
                    strpos($rel, $sub . '/') === 0
                    && strpos($rel, $sub . '/thumbs/') !== 0
                ) {
                    $thumb = cashfy_ensure_thumbnail($rel, $sub);
                    if ($thumb !== null) {
                        $rel = $thumb;
                    }
                    break;
                }
            }
        }

        $encoded = str_replace('%2F', '/', rawurlencode($rel));
        return $prefix . 'MVC/controller/media.php?file=' . $encoded;
    }

    // Fallback para caminhos estáticos fora de cashfy-uploads/ (ex.: assets/img/...)
    $clean = ltrim(str_replace('\\', '/', trim($db_path)), '/');

    if ($clean === '' || strpos($clean, '..') !== false) {
        return '';
    }

    return $prefix . $clean;
}

/* Apaga imagem principal e eventuais miniaturas associadas dentro de ../cashfy-uploads/<sub>/ */
function cashfy_delete_image_variants(?string $db_path, string $sub): void
{
    $rel = cashfy_normalize_upload_path($db_path);

    if ($rel === null || strpos($rel, $sub . '/') !== 0) {
        return;
    }

    $root = cashfy_upload_root();
    $main_file = $root . $rel;

    if (is_file($main_file)) {
        @unlink($main_file);
    }

    $stem = pathinfo($rel, PATHINFO_FILENAME);

    if ($stem === '' || strpos($stem, '..') !== false) {
        return;
    }

    foreach (['webp', 'jpg', 'jpeg', 'png', 'gif'] as $ext) {
        $thumb_file = $root . $sub . '/thumbs/' . $stem . '.' . $ext;
        if (is_file($thumb_file)) {
            @unlink($thumb_file);
        }
    }
}

/*
 * Emite as tags centralizadas de favicon e Apple Touch Icon (claro/escuro),
 * compatíveis com subpastas em localhost e domínio raiz na Hostinger.
 */
function cashfy_render_head_icons(string $prefix = ''): string
{
    $p = htmlspecialchars($prefix . 'assets/img/', ENT_QUOTES, 'UTF-8');

    return '<link rel="icon" href="' . $p . 'favicon.ico" sizes="any">' . "\n"
        . '  <link rel="icon" href="' . $p . 'favicon-32x32.png" type="image/png" sizes="32x32" id="icon"'
        . ' data-light-href="' . $p . 'favicon-32x32.png" data-dark-href="' . $p . 'favicon-32x32-dark.png">' . "\n"
        . '  <link rel="icon" href="' . $p . 'favicon-16x16.png" type="image/png" sizes="16x16" id="icon-16"'
        . ' data-light-href="' . $p . 'favicon-16x16.png" data-dark-href="' . $p . 'favicon-16x16-dark.png">' . "\n"
        . '  <link rel="apple-touch-icon" href="' . $p . 'apple-touch-icon.png" sizes="180x180" id="apple-icon"'
        . ' data-light-href="' . $p . 'apple-touch-icon.png" data-dark-href="' . $p . 'apple-touch-icon-dark.png">';
}
