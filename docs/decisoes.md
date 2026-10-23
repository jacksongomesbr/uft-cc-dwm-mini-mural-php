# Decisões de projeto

Cada decisão abaixo registra o que foi escolhido, a alternativa descartada e a
consequência que a equipe aceitou ao escolher. Sem a alternativa descartada, o
registro vira descrição do código; sem a consequência, ele esconde o custo de
quem vier mexer no código depois.

O objetivo do projeto orienta todas elas: mostrar a versão mais simples de uma
aplicação web com banco de dados, legível para quem está no 4º ou 5º período.

## Arquitetura

### D1. Monólito com HTML montado no servidor

- **Decisão:** um único programa PHP recebe o pedido, consulta o banco e devolve
  a página HTML pronta. O navegador recebe HTML, CSS e um JavaScript pequeno.
- **Alternativa descartada:** API JSON em PHP e uma página que monta a tela com
  JavaScript (`fetch`).
- **Motivo:** com HTML montado no servidor, o caminho de cada funcionalidade
  cabe em um arquivo, e o aluno vê a requisição inteira sem precisar de duas
  bases de código.
- **Consequência aceita:** toda ação recarrega a página inteira. Um app mobile
  não consegue reaproveitar o servidor, porque ele devolve HTML em vez de
  dados; para isso seria preciso acrescentar rotas JSON.

### D2. Um arquivo PHP por página, sem roteador

- **Decisão:** `public/index.php`, `recado.php`, `arquivar.php` e `sobre.php`
  são as rotas. O endereço é o nome do arquivo.
- **Alternativa descartada:** um controlador frontal (`public/index.php` único)
  com roteador e endereços limpos, como `/recados/42`.
- **Motivo:** o servidor web entrega cada arquivo `.php` sem regra de
  reescrita, e o aluno encontra o código de uma página pelo endereço que vê no
  navegador.
- **Consequência aceita:** os endereços mostram `.php` e o id vai na query
  string (`/recado.php?id=42`). Cada página repete a linha
  `require __DIR__ . '/../src/inicializar.php';`.

### D3. Código em `src/`, fora da pasta servida

- **Decisão:** a raiz de documentos do servidor web é `public/`. Funções,
  templates e `config.php` ficam em `src/` e na raiz do projeto, fora do alcance
  do navegador.
- **Alternativa descartada:** tudo na mesma pasta, como em muitos tutoriais.
- **Motivo:** com tudo junto, `http://localhost:8081/config.php` seria um
  endereço válido, e um defeito na configuração do servidor poderia expor a
  senha do banco.
- **Consequência aceita:** a configuração do servidor precisa apontar para
  `public/` (`DocumentRoot` no Apache, `root` no nginx), e cada página sobe um
  nível (`/../src/`) para incluir as funções.

### D4. Apache com mod_php ou nginx com PHP-FPM

- **Decisão:** o projeto roda atrás de um servidor web de verdade. A pasta
  `servidor/` traz uma configuração pronta para cada opção: `apache.conf`, com
  o PHP carregado como módulo do Apache, e `nginx.conf`, com o nginx repassando
  os arquivos `.php` ao PHP-FPM por FastCGI.
- **Alternativa descartada:** o servidor embutido do PHP (`php -S`).
- **Motivo:** o manual do PHP descreve o servidor embutido como apoio ao
  desenvolvimento e recomenda não usá-lo em rede pública. Com Apache ou nginx,
  o aluno vê a divisão que existe em qualquer implantação: o servidor web
  recebe a conexão e entrega os arquivos estáticos, e o PHP só executa os
  scripts.
- **Consequência aceita:** rodar o projeto exige instalar e configurar um
  servidor web. O PHP passa a rodar com o usuário do servidor (`www-data` no
  Debian e no Ubuntu), que precisa ler a pasta do projeto. Para ver um erro, o
  aluno abre o arquivo de log do servidor.

### D5. Funções simples em vez de classes

- **Decisão:** cada arquivo de `src/` agrupa funções de um assunto:
  `recados.php` tem todo o SQL, `validacao.php` tem a regra do texto,
  `preferencias.php` tem o cookie.
- **Alternativa descartada:** classes `Recado`, `RecadoRepository` e
  `RecadoController`, com autoload do Composer.
- **Motivo:** a separação de responsabilidades fica visível pelos nomes dos
  arquivos, sem exigir orientação a objetos nem Composer do aluno.
