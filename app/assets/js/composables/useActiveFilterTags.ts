import type {Field, Model} from "@/composables/useVueFormGenerator";
import {type Ref} from "vue";

enum FilterType {
    INVALID,
    OBJECTLIST, // Expects a "name" field for each object
    DATERANGE, // Expects a "from" and "till" field with each day, month and year fields
    BOOLEAN,
    STRING,
}

export type FilterTag = {
    label: string,
    value: string,
    key: string,
    type: FilterType
}

/**
 * Composable to manage active filter tags
 * @param modelRef a ref to the model holding the current filters
 * @param getFieldConfig function that given the name (key) of a field will return its Field object which also contains the type which we need
 * @param ignore list of field names to ignore. Those fields will not appear in as a filter tag
 */
export function useActiveFilterTags(modelRef: Ref<Model>, getFieldConfig: (key: string) => Field, ignore: string[] = []) {

    const model = modelRef;

    /**
     * Converts supported types into matching FilterType enum
     */
    const stringTypeToEnum = new Map<string, FilterType>(Object.entries({
        "input": FilterType.STRING,
        "multiselectClear": FilterType.OBJECTLIST,
        "DMYRange": FilterType.DATERANGE,
        "checkboxBS5": FilterType.BOOLEAN,
        "checkbox": FilterType.BOOLEAN,
    }));

    /**
     * Gets a FilterType enum
     * @param k key of the Field
     */
    function getFilterType(k: string): FilterType {
        const type = getFieldConfig(k).type;
        return stringTypeToEnum.has(type) ? stringTypeToEnum.get(type)! : FilterType.INVALID;
    }

    /**
     * Mapping a FilterType to the function that has to be executed on it's key and value to get a correct FilterTag
     */
    const typesFunctionsMap: Map<FilterType, (k: string, v: any) => FilterTag[]> = new Map();

    typesFunctionsMap.set(FilterType.STRING, (k: string, v: string) => {
        let res: FilterTag[] = [];
        if (v.length > 0){
            const schemaField = getFieldConfig(k);
            res.push({label: `${schemaField.label ? schemaField.label : k}: `, value: v, key: k, type: FilterType.STRING});
        }
        return res;
    });

    typesFunctionsMap.set(FilterType.OBJECTLIST, (k: string, v: any[]) => {
        let res: FilterTag[] = [];
        v.forEach((item) => {
            if (item.name){
                const schemaField = getFieldConfig(k);
                res.push({label: `${schemaField.label ? schemaField.label : ""}: `, value: item.name, key: k, type: FilterType.OBJECTLIST});
            }
        });
        return res
    });

    /**
     * This is just used to really make sure date ranges have the correct till, from fields with day, month or year
     * @param v
     */
    function isDateRange(v: any): boolean {
        return !!v && v.from && v.till && (v.from.day || v.till.day || v.from.month || v.till.month || v.from.year || v.till.year);
    }

    typesFunctionsMap.set(FilterType.DATERANGE, (k: string, v: any) => {
        let res: FilterTag[] = [];
        if(isDateRange(v)){
            const schemaField = getFieldConfig(k);
            if(!(v.till.day || v.till.month || v.till.year)){
                res.push({label: `${schemaField.label ? schemaField.label : k}: `, value: `${v.from.day? v.from.day : "?"}/${v.from.month ? v.from.month : "?"}/${v.from.year ? v.from.year : "?"}`, key: k, type: FilterType.DATERANGE});
            }else {
                res.push({label: `${schemaField.label ? schemaField.label : k}: `, value: `${v.from.day? v.from.day : "?"}/${v.from.month ? v.from.month : "?"}/${v.from.year ? v.from.year : "?"} - 
                ${v.till.day ? v.till.day : "?"}/${v.till.month ? v.till.month : "?"}/${v.till.year ? v.till.year : "?"}`, key: k, type: FilterType.DATERANGE});
            }
        }
        return res
    });

    typesFunctionsMap.set(FilterType.BOOLEAN, (k: string, v: boolean) => {
        let res: FilterTag[] = [];
        if (v) {
            const schemaField = getFieldConfig(k);
            res.push({label: `${schemaField.label ? schemaField.label : k}`, value: "", key: k, type: FilterType.BOOLEAN});
        }
        return res
    });

    /**
     * Returns a list of FilterTags based on the model
     */
    const getActiveFilterTagStrings = (): FilterTag[] => {
        let res: FilterTag[] = [];
        Object.entries(model.value).forEach(([k,v],_) => {
            if (!ignore.includes(k)){
                const handle = typesFunctionsMap.get(getFilterType(k));
                if (handle){
                    res = res.concat(handle(k,v))
                }
            }
        });
        return res
    }

    /**
     * Maps a FilterType to a function that given the current model value and a FilterTag will return an updated model value
     */
    const closeFilterFunctionsMap: Map<FilterType, (model: Model, tag: FilterTag) => Model> = new Map();

    closeFilterFunctionsMap.set(FilterType.STRING, (model: Model, tag: FilterTag) => {
        delete model[tag.key]
        return model;
    });

    closeFilterFunctionsMap.set(FilterType.BOOLEAN, closeFilterFunctionsMap.get(FilterType.STRING)!);

    closeFilterFunctionsMap.set(FilterType.OBJECTLIST, (model: Model, tag: FilterTag) => {
        model[tag.key] = model[tag.key].filter((item: any) => item.name !== tag.value);
        return model;
    });

    closeFilterFunctionsMap.set(FilterType.DATERANGE, (model: Model, tag: FilterTag) => {
        model[tag.key].from = {day: null, month: null, year: null};
        model[tag.key].till = {day: null, month: null, year: null};
        return model;
    });

    /**
     * Close an active filter tag and update the model
     * @param tag tag to close
     */
    const closeActiveFilterTag = (tag: FilterTag) => {
        const handle = closeFilterFunctionsMap.get(tag.type);
        if (handle){
            let res = handle(model.value, tag);
            model.value = {...res}
        }
    }

    return {
        getActiveFilterTagStrings,
        closeActiveFilterTag
    }
}
