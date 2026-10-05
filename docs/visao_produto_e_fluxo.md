# CargaPrime — visão do produto e fluxos

## Implementação atual 3.6.3

A CargaPrime é um protótipo académico B2B para compra simulada de materiais industriais por tonelada e acompanhamento logístico. O administrador representa a operação, gere produtos, stock, encomendas e apoio; o cliente compra e acompanha as suas encomendas.

Fluxo atual:
1. Administração cria ou edita produtos e identifica a fábrica/indústria responsável.
2. Cliente consulta `products.php`, pesquisa/filtra e adiciona toneladas ao carrinho.
3. Se necessário, cliente autentica-se; o carrinho de visitante é preservado.
4. Checkout revalida o stock real dentro de transação MySQL.
5. Encomenda é criada e o stock é descontado.
6. Administração atualiza estados e pode inserir um link externo de localização quando a encomenda está “A caminho”.
7. Cliente consulta estado, documento, ajuda e localização quando disponível.
8. Cliente pode confirmar a entrega quando a encomenda está “A caminho”.

## Evolução futura

A evolução comercial poderá incluir contas próprias para fábricas/indústrias, transportadoras e motoristas, aceitação e atribuição de fretes, associação do motorista, ETA e integrações documentais/fiscais. Estas funcionalidades não fazem parte do protótipo atual.

## Linguagem correta

- O carrinho não reserva stock; o checkout revalida o stock antes da confirmação.
- O link de localização é inserido manualmente pela administração; não existe GPS próprio nem tracking automático.
- Fábricas, transportadoras e motoristas não possuem login próprio na versão académica.
