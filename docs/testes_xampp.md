# Testes finais no XAMPP

Execute esta lista depois de copiar a pasta para `C:\xampp\htdocs\` e importar `database/cargaprime_db.sql`.

**Teste crítico primeiro:** Gesso 24 t → adicionar 19 t ao carrinho (continua 24 t) → confirmar encomenda (fica 5 t) → segunda encomenda 5 t (fica 0 t) → terceira tentativa 3 t (recusada). Confirme também em `admin/stock-diagnostic.php`.

1. Abra `http://localhost/CargaPrime_3.6.3/` e confirme que cores, cartões e menu aparecem corretamente.
2. Abra `products.php`, pesquise “gesso”, alterne entre categorias e entre “Todos”/“Em stock”. A atualização deve ocorrer sem recarregar a página inteira e a URL deve continuar refletindo os filtros.
3. Ative e silencie o som em dois vídeos diferentes; apenas um vídeo deve emitir som de cada vez.
4. Adicione uma quantidade inferior ao stock e altere-a no carrinho; totais e contador devem atualizar sem mudar a posição da página.
5. Tente indicar mais toneladas do que as disponíveis no catálogo e no carrinho; o campo deve aceitar a digitação para que a aplicação apresente o alerta explícito “Só existem X t disponíveis. Reduza a quantidade.”. O pedido AJAX deve ser recusado, o valor não deve entrar na sessão e a encomenda não pode ser finalizada.
6. Crie um cliente maior de idade e confirme os botões “Ver” das duas palavras-passe.
7. Tente criar um cliente menor de idade; o registo deve ser recusado.
8. Como cliente, finalize uma encomenda escolhendo data e horário e consulte `As minhas encomendas`.
9. Entre como `admin` / `Admin@123`, edite um produto, troque a imagem e guarde sem recarregar.
10. Crie um produto com categoria e imagem; confirme que aparece na loja e na pesquisa.
11. Na Administração, altere o pedido de `Pendente` para `Aprovada` e confirme a resposta imediata.
12. Faça uma encomenda com exatamente todo o stock de um produto; o stock deve passar para zero.
13. Com um artigo no carrinho, reduza o respetivo stock na Administração para um valor inferior à quantidade selecionada; ao regressar ao carrinho, deve aparecer o aviso e o botão `Concluir compra` deve ficar bloqueado.
14. Adicione produtos na página inicial e confirme que “Carrinho atual” e “Valor total” mudam sem recarregar a página.
15. Faça uma encomenda com exatamente todo o stock e confirme que o cartão passa a apresentar “Sem stock” e o botão “Indisponível”.
16. Sem iniciar sessão, clique em “Administração” no rodapé e confirme que o login é solicitado.
17. Abra o código-fonte da página inicial e confirme a existência de description, canonical, Open Graph, Twitter Card e dados estruturados.
18. Abra `robots.txt` e `sitemap.xml` pelo endereço local e confirme que ambos são apresentados sem erro.

## Nota sobre a base de dados

A configuração está preparada para MySQL na porta `3308`, conforme o seu XAMPP. Se o seu painel mostrar outra porta, altere apenas `DB_PORT` em `config/database.php`.

## Teste de privacidade entre clientes

- Cliente A (janela normal): adicionar um produto ao carrinho.
- Cliente B (janela anónima/outro navegador): iniciar sessão e confirmar que não vê o carrinho do Cliente A.
- Cliente A: abrir Ajuda e criar conversa associada a uma encomenda/produto.
- Cliente B: abrir Ajuda e confirmar que não vê a conversa do Cliente A.
- Administração: abrir Pedidos de ajuda e confirmar que a conversa identifica a fábrica responsável.

**Importante:** duas abas do mesmo perfil do navegador partilham a mesma sessão PHP. Use sessões de navegador separadas para representar clientes simultâneos.


## Testes acrescentados após feedback de avaliação

- **Carrinho de visitante após login:** em janela anónima, adicione 0,5 t; clique em “Entrar para concluir”; autentique-se e confirme que a seleção continua no carrinho. Se a conta já possuir itens, confirme a integração sem perda do carrinho existente.
- **Documento de transporte / escape:** introduza temporariamente um nome como `<b>PRODUTO TESTE</b>`, gere uma encomenda e confirme que o documento apresenta as tags literalmente, sem negrito nem execução de HTML/JavaScript. Restaure o nome do produto após o teste.
- **Paginação do catálogo — validada:** teste executado com 7 produtos ativos temporários. Resultado observado: 6 itens na Página 1, 1 item na Página 2, controlos Anterior/Seguinte e numeração 1/2 funcionais; a home manteve o limite de 6 produtos e apresentou “Ver todos os produtos”. Os dados temporários não integram o seed final.
- **Home:** com mais de 6 produtos ativos, confirme que a home continua a mostrar no máximo 6 e apresenta “Ver todos os produtos”, levando a `products.php`.
