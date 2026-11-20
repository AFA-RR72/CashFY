<?php
require_once('../config/auth.php');   // session_start + login pelo cookie "lembrar de mim"
require_once('../config/upload.php');

check_login();

/* Guarda o aviso na sessão e volta para a página indicada */
function photo_back(string $page, string $text, string $type): void
{
    $_SESSION['photo_notice'] = ['type' => $type, 'text' => $text];
    header('Location: ../view/' . $page);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/perfil_photo.php');
    exit;
}

$user = get_user_by_id((int) $_SESSION['id']);

if (!$user) {
    header('Location: ../view/login.php');
    exit;
}

$had_photo = !empty($user['profile_photo']);

/* ---------- Excluir foto ---------- */
if (isset($_POST['delete_profile_photo'])) {
    if (!$had_photo) {
        photo_back('perfil_photo.php', 'Você não tem uma foto de perfil.', 'error');
    }

    update_photo((int) $user['id'], null);
    cashfy_delete_upload($user['profile_photo'], 'perfil');

    photo_back('perfil.php', 'Foto removida com sucesso.', 'success');
}

/* ---------- Adicionar / alterar foto ---------- */
if (cashfy_post_too_large()) {
    photo_back('perfil_photo.php', CASHFY_MAX_IMAGE_TEXT, 'error');
}

$file = $_FILES['profile_photo'] ?? null;

if ($file === null || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
    photo_back('perfil_photo.php', 'Você precisa inserir uma foto.', 'error');
}

$new_photo = cashfy_save_image($file, 'perfil', $upload_error);

if ($new_photo === null) {
    photo_back('perfil_photo.php', $upload_error, 'error');
}

try {
    update_photo((int) $user['id'], $new_photo);
} catch (Throwable $e) {
    cashfy_delete_upload($new_photo, 'perfil');
    error_log('perfil_photo: ' . $e->getMessage());
    photo_back('perfil_photo.php', 'Não foi possível salvar a foto. Tente novamente.', 'error');
}

// A foto antiga só sai do disco depois que a nova já está salva no banco
if ($had_photo) {
    cashfy_delete_upload($user['profile_photo'], 'perfil');
}

photo_back('perfil.php', $had_photo ? 'Foto alterada com sucesso.' : 'Foto adicionada com sucesso.', 'success');
