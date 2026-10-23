<?php

declare(strict_types=1);

/**
 * Lê um campo de texto do formulário enviado por POST.
 *
 * Um pedido montado à mão pode mandar texto[]=a, e $_POST['texto'] chega
 * como array. Nesse caso o campo conta como vazio.
 */
function lerCampo(string $nome): string
{
    $valor = $_POST[$nome] ?? '';

    return is_string($valor) ? $valor : '';
}

/** Lê um id inteiro e positivo da URL (GET) ou do formulário (POST). */
function lerId(int $origem): ?int
{
    $id = filter_input($origem, 'id', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    return is_int($id) ? $id : null;
}

function ehPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Recusa qualquer método que não seja POST, com o código 405. */
function exigirPost(): void
{
    if (!ehPost()) {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=utf-8');
        exit("Esta página só aceita envio de formulário.\n");
    }
}

/**
 * Manda o navegador para outra página com o código 303.
 *
 * É o passo final do padrão POST/redirect/GET: depois de gravar, o navegador
 * faz um GET novo. Recarregar a página não reenvia o formulário.
 */
function redirecionar(string $caminho): never
{
    header('Location: ' . $caminho, true, 303);
    exit;
}
