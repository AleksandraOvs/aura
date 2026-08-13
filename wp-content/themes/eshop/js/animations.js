document.addEventListener('DOMContentLoaded', () => {
    const animatedElements = document.querySelectorAll('[data-scroll-animation]');

    if (!animatedElements.length) return;

    const observer = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                entry.target.classList.add('is-visible');

                // Если нужно анимировать только один раз
                observer.unobserve(entry.target);
            });
        },
        {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px',
        }
    );

    animatedElements.forEach((element) => {
        observer.observe(element);
    });

    /*
     * ========================================
     * Кнопка "Наверх"
     * ========================================
     */

    const scrollTopButton = document.querySelector('.scroll-top');

    if (scrollTopButton) {
        const toggleScrollTopButton = () => {
            if (window.scrollY > window.innerHeight) {
                scrollTopButton.classList.add('is-visible');
            } else {
                scrollTopButton.classList.remove('is-visible');
            }
        };

        window.addEventListener('scroll', toggleScrollTopButton, {
            passive: true
        });

        // Проверяем состояние сразу при загрузке
        toggleScrollTopButton();

        // Плавный скролл наверх
        scrollTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});