import { createApp } from 'vue'
import TextSearchApp from '@/components/Text/TextSearchApp.vue'

import { i18n } from "../locales/i18n";
import { useI18n } from "vue-i18n";

// vue3-form-generator-legacy custom fields
import VueFormGenerator from 'vue3-form-generator-legacy'
import VueMultiselect from "vue-multiselect";
import FieldMultiselectClear from "@/components/FormGenerator/FieldMultiselectClear.vue";
import FieldCheckbox from "@/components/FormGenerator/FieldCheckbox.vue";
import FieldDMYRange from '@/components/FormGenerator/FieldDMYRange.vue';

// create app
const app = createApp({
    setup() {
        const { t } = useI18n({})
        return { t }
    }
})
app.use(i18n)

// register custom fields
app.use(VueFormGenerator)
app.component('multiselect', VueMultiselect)
app.component('fieldMultiselectClear', FieldMultiselectClear)
app.component('fieldCheckboxBS5', FieldCheckbox);
app.component('fieldDMYRange', FieldDMYRange);

// main app component
app.component('text-search-app', TextSearchApp)
app.mount('#app')
