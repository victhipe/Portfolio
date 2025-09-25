document.addEventListener('DOMContentLoaded', () => {

    // --- МОБИЛЬНАЯ НАВИГАЦИЯ (БУРГЕР-МЕНЮ) ---
    const hamburgerMenu = document.getElementById('hamburger-menu');
    const mainNav = document.getElementById('main-nav');
    const navLinks = mainNav.querySelectorAll('a');

    const toggleMenu = () => {
        hamburgerMenu.classList.toggle('active');
        mainNav.classList.toggle('nav-active');
        
        // Блокируем прокрутку страницы, когда меню открыто
        if (mainNav.classList.contains('nav-active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }
    };

    hamburgerMenu.addEventListener('click', toggleMenu);

    // Закрываем меню при клике на ссылку (для одностраничников)
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (mainNav.classList.contains('nav-active')) {
                toggleMenu();
            }
        });
    });


    // --- ЛАЙТБОКС (УВЕЛИЧЕНИЕ ФОТО) ---
    const galleryItems = document.querySelectorAll('.gallery-item');
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const closeBtn = document.querySelector('.close-btn');

    galleryItems.forEach(item => {
        item.addEventListener('click', () => {
            const highResSrc = item.querySelector('img').getAttribute('data-src');
            lightboxImg.setAttribute('src', highResSrc);
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden'; // Запретить прокрутку фона
        });
    });

    // Функция закрытия лайтбокса
    const closeLightbox = () => {
        lightbox.classList.remove('active');
        // Проверяем, не открыто ли мобильное меню, прежде чем разрешать прокрутку
        if (!mainNav.classList.contains('nav-active')) {
             document.body.style.overflow = 'auto';
        }
    };

    closeBtn.addEventListener('click', closeLightbox);

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) {
            closeLightbox();
        }
    });


    // --- АНИМАЦИЯ ПРИ ПРОКРУТКЕ (INTERSECTION OBSERVER) ---
    const animatedSections = document.querySelectorAll('.animated-section');

    const observerOptions = {
        root: null, 
        rootMargin: '0px',
        threshold: 0.15 
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    animatedSections.forEach(section => {
        observer.observe(section);
    });

});