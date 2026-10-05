# Isolamento de carrinho e suporte

## Carrinho

O carrinho deixou de depender de uma única chave global de sessão. Para utilizadores autenticados, cada item é gravado em `cart_items` com a chave composta `user_id + product_id`. Assim, contas diferentes mantêm carrinhos diferentes mesmo quando utilizam o mesmo projeto e a mesma base de dados.

No login, o carrinho iniciado como visitante é integrado no carrinho autenticado. A função de merge valida os produtos e o stock atual, grava as quantidades válidas em `cart_items` e só depois remove da sessão os dados temporários de `guest_cart`. No logout, o carrinho autenticado permanece associado ao respetivo `user_id`, mas deixa de ser apresentado ao utilizador seguinte.

## Ajuda

Cada pedido de ajuda é associado a:

- `user_id` do cliente;
- `order_id` da encomenda;
- `order_item_id` do produto;
- `factory_name` e `factory_address` responsáveis.

O cliente só consegue criar ou responder a conversas que pertençam ao próprio `user_id`. A escolha da encomenda/produto é feita a partir de uma lista gerada exclusivamente com encomendas da conta autenticada.

O painel administrativo identifica a fábrica responsável em cada conversa e responde em nome dessa fábrica.

## Teste recomendado

1. Iniciar sessão como Cliente A num navegador normal e adicionar um produto ao carrinho.
2. Iniciar sessão como Cliente B numa janela anónima ou noutro navegador.
3. Confirmar que o carrinho do Cliente B está vazio ou contém apenas os seus próprios itens.
4. Abrir uma conversa de ajuda no Cliente A associada a uma encomenda/produto.
5. Confirmar que a conversa não aparece na área do Cliente B.
6. No administrador, confirmar que a conversa apresenta produto e fábrica responsável.
