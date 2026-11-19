import './bootstrap';

const adminSidebarToggle = document.querySelectorAll('[data-admin-sidebar-toggle]');
const adminSidebar = document.getElementById('adminProSidebar');

adminSidebarToggle.forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const isMobile = window.matchMedia('(max-width: 991.98px)').matches;
        const className = isMobile ? 'admin-pro-mobile-open' : 'admin-pro-collapsed';
        const isExpanded = document.body.classList.toggle(className);

        if (isMobile) {
            document.querySelectorAll('[data-admin-sidebar-toggle][aria-controls]').forEach((button) => {
                button.setAttribute('aria-expanded', String(isExpanded));
            });
        }
    });
});

adminSidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            document.body.classList.remove('admin-pro-mobile-open');
            document.querySelectorAll('[data-admin-sidebar-toggle][aria-controls]').forEach((button) => {
                button.setAttribute('aria-expanded', 'false');
            });
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('admin-pro-mobile-open')) {
        document.body.classList.remove('admin-pro-mobile-open');
        document.querySelectorAll('[data-admin-sidebar-toggle][aria-controls]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
        });
    }
});

document.querySelectorAll('[data-movie-suggestions]').forEach((input) => {
    const suggestions = document.getElementById(input.getAttribute('list'));
    let timer;
    let request;

    input.addEventListener('input', () => {
        window.clearTimeout(timer);

        const search = input.value.trim();

        if (search.length < 2) {
            request?.abort();
            suggestions.replaceChildren();
            return;
        }

        timer = window.setTimeout(async () => {
            request?.abort();
            request = new AbortController();

            const endpoint = new URL(input.dataset.movieSuggestions, window.location.origin);
            endpoint.searchParams.set('search', search);

            try {
                const response = await fetch(endpoint, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });

                if (! response.ok) {
                    throw new Error(`Movie suggestions request failed (${response.status}).`);
                }

                const titles = await response.json();
                const options = titles.map((title) => {
                    const option = document.createElement('option');
                    option.value = title;

                    return option;
                });

                suggestions.replaceChildren(...options);
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                console.error('Unable to load movie search suggestions.', error);
            }
        }, 200);
    });
});

document.querySelectorAll('[data-home-carousel]').forEach((element) => {
    const Carousel = window.bootstrap?.Carousel;

    if (! Carousel || element.querySelectorAll('.carousel-item').length < 2) {
        return;
    }

    const carousel = Carousel.getOrCreateInstance(element, {
        interval: 6500,
        pause: 'hover',
        touch: true,
        wrap: true,
    });
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (reducedMotion.matches) {
        carousel.pause();
    } else {
        carousel.cycle();
    }

    reducedMotion.addEventListener('change', (event) => {
        if (event.matches) {
            carousel.pause();
        } else {
            carousel.cycle();
        }
    });
});

document.querySelectorAll('[data-schedule-city]').forEach((citySelect) => {
    citySelect.addEventListener('change', () => {
        const form = citySelect.form;
        const cinemaSelect = form?.elements.namedItem('cinema_id');

        if (cinemaSelect) {
            cinemaSelect.value = '';
        }

        form?.requestSubmit();
    });
});
