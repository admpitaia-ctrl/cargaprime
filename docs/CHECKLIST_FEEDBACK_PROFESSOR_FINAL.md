# Checklist final — feedback do professor

## Pontos do feedback — concluídos

- [x] Carrinho de visitante preservado após login/registo e retorno ao carrinho quando existe seleção.
- [x] Percurso `Catálogo → Carrinho → Login ou registo → Checkout` restabelecido sem perda da seleção.
- [x] Documento de transporte corrigido: itens recuperados separadamente e escapados individualmente; deixou de existir HTML agregado em `GROUP_CONCAT`.
- [x] Teste de segurança realizado com `<b>PRODUTO TESTE</b>` apresentado literalmente no documento de transporte.
- [x] Alegação fixa “+40 produtos” removida; contagem passa a refletir a base de dados.
- [x] Página dedicada `products.php` criada para o catálogo completo.
- [x] Pesquisa por texto implementada.
- [x] Filtro por categoria implementado.
- [x] Filtro de disponibilidade implementado.
- [x] Produtos com stock apresentados antes dos produtos sem stock.
- [x] Produtos sem stock permanecem visíveis, no final, e bloqueados para compra.
- [x] Home limitada a seis produtos e acesso “Ver todos os produtos”.
- [x] Paginação real de seis produtos por página implementada.
- [x] Paginação validada com sete produtos ativos temporários: seis itens na Página 1 e um item na Página 2.
- [x] Controlos `Anterior`, `Seguinte`, `1` e `2` comprovados visualmente.
- [x] Navegação do catálogo com AJAX progressivo, mantendo URL e fallback por GET.
- [x] Texto sobre reserva de stock uniformizado: o carrinho não reserva stock; o checkout revalida antes de confirmar.
- [x] `sitemap.xml` inclui `products.php`.
- [x] Relatório atualizado com as correções, evidências e análise crítica ampliada.
- [x] Briefing atualizado para refletir o catálogo dedicado, paginação e fluxo real.
- [x] Funcionalidades futuras de fábrica/transportadora/motorista separadas das funcionalidades já implementadas.
- [x] Evidências Lighthouse/W3C, stock, autenticação, administração e concorrência de stock mantidas no relatório.
- [x] Evidências finais da paginação inseridas no relatório como Figuras 47, 48 e 49.

## Auditoria de coerência final

- [x] Seed SQL conferido: três produtos de demonstração numa instalação limpa.
- [x] Perfis do protótipo conferidos: apenas administrador e cliente; fábrica/transportadora/motorista permanecem evolução futura.
- [x] Link de localização descrito de forma fiel: inserção manual pela administração, sem GPS próprio, ETA automático ou associação automática de motorista.
- [x] Figma uniformizado entre relatório, briefing e README.
- [x] 49 figuras do relatório conferidas com as respetivas legendas; Figuras 47–49 revistas especificamente para a evidência de paginação.
- [x] Documentação interna antiga/conflitante removida.
- [x] PHP e JavaScript verificados sem erros de sintaxe.

## Conclusão

Todos os pontos de melhoria indicados pelo professor foram tratados no projeto ou na documentação e possuem evidência compatível com o que foi efetivamente implementado. O script SQL final inclui três produtos de demonstração numa instalação limpa. A paginação foi validada num ambiente local temporariamente configurado com sete produtos ativos; os registos adicionais usados nesse teste não integram o seed SQL entregue.

## Verificações opcionais antes do envio

Estas verificações não correspondem a pendências do feedback do professor, mas são recomendadas como smoke test imediatamente antes de submeter o ZIP:

- abrir a home e o catálogo uma vez em desktop e mobile;
- concluir uma encomenda de teste curta;
- confirmar que o ZIP abre e contém o projeto, relatório e briefing.
