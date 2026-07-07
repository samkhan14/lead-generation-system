/**
 * Materio layout initialization adapted for Vue/Inertia.
 * Based on Materio assets/js/main.js — skips DOMContentLoaded wrapper.
 */

const tooltipInstances = [];

export function initMaterio(options = {}) {
    const {
        initMenu = true,
        collapseMenuOnDesktop = false,
    } = options;

    if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {
        document.body.classList.add('ios');
    }

    let menu = null;

    if (typeof Waves !== 'undefined') {
        Waves.init();
        Waves.attach(".btn[class*='btn-']:not(.position-relative):not([class*='btn-outline-'])", ['waves-light']);
        Waves.attach("[class*='btn-outline-']:not(.position-relative)");
        Waves.attach('.pagination .page-item .page-link');
        Waves.attach('.dropdown-menu .dropdown-item');
        Waves.attach('[data-bs-theme="light"] .list-group .list-group-item-action');
        Waves.attach('.nav-tabs:not(.nav-tabs-widget) .nav-item .nav-link');
        Waves.attach('.nav-pills .nav-item .nav-link', ['waves-light']);
    }

    if (initMenu && window.Helpers && window.Menu) {
        const layoutMenuEl = document.querySelectorAll('#layout-menu');

        layoutMenuEl.forEach((element) => {
            menu = new window.Menu(element, {
                orientation: 'vertical',
                closeChildren: false,
            });
            window.Helpers.scrollToActive(false);
            window.Helpers.mainMenu = menu;
        });

        document.querySelectorAll('.layout-menu-toggle').forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                window.Helpers.toggleCollapsed();
            });
        });

        const menuInnerContainer = document.getElementsByClassName('menu-inner');
        const menuInnerShadow = document.getElementsByClassName('menu-inner-shadow')[0];

        if (menuInnerContainer.length > 0 && menuInnerShadow) {
            menuInnerContainer[0].addEventListener('ps-scroll-y', function handleScroll() {
                if (this.querySelector('.ps__thumb-y')?.offsetTop) {
                    menuInnerShadow.style.display = 'block';
                } else {
                    menuInnerShadow.style.display = 'none';
                }
            });
        }
    }

    if (window.bootstrap?.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipTriggerEl) => {
            tooltipInstances.push(new window.bootstrap.Tooltip(tooltipTriggerEl));
        });
    }

    const accordionActiveFunction = (e) => {
        const item = e.target.closest('.accordion-item');

        if (!item) {
            return;
        }

        if (e.type === 'show.bs.collapse') {
            item.classList.add('active');
            item.previousElementSibling?.classList.add('previous-active');
        } else {
            item.classList.remove('active');
            item.previousElementSibling?.classList.remove('previous-active');
        }
    };

    document.querySelectorAll('.accordion').forEach((accordionTriggerEl) => {
        accordionTriggerEl.addEventListener('show.bs.collapse', accordionActiveFunction);
        accordionTriggerEl.addEventListener('hide.bs.collapse', accordionActiveFunction);
    });

    window.Helpers?.setAutoUpdate(true);
    window.Helpers?.initPasswordToggle();
    window.Helpers?.initSpeechToText();

    if (collapseMenuOnDesktop && window.Helpers && !window.Helpers.isSmallScreen()) {
        window.Helpers.setCollapsed(true, false);
    }

    return function destroyMaterio() {
        tooltipInstances.splice(0).forEach((instance) => instance.dispose());

        if (menu?.destroy) {
            menu.destroy();
        }

        window.Helpers?.setAutoUpdate(false);
        window.Helpers?.destroy?.();
        window.Helpers.mainMenu = null;
    };
}
