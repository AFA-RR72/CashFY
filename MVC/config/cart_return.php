<?php require_once(__DIR__ . '/init.php');

/*
 * Voltar para a página de onde o carrinho foi usado (index, categoria ou seller).
 *
 * O formulário do carrinho envia a URL da página atual em "return". Aqui ela é
 * validada: só as 3 páginas que têm carrinho são aceitas, e o redirecionamento é
 * sempre montado em cima de BASE_URL (nunca vai para um endereço vindo do usuário).
 */

function cart_current_page(): string
{
    return $_SERVER['REQUEST_URI'] ?? '';
}

function cart_return_url(string $raw, string $fallback): string
{
    $pages = [
        'index.php' => 'index.php',
        'categoria.php' => 'MVC/view/categoria.php',
        'seller.php' => 'MVC/view/seller.php',
    ];

    $path = (string) parse_url($raw, PHP_URL_PATH);
    $name = ($path === '' || substr($path, -1) === '/') ? 'index.php' : basename($path);

    if ($raw === '' || !isset($pages[$name])) {
        return $fallback;
    }

    parse_str((string) parse_url($raw, PHP_URL_QUERY), $query);

    // O modal do produto não deve reabrir ao voltar
    unset($query['product']);

    $url = BASE_URL . $pages[$name];

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

?>