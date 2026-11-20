<?php
/*
 * Helpers de upload de imagens (foto de perfil e foto de produtos).
 *
 * Feito para hospedagem Linux (Hostinger):
 *  - cria a pasta de destino se ela não existir e confere se dá para gravar nela;
 *  - valida o CONTEÚDO do arquivo (getimagesize), não só o nome/extensão;
 *  - grava com nome único e extensão real (jpg/png/gif/webp). Nunca sobrescreve o arquivo
 *    antigo, então navegador, CDN e cache da Hostinger nunca servem a foto velha;
 *  - aplica permissão 0644 (o arquivo temporário do PHP nasce 0600 no Linux, e isso pode
 *    deixar a imagem inacessível pelo servidor web; no Windows/XAMPP isso não acontece).
 */
require_once __DIR__ . '/init.php';

const CASHFY_MAX_IMAGE_BYTES = 10 * 1024 * 1024; // 10 MB
const CASHFY_MAX_IMAGE_TEXT = 'A imagem é grande demais. Escolha uma foto de até 10 MB.';

function cashfy_upload_error_message(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return CASHFY_MAX_IMAGE_TEXT;
        case UPLOAD_ERR_PARTIAL:
            return 'O envio da imagem foi interrompido. Tente novamente.';
        case UPLOAD_ERR_NO_FILE:
            return 'Você precisa inserir uma foto.';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            return 'O servidor não conseguiu receber a imagem. Tente novamente em instantes.';
        default:
            return 'Não foi possível enviar a imagem. Tente novamente.';
    }
}

/*
 * Quando o corpo da requisição passa de post_max_size, o PHP descarta $_POST e $_FILES
 * inteiros (sem nenhum código de erro). Esta função detecta esse caso.
 */
function cashfy_post_too_large(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && empty($_POST)
        && empty($_FILES)
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function cashfy_upload_root(): string
{
    $root = realpath(BASE_PATH);

    return ($root !== false ? $root : rtrim(BASE_PATH, '/\\')) . '/';
}

/* Garante uploads/<sub>/ existente e gravável. Devolve o caminho da pasta ou null. */
function cashfy_prepare_upload_dir(string $sub): ?string
{
    $dir = cashfy_upload_root() . 'uploads/' . $sub;

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

/*
 * Valida e grava a imagem enviada em uploads/<sub>/.
 * Sucesso: devolve o caminho relativo para o banco (ex.: "uploads/perfil/ab12cd34ef567890.jpg").
 * Falha: devolve null e preenche $error com a mensagem para o usuário.
 */
function cashfy_save_image(array $file, string $sub, ?string &$error = null): ?string
{
    $error = null;
    $code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($code !== UPLOAD_ERR_OK) {
        $error = cashfy_upload_error_message($code);
        return null;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        $error = 'Não foi possível enviar a imagem. Tente novamente.';
        return null;
    }

    if ((int) ($file['size'] ?? 0) > CASHFY_MAX_IMAGE_BYTES) {
        $error = CASHFY_MAX_IMAGE_TEXT;
        return null;
    }

    $extensions = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    $info = @getimagesize($tmp);

    if ($info === false || !isset($extensions[$info[2]])) {
        $error = 'Formato de imagem não suportado. Use JPG, PNG, WEBP ou GIF.';
        return null;
    }

    $dir = cashfy_prepare_upload_dir($sub);

    if ($dir === null) {
        error_log('cashfy upload: pasta uploads/' . $sub . ' ausente ou sem permissão de escrita');
        $error = 'O servidor não conseguiu salvar a imagem. Tente novamente em instantes.';
        return null;
    }

    $name = bin2hex(random_bytes(8)) . '.' . $extensions[$info[2]];
    $destination = $dir . '/' . $name;

    if (!@move_uploaded_file($tmp, $destination)) {
        error_log('cashfy upload: move_uploaded_file falhou para ' . $destination);
        $error = 'O servidor não conseguiu salvar a imagem. Tente novamente em instantes.';
        return null;
    }

    @chmod($destination, 0644);

    return 'uploads/' . $sub . '/' . $name;
}

/*
 * Apaga do disco uma imagem antiga. Só mexe dentro de uploads/<sub>/ (nunca em outras
 * pastas) e ignora arquivo que não existe, sem gerar warning.
 */
function cashfy_delete_upload(?string $db_path, string $sub): void
{
    if (empty($db_path)) {
        return;
    }

    $db_path = str_replace('\\', '/', $db_path);

    if (strpos($db_path, '..') !== false || strpos($db_path, 'uploads/' . $sub . '/') !== 0) {
        return;
    }

    $file = cashfy_upload_root() . $db_path;

    if (is_file($file)) {
        @unlink($file);
    }
}
