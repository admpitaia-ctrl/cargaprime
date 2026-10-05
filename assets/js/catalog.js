/* CargaPrime — catálogo com navegação AJAX progressiva.
 *
 * Regras:
 * - filtros, pesquisa e paginação continuam a funcionar sem JavaScript;
 * - com JavaScript, a secção do catálogo é atualizada sem recarregar a página;
 * - a posição de scroll é preservada;
 * - a URL do navegador é atualizada para permitir copiar/voltar/avançar;
 * - a pesquisa atualiza automaticamente após uma pequena pausa na digitação.
 */
(() => {
  const selector = '.cp3-catalog-page';
  let requestController = null;
  let searchTimer = null;

  const currentCatalog = () => document.querySelector(selector);

  const revealNewContent = (root) => {
    root.querySelectorAll('.cp3-reveal').forEach((node) => node.classList.add('is-visible'));
  };

  const setLoading = (loading) => {
    currentCatalog()?.classList.toggle('is-catalog-loading', loading);
  };

  const loadCatalog = async (url, {push = true, preserveScroll = true} = {}) => {
    const catalog = currentCatalog();
    if (!catalog) return;

    const scrollY = window.scrollY;
    requestController?.abort();
    requestController = new AbortController();
    setLoading(true);

    try {
      const response = await fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html',
        },
        cache: 'no-store',
        signal: requestController.signal,
      });

      if (!response.ok) throw new Error('Não foi possível atualizar o catálogo.');

      const html = await response.text();
      const page = new DOMParser().parseFromString(html, 'text/html');
      const replacement = page.querySelector(selector);
      if (!replacement) throw new Error('Resposta de catálogo inválida.');

      catalog.replaceWith(replacement);
      revealNewContent(replacement);

      if (push) history.pushState({catalog: true}, '', response.url);
      document.title = page.title || document.title;

      if (preserveScroll) {
        requestAnimationFrame(() => window.scrollTo({top: scrollY, left: 0, behavior: 'auto'}));
      }

      document.dispatchEvent(new CustomEvent('cargaprime:catalog-updated'));
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error(error);
      // Fallback progressivo: se AJAX falhar, a navegação normal continua válida.
      window.location.assign(url);
    } finally {
      setLoading(false);
    }
  };

  const submitSearch = (form) => {
    const data = new FormData(form);
    data.delete('page');
    const query = new URLSearchParams();
    for (const [key, value] of data.entries()) {
      const trimmed = String(value).trim();
      if (trimmed !== '' && trimmed !== 'all') query.set(key, trimmed);
    }
    const action = form.getAttribute('action') || window.location.pathname;
    const target = `${action}${query.toString() ? `?${query.toString()}` : ''}`;
    loadCatalog(target);
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest(`${selector} a[href]`);
    if (!link) return;

    const target = new URL(link.href, window.location.href);
    const productsPath = new URL(`${document.documentElement.dataset.appBase || ''}/products.php`, window.location.origin).pathname;
    if (target.origin !== window.location.origin || target.pathname !== productsPath) return;

    event.preventDefault();
    loadCatalog(target.href);
  });

  document.addEventListener('submit', (event) => {
    const form = event.target.closest(`${selector} form.cp3-catalog-search`);
    if (!form) return;
    event.preventDefault();
    clearTimeout(searchTimer);
    submitSearch(form);
  });

  document.addEventListener('input', (event) => {
    const input = event.target.closest(`${selector} .cp3-catalog-search input[type="search"]`);
    if (!input) return;
    const form = input.form;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => submitSearch(form), 420);
  });

  window.addEventListener('popstate', () => {
    if (currentCatalog()) loadCatalog(window.location.href, {push: false});
  });

  // Quando outra sessão altera stock, o sincronizador global atualiza os cards.
  // Este evento também permite recalcular contadores/ordenação sem recarregar a página.
  document.addEventListener('cargaprime:stock-changed', () => {
    if (currentCatalog()) loadCatalog(window.location.href, {push: false});
  });
})();
