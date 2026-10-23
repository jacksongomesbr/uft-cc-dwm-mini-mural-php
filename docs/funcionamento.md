# Como a aplicação funciona

Este documento descreve o que a aplicação faz e o caminho de cada pedido, do
navegador ao banco e de volta. As escolhas por trás desse caminho estão em
[decisoes.md](decisoes.md).

## As quatro partes

A aplicação tem quatro partes, e cada uma tem uma responsabilidade.

| Parte | Onde roda | Responsabilidade |
|---|---|---|
| Navegador | Na máquina de quem usa | Mostra o HTML, aplica o CSS e roda o `mural.js`, que avisa erros do formulário antes do envio |
| Servidor web (Apache ou nginx) | No servidor | Recebe a conexão, entrega direto os arquivos estáticos de `public/` e passa os pedidos de `.php` ao PHP |
| PHP (mod_php ou PHP-FPM) | No servidor, dentro do Apache ou em processo próprio | Executa a página pedida: aplica as regras, consulta o banco e monta o HTML da resposta |
| PostgreSQL | No servidor de banco, que pode ser a mesma máquina | Guarda os recados e recusa dados que quebram as restrições da tabela |

Com Apache e mod_php, o PHP roda dentro do próprio processo do Apache. Com
nginx, o PHP-FPM é um processo separado, e o nginx fala com ele pelo protocolo
FastCGI. Para o código do projeto, as duas formas são iguais.

O navegador nunca fala com o banco. Todo acesso a dados passa pelo PHP, e é no
PHP que mora a regra de negócio: só quem entrou publica, só o autor arquiva o
próprio recado, o texto do recado tem de 1 a 280 caracteres, todo recado nasce
publicado e privado, e só um recado publicado pode ser arquivado.

## Funcionalidades

São seis funcionalidades: criar conta e entrar, publicar, listar, abrir o
detalhe, arquivar e escolher a ordem do mural. Todas exigem login, menos a
leitura dos recados públicos ainda publicados, que qualquer pessoa vê na página
inicial e no endereço de cada um.

```mermaid
flowchart LR
    V(["Visitante sem login"])
    U(["Usuário logado"])

    subgraph conta ["Conta"]
        C1["Criar conta: /cadastro.php"]
        C2["Entrar: /login.php"]
        C3["Sair: /sair.php"]
    end

    subgraph mural ["Mural: /"]
        F1["Publicar recado"]
        F2["Ver a lista de recados"]
        F3["Ver o resumo: publicados e arquivados"]
        F4["Arquivar o próprio recado pela lista"]
    end

    subgraph detalhe ["Detalhe: /recado.php?id=N"]
        F5["Ver o texto completo, data e hora"]
        F6["Arquivar o próprio recado pelo detalhe"]
        F7["Ver aviso de recado não encontrado"]
    end

    subgraph sobre ["Sobre: /sobre.php"]
        F8["Ler a descrição do projeto"]
        F9["Escolher a ordem do mural"]
    end

    V --> C1
    V --> C2
    V --> P1["Ver a lista de recados públicos: /"]
    P1 -- "clique no texto" --> F5
    C1 -. "já entra na conta" .-> U
    C2 --> U
    U --> C3
    U --> F1
    U --> F2
    U --> F4
    U --> F5
    U --> F6
    U --> F9
    F2 --> F3
    F2 -- "clique no texto" --> F5
    F5 -. "id inexistente" .-> F7
    F9 -. "muda a ordem de" .-> F2
    U --> F8
```

## Páginas e endereços

Cada arquivo de `public/` é uma página ou uma ação. O servidor web executa o
arquivo `.php` cujo nome está no endereço. Quando nenhum arquivo corresponde,
como em `/config.php` ou `/qualquer-coisa`, a resposta é 404.

