<script setup>
defineProps({ dark: { type: Boolean, default: false } });
</script>
<template>
    <img
        class="application-logo"
        :src="
            dark
                ? '/brand/nursery-lockup-dark.svg'
                : '/brand/nursery-lockup-light.svg'
        "
        alt="Nursery"
        width="1284"
        height="540"
        decoding="async"
    />
</template>
