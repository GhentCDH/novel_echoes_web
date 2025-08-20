<template>
    <div class="form-check">
        <input :id="getFieldID(schema)" type="checkbox" v-model="value" :autocomplete="schema.autocomplete" :disabled="disabled" :name="schema.inputName" :class="schema.fieldClasses" :required="schema.required"
            class="form-check-input"
            >
        <label class="form-check-label checkbox-label" :for="getFieldID(schema)">
            {{ schema.label }}
        </label>
    </div>
</template>

<script>
import { abstractField } from 'vue3-form-generator-legacy'

export default {
    mixins: [ abstractField ]
};
</script>