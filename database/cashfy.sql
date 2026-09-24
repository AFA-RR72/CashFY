-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/09/2026 às 03:31
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `cashfy`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `icon` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `category`
--

INSERT INTO `category` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Comida', 'comida', '<i class=\"fa-solid fa-bowl-food\"></i>'),
(2, 'Artesanato', 'artesanato', '<i class=\"fa-solid fa-paint-roller\"></i>'),
(3, 'Doces e Sobremesas', 'doces-e-sobremesas', '<i class=\"fa-solid fa-ice-cream\"></i>'),
(4, 'Plantas e Mudas', 'plantas-e-mudas', '<i class=\"fa-solid fa-leaf\"></i>'),
(5, 'Bebidas', 'bebidas', '<i class=\"fa-solid fa-martini-glass\"></i>'),
(6, 'Papelaria', 'papelaria', '<i class=\"fa-solid fa-stapler\"></i>'),
(7, 'Acessórios', 'acessorios', '<i class=\"fa-regular fa-gem\"></i>'),
(8, 'Roupas', 'roupas', '<i class=\"fa-solid fa-shirt\"></i>');

-- --------------------------------------------------------

--
-- Estrutura para tabela `educational_institute`
--

CREATE TABLE `educational_institute` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `educational_institute`
--

INSERT INTO `educational_institute` (`id`, `name`) VALUES
(1, 'IFRR'),
(3, 'SESI'),
(4, 'UERR'),
(2, 'UFRR');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','completed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `permissions`
--

INSERT INTO `permissions` (`id`, `name`) VALUES
(1, 'create_product'),
(2, 'update_product'),
(3, 'delete_product'),
(4, 'manage_users');

-- --------------------------------------------------------

--
-- Estrutura para tabela `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `price` float NOT NULL,
  `product_photo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `products`
--

INSERT INTO `products` (`id`, `user_id`, `name`, `category_id`, `description`, `price`, `product_photo`) VALUES
(18, 53, 'Reptiliano', 4, 'Reptiliano bem cuidado, brevemente goonado.', 97.67, 'uploads/products/6aa40059af9f2.jpg'),
(20, 53, 'olho aberto', 5, 'addsf avgrghgfjhjghrgfegbtgfbn dtbfgh', 22, 'uploads/products/6aa4019f76366.jpg'),
(21, 53, 'olho fechado', 8, 'OOOOOOOOLLLLLLLho FEchadoOOOOOOOOLLLLLLLho FEchadoOOOOOOOOLLLLLLLho FEchadoOOOOOOOOLLLLLLLho FEchadoOOOOOOOOLLLLLLLho FEchadoOOOOOOOOLLLLLLLho FEchadoOOOOOOOOLLLLLLLho FEchadoOOOOOOOOLLLLLLLho FEchado', 0.99, 'uploads/products/6aa40478a0bd8.jpg'),
(25, 53, 'anita', 7, 'aaaaniiitaaaaaaaaaa', 12, 'uploads/products/6aa7ed9bbdea4.jpg');

-- --------------------------------------------------------

