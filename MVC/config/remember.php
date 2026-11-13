<?php
/*
 * "Lembrar de mim"
 *
 * Cookie:  cashfy_remember = "<id do usuário>:<token aleatório>"  (HttpOnly, SameSite=Lax, 30 dias)
 * Banco:   users.remember_token guarda só o SHA-256 do token.
 * Cada login com a caixa marcada gera um token novo; sair da conta apaga o token do banco e o cookie.
 */

const REMEMBER_COOKIE = 'cashfy_remember';
const REMEMBER_DAYS = 30;

function remember_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function clear_remember_cookie(): void
{
    setcookie(REMEMBER_COOKIE, '', remember_cookie_options(time() - 3600));
    unset($_COOKIE[REMEMBER_COOKIE]);
}

function remember_user(int $user_id): void
{
    require_once(__DIR__ . '/../model/user.php');

    $token = bin2hex(random_bytes(32));

    set_remember_token($user_id, hash('sha256', $token));

    setcookie(
        REMEMBER_COOKIE,
        $user_id . ':' . $token,
        remember_cookie_options(time() + REMEMBER_DAYS * 86400)
    );
}

function forget_user(?int $user_id = null): void
{
    require_once(__DIR__ . '/../model/user.php');

    if ($user_id) {
        set_remember_token($user_id, null);
    }

    clear_remember_cookie();
}

/**
 * Se não há sessão mas existe um cookie válido, entra na conta automaticamente.
 * Chamada em auth.php, logo depois do session_start().
 */
function login_from_remember_cookie(): void
{
    if (!empty($_SESSION['id']) || empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }

    $parts = explode(':', (string) $_COOKIE[REMEMBER_COOKIE], 2);

    if (count($parts) !== 2 || !ctype_digit($parts[0]) || $parts[1] === '') {
        clear_remember_cookie();
        return;
    }

    require_once(__DIR__ . '/../model/user.php');

    $user = get_user_by_id((int) $parts[0]);

    if (
        $user
        && !empty($user['remember_token'])
        && hash_equals($user['remember_token'], hash('sha256', $parts[1]))
    ) {
        session_regenerate_id(true);
        $_SESSION['id'] = $user['id'];
        return;
    }

    clear_remember_cookie();
}
