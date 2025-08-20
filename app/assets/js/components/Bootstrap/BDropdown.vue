<template>
    <div class="dropdown">
        <button
            class="btn btn-primary dropdown-toggle"
            type="button"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
        >
            <slot name="display"></slot>
        </button>
        <ul class="dropdown-menu overflow-auto" style="max-height: 300px">
            <li class="dropdown-header">
                <slot name="header"></slot>
            </li>
            <li class="dropdown-header">
                <slot name="header2"></slot>
            </li>
            <li v-for="(item, index) in items" :key="index" class="dropdown-item d-flex justify-content-evenly align-items-center">
                <slot name="preItem" :item="item" :index="index"></slot>
                <slot name="item" :item="item" :index="index">
                    <span @click="itemClicked(index)">{{ item }}</span>
                </slot>
                <slot name="postItem" :item="item" :index="index"></slot>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">

const props = withDefaults(defineProps<{
    items: [item: number, index: number][];
}>(), {
    items: () => []
});

const emit = defineEmits(['itemClicked']);

function itemClicked(index: number) {
    emit('itemClicked', {
        index: index,
        item: props.items[index]
    });
}
</script>
