<template>
    <b-dropdown class="active ms-auto" :items="selectedIds">
        <template #display>
            {{ t(message ?? 'selectedItemsBasket.message', { count: selectedIds.length }) }}
        </template>
        <template #header2>
            <button class="btn" @click="() => {setSelectedIds([])}"> {{ t('selectedItemsBasket.unselectAll') }}</button>
        </template>
        <template #header v-if="selectedIds.length">
            <a class="btn" target="_blank"
               :href="selectedIds.length ? getHashedUrl(selectedIds[0]) : undefined"
               @mouseup="(event) => {
                   if (selectedIds.length){
                       beforeRedirect(
                       event, dataTableState, selectedIds[0], 0, totalRecords,
                       filterState, selectedIds.length? selectedIds : null);
                    }
               }"
            >
                View Charters
            </a>
        </template>
        <template #item="{item : id, index}">
            <a class="btn btn-tertiary btn-sm" target="_blank"
               :href="getHashedUrl(id)"
               @mouseup="(event) => beforeRedirect(event, dataTableState, id, index, totalRecords, filterState,
               selectedIds.length? selectedIds : null)"
            >
                {{id}}
            </a>
        </template>
        <template #postItem="{item, index}">
            <button class="btn-close btn-sm" @click="removeSelectedIndex(index)"></button>
        </template>
    </b-dropdown>
</template>

<script setup lang="ts">
import type { DataTableState } from '../../composables/useTablePagination';
import BDropdown from "@/components/Bootstrap/BDropdown.vue";
import {useI18n} from "vue-i18n";

const { t } = useI18n() // use as global scope

const props = defineProps<{
    selectedIds: number[];
    getHashedUrl: (id: number) => string;
    setSelectedIds: (ids: number[]) => void;
    removeSelectedIndex: (index: number) => void;
    dataTableState: DataTableState;
    totalRecords: number;
    filterState: object;
    beforeRedirect: (event: MouseEvent, dataTableState: DataTableState, id: number, index: number, count: number, filters, ids: number[] | null) => void;
    message: string;
}>();
</script>

<i18n>
{
    "en": {
        "selectedItemsBasket": {
            "message": "No item selected | {count} item selected | {count} items selected",
            "unselectAll": "Unselect all"
        }
    },
    "fr": {
        "selectedItemsBasket": {
            "message": "Aucun élément sélectionné | {count} élément sélectionné | {count} éléments sélectionnés",
            "unselectAll": "Tout désélectionner"
        }
    }
}
</i18n>