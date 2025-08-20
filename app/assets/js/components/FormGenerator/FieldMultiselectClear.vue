<template>
    <multiselect
        :id="selectOptions.id"
        :options="options"
        :modelValue="value"
        :multiple="selectOptions.multiple"
        :track-by="selectOptions.trackBy || null"
        :label="selectOptions.label || null"
        :searchable="selectOptions.searchable"
        :clear-on-select="selectOptions.clearOnSelect"
        :hide-selected="selectOptions.hideSelected"
        :placeholder="schema.placeholder"
        :allow-empty="selectOptions.allowEmpty"
        :reset-after="selectOptions.resetAfter"
        :close-on-select="selectOptions.closeOnSelect"
        :custom-label="customLabel"
        :taggable="selectOptions.taggable"
        :tag-placeholder="selectOptions.tagPlaceholder"
        :max="schema.max || null"
        :options-limit="selectOptions.optionsLimit"
        :group-values="selectOptions.groupValues"
        :group-label="selectOptions.groupLabel"
        :block-keys="selectOptions.blockKeys"
        :internal-search="selectOptions.internalSearch"
        :select-label="selectOptions.selectLabel"
        :selected-label="selectOptions.selectedLabel"
        :deselect-label="selectOptions.deselectLabel"
        :show-labels="selectOptions.showLabels"
        :limit="selectOptions.limit"
        :limit-text="selectOptions.limitText"
        :loading="selectOptions.loading"
        :disabled="disabled || null"
        :max-height="selectOptions.maxHeight"
        :show-pointer="selectOptions.showPointer"
        :option-height="selectOptions.optionHeight"
        @update:modelValue="updateSelected"
        @select="onSelect"
        @remove="onRemove"
        @search-change="onSearchChange"
        @tag="addTag"
        @open="onOpen"
        @close="onClose"
    >
        <template #noResult>
            <span>{{ selectOptions.noResult }}</span>
        </template>
        <template #maxElements>
            <span>{{ selectOptions.maxElements }}</span>
        </template>
        <template #clear>
            <div class="multiselect__clear" v-if="!disabled && !empty" @mousedown.prevent.stop="clearAll()">
            </div>
        </template>
        <template #caret="{ toggle }">
            <div class="multiselect__select" v-if="!disabled && empty" @mousedown.prevent.stop="toggle()">
            </div>
        </template>
        <template #option="{ option }">
            <div :class="optionClass(option)">
                <span class="option__title">{{ option.name }}</span>
                <span class="option__count">{{ option.count }}</span>
            </div>
        </template>
    </multiselect>
</template>

<script>
import {abstractField} from 'vue3-form-generator-legacy';

export default {
    mixins: [abstractField],
    computed: {
        selectOptions() {
            return this.schema.selectOptions || {};
        },
        options() {
            let values = this.schema.values;
            if (typeof (values) == 'function') {
                return values.apply(this, [this.model, this.schema]);
            } else {
                return values
            }
        },
        customLabel() {
            if (typeof this.schema.selectOptions !== 'undefined' && typeof this.schema.selectOptions.customLabel !== 'undefined' && typeof this.schema.selectOptions.customLabel === 'function') {
                return this.schema.selectOptions.customLabel;
            } else {
                //this will let the multiselect library use the default behavior if customLabel is not specified
                return undefined;
            }
        },
        valueKeys() {
            return this.getKeys(Array.isArray(this.value) ? this.value : [])
        },
        noneKey() {
            return this.schema?.noneKey ?? -1
        },
        anyKey() {
            return this.schema?.anyKey ?? -2
        },
        globalKeys() {
            return [this.noneKey, this.anyKey]
        },
        empty() {
            return this?.value === undefined || this.value === null || (Array.isArray(this.value) && this.value.length === 0);
        }
    },
    created() {
        // Check if the component is loaded globally
        // if (!this.$root.$options.components['multiselect']) {
        //     console.error("'vue-multiselect' is missing. Please download from https://github.com/monterail/vue-multiselect and register the component globally!");
        // }
    },
    methods: {
        getKeys(values) {
            return values.map(item => item.id)
        },
        getValueKeys(keys) {
            return keys.filter(key => !this.globalKeys.includes(key))
        },
        getGlobalKeys(keys) {
            return this.globalKeys.filter(value => keys.includes(value))
        },
        optionClass(option) {
            return {
                'multiselect__option--none': option.id === this.noneKey,
                'multiselect__option--any': option.id === this.anyKey
            }
        },
        updateSelected(value/*, id*/) {
            if (this.selectOptions?.multiple) {
                // get new values
                let new_values = value.filter(item => !this.valueKeys.includes(item.id))
                // check if values contain global keys
                let selected_global_keys = this.getGlobalKeys(this.getKeys(new_values));
                // if global, only global key allowed, if not, only value keys allowed
                if (selected_global_keys.length) {
                    this.value = value.filter(item => selected_global_keys[0] === item.id)
                } else {
                    this.value = value.filter(item => !this.globalKeys.includes(item.id))
                }
            } else {
                this.value = value
            }
        },
        addTag(newTag, id) {
            let onNewTag = this.selectOptions.onNewTag;
            if (typeof (onNewTag) == 'function') {
                onNewTag(newTag, id, this.options, this.value);
            }
        },
        onSearchChange(searchQuery, id) {
            let onSearch = this.selectOptions.onSearch;
            if (typeof (onSearch) == 'function') {
                onSearch(searchQuery, id, this.options);
            }
        },
        onSelect(/*selectedOption, id*/) {
            // console.log("onSelect", selectedOption, id);
        },
        onRemove(/*removedOption, id*/) {
            // console.log("onRemove", removedOption, id);
        },
        onOpen(/*id*/) {
            // console.log("onOpen", id);
        },
        onClose(/*value, id*/) {
            // console.log("onClose", value, id);
        },
        clearAll() {
            if (this.selectOptions.multiple) {
                this.value = [];
            } else {
                this.value = null;
            }
        },
        onEscStopPrevent(/*event*/) {
            // console.log(event);
        },
    },
};
</script>
