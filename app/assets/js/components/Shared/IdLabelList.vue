<script lang="ts" setup>
import {computed} from 'vue'

interface IdLabel {
    id: string | number;
    label: string;
}

type IdLabelList = Array<IdLabel>

const props = defineProps<{
    items: IdLabelList
    urlGenerator?: ((item: IdLabel) => string)
    itemClass?: string | ((item: IdLabel) => string)
}>()

const isCallable = computed(() => typeof props.urlGenerator === 'function')

const getItemClass = (item: IdLabel) => {
    if (typeof props.itemClass === 'function') {
        return props.itemClass(item)
    }
    return props.itemClass || ''
}
</script>

<template>
    <div class="id-label-items">
        <span v-for="item in items" :key="item.id" class="id-label-item" :class="getItemClass(item)">
            <slot :item="item" name="before"></slot>
            <template v-if="isCallable">
                <a :href="props.urlGenerator(item)">
                  <span>{{ item.label }}</span>
                </a>
            </template>
            <template v-else>
                <span>{{ item.label }}</span>
            </template>
            <slot :item="item" name="after"></slot>
        </span>
    </div>
</template>