# Mini Mural em PHP

Mini Mural é um mural de recados, aplicação de exemplo da disciplina de
Desenvolvimento WebMobile (UFT), feita como um monólito simples:

- HTML, CSS e JavaScript básicos no navegador;
- PHP no servidor, sem framework;
- PostgreSQL como banco, com o esquema em um arquivo SQL.

Não há Docker, Composer, migrations nem etapa de build.

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

Os passos abaixo usam Debian ou Ubuntu, o sistema mais comum em servidores e
nos laboratórios. A seção [No macOS](#no-macos) traz as diferenças.

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
e o log do PHP-FPM em `/var/log/` (nginx).

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
