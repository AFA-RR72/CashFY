    <?php session_start();
    require_once("../config/init.php");
    require_once('../model/user.php');

    $user = get_user_by_id($_SESSION['id']);

    if (isset($_POST['delete_profile_photo'])) {
        if (empty($user['profile_photo'])) {
            $_SESSION['msg'] = "Você não tem uma foto de perfil.";
            header("Location: ../view/perfil_photo.php");
        } else {
            $caminho = BASE_PATH . $user['profile_photo'];
            unlink($caminho);
            update_photo($user['id'], null);
            header("Location: ../view/perfil.php");
        }
    } elseif (!empty($user['profile_photo'])) {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK){
            $foto = $_FILES['profile_photo'];
            $caminho = (BASE_PATH . $user['profile_photo']);
            move_uploaded_file($foto['tmp_name'], $caminho);
            $_SESSION['msg'] = "Foto alterada com sucesso.";
            header("Location: ../view/perfil.php");
        } else {
            $_SESSION['msg'] = "Você precisa inserir uma foto.";
            header("Location: ../view/perfil_photo.php");
        }

    } else {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $foto = $_FILES['profile_photo'];
            $nome = uniqid() . '.jpg';
            $caminho = (BASE_PATH . "uploads/perfil/" . $nome);
            move_uploaded_file($foto['tmp_name'], $caminho);

            $caminho_banco = "uploads/perfil/$nome";
            update_photo($user['id'], $caminho_banco);

            $user = get_user_by_id($user['id']);

            $_SESSION['profile_photo'] = $user['profile_photo'];

            $_SESSION['msg'] = "Foto adicionada com sucesso.";
            header("Location: ../view/perfil.php");
        } else {
            $_SESSION['msg'] = "Você precisa inserir uma foto.";
            header("Location: ../view/perfil_photo.php");
        }

    }
    ?>