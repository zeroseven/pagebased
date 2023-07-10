import Tagin from '@zeroseven/rampage/backend/Tagin.js';

class TagsElement {
  constructor(id, ...tags) {
    this.element = document.getElementById(id);
    this.tags = tags;

    this.init();
  }

  initSuggestions(tagin) {
    const container = this.element.parentElement.appendChild(document.createElement('div'));
    container.className = 'btn-group';
    container.style.marginTop = '1em';

    this.tags.forEach(tag => {
      const button = container.appendChild(document.createElement('button'));

      button.innerText = tag;
      button.className = 'btn btn-default btn-sm';
      button.type = 'button';
      button.addEventListener('click', () => tagin.addTag(tag));
    });
  }

  init() {
    this.element.type = 'hidden';

    const tagin = new Tagin(this.element, {
      enter: true,
      placeholder: this.element.placeholder || 'Enter tags …',
      transform: tag => tag.replace(/[^a-z0-9 _-]/gi, '')
    });

    this.initSuggestions(tagin);
  }
}

export default TagsElement;
