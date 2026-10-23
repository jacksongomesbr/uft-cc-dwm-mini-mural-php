# Documentação do Mini Mural em PHP

Esta pasta explica o projeto para quem vai ler, rodar ou modificar o código.
Para instalar e rodar, comece pelo [README da raiz](../README.md).

| Documento | Responde a |
|---|---|
| [funcionamento.md](funcionamento.md) | O que a aplicação faz e qual o caminho de cada pedido entre navegador, servidor web, PHP e PostgreSQL |
| [decisoes.md](decisoes.md) | Por que o projeto é assim: cada decisão com a alternativa descartada e a consequência aceita |
| [modelo-de-dados.md](modelo-de-dados.md) | Tabela, restrições, ciclo de vida de um recado e as consultas feitas |
| [estrutura.md](estrutura.md) | Onde fica cada coisa e o que pode depender do quê |

Os diagramas usam Mermaid. O GitHub e o VS Code, com a extensão de pré-visualização
de Markdown, desenham os diagramas direto no arquivo.

## O projeto em um parágrafo

O Mini Mural é uma aplicação web em que o servidor monta cada página. O
navegador pede um endereço, o Apache ou o nginx entrega o pedido ao PHP, o PHP
consulta o PostgreSQL e devolve HTML pronto. Publicar, arquivar e escolher a
ordem são formulários comuns enviados por POST, seguidos de um redirecionamento.
O JavaScript só melhora o formulário de recado; sem ele, tudo continua
funcionando. O projeto serve para ver, sem camadas de framework, o que um
servidor web faz em cada pedido.
