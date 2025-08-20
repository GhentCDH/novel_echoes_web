<template>
    <dt class="col-sm-3 col-lg-2 mb-1" :class="{'empty-field': empty}">{{ label }}</dt>
    <dd class="col-sm-9 col-lg-10">
        <slot></slot>
    </dd>
</template>

<script setup lang="ts">
import {defineProps} from 'vue'
import {useI18n} from "vue-i18n";

const props = defineProps({
    label: {
        type: String,
        required: true
    },
    empty: {
        type: Boolean,
        default: false
    }
})
</script>

<style lang="scss">
.empty-field {
    color: #adb5bd;
}
</style>