| Endereço | Método | Login | O que faz | Resposta |
|---|---|---|---|---|
| `/` | GET | Não | Com login, mostra o formulário, o resumo e a lista na ordem do cookie; sem login, só os recados públicos ainda publicados | 200 com HTML |
| `/` | POST | Sim | Valida e grava um recado novo, público ou privado | 303 para `/` se válido, 422 com a página e a mensagem se inválido |
| `/recado.php?id=N` | GET | Só para recado privado | Mostra um recado | 200, ou 404 se o id não é inteiro ou não existe |
| `/arquivar.php` | POST | Sim, e só o autor | Arquiva o recado `id` e grava o instante | 303 para a lista ou para o detalhe; 403 se quem pede não é o autor; 405 se chegar por GET |
| `/sobre.php` | GET e POST | Sim | Mostra e grava a ordem no cookie | 200; 303 para `/sobre.php?salvo=1` depois de gravar |
| `/login.php` | GET e POST | Não | Mostra o formulário e confere nome e senha | 303 para `/` se conferem, 422 se não |
| `/cadastro.php` | GET e POST | Não | Cria a conta e já entra nela | 303 para `/` se criou, 422 com as mensagens se não |
| `/sair.php` | POST | Sim | Apaga a sessão | 303 para `/login.php`; 405 se chegar por GET |

Uma página que exige login manda quem não entrou para `/login.php`, com 303.
Todo POST sem o token CSRF recebe 403 antes de chegar à página.

## O caminho de um pedido

Toda página de `public/` começa do mesmo jeito: inclui `src/inicializar.php`,
que carrega o `config.php`, as funções do projeto e o tratamento de erro. Depois
a página lê o pedido, chama as funções de `src/` e imprime o HTML. O próprio
arquivo é a rota, sem roteador nem controlador.

### Abrir o mural

O primeiro diagrama mostra o servidor web separado do PHP. Os pedidos de
`.php` passam pelos dois; os de CSS e JavaScript param no servidor web, que
lê o arquivo do disco sem acionar o PHP.

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuário
    participant N as Navegador
    participant S as Apache ou nginx
    participant P as PHP: public/index.php
    participant B as PostgreSQL

    U->>N: abre http://localhost:8081/, já logado
    N->>S: GET / com os cookies mini_mural_sessao e mini_mural_ordem
    S->>P: executa public/index.php
    P->>P: exigirLogin() encontra o usuário na sessão
    P->>P: lerOrdem() escolhe ASC ou DESC
    P->>B: SELECT id, texto, criado_em, status ORDER BY criado_em
    B-->>P: linhas da tabela recados
    P->>P: resumirRecados() e formatarHora()
    P-->>S: HTML da página
    S-->>N: 200 com o HTML
    N->>S: GET /css/estilo.css e /js/mural.js
    S-->>N: arquivos lidos de public/, sem passar pelo PHP
    N-->>U: mural desenhado
```

Os diagramas seguintes juntam servidor web e PHP em um participante só, para
destacar o que o código do projeto faz.

### Publicar um recado válido

Depois de gravar, o PHP responde 303 sem HTML, e o navegador faz um GET novo.
Esse padrão se chama **POST/redirect/GET**. Como o último pedido da página foi
um GET, recarregar depois de publicar repete só a leitura, e o recado é gravado
uma vez.

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuário
    participant N as Navegador e mural.js
    participant P as PHP: public/index.php
    participant B as PostgreSQL

    U->>N: digita o texto e clica em Publicar
    N->>N: mural.js valida e deixa o envio seguir
    N->>P: POST / com texto=...
    P->>P: normalizarQuebrasDeLinha() e validarRecado()
    P->>B: INSERT INTO recados (texto) VALUES ($1) RETURNING id
    B-->>P: id do recado novo
    P-->>N: 303 See Other, Location: /
    N->>P: GET /
    P->>B: SELECT ... ORDER BY criado_em
    B-->>P: linhas, já com o recado novo
    P-->>N: 200 com o HTML atualizado
```

### Publicar um texto inválido

