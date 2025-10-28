
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger-menu');
    const nav = document.getElementById('main-nav');
    const navClose = document.getElementById('nav-close');
    const overlay = document.getElementById('nav-overlay');

    function openMenu() {
        nav.classList.add('active');
        overlay.classList.add('active');
        hamburger.classList.add('active');
        document.body.style.overflow = 'hidden'; 
    }

    function closeMenu() {
        nav.classList.remove('active');
        overlay.classList.remove('active');
        hamburger.classList.remove('active');
        document.body.style.overflow = ''; 
    }

    if (hamburger && nav && navClose && overlay) {
        hamburger.addEventListener('click', openMenu);
        navClose.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);

        
        const navLinks = nav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                
                if (nav.classList.contains('active')) {
                   
                   
                }
            });
        });
    } else {
        console.error("Error: No se encontraron los elementos del menú móvil.");
    }
});