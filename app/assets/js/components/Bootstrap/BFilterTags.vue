<template>
    <div class="d-flex align-items-center justify-content-start flex-wrap gap-2">
        <slot name="startButton"></slot>
        <span v-for="props in items" class="btn btn-md btn-outline-primary" @click="clickClose(props)">
            {{`${props.label}${props.value}`}}
            <i class="fa-solid fa-close"></i>
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