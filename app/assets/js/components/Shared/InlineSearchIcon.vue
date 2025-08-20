<template>
    <i class="inline-search-icon px-1 fa fa-search text-primary"></i>
</template>
<script setup lang="ts">
</script>

<style lang="scss">
.inline-search-icon {
    cursor: pointer;
    transition: color 0.3s ease;
    font-size: 70%;

    &:hover {
        color: var(--primary-color-hover);
    }
}
</style>