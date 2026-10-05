# CargaPrime 3.6.3 — stock concorrente e privacidade

## Regra de stock

- Adicionar ao carrinho **não reserva stock**.
- O stock é global na tabela `products`.
- O carrinho é isolado por `user_id`.
- No checkout, o servidor volta a ler o stock dentro de uma transação MySQL/InnoDB.
- As linhas dos produtos são bloqueadas com `SELECT ... FOR UPDATE`.
- O desconto usa ainda um `UPDATE` condicionado por `stock_tons >= quantidade`.
- Se qualquer produto deixar de ter stock suficiente, ocorre `ROLLBACK`: não é criada encomenda parcial e nenhum stock fica negativo.
- Um carrinho antigo com 3 t não consegue comprar se outros clientes já consumiram o stock até 0 t.
- O carrinho aberto é sincronizado periodicamente e bloqueia visualmente o checkout quando deteta stock insuficiente.

### Teste recomendado
1. Definir um produto com 24 t.
2. Cliente A coloca 19 t no carrinho.
3. Cliente B coloca 5 t no carrinho.
4. Cliente C coloca 3 t no carrinho.
5. A confirma: stock 5 t.
6. B confirma: stock 0 t.
7. C tenta confirmar: deve receber erro de stock insuficiente e nenhuma encomenda nova deve ser criada.

## Privacidade por cliente

- `cart_items` usa chave `(user_id, product_id)`.
- Cada cliente só lê e altera o próprio carrinho.
- `orders.php` filtra encomendas pelo utilizador autenticado.
- `support_requests` pertence ao `user_id` que abriu a conversa.
- Um cliente só consegue abrir suporte para `order_items` de encomendas que lhe pertencem.
- Cada conversa guarda a fábrica associada ao item da encomenda.
- Respostas do cliente são verificadas novamente por `request_id + user_id`.

## Nota de teste

Para representar clientes simultâneos, use sessões realmente separadas:
- Chrome normal;
- Chrome incógnito;
- Firefox/Edge ou outro perfil de navegador.
