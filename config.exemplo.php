<?php

// Copie este arquivo para config.php e ajuste os valores da sua máquina.
// O config.php fica fora do Git porque guarda a senha do banco.

return [
    'banco' => [
        'host'    => 'localhost',
        'porta'   => 5432,
        'nome'    => 'mini_mural',
        // Usuário próprio da aplicação, criado no passo de instalação do
        // banco (README). Atrás do Apache ou do nginx, o PHP roda como o
        // usuário do servidor web, que não existe no PostgreSQL.
        'usuario' => 'mini_mural',
        'senha'   => 'troque-pela-senha-escolhida',
    ],

    // Fuso usado para mostrar a hora dos recados. O banco guarda o instante
    // em UTC (timestamptz); a conversão acontece na hora de mostrar.
    'fuso_horario' => 'America/Araguaina',
];
