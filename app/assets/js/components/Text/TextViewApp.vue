<template>
    <div class="row d-flex flex-direction-row flex-nowrap align-items-stretch" v-if="text">
        <article class="d-flex col-sm-8 overflow-hidden">
            <div class="scrollable scrollable--vertical pe-2 pbottom-large w-100">

                <h1 class="pbottom-default">{{ text.title }}</h1>

                <dl class="row mb-3">
                    <dt-dd :empty="isEmpty(formatTextAuthorsAsIdLabel(text))" label="Author"><id-label-list :items="formatTextAuthorsAsIdLabel(text)" item-class="me-1"></id-label-list></dt-dd>
                    <dt-dd :empty="isEmpty(formatTextCenturiesAsIdLabel(text))" label="Century"><id-label-list :items="formatTextCenturiesAsIdLabel(text)" item-class="me-1"></id-label-list></dt-dd>
                    <dt-dd :empty="isEmpty(text.text)" label="Text"><span class="greek">{{ text.text }}</span></dt-dd>
                    <dt-dd :empty="isEmpty(text.edition)" label="Edition">{{ text.edition }}</dt-dd>
                    <dt-dd :empty="isEmpty(formatTextTypesAsIdLabel(text))" label="Type"><id-label-list :items="formatTextTypesAsIdLabel(text)" item-class="me-1"></id-label-list></dt-dd>
                    <dt-dd :empty="isEmpty(text.source)" label="Source">{{ text.source }}</dt-dd>
                    <dt-dd :empty="isEmpty(text.info)" label="Info">{{ text.info }}</dt-dd>
                </dl>

                <h2>Reference(s) to</h2>

                <div class="card mb-3 rounded-0" v-for="(item, index) in formatTextReferences(text)" :key="index">
                    <div class="card-header">
                        <h3 class="m-0">{{ item.label }}</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt-dd :empty="isEmpty(item.locus)" label="Locus">{{ item.locus }}</dt-dd>
                            <dt-dd :empty="isEmpty(item.text)" label="Text"><span class="greek">{{ item.text }}</span></dt-dd>
                        </dl>
                    </div>
                </div>
            </div>
        </article>
        <aside class="d-flex col-sm-4 overflow-hidden">
            <div class="padding-default bg-tertiary scrollable scrollable--vertical w-100 border-top-dibe">
                <Widget v-if="validContextAndResultSet()" title="Search" :collapsed="false">
                    <div class="row mbottom-default">
                        <div class="form-group">
                            <span class="btn btn-sm btn-primary" @click="returnToSearchResult">&lt; Return to list</span>
                        </div>
                        <div class="col col-3" :class="{ disabled: context.searchIndex === 1}">
                            <span class="btn btn-sm btn-primary" @click="loadByIndex(1)">
                                <i class="fa-solid fa-angles-left"></i>
                            </span>
                            <span class="btn btn-sm btn-primary" @click="loadByIndex(context.searchIndex - 1)">
                                <i class="fa-solid fa-angle-left"></i>
                            </span>
                        </div>

                        <div class="col col-6 text-center"><span>Result {{ context.searchIndex }} of {{ context.count }}</span></div>
                        <div class="col col-3 text-right" :class="{ disabled: context.searchIndex === context.count}">

                            <span class="btn btn-sm btn-primary" @click="loadByIndex(context.searchIndex + 1)">
                                <i class="fa-solid fa-angle-right"></i>
                            </span>
                            <span class="btn btn-sm btn-primary" @click="loadByIndex( context.count)">
                                <i class="fa-solid fa-angles-right"></i>
                            </span>
                        </div>
                    </div>
                </Widget>
            </div>
        </aside>
        <div
            v-if="openRequests"
            class="loading-overlay"
        >
            <div class="spinner"/>
        </div>
    </div>
</template>

<script setup lang="ts">

import {type Context, useSearchContext} from "@/composables/useSearchContext";
import {ref, computed, toValue, watch, toRef} from 'vue'

import * as qs from "qs";
import textRepository from "@/repositories/TextRepository.ts";

import {
    formatTextAuthorsAsIdLabel, formatTextCenturiesAsIdLabel, formatTextReferences,
    formatTextTitle, formatTextTypesAsIdLabel
} from "./Formatters.ts"
import {useI18n} from "vue-i18n";
import {useUrlGenerator} from "@/composables/useUrlGenerator.ts";
import DtDd from "@/components/Text/DtDd.vue";
import IdLabelList from "@/components/Shared/IdLabelList.vue";

const { t } = useI18n();

const props = defineProps({
    initUrls: {
        type: String,
        required: true
    },
})

const urls = JSON.parse(props.initUrls)
const data = ref<{ text: any }>({} as { text: any })

const { createTextUrl, getRoute } = useUrlGenerator(urls);

// Initialize
const text = computed(() => data.value.text)

const segments = window.location.pathname.split('/');
const id = Number(segments[segments.length - 1]);
getText(id);

const openRequests = ref(false)

function urlGeneratorIssuer(route, filter, filter_defaults = {}) {
    return (value) => (getRoute(route) + '?' + qs.stringify({filters: {actor_role_1: 2, [filter]: value.id}}));
}

function urlGeneratorAuthors(route, filter, filter_defaults = {}) {
    return (value) => (getRoute(route) + '?' + qs.stringify({filters: {actor_role_1: 1, [filter]: value.id}}));
}

function urlGeneratorBeneficiaries(route, filter, filter_defaults = {}) {
    return (value) => (getRoute(route) + '?' + qs.stringify({filters: {actor_role_1: 3, [filter]: value.id}}));
}

function urlGeneratorIdName(route: string, filter: string, defaults = {}) {
    return (value: any) => `${getRoute(route)}?${qs.stringify({filters: {...defaults, [filter]: value.id}})}`;
}

function updateTitle(title: string) {
    document.title = 'Novel Echoes Database - ' + title
}

function isEmpty(value: any) {
    console.log('isEmpty', value);
    if (value === null || value === undefined || value === '') {
        return true;
    }
    if (Array.isArray(value) && value.length === 0) {
        return true;
    }
    if (typeof value === 'object' && Object.keys(value).length === 0) {
        return true;
    }
    return false;
}

//Context
const {
    context,
    initContextFromUrl,
    initResultSet,
    loadByIndex,
    returnToSearchResult,
    validContextAndResultSet,
    setOnIdChanged,
} = useSearchContext();

function getText(id: number) {
    textRepository.get(id).then((response) => {
        data.value.text = response.data;
        const currentUrl = window.location.href;
        const newUrl = currentUrl.replace(/(\/text\/)\d+/, `$1${id}`);
        window.history.pushState(null, '', newUrl);
        updateTitle(text.value?.title || id);
    });
}

setOnIdChanged((newId: number) => {
    getText(newId)
});

initContextFromUrl();
if (context.value.validReadContext && !context.value.ids) {
    let readContext: Context = toValue(context);
    initResultSet(readContext, (new URL(readContext.prevUrl)).pathname + "/paginate"); //TODO how to fix url in composition API?
}

</script>

<style scoped lang="scss">
</style>