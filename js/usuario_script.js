// js/usuario_script.js
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger-menu');
    const nav = document.getElementById('main-nav');
    const navClose = document.getElementById('nav-close');
    const overlay = document.getElementById('nav-overlay');

    function openMenu() {
        nav.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Evita scroll del fondo
    }

    function closeMenu() {
        nav.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = ''; // Permite scroll de nuevo
    }

    if (hamburger && nav && navClose && overlay) {
        hamburger.addEventListener('click', openMenu);
        navClose.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);

        // Opcional: Cerrar menú al hacer clic en un enlace (para SPAs o si se prefiere)
        const navLinks = nav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Solo cierra si el menú está activo (visible en móvil)
                if (nav.classList.contains('active')) {
                   // No cerramos automáticamente para navegación normal
                   // closeMenu();
                }
            });
        });
    } else {
        console.error("Error: No se encontraron los elementos del menú móvil.");
    }
});