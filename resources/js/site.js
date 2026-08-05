document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('[data-site-header]');
    const burger = document.querySelector('[data-site-burger]');
    const mobile = document.querySelector('[data-site-mobile]');

    const onScroll = () => {
        if (!header) return;
        header.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    burger?.addEventListener('click', () => {
        const open = mobile?.classList.toggle('is-open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    const reveals = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' });
        reveals.forEach((el) => io.observe(el));
    } else {
        reveals.forEach((el) => el.classList.add('is-in'));
    }
});
