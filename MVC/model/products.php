<?php require_once(__DIR__ . "/../config/cashfy.php");

function create_product(int $user_id, string $name, int $category, string $description, float $price, string $product_photo){
    $conn = conn();

    $stmt = $conn -> prepare("INSERT INTO products (user_id, name, category_id, description, price, product_photo) VALUE (?, ?, ?, ?, ?, ?)");
    $stmt -> bind_param("isisds", $user_id, $name, $category, $description, $price, $product_photo);

    $stmt -> execute();
    $stmt -> close();
}

function get_products(){
    $conn = conn();

    // Só produtos ativos (deleted_at IS NULL): excluídos logicamente não aparecem em listagens
    $sql = "SELECT * FROM products WHERE deleted_at IS NULL";
    $products = mysqli_query($conn, $sql);

    $result = mysqli_fetch_all($products, MYSQLI_ASSOC);
    
    return $result; 
}

function get_products_by_category(int $category_id){
    $conn = conn();

    $stmt = $conn -> prepare('SELECT * FROM products WHERE category_id = ? AND deleted_at IS NULL');
    $stmt -> bind_param("i", $category_id);

    $stmt -> execute();

    $result = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);

    $stmt -> close();

    return $result;
}

// Por padrão só devolve produto ativo. $include_deleted = true devolve também o excluído
// logicamente (uso restrito a telas/consultas de histórico).
function get_product_by_id(int $id, bool $include_deleted = false){
    $conn = conn();

    $sql = "SELECT * FROM products WHERE id = ?";

    if (!$include_deleted){
        $sql .= " AND deleted_at IS NULL";
    }

    $stmt = $conn -> prepare($sql);
    $stmt -> bind_param('i', $id);

    $stmt -> execute();

    $result = $stmt -> get_result() -> fetch_assoc();

    $stmt -> close();

    return $result;
}

function update_product(int $id, string $name, float $price, int $category_id, string $description){
    $conn = conn();

    $stmt = $conn -> prepare("UPDATE products SET name = ?, price = ?, category_id = ?, description = ? WHERE id = ? AND deleted_at IS NULL");
    $stmt -> bind_param('sdisi', $name, $price, $category_id, $description, $id);

    $stmt -> execute();
    $stmt -> close();
    
    return;
}

/*
 * Exclusão de produto pelo vendedor (só o dono, só produto ativo).
 *   - nunca vendido  -> DELETE definitivo                   (retorna 'deleted')
 *   - já vendido     -> exclusão lógica (deleted_at = NOW()) (retorna 'archived')
 *   - não encontrado / de outro vendedor / já excluído / erro -> false
 *
 * Tudo numa transação, com o registro do produto travado (FOR UPDATE): se um pedido estiver
 * sendo criado para este produto neste instante, ou a venda entra antes (e o produto é só
 * arquivado) ou a exclusão vem antes (e o pedido é recusado pela checagem do create_order).
 * Um produto com histórico de vendas nunca recebe DELETE.
 */
function delete_product(int $id, int $user_id){
    $conn = conn();

    try {
        $conn -> begin_transaction();

        $stmt = $conn -> prepare("SELECT id FROM products WHERE id = ? AND user_id = ? AND deleted_at IS NULL FOR UPDATE");
        $stmt -> bind_param('ii', $id, $user_id);
        $stmt -> execute();

        $found = $stmt -> get_result() -> num_rows > 0;

        $stmt -> close();

        if (!$found){
            $conn -> rollback();
            return false;
        }

        $stmt = $conn -> prepare("SELECT 1 FROM sales WHERE product_id = ? LIMIT 1");
        $stmt -> bind_param('i', $id);
        $stmt -> execute();

        $has_sales = $stmt -> get_result() -> num_rows > 0;

        $stmt -> close();

        if ($has_sales){
            $stmt = $conn -> prepare("UPDATE products SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
            $result = 'archived';
        } else {
            $stmt = $conn -> prepare("DELETE FROM products WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
            $result = 'deleted';
        }

        $stmt -> bind_param('ii', $id, $user_id);
        $stmt -> execute();

        $affected = $stmt -> affected_rows;

        $stmt -> close();

        if ($affected < 1){
            $conn -> rollback();
            return false;
        }

        $conn -> commit();

        return $result;
    } catch (Throwable $e) {
        $conn -> rollback();
        error_log('delete_product: ' . $e -> getMessage());

        return false;
    }
}

function check_product(int $product_id){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM products WHERE id = ? AND deleted_at IS NULL");
    $stmt -> bind_param("i", $product_id);

    $stmt -> execute();

    $result = $stmt -> get_result();
    if($result -> num_rows > 0){
        $stmt -> close();
        return true;
    }else{
        $stmt -> close();
        return false;
    }
}

// Produtos de um vendedor (usado no seletor do gráfico de faturamento).
// Relatório financeiro: INCLUI os excluídos logicamente (deleted_at preenchido), para o
// vendedor continuar vendo o faturamento histórico deles. Não usar em listagens de loja.
function get_products_by_user(int $user_id){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT id, name, deleted_at FROM products WHERE user_id = ? ORDER BY name");
    $stmt -> bind_param('i', $user_id);

    $stmt -> execute();

    $result = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);

    $stmt -> close();

    return $result;
}

// Retorna o produto (id, name) somente se pertencer ao vendedor informado; senão null.
// Sem filtro de deleted_at DE PROPÓSITO: o gráfico de faturamento precisa consultar produtos
// excluídos logicamente que já tiveram vendas.
function get_product_of_user(int $product_id, int $user_id){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT id, name FROM products WHERE id = ? AND user_id = ?");
    $stmt -> bind_param('ii', $product_id, $user_id);

    $stmt -> execute();

    $result = $stmt -> get_result() -> fetch_assoc();

    $stmt -> close();

    return $result ?: null;
}

?>