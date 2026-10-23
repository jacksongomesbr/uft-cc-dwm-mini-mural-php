# Mini Mural em PHP

Mini Mural é um mural de recados, aplicação de exemplo da disciplina de
Desenvolvimento WebMobile (UFT), feita como um monólito simples:

- HTML, CSS e JavaScript básicos no navegador;
- PHP no servidor, sem framework;
- PostgreSQL como banco, com o esquema em um arquivo SQL.

Não há Composer, migrations nem etapa de build. Para rodar na sua máquina sem
instalar PHP, servidor web e PostgreSQL, use o [Docker](#rodar-com-docker).

## Funcionalidades

- Publicar um recado de até 280 caracteres, com contador e aviso de erro.
- Ver a lista de recados com hora e o resumo de publicados e arquivados.
- Abrir o detalhe de um recado, com data e hora por extenso.
- Arquivar um recado pela lista ou pelo detalhe.
- Escolher, em Sobre, a ordem do mural, guardada no navegador.

Como cada uma funciona, e por quê, está em [docs/](docs/README.md).

## O que você precisa

- PHP 8.1 ou mais novo, com as extensões `pdo_pgsql` e `mbstring`.
- PostgreSQL 14 ou mais novo, com o cliente `psql`.
- Um servidor web, em uma destas duas combinações:
  - Apache com mod_php, em que o PHP roda dentro do próprio Apache;
  - nginx com PHP-FPM, em que o nginx repassa os arquivos `.php` a um processo
    PHP separado.

As duas funcionam igual para o projeto. A pasta `servidor/` tem a configuração
pronta de cada uma, e a decisão D4, em [docs/decisoes.md](docs/decisoes.md),
explica por que o projeto usa um servidor web de verdade.

Os passos de instalação usam Debian ou Ubuntu, o sistema mais comum em
servidores e nos laboratórios. A seção [No macOS](#no-macos) traz as
diferenças.

## Rodar com Docker

O Docker sobe a aplicação em dois contêineres: `app`, com Apache, mod_php e o
código, e `banco`, com o PostgreSQL. Você precisa só do Docker com o Compose
(Docker Desktop, ou Docker Engine com o plugin `compose`).

Na pasta do projeto:

```bash
docker compose up --build
```

Quando o terminal mostrar o Apache pronto, abra <http://localhost:8081>. Na
primeira vez, o PostgreSQL roda o `sql/schema.sql` e o `sql/seed.sql`, e o
mural já aparece com os recados de exemplo.

O terminal fica preso mostrando os logs dos dois contêineres, inclusive os
erros do PHP. Para parar, use Ctrl+C. Os comandos abaixo rodam em outro
terminal, na mesma pasta.

Para desligar e remover os contêineres, mantendo os recados:

```bash
docker compose down
```

Para apagar também os recados e voltar aos exemplos na próxima subida:

```bash
docker compose down -v
```

Três detalhes mudam em relação à instalação direta:

- **Configuração:** o contêiner usa `docker/config.php`, que lê usuário, senha
  e endereço do banco das variáveis de ambiente de `compose.yaml`. O seu
  `config.php` local não entra na imagem. A senha de `compose.yaml` serve só
  para a sua máquina.
- **Mudança no código:** a imagem guarda uma cópia do código. Depois de mudar um
  arquivo, rode `docker compose up --build` de novo.
- **Mudança no esquema:** o PostgreSQL só roda o `schema.sql` com o volume de
  dados vazio. Depois de mudar o esquema, rode `docker compose down -v`, que
  apaga os recados, e suba de novo.

O `compose.yaml` serve só para desenvolvimento: as senhas estão no próprio
arquivo, e a conexão usa HTTP simples, sem HTTPS. A decisão D19, em [docs/decisoes.md](docs/decisoes.md),
explica o que foi escolhido e por quê.

## Instalar no Debian ou no Ubuntu

### 1. Instalar os pacotes

Escolha uma das duas linhas.

Com Apache:

```bash
sudo apt install apache2 libapache2-mod-php php-pgsql php-mbstring postgresql
```

Com nginx:

```bash
sudo apt install nginx php-fpm php-pgsql php-mbstring postgresql
```

### 2. Colocar o projeto em `/var/www/mini-mural`

O servidor web roda com o usuário `www-data`, que em geral não consegue ler
pastas dentro de `/home`. As configurações de `servidor/` apontam para
`/var/www/mini-mural`. Crie a pasta, passe a posse para você e copie o projeto
para lá:

```bash
sudo mkdir -p /var/www/mini-mural
```

```bash
sudo chown "$USER" /var/www/mini-mural
```

```bash
cp -r . /var/www/mini-mural
```

O último comando roda dentro da pasta do projeto. Os passos seguintes rodam em
`/var/www/mini-mural`. Se preferir outra pasta, troque o caminho nos dois
arquivos de `servidor/`.

### 3. Criar o usuário e o banco

A aplicação conecta com um usuário próprio, `mini_mural` (decisão D8). O
primeiro comando pede a senha que você vai escolher:

```bash
sudo -u postgres createuser --pwprompt mini_mural
```

```bash
sudo -u postgres createdb --owner mini_mural mini_mural
```

Carregue o esquema conectado como `mini_mural`, para que a tabela pertença a
ele. O `psql` pede a senha do passo anterior:

```bash
psql -h localhost -U mini_mural -d mini_mural -f sql/schema.sql
```

O `schema.sql` começa com `DROP TABLE`: rodá-lo de novo apaga todos os recados.
É assim que o projeto muda de esquema, já que não usa migrations.

Os recados de exemplo são opcionais:

```bash
psql -h localhost -U mini_mural -d mini_mural -f sql/seed.sql
```

### 4. Configurar a conexão

```bash
cp config.exemplo.php config.php
```

Abra o `config.php` e troque `senha` pela senha escolhida no passo 3. Os outros
valores já correspondem ao que você criou. O `config.php` fica fora do Git e
fora de `public/`.

### 5. Ligar o site no servidor web

Com Apache:

```bash
sudo cp servidor/apache.conf /etc/apache2/sites-available/mini-mural.conf
```

```bash
sudo a2ensite mini-mural
```

```bash
sudo systemctl reload apache2
```

Com nginx, confira primeiro o nome do socket do PHP-FPM com `ls /run/php/`. Se
ele não for `php8.3-fpm.sock`, corrija a linha `fastcgi_pass` em
`servidor/nginx.conf`. Depois:

```bash
sudo cp servidor/nginx.conf /etc/nginx/sites-available/mini-mural
```

```bash
sudo ln -s /etc/nginx/sites-available/mini-mural /etc/nginx/sites-enabled/
```

```bash
sudo nginx -t
```

```bash
sudo systemctl reload nginx
```

### 6. Abrir

Abra <http://localhost:8081>. As duas configurações usam a porta 8081 porque a
80 já é do site padrão do servidor, e a 8080, do site padrão do nginx no
Homebrew.

## No macOS

Use nginx com PHP-FPM, instalados pelo Homebrew. O Apache que vem com o macOS
não traz mais o mod_php.

```bash
brew install postgresql@18 php nginx
```

O comando `brew install php` instala também o PHP-FPM, já com `pdo_pgsql`. As
diferenças em relação aos passos do Debian são estas:

- **Passo 2:** o PHP-FPM do Homebrew roda com o seu usuário, então o projeto
  pode ficar onde está. Troque `/var/www/mini-mural` em `servidor/nginx.conf`
  pela pasta do projeto.
- **Passo 3:** o PostgreSQL do Homebrew usa o seu usuário do macOS como
  administrador. Rode `createuser` e `createdb` sem o `sudo -u postgres`. Antes,
  ponha os comandos no `PATH` e ligue o banco:

  ```bash
  echo 'export PATH="$(brew --prefix postgresql@18)/bin:$PATH"' >> ~/.zshrc
  ```

  ```bash
  brew services start postgresql@18
  ```

- **Passo 5:** em `servidor/nginx.conf`, troque o `fastcgi_pass` por
  `127.0.0.1:9000`. Copie o arquivo para a pasta de sites do nginx e ligue os
  dois serviços:

  ```bash
  cp servidor/nginx.conf "$(brew --prefix)/etc/nginx/servers/mini-mural.conf"
  ```

  ```bash
  brew services start php
  ```

  ```bash
  brew services start nginx
  ```

## Conferir

Rode os testes das regras, que não precisam de banco nem de servidor:

```bash
php tests/regras_test.php
```

Com o site no ar, exercite o mural no navegador:

1. Clique em Publicar com o campo vazio. A mensagem aparece e o foco volta ao
   campo.
2. Publique um recado. Ele aparece no topo da lista e o resumo muda.
3. Arquive um recado pela lista e outro pelo detalhe. Os dois passam a mostrar
   "Arquivado".
4. Abra <http://localhost:8081/recado.php?id=999>. A página diz que o recado não
   está mais no mural.
5. Em Sobre, escolha "Mais antigos primeiro", salve e volte ao Mural. A ordem se
   inverte e continua assim depois de recarregar.

Confira também que o servidor recusa um texto inválido mesmo sem o
JavaScript. O comando abaixo envia um recado vazio direto ao PHP e mostra o
código da resposta, que deve ser `422`:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -d 'texto=' http://localhost:8081/
```

E que o servidor não entrega nada fora de `public/`. A resposta deve ser `404`:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8081/config.php
```

## Estrutura

```text
mini-mural/
├── config.exemplo.php     # Modelo da configuração; copie para config.php
├── compose.yaml           # Serviços do Docker: app e banco
├── docker/
│   ├── Dockerfile         # Imagem com Apache, mod_php e pdo_pgsql
│   ├── apache.conf        # Site do Apache dentro do contêiner
│   └── config.php         # Configuração lida das variáveis de ambiente
├── public/                # Raiz de documentos do servidor web
│   ├── index.php          # Mural: lista, resumo e formulário (GET e POST)
│   ├── recado.php         # Detalhe de um recado
│   ├── arquivar.php       # Ação de arquivar (só POST)
│   ├── sobre.php          # Descrição e ordem do mural
│   ├── css/estilo.css
│   └── js/mural.js        # Contador e aviso de erro do formulário
├── src/                   # Funções do projeto; o navegador não alcança
│   ├── inicializar.php    # Incluído no topo de toda página
│   ├── banco.php          # Conexão PDO
│   ├── recados.php        # Todo o SQL de recados
│   ├── validacao.php      # Regra do texto do recado
│   ├── preferencias.php   # Cookie da ordem
│   ├── formatacao.php     # Datas e fuso
│   ├── http.php           # Leitura do pedido e redirecionamento
│   ├── visao.php          # Escape de HTML e esqueleto das páginas
│   └── templates/         # Cabeçalho, rodapé, 404 e erro
├── servidor/
│   ├── apache.conf        # Site para Apache com mod_php
│   └── nginx.conf         # Site para nginx com PHP-FPM
├── sql/
│   ├── schema.sql         # Cria a tabela (apaga a anterior)
│   └── seed.sql           # Recados de exemplo
├── tests/regras_test.php  # Testes das regras sem banco
└── docs/                  # Decisões, funcionamento e diagramas
```

## Problemas comuns

O detalhe de quase todo erro está no log. No Debian e no Ubuntu, leia
`/var/log/apache2/mini-mural-erro.log` (Apache) ou `/var/log/nginx/error.log`
e o log do PHP-FPM em `/var/log/` (nginx). Com Docker, leia
`docker compose logs app`.

| Sintoma | Causa provável | O que fazer |
|---|---|---|
| Página "Algo deu errado" e `password authentication failed` no log | Senha do `config.php` diferente da escolhida | Corrija `senha` no `config.php` |
| Página "Algo deu errado" e `connection refused` no log | O PostgreSQL está desligado | `sudo systemctl start postgresql` |
| `relation "recados" does not exist` no log | O esquema não foi carregado | Rode o `schema.sql` do passo 3 |
| `could not find driver` no log | Falta a extensão `pdo_pgsql` | Instale `php-pgsql` e recarregue o servidor |
| `502 Bad Gateway` (nginx) | O `fastcgi_pass` aponta para um socket que não existe | Confira `ls /run/php/` e corrija `servidor/nginx.conf` |
| `403 Forbidden` | O usuário `www-data` não consegue ler a pasta do projeto | Confira o passo 2 |
| O navegador baixa o `.php` em vez de mostrar a página | O PHP não está ligado ao servidor | Instale `libapache2-mod-php` (Apache) ou confira o bloco `location ~ \.php$` (nginx) |
| Texto "Arquivo config.php não encontrado" | Passo 4 não feito | Copie `config.exemplo.php` para `config.php` |
| `Cannot connect to the Docker daemon` (Docker) | O Docker não está ligado | Abra o Docker Desktop, ou ligue o serviço do Docker |
| `port is already allocated` (Docker) | Outro programa já usa a porta 8081, como o Apache da instalação direta | Desligue o outro programa, ou troque `"8081:80"` em `compose.yaml` por outra porta |
| Mudança no esquema não aparece (Docker) | O volume de dados já existia, e o PostgreSQL não roda o `schema.sql` de novo | `docker compose down -v` e suba outra vez |
