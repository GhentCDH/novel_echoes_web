import wNumb from 'wnumb'

import Articles from 'articles'

const RANGE_MIN_INVALID = -1
const RANGE_MAX_INVALID = 10000
const createSelect = (label, extra = null, extraSelectOptions = null) => {
    const result = {
        type: 'multiselectClear',
        label: label,
        labelClasses: 'control-label',
        placeholder: 'Loading',
        // lowercase first letter + remove spaces
        model: label.charAt(0).toLowerCase() + label.slice(1).replace(/[ ]/g, ''),
        // Values will be loaded using a watcher or Ajax request
        values: [],
        selectOptions: {
            customLabel: ({id, name}) => {
                return name
            },
            showLabels: false,
            loading: true,
            trackBy: 'id',
            optionsLimit: 10000
        },
        // Will be enabled by enableField
        disabled: true,
        anyKey: -2,
        allowAny: true,
        noneKey: -1,
        allowNone: true
    }
    if (extra !== null) {
        for (let key of Object.keys(extra)) {
            result[key] = extra[key]
        }
    }
    if (extraSelectOptions !== null) {
        for (let key of Object.keys(extraSelectOptions)) {
            result['selectOptions'][key] = extraSelectOptions[key]
        }
    }
    return result
}

const createMultiSelect = (label, extra = null, extraSelectOptions) => {
    const selectOptions = {multiple: true, closeOnSelect: false, ...extraSelectOptions}
    return createSelect(label, {placeholderSelectItem: extra?.placeholder, ...extra}, selectOptions)
}
const createRangeSlider = (model, label, min, max, step, decimals = 0, unit = null, extra = null) => {
    let result = {
        type: "customNoUiSlider",
        styleClasses: "field-noUiSlider",
        label: label,
        model: model,
        min: min,
        max: max,
        noUiSliderOptions: {
            connect: true,
            range: {
                'min': [RANGE_MIN_INVALID, 1],
                '10%': [min, step],
                '90%': [max, RANGE_MAX_INVALID],
                'max': [RANGE_MAX_INVALID]
            },
            start: [-1, 10000],
            tooltips: {to: formatSliderToolTip(decimals, unit)},
        }
    }

    return result;
}

const createOperators = (model, extra, allowedOperators = []) => {
    const result = {
        type: "checkboxes",
        styleClasses: "field-inline-options field-checkboxes-labels-only collapsible collapsed",
        label: 'options',
        model: model,
        parentModel: model.replace('_op', ''),
        values: [
            {name: "OR", value: "or", toggleGroup: "and_or", disabled: operatorIsDisabled},
            {name: "AND", value: "and", toggleGroup: "and_or", disabled: operatorIsDisabled},
            {name: "NOT", value: "not", disabled: operatorIsDisabled},
            {name: "ONLY", value: "only", disabled: operatorIsDisabled},
        ]
    }
    if (extra != null) {
        for (let key of Object.keys(extra)) {
            result[key] = extra[key]
        }
    }
    if (allowedOperators.length) {
        result.values = result.values.filter(item => allowedOperators.includes(item.value))
    }

    return result;
}

const formatSliderToolTip = (decimals = 0, unit = null) => {
    return function (value) {
        if (value > -1 && value < 10000) {
            return String(wNumb({decimals: decimals}).to(value)) + String(unit ?? '')
        } else {
            return 'off';
        }
    }
}

const operatorIsDisabled = (model, schema, item) => {
    let parentValues = model[schema.parentModel] === undefined ? [] : model[schema.parentModel]
    let parentCount = parentValues.length;
    let globalKeys = [model[schema.parentModel]?.noneKey ?? -1, model[schema.parentModel]?.anyKey ?? -2]

    // any/none selected? disable all
    if (parentValues.length === 1 && globalKeys.includes(parentValues[0].id)) {
        return true
    }

    if (['and', 'or'].includes(item.value)) {
        if (parentCount < 2) {
            return true
        }
    }
    if (['not', 'only'].includes(item.value)) {
        if (parentCount < 1) {
            return true
        }
    }
    return false
}

export default {
    createSelect,
    createMultiSelect,
    createRangeSlider,
    createOperators,
}
