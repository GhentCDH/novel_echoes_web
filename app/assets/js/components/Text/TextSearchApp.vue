<template>
    <div class="row search-app">
        <aside class="col-sm-3 search-app__filters h-100 position-relative">
            <div class="bg-tertiary padding-default mh-100 scrollable scrollable--vertical">
                <div v-if="modelHasChanged" class="form-group mbottom-default flex-row">

                    <b-filter-tags :items="getActiveFilterTagStrings()" @onClickClose="onCloseActiveFilter">
                        <template #startButton>
                            <button class="btn btn-md" @click="resetAllFilters">
                                {{ t('form.reset-filters') }}
                            </button>
                        </template>
                    </b-filter-tags>
                </div>
                <VueFormGenerator
                    ref="form"
                    :model="model"
                    :options="formOptions"
                    :schema="schema"
                    @validated="onFormValidated"
                />
            </div>
        </aside>

        <article class="col-sm-9 d-flex flex-column h-100 search-app__results">
            <header>
                <h1 v-if="title" class="mbottom-default">{{ title }}</h1>
            </header>
            <section class="d-flex flex-column flex-grow-1 overflow-hidden">
                <div class="d-flex flex-column w-100 h-100">
                    <nav class="row form-group">
                        <div class="col-lg-4 d-flex align-items-lg-center">
                            <b-pagination
                                :total-records="totalRecords"
                                :per-page="dataTableState.rowsPerPage"
                                :page="dataTableState.currentPage"
                                @update:page="(page) => updateDataTableState({currentPage: parseInt(page)})"
                                label-first="«" label-last="»" label-previous="‹" label-next="›"
                            ></b-pagination>
                        </div>
                        <div class="col-lg-4 d-flex align-items-lg-center justify-content-lg-center">
                            <RecordCount :per-page="dataTableState.rowsPerPage" :total-records="totalRecords"
                                         :page="dataTableState.currentPage"></RecordCount>
                        </div>
                        <div class="col-lg-4 d-flex align-items-lg-center justify-content-lg-end gap-1">
                            <b-select :id="'per-page'"
                                      :label="t('pagination.perPage')"
                                      :selected="dataTableState.rowsPerPage"
                                      :options="tableOptions.pagination.perPageValues.map(value => ({value, text: value}))"
                                      @update:selected="(value) => updateDataTableState({rowsPerPage: parseInt(value)})"
                                      class="w-auto"
                            ></b-select>
                            <selected-items-basket
                                :selected-ids="selectedIds"
                                :get-hashed-url="getHashedUrl"
                                :set-selected-ids="setSelectedIds"
                                :remove-selected-index="removeSelectedIndex"
                                :data-table-state="dataTableState"
                                :total-records="totalRecords"
                                :filter-state="filterState"
                                :before-redirect="beforeRedirect"
                                class="w-auto"
                            />
                        </div>
                    </nav>

                    <div class="flex-grow-1 scrollable">
                        <b-table :items="tableDataWithCheckbox"
                                 :fields="tableOptions.fields"
                                 :sort-by="dataTableState.orderBy"
                                 :sort-ascending="dataTableState.orderAsc"
                                 @update:sort-by="(value) => updateDataTableState({orderBy: value})"
                                 @update:sort-ascending="(value) => updateDataTableState({orderAsc: value})"
                                 class="m-0"
                        >
                            <template #actionsPreRowHeader>
                                <th>
                                    <input
                                        type="checkbox"
                                        @change="toggleAllRowsSelection"
                                        :checked="allSelected"
                                    >
                                </th>
                            </template>
                            <template #actionsPreRow="props">
                                <td>
                                    <input
                                        type="checkbox"
                                        v-model="props.row.selected"
                                        @change="() => toggleRowSelection(props.row.id)"
                                    >
                                </td>
                            </template>
                            <template #id="props">
                                <a class="btn btn-primary btn-sm" target="_blank"
                                   :href="getHashedUrl(props.row.id)"
                                   @mouseup="(event) => beforeRedirect(
                                               event, dataTableState, props.row.id, props.index, totalRecords,
                                               filterState, selectedIds.length? selectedIds : null)"
                                >
                                    {{ props.row.id }}
                                </a>
                            </template>
                            <template #century="props">
                                <IdLabelList :items="formatTextCenturiesAsIdLabel(props.item)" class="d-flex flex-column">
                                    <template #after="{item}">
                                        <InlineSearchIcon class="px-1" @click="setModel(defaultModel); updateFilterState({ century: [item.id] })"></InlineSearchIcon>
                                    </template>
                                </IdLabelList>
                            </template>
                            <template #work="props">
                                <IdLabelList :items="formatTextWorksAsIdLabel(props.item)" class="d-flex flex-column" itemClass="fst-italic">
                                    <template #after="{item}">
                                        <InlineSearchIcon class="px-1" @click="updateFilterState({ work: [item.id] })"></InlineSearchIcon>
                                    </template>
                                </IdLabelList>
                            </template>
                            <template #author="props">
                                <IdLabelList :items="formatTextAuthorsAsIdLabel(props.item)" class="d-flex flex-column">
                                    <template #after="{item}">
                                        <InlineSearchIcon class="px-1" @click="updateFilterState({ author: [item.id] })"></InlineSearchIcon>
                                    </template>
                                </IdLabelList>
                            </template>
                            <template #reference="props">
                                <ReferenceDetails :references="formatTextReferences(props.item)" class="d-flex flex-column">
                                    <template #after="{item}">
                                        <InlineSearchIcon class="px-1" @click="updateFilterState({ reference: [item.id] })"></InlineSearchIcon>
                                    </template>
                                </ReferenceDetails>
                            </template>
                        </b-table>
                    </div>

                </div>
            </section>
        </article>
        <div
            v-if="isFetching"
            class="loading-overlay"
        >
            <div class="spinner"/>
        </div>
    </div>
