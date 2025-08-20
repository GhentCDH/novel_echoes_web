<template>
    <div class="d-flex align-items-center justify-content-start flex-wrap">
        <div class="me-1 mt-1">
            <slot name="startButton"></slot>
        </div>
        <span v-for="props in items" class="btn btn-outline-primary me-1 mt-1 nonclickable">
            {{`${props.label}${props.value}`}}
            <button class="btn btn-close btn-sm btn-close" @click="clickClose(props)"></button>
        </span>
    </div>
</template>

<script setup lang="ts">
import type {FilterTag} from "@/composables/useActiveFilterTags.ts";

const props = withDefaults(defineProps<{
    items: FilterTag[];
}>(), {
    items: () => []
});

const emit = defineEmits(['onClickClose']);
function clickClose(tag: FilterTag) {
    emit('onClickClose', tag);
}
</script>

<style scoped lang="scss">
.nonclickable {
    pointer-events: none;
    cursor: default;
}
.nonclickable > .btn-close {
    pointer-events: auto;
}
</style>