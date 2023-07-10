/*!
* Tagin v2.0.2 (https://tagin.netlify.app/)
* Copyright 2020-2021 Erwin Heldy G
* Licensed under MIT (https://github.com/erwinheldy/tagin/blob/master/LICENSE)
*/
class Tagin {
  classElement = "tagin";
  classWrapper = "tagin-wrapper";
  classTag = "tagin-tag";
  classRemove = "tagin-tag-remove";
  classInput = "tagin-input";
  classInputHidden = "tagin-input-hidden";
  target;
  wrapper;
  input;
  separator;
  placeholder;
  duplicate;
  transform;
  enter;

  constructor(t, e) {
    this.target = t, this.separator = e?.separator || t.dataset.taginSeparator || ",", this.placeholder = e?.placeholder || t.dataset.taginPlaceholder || "", this.duplicate = e?.duplicate || t.dataset.taginDuplicate !== void 0, this.transform = e?.transform || t.dataset.taginTransform || "input => input", this.enter = e?.enter || t.dataset.taginEnter !== void 0, this.createWrapper(), this.autowidth(), this.addEventListener()
  }

  createWrapper() {
    const t = this.getValue() === "" ? "" : this.getValues().map(s => this.createTag(s)).join(""),
      e = document.createElement("input");
    e.type = "text", e.className = this.classInput, e.placeholder = this.placeholder;
    const a = document.createElement("div");
    a.className = `${this.classWrapper} ${this.target.className}`, a.classList.remove(this.classElement), a.insertAdjacentHTML("afterbegin", t), a.insertAdjacentElement("beforeend", e), this.target.insertAdjacentElement("afterend", a), this.wrapper = a, this.input = e
  }

  createTag(t) {
    const e = "this.closest('div').dispatchEvent(new CustomEvent('tagin:remove', { detail: this }))";
    return `<span class="${this.classTag}">${t}<span onclick="${e}" class="${this.classRemove}"></span></span>`
  }

  getValue() {
    return this.target.value.trim()
  }

  getValues() {
    return this.getValue().split(this.separator)
  }

  getTags() {
    return Array.from(this.wrapper.getElementsByClassName(this.classTag)).map(t => t.textContent)
  }

  getTag() {
    return this.getTags().join(this.separator)
  }

  updateValue() {
    this.target.value = this.getTag(), this.target.dispatchEvent(new Event("change"))
  }

  autowidth() {
    const t = document.createElement("div");
    t.classList.add(this.classInput, this.classInputHidden);
    const e = this.input.value || this.input.placeholder || "";
    t.innerHTML = e.replace(/ /g, "&nbsp;"), document.body.appendChild(t), this.input.style.setProperty("width", Math.ceil(parseInt(window.getComputedStyle(t).width.replace("px", ""))) + 1 + "px"), t.remove()
  }

  addEventListener() {
    const t = this.wrapper, e = this.input;
    t.addEventListener("click", () => e.focus()), e.addEventListener("focus", () => t.classList.add("focus")), e.addEventListener("blur", () => t.classList.remove("focus")), e.addEventListener("input", () => {
      this.appendTag(), this.autowidth()
    }), e.addEventListener("blur", () => {
      this.appendTag(!0), this.autowidth()
    }), e.addEventListener("keydown", a => {
      e.value === "" && a.key === "Backspace" && t.getElementsByClassName(this.classTag).length && (t.querySelector(`.${this.classTag}:last-of-type`).remove(), this.updateValue()), e.value !== "" && a.key === "Enter" && this.enter && (this.appendTag(!0), this.autowidth(), a.preventDefault())
    }), t.addEventListener("tagin:remove", a => {
      a.detail instanceof HTMLSpanElement && (a.detail.parentElement.remove(), this.updateValue())
    }), this.target.addEventListener("change", () => this.updateTag())
  }

  appendTag(force = !1) {
    const input = this.input, value = eval(this.transform)(input.value.trim());
    value === "" && (input.value = ""), (input.value.includes(this.separator) || force && input.value !== "") && (value.split(this.separator).filter(t => t !== "").forEach(t => {
      this.getTags().includes(t) && this.duplicate === !1 ? this.alertExist(t) : (input.insertAdjacentHTML("beforebegin", this.createTag(t)), this.updateValue())
    }), input.value = "", input.removeAttribute("style"))
  }

  alertExist(t) {
    for (const e of this.wrapper.getElementsByClassName(this.classTag)) e.textContent === t && e instanceof HTMLSpanElement && (e.style.transform = "scale(1.09)", setTimeout(() => {
      e.removeAttribute("style")
    }, 150))
  }

  updateTag() {
    this.getValue() !== this.getTag() && ([...this.wrapper.getElementsByClassName(this.classTag)].map(t => t.remove()), this.getValue().trim() !== "" && this.input.insertAdjacentHTML("beforebegin", this.getValues().map(t => this.createTag(t)).join("")))
  }

  addTag(t) {
    this.input.value = (Array.isArray(t) ? t.join(this.separator) : t) + this.separator, this.input.dispatchEvent(new Event("input"))
  }
}

export {Tagin as default};
