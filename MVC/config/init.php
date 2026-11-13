<?php
define('BASE_PATH', __DIR__ . '/../../');

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
$cashfy_path = '/activities/CashFY';

if ($cashfy_root && $cashfy_docroot && strpos($cashfy_root, $cashfy_docroot) === 0) {
    $cashfy_path = str_replace('\\', '/', substr($cashfy_root, strlen($cashfy_docroot)));
}

define('BASE_URL', ($cashfy_https ? 'https' : 'http') . '://' . $cashfy_host . rtrim($cashfy_path, '/') . '/');
?>