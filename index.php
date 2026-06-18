<?php require_once __DIR__ . "/MVC/config/init.php";
require_once BASE_PATH . "config/auth.php"; ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CASHFY</title>
    <link rel="stylesheet" href="MVC/view/index.css">
</head>

<body>
    <div class="header">
        <div class="foto">
            <img src="icones/ariana garnde.jpg" alt="ariana">
            <h1>CASHFY</h1>

        </div>
        <div class="menu">
            <a href="contato.html">contato</a>

            <a href="sobre.html">sobre</a>

            <a href="MVC/controller/log-out.php"> <?php if (isset($_SESSION['user_id'])){echo "encerrar sessão"; } ?></a>

            <?php
            if (isset($_SESSION['log-out_msg'])) {
                echo '
                <div class="box">
                    <p>' . htmlspecialchars($_SESSION['log-out_msg']) . '</p>
                    <a href="MVC/controller/log-out.php?log-out=1">Sim</a>
                    <a href="index.php">Não</a>
                </div>
                ';
                unset($_SESSION['log-out_msg']);
            }
            ?>

            <a href="MVC/view/log-in.php">vender aqui</a>


        </div>
    </div>


    <div class="pesquisa">
        <form class="pesquisa">
            <input type="search" name="pesquisa" placeholder="pesquise">
            <button type="submit">pesquisar</button>
        </form>
    </div>

    <div class="categorias">

        <h2>CATEGORIAS</h2>

        <div class="produto">


            <div class="card">
                <img src="icones/download (1).jpg" alt="pink" width="150" height="150">
                <h4>título</h4>
                <p>bio do produto</p>
            </div>

            <div class="card">
                <img src="icones/download (1).jpg" alt="pink" width="150" height="150">
                <h4>título</h4>
                <p>bio do produto</p>
            </div>

            <div class="card">
                <img src="icones/download (1).jpg" alt="pink" width="150" height="150">
                <h4>título</h4>
                <p>bio do produto</p>
            </div>

            <div class="card">
                <img src="icones/download (1).jpg" alt="pink" width="150" height="150">
                <h4>título</h4>
                <p>bio do produto</p>
            </div>

            <div class="card">
                <img src="icones/download (1).jpg" alt="pink" width="150" height="150">
                <h4>título</h4>
                <p>bio do produto</p>
            </div>

        </div>
    </div>

    <div class="vendedores">

        <h2>VENDEDORES</h2>

        <div class="card-vendedor">
            <img src="icones/pink-panther-ess-pink-pantheress.png" alt="Vendedor">

            <div class="info">
                <h3><?php
                if (isset($_SESSION['name'])) {
                    echo htmlspecialchars($_SESSION['name']);
                } else {
                    echo "Nome";
                }
                ?></h3>
                <p>Descrição etc etc</p>
                <button class="botaocomprar">COMPRAR</button>
            </div>
        </div>

        <div class="card-vendedor">
            <img src="icones/pink-panther-ess-pink-pantheress.png" alt="Vendedor">

            <div class="info">
                <h3>Nome</h3>
                <p>Descrição etc etc</p>
                <button class="botaocomprar">COMPRAR</button>
            </div>
        </div>

    </div>
</body>

</html>