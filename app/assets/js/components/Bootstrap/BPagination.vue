<template>
    <nav aria-label="Page navigation">
        <ul class="pagination m-0 user-select-none">
            <li v-if="showFirst" class="page-item" :class="{ disabled: currentPage === 1 }">
                <a class="page-link box-shadow-none" href="#" @click.prevent="changePage(1)">{{ labelFirst ?? t('pagination.first') }}</a>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">{{ labelPrevious ?? t('pagination.previous')}}</a>
            </li>
            <li class="page-item" v-for="page in visiblePages" :key="page" :class="{ active: currentPage === page }">
                <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">{{ labelNext ?? t('pagination.next')}}</a>
            </li>
            <li v-if="showLast && endPage < totalPages" class="page-item">
                <a class="page-link" href="#" @click.prevent="changePage(totalPages)">{{ labelLast ?? t('pagination.last')}}</a>
            </li>
        </ul>
    </nav>
</template>
<script>
import {useI18n} from "vue-i18n";

export default {
    name: 'BNavigation',
    setup() {
        const { t } = useI18n() // use as global scope
        return { t }
    },
    props: {
        totalRecords: {
            type: Number,
            required: true
        },
        page: {
            type: Number,
            required: true
        },
        perPage: {
            type: Number,
            required: true
        },
        maxVisiblePages: {
            type: Number,
            default: 5
        },
        showFirst: {
            type: Boolean,
            default: true
        },
        showLast: {
            type: Boolean,
            default: true
        },
        labelFirst: {
            type: [String, null],
            default: null
        },
        labelPrevious: {
            type: [String, null],
            default: null
        },
        labelNext: {
            type: [String, null],
            default: null
        },
        labelLast: {
            type: [String, null],
            default: null
        }
    },
    computed: {
        totalPages() {
            return Math.ceil(this.totalRecords / this.perPage);
        },
        currentPage: {
            get() {
                return this.page;
            },
            set(newPage) {
                this.$emit('update:page', newPage);
            }
        },
        visiblePages() {
            const chunk = this.maxVisiblePages;
            const currentChunk = Math.ceil(this.currentPage / chunk);
            const start = (currentChunk - 1) * chunk + 1;
            const end = Math.min(this.totalPages, currentChunk * chunk);

            const pages = [];
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },
        startPage() {
            return this.visiblePages[0];
        },
        endPage() {
            return this.visiblePages[this.visiblePages.length - 1];
        }
    },
    methods: {
        changePage(page) {
            if (page > 0 && page <= this.totalPages) {
                this.currentPage = page;
            }
        }
    }
}
</script>

<i18n>
{
    "en": {
        "pagination": {
            "first": "First",
            "previous": "Previous",
            "next": "Next",
            "last": "Last"
        }
    },
    "fr": {
        "pagination": {
            "first": "Premier",
            "previous": "Précédent",
            "next": "Suivant",
            "last": "Dernièr"
        }
    }
}
</i18n>

<style lang="scss">
.page-link:focus {
    box-shadow: none;
}
</style>