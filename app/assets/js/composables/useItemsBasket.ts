import {useSimpleState} from "./useSimpleState.ts";
import {computed, type Ref} from "vue";

export function useItemsBasket(
    tableData: Ref<any, any>
){

    const {state: selectedIds, setState: setSelectedIds} = useSimpleState<number[]>([]);

    function removeSelectedId(id: number){
        selectedIds.value.splice(selectedIds.value.indexOf(id), 1);
    }

    function removeSelectedIndex(index: number){
        selectedIds.value.splice(index, 1);
    }

    function toggleRowSelection(id: number){
        if (selectedIds.value.includes(id)){
            removeSelectedId(id)
        } else {
            setSelectedIds([...selectedIds.value, id].sort((a, b) => a-b));
        }
    }

    function toggleAllRowsSelection(){
        const ids: number[] = tableData.value.map((row: any) => row.id);
        if (ids.every((v: number) => selectedIds.value.includes(v))){
            ids.forEach((id: number) => {
                removeSelectedId(id)
            })
        } else {
            setSelectedIds([...new Set([...selectedIds.value, ...ids])].sort((a, b) => a-b));
        }
    }

    const allSelected = computed(() => {
        const ids = tableData.value.map((row: any) => row.id);
        return ids.every((v: number) => selectedIds.value.includes(v))
    });

    const tableDataWithCheckbox = computed(() => {
        return tableData.value.map(row => ({
            ...row,
            selected: selectedIds.value.includes(row.id)
        }))
    });

    return {
        selectedIds,
        setSelectedIds,
        removeSelectedId,
        removeSelectedIndex,
        toggleAllRowsSelection,
        toggleRowSelection,
        allSelected,
        tableDataWithCheckbox
    }
}