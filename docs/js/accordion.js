class Details {
  constructor(el, settings) {
    this.group = el
    this.details = this.group.getElementsByClassName("details")
    this.toggles = this.group.getElementsByClassName("details__summary")
    this.contents = this.group.getElementsByClassName("details__content")

    this.settings = Object.assign(
      {
        speed: 300,
        one_visible: false,
      },
      settings
    )

    for (let i = 0; i < this.details.length; i++) {
      const detail = this.details[i]
      const toggle = this.toggles[i]
      const content = this.contents[i]

      detail.style.transitionDuration = this.settings.speed + "ms"

      if (!detail.hasAttribute("open")) {
        detail.style.height = toggle.clientHeight + "px"
      } else {
        detail.style.height = toggle.clientHeight + content.clientHeight + "px"
      }
    }

    this.group.addEventListener("click", e => {
      if (e.target.classList.contains("details__summary")) {
        e.preventDefault()

        let num = 0
        for (let i = 0; i < this.toggles.length; i++) {
          if (this.toggles[i] === e.target) {
            num = i
            break
          }
        }

        if (!e.target.parentNode.hasAttribute("open")) {
          this.open(num)
        } else {
          this.close(num)
        }
      }
    })
  }

  open(i) {
    const detail = this.details[i]
    const toggle = this.toggles[i]
    const content = this.contents[i]

    if (this.settings.one_visible) {
      for (let a = 0; a < this.toggles.length; a++) {
        if (i !== a) this.close(a)
      }
    }

    detail.classList.remove("is-closing")

    const toggle_height = toggle.clientHeight

    detail.setAttribute("open", true)
    const content_height = content.clientHeight
    detail.removeAttribute("open")

    detail.style.height = toggle_height + content_height + "px"

    detail.setAttribute("open", true)
  }

  close(i) {
    const detail = this.details[i]
    const toggle = this.toggles[i]

    detail.classList.add("is-closing")

    const toggle_height = toggle.clientHeight

    detail.style.height = toggle_height + "px"

    setTimeout(() => {
      if (detail.classList.contains("is-closing")) {
        detail.removeAttribute("open")
      }
      detail.classList.remove("is-closing")
    }, this.settings.speed)
  }
}

;(() => {
  const els = document.getElementsByClassName("details-group")

  for (let i = 0; i < els.length; i++) {
    new Details(els[i], {
      speed: 200,
      one_visible: true,
    })
  }
})()
