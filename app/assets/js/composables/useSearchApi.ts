import {ref, toValue, watch, shallowRef} from 'vue';
import {useFetch} from '@vueuse/core';
import qs from 'qs'

export enum queryMode {
    search = 'search',
    aggregate = 'aggregate',
    search_aggregate = 'search_aggregate',
}

export type SearchQuery = {
    orderBy: string;
    ascending: boolean;
    limit: number;
    page: number;
    filters: SearchFilters | null;
}

export type SearchFilters = {
    [key: string]: any
}

export type PaginationState = {
    orderBy: string;
    orderAsc: boolean;
    rowsPerPage: number;
    currentPage: number;
}

export function useSearchApi(url: string) {

    const endpoint = ref<string>(toValue(url));

    const useFetchUrl = ref<string>('')
    const fetchOptions = {
        immediate: false
    }

    const {isFetching, error, data, execute} = useFetch(useFetchUrl, fetchOptions).get().json()

    // result state
    const count = ref<number>(0)
    const aggregations = shallowRef<any>({})
    const results = shallowRef<any>([])

    // watch for changes in the data and fill the results, count and aggregations
    watch(data, (newData) => {
        if (newData) {
            count.value = newData.count
            results.value = newData.data
            if (newData?.aggregation) {
                aggregations.value = newData.aggregation
            }
        }
    })

    const createSearchQuery = (paginationState: PaginationState, filterState: SearchFilters) => {
        const query: SearchQuery = {
            orderBy: paginationState.orderBy,
            ascending: paginationState.orderAsc,
            limit: paginationState.rowsPerPage,
            page: paginationState.currentPage,
            filters: null,
        }

        query.filters = {...filterState}
        return query
    }

    const query = async (paginationState: PaginationState, filterState: SearchFilters, mode: queryMode)=> {
        const query = createSearchQuery(paginationState, filterState)
        await fetch(query, mode)
    }

    // fetch data from the api
    const fetch = async (params: SearchQuery, mode: queryMode) => {
        const queryParams = {...params}
        queryParams['mode'] = mode

        useFetchUrl.value = endpoint.value + '?' + qs.stringify(queryParams)
        await execute()
    }

    return {
        isFetching,
        error,
        data,
        results,
        count,
        aggregations,
        fetch,
        query,
        createSearchQuery,
    }
}