</template>

<script setup lang="ts">
import {useI18n} from 'vue-i18n'

import {onMounted, ref, shallowRef, watch} from 'vue'


import BPagination from "../Bootstrap/BPagination.vue";
import BSelect from "../Bootstrap/BSelect.vue";
import RecordCount from "../Bootstrap/RecordCount.vue";
import BTable from "../Bootstrap/BTable.vue";

import charterRepository from "@/repositories/TextRepository.ts";
import {type DataTableState, useTablePagination} from "@/composables/useTablePagination";
import {useSimpleState} from "@/composables/useSimpleState.ts";
import {useVueFormGenerator, type ValidatorFn} from "@/composables/useVueFormGenerator";
import {type SearchFilters, useSearchApi} from "@/composables/useSearchApi";
import {createSchema} from '@/components/Text/TextSearchAppForm.js'
import qs from "qs";
import {useSearchContext} from "@/composables/useSearchContext.ts";
import {type FilterTag, useActiveFilterTags} from "@/composables/useActiveFilterTags.ts";
import BFilterTags from "@/components/Bootstrap/BFilterTags.vue";
import SelectedItemsBasket from "@/components/SearchContext/SelectedItemsBasket.vue";
import {useItemsBasket} from "@/composables/useItemsBasket.ts";

import {useUrlGenerator} from "@/composables/useUrlGenerator.ts";
import FormatValue from "@/components/Sidebar/FormatValue.vue";
import {
    formatTextAuthorsAsIdLabel,
    formatTextCenturiesAsIdLabel,
    formatTextReferences, formatTextWorkLocus,
    formatTextWorksAsIdLabel,
} from "./Formatters.ts";
import IdLabelList from "@/components/Shared/IdLabelList.vue";
import InlineSearchIcon from "@/components/Shared/InlineSearchIcon.vue";
import ReferenceDetails from "@/components/Text/ReferenceDetails.vue";

const {t} = useI18n()

// props
const props = defineProps({
    initUrls: {
        type: String,
        default: '{}',
    },
    title: {
        type: String,
        default: null
    }
})

const {initUrls, title} = props
const urls = JSON.parse(initUrls)

const {getRoute} = useUrlGenerator(urls)

// table options

const tableOptions = {
    fields: [
        {key: 'id', label: t('label.id'), sortable: true, thClass: 'no-wrap'},
        {key: 'century', label: t('Century'), sortable: true, thClass: 'no-wrap'},
        {key: 'author', label: t('Author'), sortable: true, thClass: 'no-wrap'},
        {key: 'work', label: t('Work'), sortable: true, thClass: 'no-wrap'},
        {key: 'locus', label: t('Locus'), sortable: false, thClass: 'no-wrap'},
        {key: 'reference', label: t('References'), sortable: true, thClass: 'no-wrap'},
    ],
    pagination: {
        chunk: 5,
        perPageValues: [25, 50, 100],
    },
}

