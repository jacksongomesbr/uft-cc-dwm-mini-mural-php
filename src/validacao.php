<?php

declare(strict_types=1);

const LIMITE_DO_RECADO = 280;

/**
 * Confere o texto de um recado e devolve a mensagem de erro, ou null se o
 * texto é válido.
 *
 * Esta é a regra que vale. O JavaScript do navegador repete a conferência só
 * para avisar mais cedo; um pedido que chega sem passar por ele é conferido
 * aqui do mesmo jeito.
 */
function validarRecado(string $texto, int $limite = LIMITE_DO_RECADO): ?string
{
    $textoLimpo = trim($texto);

    if ($textoLimpo === '') {
        return 'Escreva o recado antes de publicar.';
    }

    // mb_strlen conta caracteres; strlen contaria bytes, e "às" teria 3.
    if (mb_strlen($textoLimpo) > $limite) {
        return "O recado aceita até $limite caracteres.";
    }

    return null;
}

/**
 * O navegador envia cada quebra de linha do textarea como \r\n, dois
 * caracteres, mas o atributo maxlength conta como um. Trocar por \n faz o
 * PHP contar igual ao navegador.
 */
function normalizarQuebrasDeLinha(string $texto): string
{
    return str_replace("\r\n", "\n", $texto);
}