O texto inválido pode ser barrado em dois lugares. Com JavaScript ligado, o
`mural.js` cancela o envio, mostra a mensagem e devolve o foco ao campo; nenhum
pedido sai do navegador. Sem JavaScript, ou com um pedido montado à mão, o PHP
faz a mesma conferência e responde 422 com o formulário preenchido. Nos dois
casos o banco não recebe nada.

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuário
    participant N as Navegador e mural.js
    participant P as PHP: public/index.php
    participant B as PostgreSQL

    U->>N: clica em Publicar com o campo vazio
    alt JavaScript ligado
        N->>N: validar() devolve a mensagem
        N-->>U: mensagem abaixo do campo e foco no campo
    else JavaScript desligado ou pedido feito à mão
        N->>P: POST / com texto vazio
        P->>P: validarRecado() devolve a mensagem
        P->>B: SELECT para desenhar a lista
        B-->>P: linhas
        P-->>N: 422 com a página, a mensagem e o texto digitado
    end
```

### Arquivar um recado

O botão "Arquivar" é um formulário com o campo oculto `id`, e só aparece para o
autor do recado. Arquivar muda dados, por isso usa POST: um link comum seria um
GET, e um GET pode ser disparado por um buscador ou por uma pré-carga do
navegador. Como um POST pode ser montado à mão, sem o botão, a página confere o
autor de novo antes de gravar.

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuário
    participant N as Navegador
    participant P as PHP: public/arquivar.php
    participant B as PostgreSQL

    U->>N: clica em Arquivar, no próprio recado
    N->>P: POST /arquivar.php com id=4 e o token
    P->>P: exigirLogin() e lerId()
    P->>B: SELECT ... WHERE recados.id = 4
    B-->>P: o recado, com autor_id
    alt quem pede não é o autor
        P-->>N: 403 com Só quem publicou o recado pode arquivá-lo.
    end
    P->>B: UPDATE recados SET status = arquivado, arquivado_em = now() WHERE id = 4 AND autor_id = ... AND status = publicado
    B-->>P: 1 linha alterada, ou 0 se já estava arquivado
    alt veio da lista
        P-->>N: 303, Location: / com a âncora do recado
    else veio do detalhe (voltar_para=detalhe)
        P-->>N: 303, Location: /recado.php?id=4
    end
    N->>P: GET da página de destino
    P-->>N: 200 com o recado marcado como Arquivado
```

### Escolher a ordem do mural

A ordem é a única informação que fica no navegador. O PHP grava um cookie, e o
navegador o devolve em todo pedido seguinte. A página Sobre nem abre conexão
com o banco.

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuário
    participant N as Navegador
    participant P as PHP: public/sobre.php

    U->>N: marca Mais antigos primeiro e clica em Salvar ordem
    N->>P: POST /sobre.php com ordem=mais-antigos
    P->>P: ordemValida() confere a lista fechada ORDENS
    P-->>N: 303, Set-Cookie: mini_mural_ordem=mais-antigos
    N->>P: GET /sobre.php?salvo=1 com o cookie
    P-->>N: 200 com Preferência salva
    Note over N,P: Daqui em diante, todo GET / leva o cookie, e a lista vem em ordem crescente
```

## Login e acesso

O login usa a sessão do PHP. No login, o PHP guarda o usuário em
`$_SESSION['usuario']`, nos arquivos de sessão do servidor, e o navegador recebe
só um identificador, no cookie `mini_mural_sessao`. Em cada pedido seguinte, o
navegador devolve o cookie, `iniciarSessao()` recarrega `$_SESSION` e
`usuarioLogado()` diz quem é a pessoa. As funções ficam em `src/sessao.php`.

### Entrar

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuário
    participant N as Navegador
    participant P as PHP: public/login.php
    participant B as PostgreSQL

    U->>N: abre http://localhost:8081/
    N->>P: GET / sem cookie de sessão
    P-->>N: 303, Location: /login.php
    N->>P: GET /login.php
    P-->>N: 200 com o formulário, o token CSRF e o cookie mini_mural_sessao
    U->>N: digita nome e senha e clica em Entrar
    N->>P: POST /login.php com nome, senha, token e o cookie
    P->>P: inicializar.php confere o token
    P->>B: SELECT id, nome, senha_hash FROM usuarios WHERE nome = ...
    B-->>P: a linha do usuário, ou nenhuma
    P->>P: password_verify(senha, senha_hash)
    alt nome e senha conferem
        P->>P: session_regenerate_id(true) e $_SESSION usuario
        P-->>N: 303, Location: / e cookie com identificador novo
    else não conferem
        P-->>N: 422 com Usuário ou senha incorretos.
    end
```

