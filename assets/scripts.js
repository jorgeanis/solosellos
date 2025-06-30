
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll('.fade-in').forEach(el => {
    el.style.opacity = 0;
    el.style.transform = "translateY(20px)";
    el.style.transition = "all 0.6s ease-out";
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          el.style.opacity = 1;
          el.style.transform = "translateY(0)";
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.1 });
    observer.observe(el);
  });
});
