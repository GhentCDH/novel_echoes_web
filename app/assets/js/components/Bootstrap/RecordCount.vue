<template>
    <div v-if="totalRecords">{{ t(message ?? 'recordCount.message', messages) }}</div>
</template>

<script>
import {useI18n} from "vue-i18n";

export default {
    name: 'RecordCount',
    setup() {
        const {t} = useI18n() // use as global scope
        return {t}
    },
    props: {
        totalRecords: {
            type: Number,
            required: true,
        },
        perPage: {
            type: Number,
            required: true,
        },
        page: {
            type: Number,
            required: true,
        },
        message: {
            type: [String, null],
            default: null,
        }
    },
    computed: {
        messages() {
            const from = ((this.page - 1) * this.perPage) + 1;
            const to = this.page === this.totalPages ? this.totalRecords : from + this.perPage - 1;

            return {
                count: this.totalRecords,
                to, from
            };
        },
        totalPages() {
            return Math.ceil(this.totalRecords / this.perPage);
        }
    }
};
</script>

<i18n>
{
    "en": {
        "recordCount.message": "Showing records {from} to {to} out of {count}"
    },
    "fr": {
        "recordCount.message": "{from} - {to} sur {count}"
    }
}
</i18n>