--
-- Estrutura para tabela `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'seller'),
(3, 'client');

-- --------------------------------------------------------

--
-- Estrutura para tabela `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permissions_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permissions_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(2, 1),
(2, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` float NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `sales`
--

INSERT INTO `sales` (`id`, `user_id`, `product_id`, `quantity`, `price`, `date`) VALUES
(1, 53, 18, 2, 99.57, '2026-09-09'),
(2, 53, 18, 4, 122, '2026-09-10'),
(3, 53, 25, 5, 12, '2026-09-15'),
(4, 53, 21, 1, 0.99, '2026-09-16'),
(5, 53, 20, 5, 21, '2026-09-15'),
(6, 53, 18, 1, 99.57, '2026-09-15'),
(7, 53, 20, 1, 22, '2026-09-15'),
(8, 53, 18, 1, 99.57, '2026-09-15'),
(9, 53, 25, 1, 12, '2026-09-15'),
(10, 53, 21, 1, 0.99, '2026-09-15'),
(11, 53, 21, 1, 0.99, '2026-09-15'),
(12, 53, 20, 1, 22, '2026-08-10'),
(13, 53, 20, 1, 22, '2026-09-11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `institute_id` int(11) NOT NULL,
  `phone_number` varchar(16) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `institute_id`, `phone_number`, `description`, `email`, `password`, `profile_photo`, `role_id`, `remember_token`) VALUES
(53, 'Andrei Freire de Almeida', 1, '(95) 9 8113-8069', 'auraauraauraauraauraaura', 'andreifalmeida08@gmail.com', '$2y$10$6WwC9p8895wuAHQZj1nSquTrV3fd.Pfp7EzXob7l8qZx5bfqdG93u', 'uploads/perfil/6a8138ff6a1fa.jpg', 2, NULL),
(54, 'Lenalinda', 4, NULL, NULL, 'lena@email.com', '$2y$10$6WwC9p8895wuAHQZj1nSquTrV3fd.Pfp7EzXob7l8qZx5bfqdG93u', NULL, 3, NULL),
(55, 'Carlos Henrique', 3, '95991535938', 'Carlos Henrique, vendedor de xp do fortnite', 'carlos@email.com', '$2y$10$joXcvHldaNoTljNbFfyVT.Zimx2Ac.Ntl66wLxiUk1Clbz6IMi2Ua', 'uploads/perfil/6a9bc33ea138f.jpg', 2, NULL),
(56, 'Mateus Costa de Sousa', 1, NULL, NULL, 'mininolocoiala@gmail.com', '$2y$10$J2IzklEz17HNSPG1Obn2fOdUt2VxTz0eK5R7VxOFO9jU.Wb75YGsO', NULL, 3, NULL),
(57, 'Maria', 3, '95991111111', 'Eu sou a Maria Lara. meu nome é Maria e o meu outro nome é Lara Valentina. Eu sou super responsável, e me chamo Maria Lara Valentina.', 'maria@email.com', '$2y$10$Gp43f4laZ7uGJKnDO11oNOA/7V9cObjslAtk2pGZAfW503hsh/WzS', 'uploads/perfil/6a9c3e996b671.jpg', 2, NULL),
(58, 'José Henrique', 3, '95991761788', 'czksfvzuksbfdlshbfxcvzzxczxzxczcx', 'jose@email.com', '$2y$10$faTw4nXgA/98b7hM3t30ju3lowWt..VsDFs/2o3rAhtp0TTOPsG4S', 'uploads/perfil/6a7f7b06e7244.jpg', 2, NULL),
(61, 'Andrei Almeida', 4, '95981138069', 'aaaaaannnnnnnddddddddddrrrrrrrrreeeeeeeeeeeeeiiiiiiiiiiiii', 'andreifalmeida08@hotmail.com', '$2y$10$mxzr.Zvq7ItiWAyTLEouUeI7hLNqgBAFPSjqvRRz0TKR27rH5CcMa', 'uploads/perfil/6a9c486974933.jpg', 2, NULL),
(62, 'Elvis Gabriel Philips', 2, NULL, NULL, 'elvis@email.com', '$2y$10$aiIyoDL/x2BJBu8lwq4Rpeb6nY7blLNRt14IrGQU.JOqkO51GjbeG', NULL, 3, NULL),
(63, 'gabi', 4, NULL, NULL, 'gabi@|email.com', '$2y$10$.zfri4CXqW3ZCzK5zY4cXuXVq8i5AaHduWRKs61.roKDPWUFIfj3u', NULL, 3, NULL),
(64, 'Samuel', 4, NULL, NULL, 'samuel@email.com', '$2y$10$NqhuiHzkukgCI1WyWKAI4ORtVPeYRkwGL7saaMgv5R8.WyaSWaYue', NULL, 3, NULL),
(65, 'Maiana', 4, NULL, NULL, 'maiana@email.com', '$2y$10$SqfwmSlY6UYtbv/RENneYOT6Y/bsXXaYU3iF.jGchLJzHUr0CLeoO', 'uploads/perfil/6a807b27101ce.jpg', 3, NULL),
(66, 'Raul Oliveira', 1, NULL, NULL, 'raul@email.com', '$2y$10$jwWHM00zoAbjl9hTjSvDjeYhEQTQ47YL5EzV/hhsDWGIQguZP5.o.', NULL, 3, NULL),
(67, 'Mateus Costa de Sousa', 1, '95999999999', '12345678910111213141511617', 'mateus@email.com', '$2y$10$xLwRTKBxSwIYdUvQRAZVf.tiOTzw0VDBo.Wj2yj53am7O2gIBFeF2', 'uploads/perfil/6a9c4898e1806.jpg', 2, NULL),
(68, 'Bruna', 1, '2147483647', 'Sou socialista, narcisista e progressista. Homossexual.', 'bruna@email.com', '$2y$10$r/h5VucHp8zpyZlmIWOoZuDC8lIv/f7JgRa2SQwmioeVsY//VKJda', 'uploads/perfil/6a9c492919422.jpg', 2, NULL),
(69, 'Sofia', 1, '2147483647', 'Sou Sofia, vendo obrigados, eu amo tocaar gazela.', 'sofia@email.com', '$2y$10$eoLV2Xa//H8cbMRK1fkTYurLrzSGG8BV6wZKr/9qmVdWLraA4Ggqq', 'uploads/perfil/6a9c4951662b9.jpg', 2, NULL),
(70, 'Gabriel Monteiro', 3, '95991635119', 'Vendo Legho No-injgsio por 300 reais', 'gabriel@email.com', '$2y$10$s8nheRFUyTLCLCfhLiZaLO/2m6kpR3evlw0Xx7ZpRkE09yHy8Khje', 'uploads/perfil/6a9c4986c22d7.jpg', 2, NULL),
(71, 'Neyglan', 1, NULL, NULL, 'neyglan@email.com', '$2y$10$.rk7Op//aLS70N42OLHNQux6aoHXY9xNENRng0GcXERnJSjtj.Z8y', NULL, 3, NULL),
(72, 'Ídio', 1, '95981118381', 'Basqueteball (quando der). Gostosinho. Cross-Over = Cruzar Por cima das perna. Cross = Cruzar, Over = Por cima das perna', 'idio@email.com', '$2y$10$JtdF4Inn.1WkYRcHY54g9eZRBg9HiVKrpSjsyGeENYdgw6OENJKny', 'uploads/perfil/6a9c49f4c33e9.jpg', 2, NULL),
(73, 'Maria Leandra', 3, '95991761788', '123214341242553232153135122412432', 'marialeandra@email.com', '$2y$10$2SF8vIh1En22av1T5rWfHOrkWVyUwS6OOkJvRL/B8rsmp8OV2aeJq', 'uploads/perfil/6a9c49c470059.jpg', 2, NULL),
(74, 'Filipe', 1, '95981211347', '012345678901234567890123456789', 'filipe@email.com', '$2y$10$HAVuRPnBPPR/6LDEk0HWn.O3A4Y.oKE3qlZQqzeiBUDGXHG8otYU6', 'uploads/perfil/6a9c4a3797a70.jpg', 2, NULL),
(75, 'Felipe', 3, '95999999999', 'zsdfscdgvh jbghcgdhnhncbjvhxvgbnbbgbhvvbhnbd hnjxhjc', 'felipe@email.com', '$2y$10$xosD/ThJlVPk7FV91z8fAehGf7RfRUyi4rYIA26U2shjGwupStHGC', 'uploads/perfil/6a9c3f036b677.jpg', 2, NULL),
(76, 'teste', 1, '0', 'Teste testado e aprovado', 'teste@email.com', '$2y$10$w95y9iR.3omPFOAC8T/PS.TELSSXHAXoyEitY95cXsTOal6qBee9a', 'uploads/perfil/6aa621d7b54f2.jpg', 2, NULL),
(77, 'carlos henrique souza ferreira', 1, '95991535938', 'filho da dona da choquei, apresentador do bbb, figurante de chiquititas', 'carlos13henrique2008@gmail.com', '$2y$10$V6SZ4mLXAtGsWHsRYCgTROzChoo15d6hUHLPAXxV9hC9UVXAuFkwq', 'uploads/perfil/6aad58ab63a29.jpg', 2, NULL),
(78, 'cliente', 3, NULL, NULL, 'cliente@email.com', '$2y$10$7sy2yCjjpR3Sa79Umri2qett/Jx8wkzmcILJWjuM09b9TZPui/XTq', NULL, 3, NULL),
(79, 'vendedor', 3, '99999999999', 'Vendedor, vendedor clássico do cashfy', 'vendedor@email.com', '$2y$10$m5c.G4SsEETajSnBakqHGORX2LcVGMTTDfWpGqeaUcQCY3Hh4dWP2', 'uploads/perfil/6ab0029d017f0.jpg', 2, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Índices de tabela `educational_institute`
--
ALTER TABLE `educational_institute`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices de tabela `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Índices de tabela `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Índices de tabela `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_product` (`user_id`),
  ADD KEY `fk_ctagory` (`category_id`);

--
-- Índices de tabela `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD KEY `role_id` (`role_id`),
  ADD KEY `permissions_id` (`permissions_id`);

--
-- Índices de tabela `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_sale` (`user_id`),
  ADD KEY `product_sale` (`product_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_institute` (`institute_id`),
  ADD KEY `fk_role` (`role_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `educational_institute`
--
ALTER TABLE `educational_institute`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Restrições para tabelas `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_ctagory` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_product` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `permissions_id` FOREIGN KEY (`permissions_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_user_sale` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_sale` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_institute` FOREIGN KEY (`institute_id`) REFERENCES `educational_institute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
