<?php
/*
 * Entrega segura e rápida de imagens armazenadas fora do public_html (../cashfy-uploads/).
 *
 * Como cashfy-uploads/ fica no mesmo nível do public_html (acima do DOCUMENT_ROOT na Hostinger),
 * o navegador não pode requisitar ../cashfy-uploads/ diretamente via HTTP.
 * Este endpoint não inicia sessão (sem session lock) nem abre conexão com o banco de dados,
 * valida estritamente o caminho e envia cabeçalhos completos de cache HTTP (ETag, Last-Modified, 304).
 */
require_once __DIR__ . '/../config/init.php';

$raw = (string) ($_GET['file'] ?? '');
$clean = str_replace('\\', '/', trim($raw));

// Aceita caminho interno (perfil/abc.webp) ou com prefixos legados/relativos
$clean = preg_replace('#^(?:\.\./)+#', '', $clean);
$clean = ltrim((string) $clean, '/');

if (strpos($clean, 'cashfy-uploads/') === 0) {
    $clean = substr($clean, strlen('cashfy-uploads/'));
} elseif (strpos($clean, 'uploads/') === 0) {
    $clean = substr($clean, strlen('uploads/'));
}

// Allowlist estrita de subpastas e extensões de imagem
if (
    $clean === ''
    || strpos($clean, "\0") !== false
    || strpos($clean, '..') !== false
    || !preg_match('#^(perfil|products|icones)/(?:thumbs/)?[A-Za-z0-9._-]+\.(webp|jpe?g|png|gif)$#i', $clean, $m)
) {
    http_response_code(404);
    exit;
}

$uploads_root = realpath(CASHFY_UPLOADS_DIR);

if ($uploads_root === false || !is_dir($uploads_root)) {
    http_response_code(404);
    exit;
}

$target = realpath($uploads_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean));
$root_prefix = rtrim($uploads_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

if ($target === false || strpos($target, $root_prefix) !== 0 || !is_file($target) || !is_readable($target)) {
    http_response_code(404);
    exit;
}

$ext = strtolower($m[2]);
$mime_map = [
    'webp' => 'image/webp',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
];
$mime = $mime_map[$ext] ?? 'application/octet-stream';

$mtime = filemtime($target) ?: time();
$size = filesize($target);
$etag = '"' . md5($clean . '|' . $mtime . '|' . $size) . '"';

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=2592000');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);

$if_none_match = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
$if_modified_since = trim((string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? ''));

if (
    ($if_none_match !== '' && ($if_none_match === $etag || $if_none_match === 'W/' . $etag))
    || ($if_modified_since !== '' && strtotime($if_modified_since) >= $mtime)
) {
    http_response_code(304);
    exit;
}

if ($size !== false) {
    header('Content-Length: ' . $size);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
    exit;
}

readfile($target);
exit;