// pagination state
const defaultDataTableState: DataTableState = {
    orderBy: 'id',
    orderAsc: false,
    rowsPerPage: 25,
    currentPage: 1,
}

const {
    state: dataTableState,
    setCurrentPage,
    setState: setDataTableState,
    updateState: patchDataTableState,
    toState: toDataTableState,
} = useTablePagination(defaultDataTableState)

// filter state
const {state: filterState, setState: setFilterState} = useSimpleState([]);

// form schema & model
const defaultModel = {
}

const initialValidators: Record<string, ValidatorFn> = {
}

const {
    model,
    schema,
    formOptions,
    setSchema,
    setModel,
    toModel,
    modelHasChanged,
    flattenModel,
    updateFieldValues,
    getFieldConfig,
} = useVueFormGenerator({}, defaultModel, initialValidators);

const {
    getActiveFilterTagStrings,
    closeActiveFilterTag
} = useActiveFilterTags(model, getFieldConfig, Object.keys(defaultModel))

const onCloseActiveFilter = (tag: FilterTag) => {
    closeActiveFilterTag(tag);
    updateFilterState(flattenModel(model.value))
}

const {
    getHashedUrl,
    beforeRedirect,
} = useSearchContext(getRoute('text_search')) // todo: should use text_get_single route

// useVueFormGeneratorCollapsibleGroups(schema, 'text-search-groups')

// search api

const {
    data,
    isFetching,
    error,
    query,
    count: totalRecords,
    results: tableData,
    aggregations,
    createSearchQuery,
} = useSearchApi(getRoute('text_search_api'))

//selected rows
const {
    selectedIds,
    setSelectedIds,
    removeSelectedIndex,
    removeSelectedId,
    toggleRowSelection,
    toggleAllRowsSelection,
    allSelected,
    tableDataWithCheckbox
} = useItemsBasket(tableData);

watch(aggregations, (currentAggregations) => {
    if (currentAggregations) {
        updateFieldValues(currentAggregations)
    }
})

// map api
const onFormValidated = (isValid, errors) => {
    if (!isValid) {
        return
    }
    updateFilterState(flattenModel(model.value))
}

const resetAllFilters = () => {
    setModel(defaultModel)
    setCurrentPage(1)
    updateFilterState(flattenModel(model.value))
}

const pushHistory = () => {
    const state = {
        model: JSON.parse(JSON.stringify(model.value)),
        dataTableState: JSON.parse(JSON.stringify(dataTableState.value)),
        filterState: JSON.parse(JSON.stringify(filterState.value)),
    }
    // console.log('Pushing state to history:', state);
    const query = createSearchQuery(dataTableState.value, filterState.value);

    history.pushState(state, '', document.location.href.split('?')[0] + '?' + qs.stringify(query))
}

const onPopHistory = (event: PopStateEvent) => {
    if (event.state) {
        // console.log('Popping state from history:', event.state);
        setModel(event.state.model)
        setDataTableState(event.state.dataTableState)
        setFilterState(event.state.filterState)

        // search & aggregate
        query(dataTableState.value, filterState.value, 'search_aggregate').then(() => {
            console.log('Search and aggregation completed after popstate event');
        });
    }
}

const updateDataTableState = (payload: Partial<DataTableState>) => {
    // update datatable state
    patchDataTableState(payload)

    // push query to history
    pushHistory()

    // paginate (DO NOT aggregate!)
    return query(dataTableState.value, filterState.value, 'search');
}

const updateFilterState = (payload: any) => {
    // reset model
    // todo: this creates an invalid model, not based on the schema
    setModel({...defaultModel, ...toModel(payload)})

    // update filter state
    setFilterState(payload)
    // reset pagination
    setCurrentPage(1)

    // push query to history
    pushHistory()

    // search & aggregate
    return query(dataTableState.value, filterState.value, 'search_aggregate');
}

// init form schema

setSchema(createSchema({
    t
}))

let params = qs.parse(window.location.href.split('?', 2)[1])

// init filterstate
const filters = params['filters'] ?? {}
setFilterState(filters)

// init model
setModel({...defaultModel, ...toModel(filters)})

// init dataTableState
setDataTableState(toDataTableState(params))

// initial query
query(dataTableState.value, filterState.value, 'search_aggregate');

// add onpopstate event listener
// to handle browser back/forward navigation
onMounted(() => {
    window.onpopstate = ((event: PopStateEvent) => {
        onPopHistory(event)
    })
})
</script>