A troca do identificador no login protege contra a **fixação de sessão**: se
alguém conseguisse plantar um identificador no navegador da vítima antes do
login, esse identificador deixa de valer quando ela entra.

### Criar conta

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuário
    participant N as Navegador
    participant P as PHP: public/cadastro.php
    participant B as PostgreSQL

    U->>N: preenche usuário, senha e confirmação
    N->>P: POST /cadastro.php com os campos e o token
    P->>P: normalizarNome() e validarCadastro()
    alt algum campo inválido
        P-->>N: 422 com a mensagem embaixo de cada campo
    else campos válidos
        P->>B: INSERT INTO usuarios (nome, senha_hash) VALUES (..., password_hash(...))
        alt nome já existe
            B-->>P: erro 23505, violação de UNIQUE
            P-->>N: 422 com Esse nome de usuário já existe.
        else gravou
            B-->>P: id e nome do usuário novo
            P->>P: entrar() com o usuário novo
            P-->>N: 303, Location: /
        end
    end
```

### Recados públicos sem login

```mermaid
sequenceDiagram
    autonumber
    actor V as Visitante sem login
    participant N as Navegador
    participant P as PHP: public/recado.php
    participant B as PostgreSQL

    V->>N: abre http://localhost:8081/
    N->>P: GET / sem login, atendido por public/index.php
    P->>B: SELECT ... WHERE recados.publico AND recados.status = publicado
    B-->>P: só os recados públicos ainda publicados
    P-->>N: 200 com a lista, sem formulário e sem botões
    V->>N: clica em um recado, ou abre o endereço dele
    N->>P: GET /recado.php?id=N
    P->>B: SELECT ... FROM recados JOIN usuarios WHERE recados.id = N
    B-->>P: o recado, com publico, status e autor
    alt público e publicado
        P-->>N: 200 com o recado, sem o botão de arquivar
    else privado ou arquivado
        P-->>N: 303, Location: /login.php
    end
