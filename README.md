# CargaPrime

**Plataforma B2B full-stack para logística e fornecimento industrial.**  
Projeto final desenvolvido no âmbito do Curso Avançado em Desenvolvimento Web — Master D.

## Sobre o projeto

A CargaPrime foi criada a partir de um problema real observado no setor da logística e do fornecimento industrial: informação de produto, stock, encomenda e acompanhamento do transporte pode ficar dispersa entre cliente, fornecedor e operação.

O projeto simula uma plataforma B2B onde clientes empresariais podem consultar materiais industriais, verificar stock, adicionar quantidades ao carrinho, concluir encomendas e acompanhar o respetivo estado.

## Funcionalidades principais

- Catálogo com pesquisa, filtros e paginação no servidor
- Autenticação e perfis de acesso
- Carrinho persistente por utilizador
- Checkout com validação de stock
- Gestão administrativa de produtos, stock e encomendas
- Área de cliente com acompanhamento de encomendas
- Suporte associado ao utilizador e à encomenda
- Documento de transporte
- Integração de link externo para localização/acompanhamento
- SEO técnico, sitemap, robots.txt, Open Graph e JSON-LD

## Destaque técnico

O checkout utiliza **transações MySQL/InnoDB** e `SELECT ... FOR UPDATE` para voltar a validar o stock no momento da confirmação da compra. Isso evita que duas encomendas consumam simultaneamente as mesmas unidades disponíveis.

## Tecnologias

`PHP` · `MySQL` · `JavaScript` · `HTML5` · `CSS3` · `PDO` · `AJAX`

**Ferramentas:** Figma · XAMPP · phpMyAdmin · VS Code

## Segurança implementada

- `password_hash` / `password_verify`
- Prepared statements com PDO
- Tokens CSRF
- Regeneração de sessão no login
- Escape de saída HTML
- Controlo de acesso por função
- Validação no cliente e no servidor
- Transações para consistência do stock

## Resultados de validação

Na validação final documentada do projeto:

- W3C Nu HTML Checker: **0 erros e 0 avisos**
- Lighthouse: **Performance 76 · Accessibility 96 · Best Practices 100 · SEO 100**

## Instalação local

1. Copiar a pasta do projeto para `C:\xampp\htdocs\`.
2. Iniciar Apache e MySQL no XAMPP.
3. Importar `database/cargaprime_db.sql` no phpMyAdmin.
4. Confirmar a porta MySQL em `config/database.php`.
5. Abrir o projeto no navegador através do `localhost`.

### Conta de demonstração

- Utilizador: `admin`
- Palavra-passe: `Admin@123`

> Estas credenciais existem apenas para demonstração local do projeto académico.

## Design

O projeto foi planeado com wireframes mobile/desktop e style guide em Figma.

## Estrutura

- `admin/` — gestão administrativa
- `api/` — endpoints usados por AJAX e sincronização de stock
- `assets/` — CSS, JavaScript, imagens e media
- `config/` — configuração e ligação à base de dados
- `database/` — estrutura e dados iniciais MySQL
- `docs/` — documentação técnica e testes
- `includes/` — componentes e funções reutilizáveis

## Contexto

Projeto académico e demonstrativo. O domínio `cargaprime.pt` é utilizado apenas como referência simulada no trabalho de SEO e não representa um serviço comercial ativo.
