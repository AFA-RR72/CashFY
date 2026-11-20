<?php require_once(__DIR__ . "/../config/cashfy.php");

function check_email($email){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE email = ?");
    $stmt -> bind_param("s", $email);

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

function create($name, $institute_id, $email, $password, $role){
    $conn = conn();

    $stmt = $conn -> prepare("INSERT INTO users (name, institute_id, email, password, role_id) VALUES (?,?,?,?,?)");
    $stmt -> bind_param("sissi", $name, $institute_id, $email, $password, $role);
    
    $stmt -> execute();
    $stmt -> close();

    return true;
}

function create_seller($name, $institute_id, $email, $contact, $description, $password, $role){
    $conn = conn();

    $stmt = $conn -> prepare("INSERT INTO users (name, institute_id, email, phone_number, description, password, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt -> bind_param("sissssi", $name, $institute_id, $email, $contact, $description, $password, $role);
    
    $stmt -> execute();
    $stmt -> close();

    return true;
}

function get_user_by_email($email){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE email = ?");
    $stmt ->bind_param("s", $email);

    $stmt -> execute();

    $result = $stmt -> get_result();

    $user = $result -> fetch_assoc();

    $stmt -> close();
    return $user;
}

function get_user_by_id($id){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE id = ?");
    $stmt -> bind_param("i", $id);

    $stmt -> execute();

    $result = $stmt -> get_result();
    $user = $result -> fetch_assoc();

    $stmt -> close();
    return $user;
}
function search_user_by_name(string $name)
{
    $conn = conn();

    $name = trim($name);
    $search = "%$name%";
    $start = "$name%";

    $stmt = $conn -> prepare(
        "SELECT * FROM users
        WHERE name LIKE ?
        AND role_id = 2
        ORDER BY
            CASE
                WHEN name = ? THEN 1
                WHEN name LIKE ? THEN 2
                ELSE 3
            END,
            name ASC"
    );

    $stmt -> bind_param("sss", $search, $name, $start);

    $stmt -> execute();

    $result = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);

    $stmt -> close();

    return $result;
}

function get_users(){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM users");
    $stmt -> execute();

    $result = $stmt -> get_result();
    $users = $result -> fetch_all(MYSQLI_ASSOC);

    $stmt ->close();
    return $users;
}

function update_photo(int $id, ?string $profile_photo){
    $conn = conn();

    $stmt = $conn -> prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
    $stmt -> bind_param("si", $profile_photo, $id);

    $stmt -> execute();
    $stmt -> close();
}


function update_user(int $id, string $name, int $institute_id, string $phone_number, string $description, string $email){
    $conn = conn();

    $stmt = $conn -> prepare("UPDATE users SET name = ?, institute_id = ?, phone_number = ?, description = ?, email = ? WHERE id = ?");
    $stmt -> bind_param('sisssi', $name, $institute_id, $phone_number, $description, $email, $id);
    
    $stmt -> execute();
    $stmt -> close();
    return;
}

/**
 * Guarda (ou apaga, com null) o hash do token do "lembrar de mim" do usuário.
 * Nunca se grava o token puro: só o SHA-256 dele (64 caracteres, cabe em varchar(100)).
 */
function set_remember_token(int $user_id, ?string $token_hash): void
{
    $conn = conn();

    $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt->bind_param('si', $token_hash, $user_id);
    $stmt->execute();
    $stmt->close();
}

if (!function_exists('pegarIniciais')) {
    function pegarIniciais(string $frase, array $ignorar = ['de', 'e', 'do', 'da', 'dos', 'das', 'o', 'a', 'com', 'em']): string
    {
        $palavras = preg_split('/\s+/', trim($frase));
        $palavrasValidas = [];

        foreach ($palavras as $palavra) {
            $palavraMinuscula = mb_strtolower($palavra, 'UTF-8');
            if (empty($palavraMinuscula) || in_array($palavraMinuscula, $ignorar, true)) {
                continue;
            }
            $palavrasValidas[] = $palavra;
        }

        $quantidade = count($palavrasValidas);
        if ($quantidade === 0) {
            return '';
        }
        if ($quantidade === 1) {
            return mb_strtoupper(mb_substr($palavrasValidas[0], 0, 1, 'UTF-8'), 'UTF-8');
        }
        if ($quantidade === 2) {
            return mb_strtoupper(
                mb_substr($palavrasValidas[0], 0, 1, 'UTF-8') . mb_substr($palavrasValidas[1], 0, 1, 'UTF-8'),
                'UTF-8'
            );
        }

        return mb_strtoupper(
            mb_substr($palavrasValidas[0], 0, 1, 'UTF-8')
            . mb_substr($palavrasValidas[1], 0, 1, 'UTF-8')
            . mb_substr($palavrasValidas[$quantidade - 1], 0, 1, 'UTF-8'),
            'UTF-8'
        );
    }
}

if (!function_exists('format_phone_number')) {
    function format_phone_number($phone_number): string
    {
        $number = preg_replace('/\D/', '', (string) $phone_number);
        if (strlen($number) === 11) {
            return '(' . substr($number, 0, 2) . ') ' . substr($number, 2, 5) . '-' . substr($number, 7, 4);
        }
        if (strlen($number) === 10) {
            return '(' . substr($number, 0, 2) . ') ' . substr($number, 2, 4) . '-' . substr($number, 6, 4);
        }
        return $number;
    }
}

if (!function_exists('whatsapp_url')) {
    function whatsapp_url(string $phone_number): string
    {
        $number = preg_replace('/\D/', '', $phone_number);
        if (strlen($number) === 10 || strlen($number) === 11) {
            $number = '55' . $number;
        }
        return 'https://wa.me/' . $number;
    }
}
?>
