import { nextTick, onBeforeUnmount, onMounted } from 'vue';
import { initMaterio } from '../initMaterio.js';

export default function useMaterio(options = {}) {
    const {
        htmlClass = 'layout-menu-fixed layout-compact',
        initMenu = true,
        collapseMenuOnDesktop = false,
    } = options;

    let teardown = null;
    const html = document.documentElement;
    let previousHtmlClass = html.className;

    onMounted(async () => {
        previousHtmlClass = html.className;
        html.className = htmlClass;

        await nextTick();

        teardown = initMaterio({ initMenu, collapseMenuOnDesktop });
    });

    onBeforeUnmount(() => {
        teardown?.();
        html.className = previousHtmlClass;
    });
}
