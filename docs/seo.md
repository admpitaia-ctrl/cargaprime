# SEO — CargaPrime 3.6.3

## Objetivo

Aplicar a pirâmide de SEO solicitada no projeto final: base técnica, conteúdo/on-page e autoridade. Como a CargaPrime é executada localmente no XAMPP, a autoridade externa é descrita como estratégia futura, enquanto a base técnica e o conteúdo são demonstráveis no código e por auditorias locais.

## 1. Base técnica implementada

- `lang="pt-PT"` e `meta viewport`.
- `title` e meta description específicos por página.
- `canonical` baseado em `https://www.cargaprime.pt` (domínio académico simulado).
- `meta robots`: páginas públicas indexáveis; páginas privadas/administração em `noindex, nofollow`.
- Open Graph e Twitter Card com título, descrição, URL, imagem e `image:alt`.
- JSON-LD `OnlineStore` na página inicial, com idioma `pt-PT` e área servida `PT`.
- `robots.txt` a bloquear áreas internas e a referenciar o sitemap.
- `sitemap.xml` com as páginas públicas previstas.
- estrutura responsiva mobile first.
- semântica HTML revista com base no W3C Nu HTML Checker.

## 2. Conteúdo / on-page

A página inicial responde a intenções coerentes com o protótipo: materiais industriais, compra por tonelada, stock disponível, preparação e acompanhamento do transporte. Os títulos e descrições evitam prometer integrações futuras ainda não implementadas.

## 3. Autoridade — aplicação futura

Numa publicação real, a autoridade poderia ser trabalhada através de conteúdos técnicos, páginas de fabricantes/parceiros, referências em associações do setor, artigos sobre logística industrial e backlinks relevantes. Esta camada não é apresentada como concluída no protótipo local.

## 4. Auditoria Lighthouse final

Na auditoria final realizada em ambiente local foram observados:

- SEO: 100/100;
- Best Practices: 100/100;
- Accessibility: 96/100;
- Performance: 76/100 no registo final (com variação de 75–76/100 entre execuções locais anteriores).

A pontuação de performance não é uma pontuação de SEO e pode variar devido ao vídeo/imagens, ao ambiente local, cache e condições da execução.

## 5. Validação W3C final

O primeiro teste W3C detetou seis ocorrências de conteúdo HTML inválido: um `div` dentro de `label` e cinco `div` dentro de `button`. Foram corrigidas em `index.php` com elementos `span` semanticamente válidos, mantendo as mesmas classes/estrutura visual por CSS.

Após a correção, foi repetida a validação no W3C Nu HTML Checker. O resultado final foi **0 erros e 0 avisos**, com a mensagem **“Document checking completed. No errors or warnings to show.”**. Esta evidência está incorporada no relatório final.

## 6. Provas incorporadas no relatório

1. captura de `includes/header.php` mostrando title/description/robots/canonical/Open Graph/Twitter/JSON-LD;
2. captura de `robots.txt`;
3. captura de `sitemap.xml`;
4. Lighthouse com os quatro indicadores visíveis;
5. W3C Nu HTML Checker após a correção, com **0 erros e 0 avisos** visíveis;
6. captura responsiva em 390/412 px e 1440 px.
