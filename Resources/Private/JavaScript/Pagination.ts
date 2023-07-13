namespace Zeroseven {
  type Map = {
    replace: HTMLElement[],
    append: HTMLElement[]
  };

  const dataAttributes = {
    loading: 'loading'
  }

  const getMap = (replaceSelectors: string[], appendSelectors: string[], doc?: Document): Map => ({
    replace: replaceSelectors.map(selector => (doc || document).querySelector(selector)).filter(element => element) as HTMLElement[],
    append: appendSelectors.map(selector => (doc || document).querySelector(selector)).filter(element => element) as HTMLElement[]
  });

  const getSourceNodes = async (url: string, replaceSelectors: string[], appendSelectors: string[]): Promise<Map> =>
    await fetch(url).then((response: Response) => {
      if (response.ok) {
        return response.text();
      } else {
        return Promise.reject(response);
      }
    }).then((markup: string) => getMap(replaceSelectors, appendSelectors, (new DOMParser()).parseFromString(markup, 'text/html')));

  const replaceHistory = (url: string): void => url && window.history.replaceState(null, null, url);

  const disableLink = (link: HTMLAnchorElement | HTMLButtonElement): void => {
    'disabled' in link && (link.disabled = true);
    'href' in link && (link.href = 'javascript:void(0)');

    link.removeAttribute('onclick');
    link.style.pointerEvents = 'none';
    link.dataset[dataAttributes.loading] = 'true';
  }

  const updateLoadingState = (map: Map, state: boolean) => Object.keys(map).forEach(key => {
    map[key].forEach(element => state ? (element.dataset[dataAttributes.loading] = 'true') : delete element.dataset[dataAttributes.loading]);
  });

  const triggerEvent = (action: string, parameter?: any): void => {
    let event;
    let name = 'pagebased:pagination:' + action;

    if (typeof window.CustomEvent === 'function') {
      event = new CustomEvent(name, {detail: parameter || {}});
    } else {
      event = document.createEvent('CustomEvent');
      event.initCustomEvent(name, true, true, parameter || {});
    }

    document.dispatchEvent(event);
  };

  const load = (url: string, replaceSelectors: string[], appendSelectors: string[], e: Event): void => {
    if ('fetch' in window && 'Promise' in window) {
      const event = e || window.event;
      const link = event.target as HTMLAnchorElement | HTMLButtonElement;
      const originalUrl = link.dataset.url || ('href' in link ? link.href : '');
      const target = getMap(replaceSelectors, appendSelectors);

      event && event.preventDefault();

      updateLoadingState(target, true);
      disableLink(link);
      triggerEvent('start', {target: target, link: link, url: originalUrl})

      // Get nodes from ajax request
      getSourceNodes(url, replaceSelectors, appendSelectors).then(source => {
        triggerEvent('loaded', {target: target, source: source, link: link});

        // Collect nodes
        const replaced = [] as HTMLElement[];
        const appended = [] as HTMLElement[];

        // Replace target nodes with given source nodes
        target.replace.forEach((element, index) => {
          replaced.push(source.replace[index]);
          element.replaceWith(source.replace[index]);
        });

        // Append all children of given source nodes to target nodes
        target.append.forEach((element, index) => {
          Array.prototype.slice.call(source.append[index].children).forEach(child => {
            appended.push(element.appendChild(child));
          });
        });

        // Replace url with original url
        replaceHistory(originalUrl);

        // Remove loading state
        updateLoadingState(target, false);

        // Trigger event
        triggerEvent('complete', {replaced: replaced, appended: appended, target: target, link: link});
      }).catch(() => confirm('Oops, an error occurred!\nDo you want to try again?') && (window.location.href = originalUrl));
    }
  }

  export var Pagebased = {
    Pagination: {load: load}
  };
}
