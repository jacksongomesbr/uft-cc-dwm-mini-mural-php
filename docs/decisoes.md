# Decisões de projeto

Cada decisão abaixo registra o que foi escolhido, a alternativa descartada e a
consequência que a equipe aceitou ao escolher. Sem a alternativa descartada, o
registro vira descrição do código; sem a consequência, ele esconde o custo de
quem vier mexer no código depois.

O objetivo do projeto orienta todas elas: mostrar a versão mais simples de uma
aplicação web com banco de dados, legível para quem está no 4º ou 5º período.

Os números não mudam de uma etapa para outra, para você poder comparar os
ramos. Quando uma etapa revê uma decisão anterior, a decisão antiga ganha uma
nota que aponta para a nova, e a nova entra no fim do documento.

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

> **Revista na etapa 3 (D24):** os recados agora são compartilhados entre os
> usuários logados, e cada recado guarda o autor.

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

> **Mantida na etapa 3:** com login, a ordem poderia ir para o banco, por
> usuário. Ela continua no cookie para não mudar o comportamento da etapa 2.

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

> **Revista na etapa 4 (D26):** continua repetível para o autor. Para outro
> usuário, a página responde 403 antes de chegar ao `UPDATE`.

- **Decisão:** o `UPDATE` filtra `status = 'publicado'`. Arquivar duas vezes,
  ou arquivar um id que não existe, não altera nada e redireciona normalmente.
- **Alternativa descartada:** consultar o recado antes e responder 404 ou 409
  quando ele já está arquivado ou não existe.
- **Motivo:** dois cliques seguidos no botão, ou um botão em uma aba antiga,
  levam ao mesmo resultado sem página de erro.
- **Consequência aceita:** quem arquiva um id inexistente pela lista não recebe
  aviso. Pelo detalhe, o redirecionamento cai na página "não encontrado".

### D18. Sem proteção CSRF

> **Substituída na etapa 3 por D21:** com login, a proteção CSRF passou a ser
> obrigatória, como a consequência abaixo já previa.

- **Decisão:** os formulários não levam token anti-CSRF.
- **Alternativa descartada:** token aleatório em sessão, conferido em cada POST.
- **Motivo:** sem login, um site malicioso que envie um formulário em nome de
  quem visita o mural consegue no máximo publicar ou arquivar um recado, o que
  qualquer pessoa já pode fazer direto. O token exigiria sessão e mais um campo
  em cada formulário.
- **Consequência aceita:** o projeto não serve de modelo para uma aplicação com
  login. No dia em que houver usuário, o token passa a ser obrigatório, e o
  cookie de sessão deve ter `SameSite`.

## Ambiente de desenvolvimento

### D19. Docker com dois serviços: aplicação e banco

- **Decisão:** `compose.yaml` sobe dois contêineres. O `app` usa a imagem
  oficial `php:8.4-apache`, com Apache e mod_php, mais a extensão `pdo_pgsql`.
  O `banco` usa a imagem oficial `postgres:18`, que executa o `schema.sql` e o
  `seed.sql` na primeira partida.
- **Alternativa descartada:** três contêineres, com nginx e PHP-FPM separados,
  ou um contêiner só, com Apache, PHP e PostgreSQL juntos.
- **Motivo:** no Apache com mod_php, servidor web e PHP são um processo só, e
  cabem em um contêiner sem perder a divisão de D4. O banco fica separado
  porque tem ciclo de vida próprio: os dados ficam em um volume e sobrevivem à
  troca da imagem da aplicação. A imagem do PostgreSQL já sabe executar
  arquivos SQL na primeira partida, então o esquema continua sendo o mesmo
  `schema.sql` da instalação direta (D7).
- **Consequência aceita:** o contêiner usa `docker/config.php`, que lê o banco
  das variáveis de ambiente, no lugar do `config.php` local; os dois arquivos
  precisam continuar com as mesmas chaves. A imagem guarda uma cópia do código,
  então cada mudança pede `docker compose up --build`. O `schema.sql` só roda
  com o volume vazio, e mudar o esquema exige `docker compose down -v`, que
  apaga os recados. As senhas ficam escritas em `compose.yaml`, o que serve
  para a máquina de desenvolvimento e não para produção.

## Login (etapa 3)

### D20. Login com a sessão do PHP

- **Decisão:** o login guarda o usuário em `$_SESSION`. O PHP grava os dados no
  servidor e entrega ao navegador só um identificador, no cookie
  `mini_mural_sessao`, com `HttpOnly` e `SameSite=Lax`. O identificador é
  trocado no login (`session_regenerate_id`), e a saída apaga a sessão e o
  cookie. As funções ficam em `src/sessao.php`.
