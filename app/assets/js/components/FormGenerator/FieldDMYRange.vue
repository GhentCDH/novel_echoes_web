<template>
    <div class="field-DMYRange">
        <div class="input-group mbottom-small from" :class="{'has-from': hasFrom}">
            <span class="input-group-text">{{ hasTill ? schema?.labelFrom ?? t('vfg.dmyRange.label.till') : schema?.labelExact ?? t('vfg.dmyRange.label.exact') }}</span>
            <input
                class="form-control date-input"
                type="text"
                style="text-align:center;"
                size="2"
                :value="range.from.day"
                :disabled="disabled"
                :maxlength="2"
                :placeholder="schema?.placeholderDay ?? t('vfg.dmyRange.placeholder.day')"
                :readonly="schema.readonly"
                @input="range.from.day = $event.target.value; onChange()"
            >
            <input
                class="form-control date-input"
                type="text"
                style="text-align:center;"
                size="2"
                :value="range.from.month"
                :disabled="disabled"
                :maxlength="2"
                :placeholder="schema?.placeholderMonth ?? t('vfg.dmyRange.placeholder.month')"
                :readonly="schema.readonly"
                @input="range.from.month = $event.target.value; onChange()"
            >
            <input
                class="form-control date-input"
                type="text"
                style="text-align:center;"
                size="4"
                :value="range.from.year"
                :disabled="disabled"
                :maxlength="4"
                :placeholder="schema?.placeholderYear ?? t('vfg.dmyRange.placeholder.year')"
                :readonly="schema.readonly"
                @input="range.from.year = $event.target.value; onChange()"
            >
        </div>
        <div class="input-group till" :class="{'has-till': hasTill}">
            <span class="input-group-text">{{ schema?.labelTill ?? t('vfg.dmyRange.label.till') }}</span>
            <input
                class="form-control date-input"
                type="text"
                style="text-align:center;"
                size="2"
                :value="range.till.day"
                :disabled="disabled"
                :maxlength="2"
                :placeholder="schema?.placeholderDay ?? t('vfg.dmyRange.placeholder.day')"
                :readonly="schema.readonly"
                @input="range.till.day = $event.target.value; onChange()"
            >
            <input
                class="form-control date-input"
                type="text"
                style="text-align:center;"
                size="2"
                :value="range.till.month"
                :disabled="disabled"
                :maxlength="2"
                :placeholder="schema?.placeholderMonth ?? t('vfg.dmyRange.placeholder.month')"
                :readonly="schema.readonly"
                @input="range.till.month = $event.target.value; onChange()"
            >
            <input
                class="form-control date-input"
                type="text"
                style="text-align:center;"
                size="4"
                :value="range.till.year"
                :disabled="disabled"
                :maxlength="4"
                :placeholder="schema?.placeholderYear ?? t('vfg.dmyRange.placeholder.year')"
                :readonly="schema.readonly"
                @input="range.till.year = $event.target.value; onChange()"
            >
        </div>
    </div>
</template>

<i18n>
{
    "en": {
        "vfg": {
            "dmyRange": {
                "label": {
                    "from": "From",
                    "till": "Till",
                    "exact": "From / Exact"
                },
                "placeholder": {
                    "day": "dd",
                    "month": "mm",
                    "year": "yyyy"
                }
            }
        }
    },
    "fr": {
        "vfg": {
            "dmyRange": {
                "label": {
                    "from": "de",
                    "till": "à",
                    "exact": "de / exactement"
                },
                "placeholder": {
                    "day": "jj",
                    "month": "mm",
                    "year": "aaaa"
                }
            }
        }
    }

}
</i18n>

<script>
import {abstractField} from 'vue3-form-generator-legacy'
import {useI18n} from "vue-i18n";
import {defineComponent} from "vue";


export default defineComponent({
    mixins: [abstractField],
    setup() {
        const { t } = useI18n() // use as global scope
        return { t }
    },
    data() {
        return {
            range: {
                from: {
                    day: null,
                    month: null,
                    year: null
                },
                till: {
                    day: null,
                    month: null,
                    year: null
                }
            },
            default: {
                from: {
                    day: null,
                    month: null,
                    year: null
                },
                till: {
                    day: null,
                    month: null,
                    year: null
                }
            }
        };
    },
    computed: {
        hasFrom() {
            return !!this.value.from.year || !!this.value.from.month || !!this.value.from.day;
        },
        hasTill() {
            return !!this.value.till.year || !!this.value.till.month || !!this.value.till.day;
        }
    },
    methods: {
        formatValueToField(value) {
            if (value === null || value === undefined) {
                return JSON.parse(JSON.stringify(this.default))
            }
            const newValue = {
                from: {
                    day: value?.from?.day,
                    month: value?.from?.month,
                    year: value?.from?.year
                },
                till: {
                    day: value?.till?.day,
                    month: value?.till?.month,
                    year: value?.till?.year
                }
            };
            this.range = newValue;
            return newValue;
        },
        formatValueToModel(value) {
            if (Object.values(value.from).every(v => v === null || v === '') && Object.values(value.till).every(v => v === null || v === '')) {
                return null;
            }
            return value;
        },
        onChange() {
            this.value = JSON.parse(JSON.stringify(this.range))
        }
    }
})
</script>

<style lang="scss">
.field-DMYRange {
    .input-group {

        & + .input-group {
            margin-top: 5px;
        }

        span {
        }

        input {
            width: auto;
        }
    }
}
</style>