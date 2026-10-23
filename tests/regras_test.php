<?php

declare(strict_types=1);

// Teste das regras que não dependem de banco nem de servidor.
// Rode com: php tests/regras_test.php

require __DIR__ . '/../src/validacao.php';
require __DIR__ . '/../src/preferencias.php';
require __DIR__ . '/../src/formatacao.php';
require __DIR__ . '/../src/recados.php';

date_default_timezone_set('America/Araguaina');

$falhas = 0;

function confere(string $descricao, mixed $obtido, mixed $esperado): void
{
    global $falhas;
    if ($obtido === $esperado) {
        echo "ok    $descricao\n";
        return;
    }
    $falhas++;
    echo "FALHA $descricao\n";
    echo '      esperado: ' . var_export($esperado, true) . "\n";
    echo '      obtido:   ' . var_export($obtido, true) . "\n";
}

// validarRecado
confere('texto vazio é recusado', validarRecado(''), 'Escreva o recado antes de publicar.');
confere('só espaços é recusado', validarRecado("  \n "), 'Escreva o recado antes de publicar.');
confere('texto comum é aceito', validarRecado('Reunião às 14h.'), null);
confere('280 caracteres acentuados é aceito', validarRecado(str_repeat('á', 280)), null);
confere('281 caracteres é recusado', validarRecado(str_repeat('a', 281)), 'O recado aceita até 280 caracteres.');
confere('espaços nas pontas não contam', validarRecado('  ' . str_repeat('a', 280) . '  '), null);
confere('\r\n vira \n', normalizarQuebrasDeLinha("a\r\nb"), "a\nb");

// lerOrdem
$_COOKIE = [];
confere('sem cookie, mais recentes', lerOrdem(), 'mais-recentes');
$_COOKIE[COOKIE_DA_ORDEM] = 'mais-antigos';
confere('cookie mais-antigos', lerOrdem(), 'mais-antigos');
$_COOKIE[COOKIE_DA_ORDEM] = "'; DROP TABLE recados; --";
confere('cookie desconhecido, mais recentes', lerOrdem(), 'mais-recentes');

// formatação: o banco devolve em UTC; a tela mostra no fuso de Araguaína (-03)
confere('hora no fuso local', formatarHora('2026-03-12 17:02:00+00'), '14:02');
confere('data e hora por extenso', formatarDataHora('2026-03-12 17:02:00+00'), '12 de março de 2026 às 14:02');

// resumirRecados
$recados = [
    ['status' => 'publicado'],
    ['status' => 'publicado'],
    ['status' => 'arquivado'],
];
confere('resumo conta publicados e arquivados', resumirRecados($recados), ['publicados' => 2, 'arquivados' => 1]);
confere('resumo de lista vazia', resumirRecados([]), ['publicados' => 0, 'arquivados' => 0]);

echo $falhas === 0 ? "\nTudo certo.\n" : "\n$falhas falha(s).\n";
exit($falhas === 0 ? 0 : 1);
