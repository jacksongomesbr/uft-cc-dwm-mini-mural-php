<?php

// Configuração usada dentro do contêiner, no lugar do config.php local. Os
// valores vêm das variáveis de ambiente definidas em compose.yaml.

return [
    'banco' => [
        // Nome do serviço do banco em compose.yaml. Dentro da rede do Compose,
        // cada serviço é encontrado pelo próprio nome.
        'host'    => getenv('BANCO_HOST'),
        'porta'   => (int) getenv('BANCO_PORTA'),
        'nome'    => getenv('BANCO_NOME'),
        'usuario' => getenv('BANCO_USUARIO'),
        'senha'   => getenv('BANCO_SENHA'),
    ],
    'fuso_horario' => 'America/Araguaina',
];
