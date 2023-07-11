import Tagify from '@yaireo/tagify';

class TagsElement {
  private readonly element: HTMLElement;
  private readonly tags: string[];

  constructor(id, ...tags: string[]) {
    this.element = document.getElementById(id);
    this.tags = tags;

    new Tagify(this.element, {
      whitelist: this.tags,
      originalInputValueFormat: (value => value.map(item => item.value).join(', ').trim())
    });
  }
}

export default TagsElement;
