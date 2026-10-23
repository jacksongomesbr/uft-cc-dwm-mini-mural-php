<?php

declare(strict_types=1);

// Sair: encerra a sessão e volta para o login. Só por POST, como toda ação
// que muda estado: um link de sair poderia ser disparado por uma imagem em
// outro site e desconectar a pessoa sem ela pedir.

require __DIR__ . '/../src/inicializar.php';

exigirPost();
sair();
redirecionar('/login.php');
