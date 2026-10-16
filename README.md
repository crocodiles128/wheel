# Sorteio Siemens Energy

Aplicação web em PHP e MySQL para inscrição de corredores pelo número de peito e realização de sorteios no estilo de abertura de caixas do CS. A inscrição pública aplica limite de cinco tentativas por IP por minuto. Colaboradores autenticados podem operar o sorteio e encerrar as inscrições.

## Instalação

1. Importe `schema.sql` no MySQL.
2. Copie `db-config.example.php` para `db-config.php` e preencha as credenciais do banco.
3. Crie uma conta de colaborador em `admins`, armazenando a senha com `password_hash()` do PHP.
4. Hospede os arquivos em servidor com PHP e PDO MySQL habilitados.

## Páginas

- `index.html`: inscrição pública.
- `sorteio.php`: painel protegido dos colaboradores.
- `telao.php`: tela de apresentação do sorteio, aberta pelo painel.

Os sorteios são persistidos na tabela `sorteio`. A exportação e a restauração dos dados podem ser feitas pelas ferramentas usuais do MySQL.

## Direitos autorais

Consulte `LICENSE.md` para os termos de uso. A titularidade deve ser confirmada considerando a origem de cada componente e os acordos aplicáveis ao desenvolvimento.
