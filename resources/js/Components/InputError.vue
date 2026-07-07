<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message" class="text-danger small mt-1">
        {{ message }}
    </div>
</template>
