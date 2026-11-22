<?php
/*
 * Helpers de upload de imagens (foto de perfil e foto de produtos).
 *
 * Feito para hospedagem Linux (Hostinger):
 *  - cria a pasta de destino se ela não existir e confere se dá para gravar nela;
 *  - valida o CONTEÚDO do arquivo (getimagesize), não só o nome/extensão;
 *  - otimiza via PHP GD (priorizando WebP, compressão, redimensionamento e miniatura);
 *  - grava com nome único (nunca sobrescreve o arquivo antigo, então navegador, CDN e
 *    cache da Hostinger nunca servem a foto velha);
 *  - aplica permissão 0644 (o arquivo temporário do PHP nasce 0600 no Linux, e isso pode
 *    deixar a imagem inacessível pelo servidor web; no Windows/XAMPP isso não acontece).
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/image_optimizer.php';

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

/*
 * Valida, otimiza (WebP + redimensionamento + miniatura) e grava em ../cashfy-uploads/<sub>/.
 * Sucesso: devolve o caminho relativo para o banco (ex.: "cashfy-uploads/perfil/ab12cd34ef567890.webp").
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

    $supported_types = [
        IMAGETYPE_JPEG => true,
        IMAGETYPE_PNG => true,
        IMAGETYPE_GIF => true,
        IMAGETYPE_WEBP => true,
    ];

    $info = @getimagesize($tmp);

    if ($info === false || !isset($supported_types[$info[2]])) {
        $error = 'Formato de imagem não suportado. Use JPG, PNG, WEBP ou GIF.';
        return null;
    }

    return cashfy_optimize_uploaded_image($tmp, $info, $sub, $error);
}

/*
 * Apaga do disco a imagem antiga e sua miniatura. Só mexe dentro de ../cashfy-uploads/<sub>/
 * (nunca em outras pastas) e ignora arquivo que não existe, sem gerar warning.
 */
function cashfy_delete_upload(?string $db_path, string $sub): void
{
    cashfy_delete_image_variants($db_path, $sub);
}
