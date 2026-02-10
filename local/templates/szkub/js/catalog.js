document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".apartment-card__fav").forEach((button) => {
    button.addEventListener("click", () => {
      button.classList.toggle("is-active");
    });
  });
});
