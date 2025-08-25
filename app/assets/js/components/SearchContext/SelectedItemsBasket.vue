<template>
    <b-dropdown class="active" :items="selectedIds">
        <template #display>
            {{ labelBasketDropdown || t('selectedItemsBasket.message', { count: selectedIds.length }) }}
        </template>
        <template #header2>
            <button v-if="selectedIds.length" class="btn btn-sm" @click="() => {setSelectedIds([])}"> {{ labelUnselectAll || t('selectedItemsBasket.unselectAll') }}</button>
        </template>
        <template #header v-if="selectedIds.length">
            <a class="btn btn-sm" target="_blank"
               :href="selectedIds.length ? getHashedUrl(selectedIds[0]) : undefined"
               @mouseup="(event) => {
                   if (selectedIds.length){
                       beforeRedirect(
                       event, dataTableState, selectedIds[0], 0, totalRecords,
                       filterState, selectedIds.length? selectedIds : null);
                    }
               }"
            >
                {{ labelViewItems || t('selectedItemsBasket.viewItems', { count: selectedIds.length}) }} ({{ selectedIds.length }})
            </a>
        </template>
        <template #item="{item : id, index}">
            <a class="btn btn-primary btn-sm" target="_blank"
               :href="getHashedUrl(id)"
               @mouseup="(event) => beforeRedirect(event, dataTableState, id, index, totalRecords, filterState,
               selectedIds.length? selectedIds : null)"
            >
                {{id}}
            </a>
        </template>
        <template #postItem="{item, index}">
            <button class="btn btn-sm btn-primary" @click="removeSelectedIndex(index)"><i class="fa-solid fa-trash"></i></button>
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
    labelBasketDropdown: string | null;
    labelUnselectAll: string | null;
    labelViewItems: string | null;
}>();
</script>

<i18n>
{
    "en": {
        "selectedItemsBasket": {
            "message": "No item selected | {count} item selected | {count} items selected",
            "unselectAll": "Unselect all",
            "viewItems": "View item | View items"
        }
    },
    "fr": {
        "selectedItemsBasket": {
            "message": "Aucun élément sélectionné | {count} élément sélectionné | {count} éléments sélectionnés",
            "unselectAll": "Tout désélectionner",
            "viewItems": "Voir l'élément | Voir les éléments"
        }
    }
}
</i18n>