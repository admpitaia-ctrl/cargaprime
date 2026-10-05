# Regra final de stock - CargaPrime 3.6.3

## Fonte de verdade
O valor real está em `products.stock_tons` no MySQL. A página inicial e a administração apenas apresentam esse valor.

## Quando o stock é descontado
Adicionar ao carrinho não reserva nem desconta stock. O desconto acontece somente quando o utilizador confirma a encomenda em `checkout.php`.

O checkout abre uma transação, bloqueia as linhas dos produtos com `SELECT ... FOR UPDATE`, valida novamente as quantidades, grava `orders` e `order_items`, executa `UPDATE products SET stock_tons = stock_tons - ? ...` e só depois faz `COMMIT`. Se qualquer passo falhar, faz `ROLLBACK`.

## Exemplo obrigatório
- início: Gesso = 24 t;
- cliente A adiciona 19 t ao carrinho: continua 24 t;
- cliente A confirma a encomenda: passa para 5 t;
- cliente B confirma 5 t: passa para 0 t;
- qualquer tentativa seguinte (por exemplo 3 t) é recusada.

## Páginas abertas em simultâneo
`api/stock.php` é consultado periodicamente pelo JavaScript e quando a janela volta a ganhar foco. Isso atualiza o stock visível na loja/admin e refresca o carrinho se outra compra tiver alterado o stock.

## Base de dados final
A entrega usa um único ficheiro: `database/cargaprime_db.sql`. Foram removidas migrações antigas, incluindo a migração que repunha o Gesso em 24 t, porque essa instrução podia mascarar os resultados durante os testes.

## Diagnóstico
O administrador pode abrir `admin/stock-diagnostic.php` para ver o valor diretamente lido da tabela `products`.


## Alerta explícito de excesso de stock

O campo de quantidade permite ao utilizador escrever um valor superior ao stock apenas para fins de validação visual. Ao ultrapassar o limite, JavaScript apresenta a mensagem `Só existem X t disponíveis. Reduza a quantidade.` e bloqueia o envio AJAX. O PHP repete a mesma regra em `cart.php`, e o `checkout.php` volta a validar dentro da transação MySQL com `SELECT ... FOR UPDATE`.
