document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop();

    // 1. Marcar link activo
    document.querySelectorAll('.sb-sidenav-menu a.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href === currentPage) {
            link.classList.add('active');

            // 2. Si está dentro de un collapse, abrirlo
            const collapse = link.closest('.collapse');
            if (collapse) {
                collapse.classList.add('show');

                const toggle = document.querySelector(
                    `[data-bs-target="#${collapse.id}"]`
                );

                if (toggle) {
                    toggle.classList.remove('collapsed');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
});