```

## Validação em três camadas

A regra do texto do recado aparece em três lugares, com papéis diferentes.

| Camada | Onde | Papel |
|---|---|---|
| Navegador | `maxlength` no `<textarea>` e `validar()` em `public/js/mural.js` | Conforto: avisa antes do envio e impede digitar além do limite |
| PHP | `validarRecado()` em `src/validacao.php` | Regra que vale: decide se o recado é gravado e qual mensagem a pessoa vê |
| Banco | restrições `CHECK` em `sql/schema.sql` | Última barreira: recusa a gravação se um defeito no PHP deixar passar um texto inválido |

O navegador não é confiável como barreira, porque qualquer pessoa pode enviar um
POST com `curl` sem passar pelo formulário. Por isso o PHP repete a conferência.

Um detalhe de contagem: o navegador envia cada quebra de linha do `<textarea>`
como `\r\n`, dois caracteres, mas o `maxlength` conta como um. A função
`normalizarQuebrasDeLinha()` troca `\r\n` por `\n` antes de validar, e assim o
PHP conta igual ao navegador. A contagem usa `mb_strlen`, que conta caracteres,
porque `strlen` conta bytes, e cada letra acentuada ocupa dois bytes em UTF-8.

## Cuidados de segurança

A tabela liga cada risco ao cuidado que o evita e ao arquivo onde ele está.

| Risco | Cuidado | Onde |
|---|---|---|
| Injeção de SQL | Todo valor vindo do pedido vai por parâmetro em `prepare` e `execute`; a ordem escolhe entre duas strings fixas | `src/recados.php` |
| Script injetado em recado (XSS) | Todo texto passa por `e()`, que chama `htmlspecialchars`, antes de ir para o HTML | `src/visao.php` e páginas |
| Redirecionamento para site externo | O destino depois de arquivar sai de duas opções fixas, nunca do pedido | `public/arquivar.php` |
| Mudança de dados por link | Publicar, arquivar, salvar a ordem e sair só aceitam POST | `src/http.php` (`exigirPost`, `ehPost`) |
| Arquivar recado de outra pessoa | A página confere o autor e responde 403; o `UPDATE` repete o filtro por `autor_id` | `public/arquivar.php` e `arquivarRecado()` |
| Formulário enviado por outro site (CSRF) | Token aleatório na sessão, conferido em todo POST com `hash_equals` | `src/sessao.php` e `src/inicializar.php` |
| Senha vazada do banco | Só o hash do `password_hash` vai para a tabela | `src/usuarios.php` |
| Descobrir quais nomes existem | Mesma mensagem e mesmo tempo para nome inexistente e senha errada | `conferirLogin()` em `src/usuarios.php` |
| Fixação de sessão | Identificador trocado no login e `session.use_strict_mode` ligado | `src/sessao.php` |
| Senha no log de erros | Parâmetros de senha com `#[\SensitiveParameter]` | `src/usuarios.php` e `src/validacao.php` |

Um risco ficou sem cuidado: não há limite de tentativas de login, e alguém
pode testar senhas à vontade. A decisão D20, em [decisoes.md](decisoes.md),
registra essa consequência.

## Com Docker

O `compose.yaml` monta as mesmas partes em contêineres. O contêiner `app` junta
servidor web e PHP, como na instalação com Apache e mod_php, e o `banco` roda o
PostgreSQL. O navegador continua falando só com o Apache, pela porta 8081 da
sua máquina.

```mermaid
flowchart LR
    navegador["Navegador"]

    subgraph maquina ["Sua máquina"]
        porta["localhost:8081"]
        subgraph compose ["Rede do Compose"]
            app["app: Apache + mod_php + código, porta 80"]
            banco[("banco: PostgreSQL, porta 5432")]
        end
        volume[("volume dados")]
    end

    navegador --> porta
    porta --> app
    app -- "host banco" --> banco
    banco --- volume
```

Dentro da rede do Compose, cada serviço é encontrado pelo próprio nome. Por
isso o `docker/config.php` usa `banco` como endereço do PostgreSQL. Dentro do contêiner,
`localhost` seria o próprio contêiner `app`. A porta 5432 do banco não é publicada na sua máquina: só o `app`
chega a ela.

Na primeira subida, com o volume `dados` vazio, a imagem do PostgreSQL cria o
usuário e o banco `mini_mural` e executa os arquivos montados em
`/docker-entrypoint-initdb.d`, primeiro o `schema.sql` e depois o `seed.sql`. A
aplicação só liga depois que o banco responde ao `pg_isready`, conferido pelo
`healthcheck` do `compose.yaml`.

As sessões do PHP ficam em arquivos dentro do contêiner `app`. Um
`docker compose up --build` cria um contêiner novo, sem esses arquivos, e todo
mundo precisa entrar de novo. Os usuários e recados continuam no volume `dados`.

## Quando algo dá errado

Uma exceção não tratada, como o banco desligado ou um SQL com defeito, cai no
tratador definido em `src/inicializar.php`. O detalhe técnico vai para o log de
erros, e o navegador recebe uma página 500 sem informação interna. Com Apache,
o log é o `mini-mural-erro.log` definido em `servidor/apache.conf`; com nginx,
é o log do PHP-FPM; com Docker, é a saída do contêiner, que `docker compose logs
app` mostra. Se a página "Algo deu errado" aparecer, leia o log.
