<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Write your email content…' },
});

const emit = defineEmits(['update:modelValue']);

const editorRef = ref(null);

const sync = () => {
    if (editorRef.value) {
        emit('update:modelValue', editorRef.value.innerHTML);
    }
};

const exec = (command, value = null) => {
    document.execCommand(command, false, value);
    editorRef.value?.focus();
    sync();
};

const setLink = () => {
    const url = window.prompt('Enter URL');
    if (url) {
        exec('createLink', url);
    }
};

watch(() => props.modelValue, (value) => {
    if (editorRef.value && editorRef.value.innerHTML !== value) {
        editorRef.value.innerHTML = value || '';
    }
});

onMounted(() => {
    if (editorRef.value) {
        editorRef.value.innerHTML = props.modelValue || '';
    }
});
</script>

<template>
    <div class="card overflow-hidden">
        <div class="card-header d-flex flex-wrap gap-1 py-2">
            <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" @click="exec('bold')">B</button>
            <button type="button" class="btn btn-sm btn-outline-secondary fst-italic" @click="exec('italic')">I</button>
            <button type="button" class="btn btn-sm btn-outline-secondary text-decoration-underline" @click="exec('underline')">U</button>
            <span class="vr mx-1" />
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="exec('insertUnorderedList')">• List</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="exec('insertOrderedList')">1. List</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="setLink">Link</button>
            <span class="vr mx-1" />
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="exec('removeFormat')">Clear</button>
        </div>
        <div
            ref="editorRef"
            contenteditable="true"
            class="card-body small"
            style="min-height: 220px; outline: none;"
            :data-placeholder="placeholder"
            @input="sync"
            @blur="sync"
        />
    </div>
</template>

<style scoped>
[contenteditable]:empty:before {
    content: attr(data-placeholder);
    color: var(--bs-secondary-color);
}
</style>