- **Alternativa descartada:** um token assinado no próprio cookie, como JWT, ou
  a autenticação HTTP Basic do navegador.
- **Motivo:** a sessão vem pronta no PHP e mostra a ideia central do login em
  uma aplicação web: o servidor lembra quem é a pessoa entre um pedido e outro.
  Com JWT, o projeto precisaria de uma biblioteca e de um jeito de invalidar o
  token na saída. A autenticação Basic não tem tela de login nem botão de sair.
- **Consequência aceita:** as sessões ficam em arquivos no servidor. Com mais de
  um servidor, ou com um contêiner novo no Docker, as sessões se perdem e todos
  precisam entrar de novo. Não há limite de tentativas de login: alguém pode
  testar senhas à vontade, o que um sistema em produção precisaria impedir.

### D21. Proteção CSRF com token na sessão

- **Decisão:** cada sessão tem um token aleatório. Todo formulário POST o envia
  em um campo oculto, e `src/inicializar.php` recusa com 403 qualquer POST sem o
  token certo, antes de a página tratar o formulário. A saída também é um POST.
- **Alternativa descartada:** conferir o token em cada página, ou confiar só no
  `SameSite=Lax` do cookie de sessão.
- **Motivo:** com login, um site malicioso pode fazer o navegador de quem está
  logado publicar ou arquivar em nome dele. Conferir em um lugar só impede que
  uma página nova esqueça a conferência. O `SameSite=Lax` já impede o envio do
  cookie em POST vindo de outro site, mas depende de o navegador respeitar o
  atributo, e o token protege mesmo quando ele falha.
- **Consequência aceita:** todo `<form method="post">` precisa de
  `<?= campoCsrf() ?>`; o formulário que esquecer recebe 403. Um formulário
  aberto antes de a sessão expirar também recebe 403, e a pessoa precisa
  recarregar a página.

### D22. Senha guardada com `password_hash`

- **Decisão:** a tabela `usuarios` guarda só o resultado de `password_hash`
  (bcrypt, com sal aleatório), conferido com `password_verify`. A senha tem de 8
  caracteres a 72 bytes. Nome inexistente e senha errada recebem a mesma
  mensagem, e a conferência gasta o mesmo tempo nos dois casos. Os parâmetros
  de senha levam `#[\SensitiveParameter]`.
- **Alternativa descartada:** um hash simples, como `sha256`, calculado à mão, ou
  regras de senha mais fortes, como exigir símbolos.
- **Motivo:** um hash rápido deixa quem copiar a tabela testar uma quantidade
  enorme de senhas em pouco tempo; o bcrypt é lento de propósito. O `password_hash` escolhe o
  algoritmo e o sal sozinho. Mensagem e tempo iguais não revelam quais nomes
  estão cadastrados. O `SensitiveParameter` tira a senha dos rastros de erro
  que vão para o log.
- **Consequência aceita:** o bcrypt ignora o que passa de 72 bytes, por isso o
  limite existe e aparece na mensagem. As regras de senha são só de tamanho; o
  projeto não confere senhas comuns.

### D23. Nome de usuário em minúsculas e único no banco

- **Decisão:** o PHP grava o nome em minúsculas, e a coluna `nome` é `UNIQUE`.
  O cadastro tenta o `INSERT` e trata o erro 23505 do PostgreSQL como "nome já
  existe". O `CHECK usuario_nome_valido` repete a regra de caracteres.
- **Alternativa descartada:** conferir com um `SELECT` antes de inserir.
- **Motivo:** dois cadastros com o mesmo nome ao mesmo tempo passariam os dois
  pelo `SELECT`; só o `UNIQUE` do banco decide quem chegou primeiro. Gravar em
  minúsculas impede "Ana" e "ana" como contas diferentes, e o login aceita o
  nome digitado de qualquer jeito.
- **Consequência aceita:** o nome aparece sempre em minúsculas. Acentos e
  espaços ficam fora do nome de usuário.

### D24. Tudo exige login, menos o recado público

> **Revista na etapa 4 (D25 e D26):** a página inicial mostra a lista dos
> recados públicos a quem não entrou, e só o autor arquiva.

- **Decisão:** mural, Sobre, publicar e arquivar exigem login; quem não entrou
  vai para `/login.php`. A exceção é o detalhe de um recado marcado como
  público, que qualquer pessoa lê pelo endereço, sem botão de arquivar. Todo
  recado nasce privado (`publico boolean DEFAULT false`), e a caixa "Público"
  só existe no formulário de publicação. O recado guarda o autor (`autor_id`).
  Qualquer usuário logado arquiva qualquer recado, como na etapa 2.
- **Alternativa descartada:** mostrar a quem não entrou uma lista dos recados
  públicos em `/`, ou deixar só o autor arquivar o próprio recado.
