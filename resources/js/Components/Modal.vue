<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;
            dialog.value?.showModal();
        } else {
            document.body.style.overflow = '';

            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
            }, 200);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

const sizeClass = computed(() => ({
    sm: 'modal-sm',
    md: '',
    lg: 'modal-lg',
    xl: 'modal-xl',
    '2xl': 'modal-xl',
}[props.maxWidth] ?? 'modal-xl'));
</script>

<template>
    <dialog
        ref="dialog"
        class="p-0 border-0 bg-transparent materio-modal-dialog"
    >
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="show"
                class="modal-backdrop fade show"
                @click="close"
            />
        </Transition>

        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div
                v-show="show"
                class="modal fade show d-block"
                tabindex="-1"
                role="dialog"
            >
                <div
                    class="modal-dialog modal-dialog-centered"
                    :class="sizeClass"
                    @click.stop
                >
                    <div class="modal-content">
                        <slot v-if="showSlot" />
                    </div>
                </div>
            </div>
        </Transition>
    </dialog>
</template>

<style scoped>
.materio-modal-dialog {
    z-index: 1090;
    margin: 0;
    min-height: 100%;
    min-width: 100%;
    overflow-y: auto;
    max-width: none;
    max-height: none;
}

.materio-modal-dialog::backdrop {
    background: transparent;
}
</style>
