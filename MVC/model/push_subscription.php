<?php
require_once(__DIR__ . '/../config/cashfy.php');
require_once(__DIR__ . '/../config/vapid.php');

function ensure_push_subscriptions_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $conn = conn();
    $sql = "
    CREATE TABLE IF NOT EXISTS `push_subscriptions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `endpoint` text NOT NULL,
      `endpoint_hash` char(64) NOT NULL,
      `p256dh` varchar(255) NOT NULL,
      `auth` varchar(255) NOT NULL,
      `content_encoding` varchar(32) NOT NULL DEFAULT 'aes128gcm',
      `user_agent` varchar(255) DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_push_endpoint_hash` (`endpoint_hash`),
      KEY `idx_push_user_id` (`user_id`),
      CONSTRAINT `fk_push_subscriptions_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    @mysqli_query($conn, $sql);
}

function validate_push_subscription_payload(array $data): ?array
{
    $endpoint = trim((string) ($data['endpoint'] ?? ''));
    $keys = is_array($data['keys'] ?? null) ? $data['keys'] : [];
    $p256dh = trim((string) ($keys['p256dh'] ?? ($data['p256dh'] ?? '')));
    $auth = trim((string) ($keys['auth'] ?? ($data['auth'] ?? '')));
    $encoding = trim((string) ($data['contentEncoding'] ?? 'aes128gcm'));

    if ($endpoint === '' || strlen($endpoint) > 2048) {
        return null;
    }

    $parts = parse_url($endpoint);
    if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
        return null;
    }

    $host = strtolower($parts['host']);
    if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        return null;
    }

    $p256dhRaw = base64url_decode_data($p256dh);
    $authRaw = base64url_decode_data($auth);

    if (strlen($p256dhRaw) !== 65 || $p256dhRaw[0] !== "\x04" || strlen($authRaw) !== 16) {
        return null;
    }

    if (!in_array($encoding, ['aes128gcm', 'aesgcm'], true)) {
        $encoding = 'aes128gcm';
    }

    return [
        'endpoint' => $endpoint,
        'endpoint_hash' => hash('sha256', $endpoint),
        'p256dh' => base64url_encode_data($p256dhRaw),
        'auth' => base64url_encode_data($authRaw),
        'content_encoding' => $encoding,
    ];
}

function save_push_subscription(int $user_id, array $subscriptionData, ?string $userAgent = null): bool
{
    ensure_push_subscriptions_table();

    $valid = validate_push_subscription_payload($subscriptionData);
    if (!$valid || $user_id <= 0) {
        return false;
    }

    $conn = conn();
    $ua = $userAgent !== null ? mb_substr(trim($userAgent), 0, 255, 'UTF-8') : null;

    $stmt = $conn->prepare("
        INSERT INTO push_subscriptions (user_id, endpoint, endpoint_hash, p256dh, auth, content_encoding, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            endpoint = VALUES(endpoint),
            p256dh = VALUES(p256dh),
            auth = VALUES(auth),
            content_encoding = VALUES(content_encoding),
            user_agent = VALUES(user_agent),
            updated_at = CURRENT_TIMESTAMP
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'issssss',
        $user_id,
        $valid['endpoint'],
        $valid['endpoint_hash'],
        $valid['p256dh'],
        $valid['auth'],
        $valid['content_encoding'],
        $ua
    );

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function delete_push_subscription_by_endpoint(string $endpoint, ?int $user_id = null): bool
{
    ensure_push_subscriptions_table();

    $endpoint = trim($endpoint);
    if ($endpoint === '') {
        return false;
    }

    $hash = hash('sha256', $endpoint);
    $conn = conn();

    if ($user_id !== null) {
        $stmt = $conn->prepare("DELETE FROM push_subscriptions WHERE endpoint_hash = ? AND user_id = ?");
        $stmt->bind_param('si', $hash, $user_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM push_subscriptions WHERE endpoint_hash = ?");
        $stmt->bind_param('s', $hash);
    }

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function get_user_push_subscriptions(int $user_id): array
{
    ensure_push_subscriptions_table();

    $conn = conn();
    $stmt = $conn->prepare("
        SELECT id, user_id, endpoint, p256dh, auth, content_encoding
        FROM push_subscriptions
        WHERE user_id = ?
        ORDER BY id DESC
    ");
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function ecdsa_der_to_raw_rs(string $der): ?string
{
    $len = strlen($der);
    if ($len < 8 || $der[0] !== "\x30") {
        return null;
    }

    $offset = 2;
    if ((ord($der[1]) & 0x80) !== 0) {
        $offset += (ord($der[1]) & 0x7f);
    }

    if ($offset >= $len || $der[$offset] !== "\x02") {
        return null;
    }
    $rLen = ord($der[$offset + 1]);
    $r = substr($der, $offset + 2, $rLen);
    $offset += 2 + $rLen;

    if ($offset >= $len || $der[$offset] !== "\x02") {
        return null;
    }
    $sLen = ord($der[$offset + 1]);
    $s = substr($der, $offset + 2, $sLen);

    $r = ltrim($r, "\x00");
    $s = ltrim($s, "\x00");

    if (strlen($r) > 32 || strlen($s) > 32) {
        return null;
    }

    return str_pad($r, 32, "\x00", STR_PAD_LEFT) . str_pad($s, 32, "\x00", STR_PAD_LEFT);
}

function create_vapid_authorization_header(string $endpoint, array $vapid): ?string
{
    if (empty($vapid['private_pem']) || empty($vapid['public_key'])) {
        return null;
    }

    $parsed = parse_url($endpoint);
    if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
        return null;
    }

    $audience = $parsed['scheme'] . '://' . $parsed['host']
        . (!empty($parsed['port']) ? ':' . $parsed['port'] : '');

    $header = base64url_encode_data(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $claims = base64url_encode_data(json_encode([
        'aud' => $audience,
        'exp' => time() + 43200,
        'sub' => $vapid['subject'],
    ], JSON_UNESCAPED_SLASHES));

    $signingInput = $header . '.' . $claims;
    $derSignature = '';

    $privKey = @openssl_pkey_get_private($vapid['private_pem']);
    if ($privKey === false) {
        return null;
    }

    if (!@openssl_sign($signingInput, $derSignature, $privKey, OPENSSL_ALGO_SHA256)) {
        return null;
    }

    $rawRs = ecdsa_der_to_raw_rs($derSignature);
    if ($rawRs === null) {
        return null;
    }

    $jwt = $signingInput . '.' . base64url_encode_data($rawRs);

    return 'vapid t=' . $jwt . ', k=' . $vapid['public_key'];
}

function hkdf_sha256(string $salt, string $ikm, string $info, int $length): string
{
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $okm = hash_hmac('sha256', $info . "\x01", $prk, true);
    return substr($okm, 0, $length);
}

function encrypt_web_push_payload(string $payload, string $userPublicKeyBase64Url, string $userAuthBase64Url): ?string
{
    $clientPubRaw = base64url_decode_data($userPublicKeyBase64Url);
    $clientAuth = base64url_decode_data($userAuthBase64Url);

    if (strlen($clientPubRaw) !== 65 || $clientPubRaw[0] !== "\x04" || strlen($clientAuth) !== 16) {
        return null;
    }

    $args = [
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ];
    $conf = cashfy_find_openssl_config();
    if ($conf !== null) {
        $args['config'] = $conf;
    }

    $localKey = @openssl_pkey_new($args);
    if ($localKey === false) {
        return null;
    }

    $localDetails = openssl_pkey_get_details($localKey);
    if (empty($localDetails['ec']['x']) || empty($localDetails['ec']['y'])) {
        return null;
    }

    $localPubRaw = "\x04"
        . str_pad($localDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT)
        . str_pad($localDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);

    $clientPubPem = cashfy_ec_raw_to_public_pem($clientPubRaw);
    $clientPubKey = @openssl_pkey_get_public($clientPubPem);
    if ($clientPubKey === false) {
        return null;
    }

    $sharedSecret = @openssl_pkey_derive($clientPubKey, $localKey, 256);
    if ($sharedSecret === false || strlen($sharedSecret) === 0) {
        return null;
    }
    $sharedSecret = str_pad($sharedSecret, 32, "\x00", STR_PAD_LEFT);

    $keyInfo = "WebPush: info\x00" . $clientPubRaw . $localPubRaw;
    $ikm = hkdf_sha256($clientAuth, $sharedSecret, $keyInfo, 32);

    $salt = random_bytes(16);
    $cek = hkdf_sha256($salt, $ikm, "Content-Encoding: aes128gcm\x00", 16);
    $nonce = hkdf_sha256($salt, $ikm, "Content-Encoding: nonce\x00", 12);

    $plaintext = $payload . "\x02";
    $tag = '';
    $ciphertext = @openssl_encrypt(
        $plaintext,
        'aes-128-gcm',
        $cek,
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        '',
        16
    );

    if ($ciphertext === false || strlen($tag) !== 16) {
        return null;
    }

    $header = $salt . pack('N', 4096) . chr(65) . $localPubRaw;

    return $header . $ciphertext . $tag;
}

function send_web_push_to_subscription(array $sub, string $jsonPayload, array $vapid): int
{
    if (!function_exists('curl_init')) {
        error_log('CashFY WebPush: extensão cURL não habilitada.');
        return 0;
    }

    $endpoint = (string) $sub['endpoint'];
    $authHeader = create_vapid_authorization_header($endpoint, $vapid);
    if ($authHeader === null) {
        return 0;
    }

    $encryptedBody = encrypt_web_push_payload($jsonPayload, (string) $sub['p256dh'], (string) $sub['auth']);
    if ($encryptedBody === null) {
        return 0;
    }

    $headers = [
        'Authorization: ' . $authHeader,
        'Content-Encoding: aes128gcm',
        'Content-Type: application/octet-stream',
        'Content-Length: ' . strlen($encryptedBody),
        'TTL: 86400',
        'Urgency: high',
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $encryptedBody,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        error_log('CashFY WebPush cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    if ($status === 404 || $status === 410) {
        delete_push_subscription_by_endpoint($endpoint);
    }

    return $status;
}

function send_push_notification_to_user(int $user_id, array $payload): int
{
    try {
        $subscriptions = get_user_push_subscriptions($user_id);
        if (empty($subscriptions)) {
            return 0;
        }

        $vapid = get_vapid_config();
        if (empty($vapid['public_key']) || empty($vapid['private_pem'])) {
            return 0;
        }

        $defaultPayload = [
            'title' => 'CashFY',
            'body' => 'Você tem uma nova atualização.',
            'url' => BASE_URL . 'MVC/view/myOrders.php',
            'icon' => BASE_URL . 'assets/img/icon-192.png',
            'badge' => BASE_URL . 'assets/img/badge-72.png',
            'tag' => 'cashfy-notification-' . time(),
            'timestamp' => time() * 1000,
        ];

        $jsonPayload = json_encode(array_merge($defaultPayload, $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonPayload === false) {
            return 0;
        }

        $sentCount = 0;
        foreach ($subscriptions as $sub) {
            $status = send_web_push_to_subscription($sub, $jsonPayload, $vapid);
            if ($status >= 200 && $status < 300) {
                $sentCount++;
            }
        }

        return $sentCount;
    } catch (Throwable $e) {
        error_log('CashFY send_push_notification_to_user: ' . $e->getMessage());
        return 0;
    }
}
?>
