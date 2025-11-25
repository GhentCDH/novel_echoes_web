<script lang="ts" setup>
import {defineProps} from 'vue'

interface Reference {
    id: number
    label: string
    locus?: string
    type: string
}

const props = defineProps<{
    references: Reference[]
}>()

const referenceClass = (ref: Reference) => {
    return `reference-item-${ref.type}`
}
</script>

<template>
    <div class="reference-list">
    <span
        v-for="ref in references"
        :key="ref.id"
        :class="referenceClass(ref)" class="reference-item"
    >
        <slot :item="ref" name="before"/>
        <span class="reference-text">{{ ref.label }}</span>
        <span v-if="ref.locus"> &#187; </span>
        <span v-if="ref.locus" class="reference-locus">{{ ref.locus }}</span>
        <slot :item="ref" name="after"/>
    </span>
    </div>
</template>

<style lang="scss">
.reference-item-work {
    .reference-text {
        font-style: italic;
    }
}
</style>