- **Consequência aceita:** as funções recebem o `PDO` como parâmetro, e trocar o
  banco nos testes exige passar outro `PDO`. Não há interface que force a
  separação; ela depende de disciplina, já que nada impede uma página de
  escrever SQL.

### D6. Sem framework

- **Decisão:** PHP puro, com PDO para o banco e `htmlspecialchars` para escapar
  HTML.
- **Alternativa descartada:** Laravel, Slim ou Symfony.
- **Motivo:** o projeto existe para mostrar o que um framework faz por baixo:
  roteamento, validação, escape, redirecionamento e sessão de banco.
- **Consequência aceita:** o projeto não tem migrations, validação declarativa,
  proteção CSRF nem templates com escape automático. Esquecer o `e()` em uma
  saída abre espaço para XSS.

## Dados

### D7. Esquema em um arquivo SQL executado à mão

- **Decisão:** `sql/schema.sql` apaga e cria a tabela; `sql/seed.sql` insere
  os exemplos. Os dois rodam com `psql -f`.
- **Alternativa descartada:** migrations versionadas (Phinx, Doctrine
  Migrations ou as do Laravel).
- **Motivo:** um arquivo SQL é o próprio esquema, legível de cima a baixo, e o
  aluno pratica o `psql`.
- **Consequência aceita:** mudar o esquema e rodar de novo apaga os recados. Não
  existe histórico de mudanças no banco nem forma de atualizar um banco em uso
  sem perder dados.

### D8. Usuário próprio no PostgreSQL

- **Decisão:** a aplicação conecta com o usuário `mini_mural`, dono do banco
  `mini_mural`, com senha.
- **Alternativa descartada:** conectar com o superusuário `postgres` ou com o
  usuário do sistema operacional.
- **Motivo:** atrás do Apache ou do nginx, o PHP roda como o usuário do servidor
  web, que não existe no PostgreSQL. Um usuário próprio funciona igual em
  qualquer máquina, e um defeito na aplicação fica restrito ao banco dela, sem
  alcançar os outros bancos do servidor.
- **Consequência aceita:** a instalação ganha um passo, criar o usuário e
  escolher a senha, e a senha fica escrita no `config.php`. Por isso o
  `config.php` fica fora do Git e fora de `public/`.

### D9. Recados no banco, compartilhados por todos

- **Decisão:** os recados ficam na tabela `recados` do PostgreSQL.
- **Alternativa descartada:** guardar os recados na sessão do PHP (`$_SESSION`)
  ou em um arquivo JSON no servidor.
- **Motivo:** na sessão, cada navegador veria só os próprios recados. No arquivo
  JSON, dois pedidos ao mesmo tempo podem gravar por cima um do outro, e o PHP
  teria de ordenar e filtrar a lista sozinho. O banco resolve a concorrência e
  faz a ordenação.
- **Consequência aceita:** dois navegadores veem o mesmo mural, e arquivar em um
  aparece no outro após recarregar. Não há dono do recado: qualquer pessoa
  arquiva qualquer recado.

### D10. Ordem do mural em cookie

- **Decisão:** a preferência de ordem fica no cookie `mini_mural_ordem`.
- **Alternativa descartada:** uma tabela `preferencias` no banco.
- **Motivo:** a ordem é uma escolha de quem usa o navegador. Sem login, o
  servidor não sabe quem é quem, e uma linha no banco seria a mesma ordem para
  todo mundo.
- **Consequência aceita:** a ordem não acompanha a pessoa em outro navegador e
  some se ela limpar os cookies. Valor desconhecido no cookie vira a ordem
  padrão, mais recentes primeiro.

### D11. Status como texto com `CHECK`

- **Decisão:** `status text CHECK (status IN ('publicado', 'arquivado'))`.
- **Alternativa descartada:** tipo enumerado do PostgreSQL (`CREATE TYPE ...
  AS ENUM`) ou uma coluna booleana `arquivado`.
- **Motivo:** o `CHECK` mostra os valores válidos na própria definição da
  tabela, e o valor gravado é a palavra que aparece na tela.
- **Consequência aceita:** cada linha repete o texto do status. Um terceiro
  status exige alterar a restrição, o que, sem migrations, significa recriar a
  tabela (D7).

### D12. Id gerado pelo banco

- **Decisão:** `id bigint GENERATED ALWAYS AS IDENTITY`.
- **Alternativa descartada:** id gerado no PHP, a partir da data e de um
  contador, ou UUID.
