const offcanvasNavbar = document.getElementById('offcanvasNavbar');
const navbar = document.querySelector('.navbar');

const observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    const ariaModalValue = mutation.target.getAttribute('aria-modal');

    if (ariaModalValue === 'true') {
      navbar.style.backdropFilter = 'none'; 
    } else {
      navbar.style.backdropFilter = 'blur(10px)'; 
    }
  });
});

const observerConfig = { attributes: true };
observer.observe(offcanvasNavbar, observerConfig);

const header = document.querySelector('.navbar');

window.addEventListener('scroll', () => {
  const scrollPos = window.scrollY;
  if (scrollPos > 10) {
    header.classList.add('scrolled', 'shadow');
  }
  else {
    header.classList.remove('scrolled', 'shadow');
  }
});

// $("#offcanvasNavbar a").click(function () {
//   $('.offcanvas').offcanvas('hide');
// });