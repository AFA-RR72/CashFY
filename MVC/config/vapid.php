<?php
require_once(__DIR__ . '/init.php');

/*
 * Configuração VAPID (RFC 8292) para Web Push Notifications.
 *
 * Prioridade de carregamento:
 *   1. Variáveis de ambiente: CASHFY_VAPID_PUBLIC_KEY, CASHFY_VAPID_PRIVATE_KEY, CASHFY_VAPID_SUBJECT
 *   2. Arquivo local ignorado no versionamento: MVC/config/vapid_keys.local.php
 *   3. Geração automática de chaves P-256 (prime256v1) na primeira execução
 */

function base64url_encode_data(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode_data(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded === false ? '' : $decoded;
}

function cashfy_find_openssl_config(): ?string
{
    $envConf = getenv('OPENSSL_CONF');
    if ($envConf && is_file($envConf)) {
        return $envConf;
    }

    $candidates = [
        'C:/xampp/apache/conf/openssl.cnf',
        'C:/xampp/php/extras/ssl/openssl.cnf',
        '/etc/ssl/openssl.cnf',
        '/etc/pki/tls/openssl.cnf',
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function cashfy_ec_raw_to_private_pem(string $privateKey32, string $publicKey65): string
{
    $der = "\x30\x77"
        . "\x02\x01\x01"
        . "\x04\x20" . $privateKey32
        . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"
        . "\xa1\x44\x03\x42\x00" . $publicKey65;

    return "-----BEGIN EC PRIVATE KEY-----\n"
        . chunk_split(base64_encode($der), 64, "\n")
        . "-----END EC PRIVATE KEY-----\n";
}

function cashfy_ec_raw_to_public_pem(string $publicKey65): string
{
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $publicKey65;

    return "-----BEGIN PUBLIC KEY-----\n"
        . chunk_split(base64_encode($der), 64, "\n")
        . "-----END PUBLIC KEY-----\n";
}

function cashfy_generate_vapid_keys(): array
{
    $args = [
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ];
    $conf = cashfy_find_openssl_config();
    if ($conf !== null) {
        $args['config'] = $conf;
    }

    $key = @openssl_pkey_new($args);
    if ($key !== false) {
        $details = openssl_pkey_get_details($key);
        if (!empty($details['ec']['x']) && !empty($details['ec']['y']) && !empty($details['ec']['d'])) {
            $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
            $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
            $d = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);
            $pubRaw = "\x04" . $x . $y;

            return [
                'public_key' => base64url_encode_data($pubRaw),
                'private_key' => base64url_encode_data($d),
                'private_pem' => cashfy_ec_raw_to_private_pem($d, $pubRaw),
            ];
        }
    }

    throw new RuntimeException('Não foi possível gerar chaves EC P-256. Verifique a extensão openssl e o arquivo openssl.cnf.');
}

function get_vapid_config(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $localFile = __DIR__ . '/vapid_keys.local.php';
    $localConfig = is_file($localFile) ? (require $localFile) : [];

    $subject = getenv('CASHFY_VAPID_SUBJECT')
        ?: ($localConfig['subject'] ?? 'mailto:cashfy@gmail.com');
    $publicKey = getenv('CASHFY_VAPID_PUBLIC_KEY')
        ?: ($localConfig['public_key'] ?? '');
    $privateKey = getenv('CASHFY_VAPID_PRIVATE_KEY')
        ?: ($localConfig['private_key'] ?? '');

    if ($publicKey === '' || $privateKey === '') {
        try {
            $generated = cashfy_generate_vapid_keys();
            $publicKey = $generated['public_key'];
            $privateKey = $generated['private_key'];

            $export = "<?php\nreturn " . var_export([
                'subject' => $subject,
                'public_key' => $publicKey,
                'private_key' => $privateKey,
            ], true) . ";\n";

            @file_put_contents($localFile, $export, LOCK_EX);
        } catch (Throwable $e) {
            error_log('CashFY VAPID keygen: ' . $e->getMessage());
            return [
                'subject' => $subject,
                'public_key' => '',
                'private_key' => '',
                'private_pem' => '',
            ];
        }
    }

    $pubBytes = base64url_decode_data($publicKey);
    $privBytes = base64url_decode_data($privateKey);

    $privatePem = '';
    if (strlen($privBytes) === 32 && strlen($pubBytes) === 65 && $pubBytes[0] === "\x04") {
        $privatePem = cashfy_ec_raw_to_private_pem($privBytes, $pubBytes);
    }

    $cached = [
        'subject' => $subject,
        'public_key' => $publicKey,
        'private_key' => $privateKey,
        'private_pem' => $privatePem,
    ];

    return $cached;
}
?>
