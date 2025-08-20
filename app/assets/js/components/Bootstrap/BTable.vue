<template>
    <table class="table">
        <thead>
        <tr>
            <slot name="actionsPreRowHeader">
            </slot>
            <th v-for="field in fieldData" :key="field.key" :class="getFieldHeaderClass(field)" @click="changeSort(field)">
                <span class="heading-label">{{ field.label }}</span>
                <template v-if="field.sortable">
                    <span :class="getSortIcon(field.key)"></span>
                </template>
            </th>
            <slot name="actionsPostRowHeader">
            </slot>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(item, index) in items" :key="item.id">
            <slot name="actionsPreRow" :item="item" :index="index" :row="item">
            </slot>
            <td v-for="field in fields" :key="field.key" :class="field.tdClass">
                <slot :name="field.key" :item="item" :index="index" :row="item">
                    {{ fieldValue(item, field.key) }}
                </slot>
            </td>
            <slot name="actionsPostRow" :item="item" :index="index" :row="item">
            </slot>
        </tr>
        </tbody>
    </table>
</template>

<script>
export default {
    name: 'BTable',
    props: {
        items: {
            type: Array,
            required: true,
            default: () => []
        },
        fields: {
            type: Array,
            required: true,
            default: () => []
        },
        sortBy: {
            type: String,
            default: null
        },
        sortAscending: {
            type: Boolean,
            default: true
        },
        sortIcon: {
            type: Object,
            default: () => ({base: 'sort-icon fa-solid', up: 'fa-chevron-up', down: 'fa-chevron-down', is: 'fa-sort'})
        }
    },
    computed: {
        fieldData() {
            return this.fields.map(field => {
                return {
                    ...field,
                    label: field.label ?? field.key,
                    sortable: field.sortable ?? false,
                    thClass: field.thClass ?? null,
                    tdClass: field.tdClass ?? null,
                };
            });
        }
    },
    methods: {
        getFieldHeaderClass(field) {
            return [
                field.thClass,
                field.sortable ? 'sortable' : '',
                this.sortBy === field.key ? ( `${field.key}-sorted-`+ (this.sortAscending ? 'asc' : 'desc') ) : ''
                ].filter(i => i).join(' ');
        },
        getSortIcon(key) {
            if (this.sortBy !== key) return this.sortIcon.base + ' ' + this.sortIcon.is;
            return this.sortIcon.base + ' ' + (this.sortAscending ? this.sortIcon.up : this.sortIcon.down);
        },
        changeSort(field) {
            if (!field.sortable) return;
            if (this.sortBy === field.key) {
                this.$emit('update:sortAscending', !this.sortAscending);
                this.$emit('sort', {sortBy: field.key, sortAscending: !this.sortAscending});
            } else {
                this.$emit('update:sortBy', field.key);
                this.$emit('update:sortAscending', true);
                this.$emit('sort', {sortBy: field.key, sortAscending: true});
            }
        },
        fieldValue(item, key) {
            return item?.[key] ?? '';
        }
    },
};
</script>

<style scoped>
</style>