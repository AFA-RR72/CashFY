<?php
define('BASE_PATH', __DIR__ . '/../../');

/*
 * Pasta externa de uploads no mesmo nível do public_html (ao lado de cashfy-private):
 * a partir do index.php corresponde a ../cashfy-uploads/ (dirname(__DIR__, 3) . '/cashfy-uploads').
 */
$cashfy_parent = realpath(__DIR__ . '/../../../');
if ($cashfy_parent === false) {
    $cashfy_parent = dirname(__DIR__, 3);
}
define('CASHFY_UPLOADS_DIR', rtrim(str_replace('\\', '/', $cashfy_parent), '/') . '/cashfy-uploads');

/*
 * BASE_URL acompanha o endereço pelo qual o site foi aberto (localhost, IP da rede, domínio
 * ou túnel HTTPS). Antes era fixo em http://localhost/..., o que quebrava redirecionamentos,
 * o SSE e o Service Worker quando o site era aberto pelo celular.
 */
$cashfy_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$cashfy_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$cashfy_root = realpath(__DIR__ . '/../../');
$cashfy_docroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$cashfy_path = '/public_html/';

if ($cashfy_root && $cashfy_docroot && strpos($cashfy_root, $cashfy_docroot) === 0) {
    $cashfy_path = str_replace('\\', '/', substr($cashfy_root, strlen($cashfy_docroot)));
}

define('BASE_URL', ($cashfy_https ? 'https' : 'http') . '://' . $cashfy_host . rtrim($cashfy_path, '/') . '/');
?>
