import {computed, toValue, toRef, toRaw, watch} from "vue";
import Articles from "articles";

import { useI18n } from "vue-i18n";


export interface Model {
    [key: string]: any
}

export interface Field {
    model: string,
    type: string,
    values?: any[],
    [key: string]: any
}

export type Fields = Field[]

export interface Group {
    fields: Fields,
    [key: string]: any
}

export type Groups = Group[]

export interface Schema {
    fields?: Fields,
    groups?: Groups
}

export type ValidatorFn = (value: any, model: Model, field: Field) => true | string

export function useVueFormGenerator(initialSchema: Schema = {}, initialModel: Model = {}, initialValidators: Record<string, ValidatorFn> = {}) {

    const { t } = useI18n()

    const defaultModel: Model = toValue(initialModel);

    const schema = toRef<Schema>(initialSchema);
    const model = toRef<Model>(JSON.parse(JSON.stringify(defaultModel)));
    const validators = toRef(initialValidators)

    const formOptions = toRef({
            validateAfterLoad: false,
            validateAfterChanged: true,
            validationErrorClass: "has-error",
            validationSuccessClass: "success"
    })

    watch(
        [schema, validators],
        () => {
            const patch = (fields: Field[] = []) => {
                fields.forEach(f => {
                    if (validators.value[f.model]) {
                        f.validator = validators.value[f.model]
                    }
                })
            }

            patch(schema.value.fields)
            schema.value.groups?.forEach(g => patch(g.fields))
        },
        { immediate: true, deep: true }
    )
    const clearErrors = () => {
        Object.values(fieldConfigs.value).forEach(f => {
            delete f.error
            delete f.errorMessage
        })
    }

    const setSchema = (newSchema: Schema) => {
        schema.value = newSchema;
    }

    const setModel = (newModel: Model) => {
        model.value = JSON.parse(JSON.stringify(toValue(newModel)));
    }

    const fieldConfigs = computed((): Fields => {
        const res = {};
        schema.value?.fields
        schema.value?.groups
        if (Array.isArray(schema.value?.fields)) {
            schema.value.fields.forEach( (field: Field) => {
                res[field.model] = field;
            });
        }
        if (Array.isArray(schema.value?.groups)) {
            schema.value.groups.forEach((group: Group) => {
                if (Array.isArray(group?.fields)) {
                    group.fields.forEach((field: Field) => {
                        res[field.model] = field;
                    });
                }
            });
        }
        return <Field[]>res;
    });

    const convertEmptyToNull = (value: any) => {
        if (Array.isArray(value) && value.length === 0) {
            return null;
        }
        if (typeof value === 'string' && value.trim() === '') {
            return null;
        }
        return value;
    }

    const modelHasChanged = computed((): boolean => {
        for (const [key, value] of Object.entries(model.value)) {
            const cleanValue = convertEmptyToNull(value)
            const cleanDefaultValue = convertEmptyToNull(defaultModel[key] ?? null)
            if (JSON.stringify(cleanValue) !== JSON.stringify(cleanDefaultValue)) {
                return true
            }
        }
        return false
    })

    const getFieldConfig = (fieldName: string): Field => {
        return fieldConfigs.value?.[fieldName] ?? null;
    }

    const updateFieldValues = (data: any, fieldNames: Array<string>|null = null, keepModelData: boolean = false) => {
        fieldNames = fieldNames && Array.isArray(fieldNames) ? fieldNames : Object.keys(fieldConfigs.value)
        for (const fieldName of fieldNames) {
            const fieldConfig = getFieldConfig(fieldName);
            if (fieldConfig && fieldConfig.type === 'multiselectClear') {
                // get aggregation values
                const values = data?.[fieldName] ?? [];

                // add current model data?
                if (keepModelData && model.value?.[fieldName]?.length) {
                    const ids = new Set(values.map(item => item.id))
                    for (const item of model.value?.[fieldName] ?? []) {
                        if (!ids.has(item.id)) {
                            values.push(item)
                        }
                    }
                }

                // sort values?
                fieldConfig.values = fieldConfig?.sortBy === 'name' ? values.sort(_sortByName) : values

                // active values? update model
                if (fieldConfig?.values) {
                    let activeValues = fieldConfig.values.filter(item => item?.active)
                    if (activeValues.length) {
                        model.value[fieldName] = activeValues
                    }
                }

                // update dependency field?
                if (fieldConfig?.dependency && model.value[fieldConfig.dependency] == null) {
                    _dependencyField(fieldConfig)
                } else {
                    _enableField(fieldConfig)
                }
            }
        }
    }

    const _disableField = (field: Field) => {
        field.disabled = true
        field.placeholder = 'Loading'
        field.selectOptions.loading = true
        field.values = []
    }

    const _dependencyField = (field: Field) => {
        if ( ! (field.dependencyName ?? getFieldConfig(field.dependency) ) ) {
            console.error('VFG config error: dependency field not found for field ' + field.model)
            return
        }

        // get everything after last '.'
        let modelName = field.model.split('.').pop()

        let label = field.dependencyName ?? getFieldConfig(field.dependency).label.toLowerCase()

        modelName && delete model.value[modelName]
        field.disabled = true
        field.selectOptions.loading = false
        field.placeholder = field?.placeholderSelectDependencyFirst ?? 'Please select ' + Articles.articlize(label) + ' first'
        // set dependency state
        field.styleClasses = [...new Set(field?.styleClasses?.split(' ') ?? []).add('field--dependency-missing')].join(' ')
    }

    const _enableField = (field: Field, search = false) => {
        if (Array.isArray(field.values) && field.values.length === 0) {
            return _noValuesField(field, search)
        }

        // get everything after last '.'
        let modelName = field.model.split('.').pop()

        // only keep current value(s) if it is in the list of possible values
        // if (model.value[modelName] != null) {
        //     if (Array.isArray(model.value[modelName])) {
        //         let newValues = []
        //         for (let index of model.value[modelName].keys()) {
        //             if ((field.values.filter(v => v.id === model.value[modelName][index].id)).length !== 0) {
        //                 newValues.push(model.value[modelName][index])
        //             }
        //         }
        //         model.value[modelName] = newValues
        //     }
        //     else if ((field.values.filter(v => v.id === model.value[modelName].id)).length === 0) {
        //         model.value[modelName] = null
        //     }
        // }

        field.selectOptions.loading = false
        field.disabled = field.originalDisabled == null ? false : field.originalDisabled;
        let label = field.label.toLowerCase()
        field.placeholder = field?.placeholderSelectItem ?? 'Select ' + Articles.articlize(label)

        // remove dependency state
        let classes = new Set(field?.styleClasses?.split(' ') ?? [])
        classes.delete('field--dependency-missing')
        field.styleClasses = [...classes].join(' ')
    }

    const _noValuesField = (field: Field, search = false) => {
        // Delete value if not on the search page
        if (!search) {
            // get everything after last '.'
            let modelName = field.model.split('.').pop()
            modelName && delete model.value[modelName]
        }

        field.disabled = true
        field.selectOptions.loading = false
        field.placeholder = field?.placeholderEmpty ?? 'No ' + field.label.toLowerCase() + ' available'
    }

    const _sortByName = (a, b) => {
        const a_name = a.name.toString()
        const b_name = b.name.toString()

        // Place 'any', 'none' filters above
        if ((a_name === 'none' || a_name === 'any') && (b_name !== 'any' && b_name !== 'none')) {
            return -1
        }
        if ((a_name !== 'any' && a_name !== 'none') && (b_name === 'any' || b_name === 'none')) {
            return 1
        }

        // Place true before false
        if (a_name === 'false' && b_name === 'true') {
            return 1
        }
        if (a_name === 'true' && b_name === 'false') {
            return -1
        }

        // Default
        return a_name.localeCompare(b_name, 'en', {sensitivity: 'base'})
    }

    // Flatten the model to a simple object
    const flattenModel = (model: Model) => {
        const result: any = {}
        if (model !== null) {
            for (const [fieldName, fieldValue] of Object.entries(model)) {
                const fieldType = getFieldConfig(fieldName)?.type ?? null;
                if (!fieldType || fieldValue == null) {
                    continue
                }
                switch (fieldType) {
                    case 'multiselectClear':
                        if (Array.isArray(model[fieldName])) {
                            let ids: any[] = []
                            for (let value of model[fieldName]) {
                                ids.push(value['id'])
                            }
                            result[fieldName] = ids
                        } else {
                            result[fieldName] = model[fieldName]['id']
                        }
                        break;
                    default:
                        result[fieldName] = structuredClone(toRaw(model[fieldName]))
                        break;
                }
            }
        }
        return result
    }

    // Convert any data to a form model
    // Try to use fieldConfigs to convert data to model
    const toModel = (data: any): Model => {
        let tmpModel = {}
        Object.entries(data).forEach(([fieldName, fieldValue]) => {
            const cleanValue = convertEmptyToNull(fieldValue);
            if (cleanValue === null || cleanValue === undefined) {
                return;
            }
            const fieldConfig = getFieldConfig(fieldName);
            if (!fieldConfig) {
                return;
            }
            switch(fieldConfig.type) {
                case 'multiselectClear':
                    let values = (Array.isArray(cleanValue) ? cleanValue : [cleanValue]).map(value => `${value}`);
                    if (Array.isArray(fieldConfig?.values) && fieldConfig.values.length !== 0) {
                        const newFieldValues = fieldConfig.values.filter(item => values.includes(`${item.id}`));
                        if (newFieldValues.length > 0) {
                            tmpModel[fieldName] = newFieldValues;
                        }
                    }
                    break;
                case 'checkbox':
                    if (fieldValue === true || fieldValue === 'true' || fieldValue === 1 || fieldValue === '1') {
                        tmpModel[fieldName] = true;
                    }
                    else if (fieldValue === false || fieldValue === 'false' || fieldValue === 0 || fieldValue === '0') {
                        tmpModel[fieldName] = false;
                    }
                    break;
                default:
                    tmpModel[fieldName] = structuredClone(toRaw(cleanValue));
                    break;
            }
        })
        return tmpModel
    }

    return {
        schema,
        model,
        formOptions,
        setModel,
        setSchema,
        toModel,
        modelHasChanged,
        getFieldConfig,
        updateFieldValues,
        flattenModel,
    }
}
