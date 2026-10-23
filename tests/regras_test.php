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

// validarCadastro e normalizarNome
confere('nome em minúsculas e sem espaços', normalizarNome('  Ana.Souza '), 'ana.souza');
confere('cadastro válido', validarCadastro('ana', 'senha-boa-123', 'senha-boa-123'), []);
confere('nome curto', array_keys(validarCadastro('ab', 'senha-boa-123', 'senha-boa-123')), ['nome']);
confere('nome com acento', array_keys(validarCadastro('joão', 'senha-boa-123', 'senha-boa-123')), ['nome']);
confere('senha curta', array_keys(validarCadastro('ana', 'curta', 'curta')), ['senha']);
confere('senha acima de 72 bytes', array_keys(validarCadastro('ana', str_repeat('a', 73), str_repeat('a', 73))), ['senha']);
confere('confirmação diferente', array_keys(validarCadastro('ana', 'senha-boa-123', 'outra-senha-1')), ['confirmacao']);

// O hash do usuário de exemplo, em seed.sql, confere com a senha documentada.
preg_match('/\$2y\$[^\']+/', file_get_contents(__DIR__ . '/../sql/seed.sql'), $achado);
confere('senha do usuário de exemplo', password_verify('senha-de-exemplo', $achado[0] ?? ''), true);

// podeArquivar e leituraPublica
$recadoDaAna = ['autor_id' => 7, 'status' => 'publicado', 'publico' => true];
confere('autor arquiva o próprio recado', podeArquivar($recadoDaAna, ['id' => 7, 'nome' => 'ana']), true);
confere('outro usuário não arquiva', podeArquivar($recadoDaAna, ['id' => 8, 'nome' => 'bia']), false);
confere('sem login não arquiva', podeArquivar($recadoDaAna, null), false);
confere('recado arquivado não arquiva de novo', podeArquivar(['status' => 'arquivado'] + $recadoDaAna, ['id' => 7, 'nome' => 'ana']), false);
confere('público e publicado tem leitura pública', leituraPublica($recadoDaAna), true);
confere('privado não tem leitura pública', leituraPublica(['publico' => false] + $recadoDaAna), false);
confere('público arquivado não tem leitura pública', leituraPublica(['status' => 'arquivado'] + $recadoDaAna), false);

echo $falhas === 0 ? "\nTudo certo.\n" : "\n$falhas falha(s).\n";
exit($falhas === 0 ? 0 : 1);