- **Motivo:** o pedido da etapa é que todo o mural exija login e que o recado
  público possa ser lido sem login. O detalhe por endereço atende a leitura
  pública com a menor mudança, e arquivar continua com a regra da etapa 2.
- **Consequência aceita:** quem não entrou só chega a um recado público se
  receber o endereço dele. Um recado não muda de privado para público depois de
  publicado. Um usuário com recados não pode ser apagado, por causa do
  `REFERENCES` da coluna `autor_id`.

## Autoria e recados públicos (etapa 4)

### D25. Página inicial com os recados públicos para quem não entrou

> **Revista na etapa 4 (D28):** quem entrou vê os recados publicados de
> todos, mas os arquivados só do próprio autor.

- **Decisão:** em `/`, quem não entrou vê a lista dos recados públicos ainda
  publicados, só para leitura, sem formulário nem botões. Quem entrou continua
  vendo o mural completo no mesmo endereço. Um recado público arquivado sai da
  vista pública: some da lista e o endereço dele passa a pedir login.
- **Alternativa descartada:** uma página separada, como `/publicos.php`, com
  `/` ainda redirecionando para o login.
- **Motivo:** a regra é que os recados públicos fiquem visíveis para todos, e o
  primeiro endereço que alguém abre é `/`. Mostrar a lista ali evita que o
  visitante caia na tela de login sem saber que há algo para ler. O arquivado
  sai da vista porque arquivar é tirar o recado do mural.
- **Consequência aceita:** `public/index.php` tem dois caminhos, um para cada
  situação, e a lista pública mora em um template próprio
  (`src/templates/recados-publicos.php`). Publicar sem login continua
  redirecionando para `/login.php`.

### D26. Só o autor arquiva, conferido na página e no banco

- **Decisão:** o botão "Arquivar" só aparece para o autor (`podeArquivar()`).
  `public/arquivar.php` busca o recado e responde 403 se quem pede não é o
  autor. O `UPDATE` de `arquivarRecado()` filtra também por `autor_id`.
- **Alternativa descartada:** só esconder o botão, ou só filtrar no `UPDATE` e
  redirecionar sem aviso quando nada muda.
- **Motivo:** esconder o botão não impede um POST montado à mão. Filtrar só no
  `UPDATE` impediria a mudança, mas a pessoa receberia o mesmo redirecionamento
  de um arquivamento bem-sucedido. O 403 diz o que aconteceu, e o filtro no
  `UPDATE` protege mesmo que uma página nova esqueça a conferência.
- **Consequência aceita:** arquivar faz duas consultas, uma para buscar o
  recado e outra para alterá-lo. Um recado de um usuário que perdeu a conta não
  pode mais ser arquivado por ninguém.

### D27. Instante do arquivamento em `arquivado_em`, amarrado ao status

- **Decisão:** a coluna `arquivado_em timestamptz` recebe `now()` no
  arquivamento e fica vazia enquanto o recado está publicado. O `CHECK
  recado_arquivado_com_data` exige que as duas colunas concordem.
- **Alternativa descartada:** uma tabela de histórico, com cada mudança de
  status, ou trocar a coluna `status` por `arquivado_em` e deduzir o status da
  data.
- **Motivo:** o mural só tem uma mudança de estado, e uma coluna basta. Manter
  `status` evita reescrever as consultas e o `CHECK` das etapas anteriores, e o
  novo `CHECK` impede que as duas colunas se contradigam.
- **Consequência aceita:** status e data guardam a mesma informação de dois
  jeitos, e o banco confere a coerência. Recriar o banco com o `schema.sql`
  desta etapa apaga os dados das anteriores, como em toda troca de etapa.

### D28. Recado arquivado só para o autor

- **Decisão:** para quem entrou, a lista traz os recados publicados de todos e
  os arquivados só do próprio autor (`listarRecados()` filtra por
  `status = 'publicado' OR autor_id = usuário`). O detalhe e o arquivamento de
  um recado arquivado de outra pessoa respondem 404, com a página de recado
  ausente. A regra fica em `podeVer()`.
- **Alternativa descartada:** responder 403 a quem abre o arquivado de outra
  pessoa, ou mostrar o arquivado na lista sem o texto.
- **Motivo:** arquivar é tirar o recado do mural para todos, menos para quem o
  escreveu. O 404 trata o recado como se não existisse e não revela que há um
  recado arquivado naquele endereço; um 403 confirmaria que ele existe.
- **Consequência aceita:** cada usuário vê um resumo diferente, porque o total
  de arquivados conta só os dele. Quem não entrou continua sendo levado ao
  login ao abrir um recado que não pode ver, e só depois do login recebe o 404.