- **Motivo:** o banco garante id único mesmo com dois pedidos ao mesmo tempo, e
  o endereço do detalhe fica curto (`?id=4`).
- **Consequência aceita:** os ids são sequenciais e revelam quantos recados já
  foram criados. Recriar a tabela com o `schema.sql` recomeça a contagem em 1.

### D13. Hora guardada em `timestamptz` e convertida no PHP

- **Decisão:** o banco guarda o instante com fuso (`timestamptz`); o PHP
  converte para `America/Araguaina` ao mostrar.
- **Alternativa descartada:** `timestamp` sem fuso, com a hora local gravada
  direto.
- **Motivo:** com fuso guardado, a hora exibida não depende do fuso da conexão
  nem do servidor; basta trocar `fuso_horario` no `config.php`.
- **Consequência aceita:** toda data passa por `dataLocal()` antes de ir para a
  tela. Mostrar `criado_em` cru deixaria a hora na forma `2026-03-12
  14:02:00-03`.

## Comportamento

### D14. Regra de negócio no PHP, repetida no navegador só como aviso

- **Decisão:** `validarRecado()` no PHP decide se o recado é gravado. O
  `mural.js` repete a conferência para avisar antes do envio, e o banco tem
  restrições `CHECK` como última barreira.
- **Alternativa descartada:** validar só no navegador, com `required` e
  `maxlength`, ou só no servidor, sem JavaScript.
- **Motivo:** o navegador está sob controle de quem usa; um `curl` envia o que
  quiser. O aviso no navegador poupa um pedido ao servidor: a mensagem aparece
  abaixo do campo, o foco volta a ele e o botão continua habilitado.
- **Consequência aceita:** a regra e as mensagens existem em PHP e em
  JavaScript. Mudar o limite exige alterar `LIMITE_DO_RECADO`, que já alimenta o
  `data-limite` do formulário, e as mensagens nos dois arquivos.

### D15. POST/redirect/GET em toda gravação

- **Decisão:** depois de gravar, o PHP responde 303 e o navegador faz um GET.
- **Alternativa descartada:** devolver o HTML da página logo depois do POST.
- **Motivo:** recarregar a página depois de um POST pede ao navegador para
  reenviar o formulário, e o recado seria gravado duas vezes.
- **Consequência aceita:** cada gravação custa dois pedidos. Uma mensagem de
  sucesso precisa ir no endereço (`?salvo=1`), porque o GET seguinte não sabe o
  que aconteceu no POST.

### D16. Texto inválido responde 422 com a página inteira

- **Decisão:** com erro de validação, a mesma página é desenhada de novo com o
  código 422, a mensagem e o texto digitado.
- **Alternativa descartada:** redirecionar de volta com a mensagem guardada em
  sessão (`$_SESSION`), o chamado "flash message".
- **Motivo:** sem sessão, o projeto não precisa explicar cookie de sessão nem
  armazenamento no servidor. O código 422 diz, a quem lê a resposta, que o
  pedido foi recusado.
- **Consequência aceita:** depois de um erro, o endereço mostra o POST; se a
  pessoa recarregar, o navegador pergunta se deve reenviar o formulário.

### D17. Arquivar é repetível

- **Decisão:** o `UPDATE` filtra `status = 'publicado'`. Arquivar duas vezes,
  ou arquivar um id que não existe, não altera nada e redireciona normalmente.
- **Alternativa descartada:** consultar o recado antes e responder 404 ou 409
  quando ele já está arquivado ou não existe.
- **Motivo:** dois cliques seguidos no botão, ou um botão em uma aba antiga,
  levam ao mesmo resultado sem página de erro.
- **Consequência aceita:** quem arquiva um id inexistente pela lista não recebe
  aviso. Pelo detalhe, o redirecionamento cai na página "não encontrado".

### D18. Sem proteção CSRF

- **Decisão:** os formulários não levam token anti-CSRF.
- **Alternativa descartada:** token aleatório em sessão, conferido em cada POST.
- **Motivo:** sem login, um site malicioso que envie um formulário em nome de
  quem visita o mural consegue no máximo publicar ou arquivar um recado, o que
  qualquer pessoa já pode fazer direto. O token exigiria sessão e mais um campo
  em cada formulário.
- **Consequência aceita:** o projeto não serve de modelo para uma aplicação com
  login. No dia em que houver usuário, o token passa a ser obrigatório, e o
  cookie de sessão deve ter `SameSite`.
