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
    <div class="overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
        <div class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 p-2">
            <button type="button" class="rounded px-2 py-1 text-sm font-semibold text-slate-700 hover:bg-white" @click="exec('bold')">B</button>
            <button type="button" class="rounded px-2 py-1 text-sm italic text-slate-700 hover:bg-white" @click="exec('italic')">I</button>
            <button type="button" class="rounded px-2 py-1 text-sm underline text-slate-700 hover:bg-white" @click="exec('underline')">U</button>
            <span class="mx-1 w-px bg-slate-300" />
            <button type="button" class="rounded px-2 py-1 text-sm text-slate-700 hover:bg-white" @click="exec('insertUnorderedList')">• List</button>
            <button type="button" class="rounded px-2 py-1 text-sm text-slate-700 hover:bg-white" @click="exec('insertOrderedList')">1. List</button>
            <button type="button" class="rounded px-2 py-1 text-sm text-slate-700 hover:bg-white" @click="setLink">Link</button>
            <span class="mx-1 w-px bg-slate-300" />
            <button type="button" class="rounded px-2 py-1 text-sm text-slate-700 hover:bg-white" @click="exec('removeFormat')">Clear</button>
        </div>
        <div
            ref="editorRef"
            contenteditable="true"
            class="min-h-[220px] px-4 py-3 text-sm leading-relaxed text-slate-800 outline-none focus:ring-0"
            :data-placeholder="placeholder"
            @input="sync"
            @blur="sync"
        />
    </div>
</template>

<style scoped>
[contenteditable]:empty:before {
    content: attr(data-placeholder);
    color: #94a3b8;
}
</style>
