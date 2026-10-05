# Teste final de SEO, HTML e responsividade — CargaPrime 3.6.3

## A. W3C Nu HTML Checker

1. Abra `http://localhost/CargaPrime_3.6.3/` no Chrome.
2. Pressione `Ctrl+U` para abrir o código-fonte HTML gerado pelo PHP.
3. Pressione `Ctrl+A` e `Ctrl+C`.
4. Abra `https://validator.w3.org/nu/#textarea`.
5. Cole o HTML em **Check by: text input**.
6. Clique em **Check**.
7. Guarde uma captura que mostre claramente o resultado final.

As seis ocorrências anteriormente identificadas foram corrigidas no código desta versão. O reteste final já foi realizado e apresentou **0 erros e 0 avisos** (“Document checking completed. No errors or warnings to show.”). Estes passos ficam registados para permitir a repetição do teste, se necessário.

## B. Lighthouse — Mobile (procedimento de repetição)

1. Abra a página inicial.
2. `F12` → **Lighthouse**.
3. Mode: **Navigation (default)**.
4. Device: **Mobile**.
5. Marque Performance, Accessibility, Best Practices e SEO.
6. Execute **Analyze page load**.
7. Guarde captura dos quatro resultados.

## C. Lighthouse — Desktop (procedimento de repetição)

Repita o mesmo teste selecionando **Desktop**. Resultados podem variar entre execuções; para o relatório, registe os valores efetivamente obtidos.

## D. Conferência manual de SEO no código

Em `includes/header.php`, confirme:

- `<title>`;
- `<meta name="description">`;
- `<meta name="robots">`;
- `<link rel="canonical">`;
- Open Graph;
- Twitter Card;
- JSON-LD na página inicial.

Abra também `robots.txt` e `sitemap.xml`.

## E. Responsividade

Use DevTools → Toggle device toolbar:

- 390 ou 412 px para mobile;
- 1440 px para desktop.

Guarde capturas sem sobreposição, corte de conteúdo ou scroll horizontal indevido.


## F. Resultados finais registados

- W3C Nu HTML Checker: **0 erros e 0 avisos**.
- Lighthouse final: **Performance 76**, **Accessibility 96**, **Best Practices 100**, **SEO 100**.
- As capturas finais estão incorporadas no relatório CargaPrime 3.6.3.
