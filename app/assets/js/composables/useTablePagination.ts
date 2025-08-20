import {type Ref, ref, toValue, watch} from "vue";

export type DataTableState = {
  currentPage: number;
  rowsPerPage: number;
  orderBy: string;
  orderAsc: boolean;
}

export type onChangeCallback = (state: DataTableState) => void;

export function useTablePagination(initialState: DataTableState) {

  const state: Ref<DataTableState> = ref<DataTableState>(toValue(initialState));

  const setCurrentPage = (page: number) => {
    state.value.currentPage = page;
  }

  const setRowsPerPage = (rows: number) => {
    state.value.rowsPerPage = rows;
  }

  const setOrderBy = (orderBy: string) => {
    state.value.orderBy = orderBy;
  }

  const setOrderAsc = (orderAsc: boolean) => {
    state.value.orderAsc = orderAsc;
  }

  const setState = (newState: DataTableState) => {
    state.value = newState;
  }

  const updateState = (payload: Partial<DataTableState>) => {
    state.value = {
      ...state.value,
      ...payload,
    }
  }

  const toState = (payload: any): DataTableState => {
    const newState: Partial<DataTableState> = {};
    // currentPage
    if (Number(payload['page'] ?? undefined)) {
      newState.currentPage = Number(payload.page);
    }
    if (Number(payload['currentPage'] ?? undefined)) {
      newState.currentPage = Number(payload.currentPage);
    }
    // rowsPerPage
    if (Number(payload['rowsPerPage'] ?? undefined)) {
      newState.rowsPerPage = Number(payload.rowsPerPage);
    }
    // orderBy
    if (payload?.orderBy) {
      if (typeof payload.orderBy === 'string') {
        newState.orderBy = payload.orderBy;
      }
    }
    // orderAsc
    if (payload?.orderAsc) {
      if (typeof payload.orderAsc === 'boolean') {
        newState.orderAsc = payload.orderAsc;
      }
    }
    if (payload?.ascending) {
        if (typeof payload.ascending === 'boolean') {
            newState.orderAsc = payload.ascending;
        }
        if (typeof payload.ascending === 'string') {
          newState.orderAsc = (payload.ascending === 'true');
        }
    }
    return {...state.value, ...newState} as DataTableState;
  }

  const onChange = (callback: onChangeCallback) => {
    return watch(state, () => callback(toValue(state)), { deep: true } );
  }

  return {
    state,
    onChange,
    setCurrentPage,
    setRowsPerPage,
    setOrderAsc,
    setOrderBy,
    setState,
    toState,
    updateState
  };
}

