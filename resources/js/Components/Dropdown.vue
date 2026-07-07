<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: '',
    },
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const menuAlignClass = computed(() => {
    if (props.align === 'left') {
        return 'dropdown-menu-start';
    }

    return 'dropdown-menu-end';
});

const open = ref(false);
</script>

<template>
    <div class="dropdown">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="open = false"
        />

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <ul
                v-show="open"
                class="dropdown-menu show"
                :class="[menuAlignClass, contentClasses]"
                @click="open = false"
            >
                <slot name="content" />
            </ul>
        </Transition>
    </div>
</template>
