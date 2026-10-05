# Briefing — CargaPrime 3.6.3

## 1. Enquadramento e problema

A CargaPrime é um projeto académico de uma plataforma B2B com loja online para consulta e encomenda simulada de materiais e cargas industriais vendidos por tonelada. O protótipo liga catálogo, stock, compra e acompanhamento logístico. Na implementação atual, os perfis existentes são cliente e administrador; os produtos são identificados pela respetiva fábrica/indústria, mas não existem contas próprias de fábrica, transportadora ou motorista.

Problemas abordados:
- falta de transparência sobre o stock disponível;
- cálculo e consulta de preço por tonelada;
- pedidos distribuídos por vários canais;
- dificuldade em acompanhar preparação, estado e localização disponibilizada para a carga.

## 2. Objetivo do website

Desenvolver uma plataforma B2B responsiva e mobile first com página inicial, catálogo dedicado, pesquisa, filtros, paginação, carrinho, autenticação, checkout simulado, área do cliente, administração, controlo de stock e acompanhamento por estados e link externo de localização.

## 3. Público-alvo

### Implementado no protótipo
- clientes profissionais, responsáveis de compras, revendedores e distribuidores;
- administração da plataforma, que gere catálogo, stock, encomendas, apoio e localização;
- fábricas/indústrias representadas nos dados dos produtos e na origem da mercadoria.

### Evolução futura
- contas próprias para fábricas/indústrias;
- transportadoras com receção e aceitação de carregamentos;
- motoristas associados às operações e à partilha de localização.

## 4. Regras e objetivos específicos

- catálogo completo em `products.php` com pesquisa, categoria, disponibilidade e paginação de 6 resultados por página;
- produtos com stock apresentados primeiro; produtos sem stock continuam visíveis no final e bloqueados para compra;
- home limitada a 6 produtos e acesso “Ver todos os produtos”;
- quantidade mínima e incremento de 0,5 t;
- o carrinho não reserva stock;
- stock global revalidado no checkout dentro de transação MySQL;
- carrinho de visitante preservado após login/registo;
- isolamento de carrinhos, encomendas e apoio por utilizador autenticado;
- estados, documento de transporte, pedidos de ajuda, localização por link externo e confirmação de entrega;
- segurança, acessibilidade, responsividade e SEO.

## 5. Fluxo de compra implementado

Início → Catálogo de produtos → Carrinho → Login ou registo → Checkout → Confirmação.

Quando existe uma seleção iniciada como visitante, ela é integrada no carrinho do cliente após autenticação. O stock é validado novamente no checkout e só é descontado quando a encomenda é confirmada.

## 6. Fluxo logístico implementado

Administração disponibiliza produtos associados às respetivas fábricas/indústrias → cliente compra → administração valida e atualiza o estado → quando a encomenda fica “A caminho”, a administração pode inserir um link externo Google Maps → cliente acompanha a localização → cliente confirma a entrega.

O protótipo não possui GPS próprio, tracking automático, ETA automático nem associação automática de transportadora/motorista.

## 7. Mapa do website

- Início → até 6 produtos → Ver todos os produtos → `products.php` → Carrinho → Login/registo → Checkout → Confirmação.
- Login → As minhas encomendas → Estado / localização / documento / ajuda.
- Administração → Resumo → Encomendas → Pedidos de ajuda → Produtos → Diagnóstico de stock.
- Evolução futura → Fábricas / transportadoras / motoristas / veículos / atribuições de frete.

## 8. Regra operacional de stock

O carrinho não reserva stock. O stock pertence ao produto na base de dados e é partilhado entre clientes. No checkout, a aplicação utiliza transação e `SELECT ... FOR UPDATE`, revalida a disponibilidade e só então grava a encomenda e desconta a quantidade. Selecionar exatamente o stock existente é permitido, mas não constitui reserva antecipada.

## 9. Identidade visual

- azul-marinho `#07182C`;
- laranja `#F37A22`;
- bege `#F3F0EA`;
- branco `#FFFFFF`;
- marca `CargaPrime.` com “Prime” em laranja.

## 10. Domínio e alojamento simulados

- domínio académico: `cargaprime.pt`;
- `APP_DOMAIN` e `APP_CANONICAL_BASE_URL` em `config/config.php`;
- projeto executado localmente em XAMPP;
- publicação, domínio e pagamentos não foram contratados.

## 11. Base inicial entregue

O ficheiro `database/cargaprime_db.sql` cria a estrutura e insere três produtos de demonstração numa instalação limpa. Registos adicionais utilizados em testes de paginação não integram o seed SQL final.

## 12. Evolução futura

- contas próprias de fábricas/indústrias;
- transportadoras, motoristas e veículos;
- aceitação/atribuição de fretes;
- integrações fiscais e pagamentos reais;
- ETA, geofencing, alertas e automações de comunicação.

## 13. Figma

- Wireframe Mobile First: https://www.figma.com/design/xP67FT5EjgqQulYOh8FL5S/CargaPrime-%E2%80%94-Figma?node-id=4-2&t=crFxOPRgiF6i6fF1-1
- Wireframe Desktop: https://www.figma.com/design/xP67FT5EjgqQulYOh8FL5S/CargaPrime-%E2%80%94-Figma?node-id=97-2&t=crFxOPRgiF6i6fF1-1
- Style Guide: https://www.figma.com/design/xP67FT5EjgqQulYOh8FL5S/CargaPrime-%E2%80%94-Figma?node-id=273-255&t=crFxOPRgiF6i6fF1-1
