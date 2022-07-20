<style scoped>
    table {
        @apply w-full;
    }
    tbody{
        @apply bg-white odd:bg-gray-100 border-0 hover:border-blue-300 hover:bg-blue-100
    }
    tr > th {
        @apply text-left border border-gray-300 px-3
    }
    tr:not(.details) > td {
        @apply text-left p-1 px-3 border align-top;
    }
    tr.details > td {
        @apply border-none p-0;
    }
    th.sorted, td.sorted  {
        @apply bg-blue-100 hover:bg-blue-100
    }
</style>

<template>
    <div>
<!-- <pre>{ currentPage: {{currentPage}} }</pre> -->
        <header class="flex justify-between mb-2 items-center">
            <slot name="header"></slot>
            <pagination-links 
                :current-page="currentPage" 
                :total-items="totalItems"
                :page-size="pageSize"
                @update:currentPage="updateCurrentPage"
                v-if="shouldPaginate"
            />
        </header>

        <div class="shadow-inner bg-gray-50">
            <table class="border-none">
                <thead>
                    <slot name="thead">
                    <tr class="bg-gray-200">
                        <th v-for="field in fields.filter(f => !f.hideHeader)" :key="field.name"
                            :title="field.sortable ? `Click to sort` : ``"
                            :class="getHeaderClass(field)"
                            @click="field.sortable && updateSort(field)"
                            :colspan="(field.colspan ? field.colspan : 1)"
                        >
                            <div class="py-1 flex justify-between place-items-center">
                                <div>
                                    <slot :name="`header-${field.name}`" :item="{field}">
                                        {{this.getFieldLabel(field)}}
                                    </slot>
                                </div>
                                <div>
                                    <div v-if="field.sortable">
                                        <icon-cheveron-up icon-color="#ccc" 
                                            v-if="realSort.field.name != field.name"
                                        ></icon-cheveron-up>
                                        <icon-cheveron-up icon-color="#333" 
                                            v-if="realSort.field.name == field.name && !realSort.desc"
                                        ></icon-cheveron-up>
                                        <icon-cheveron-down icon-color="#333" 
                                            v-if="realSort.field.name == field.name && realSort.desc"
                                        ></icon-cheveron-down>
                                    </div>
                                </div>
                            </div>
                        </th>
                    </tr>
                    </slot>
                </thead>
                <tbody v-for="item in resolvedItems" :key="item.uuid">
                    <tr :class="resolveRowClass(item)" @click="handleRowClick(item)">
                        <td 
                            v-for="field in fields" 
                            :key="field.name"
                            :class="getCellClass(field)"
                        >
                            <slot :name="getSlotName(field)" :item="item" :field="field" :value="resolveDisplayAttribute(item, field)">
                                {{resolveDisplayAttribute(item, field)}}
                            </slot>
                        </td>
                    </tr>
                    <transition name="fade-slide-down">
                        <tr class="details" :class="resolveRowClass(item)" v-if="detailRows && item.showDetails">
                            <td :colspan="fields.length">
                                <slot name="detail" :item="item">
                                    <object-dictionary :obj="item"></object-dictionary>
                                </slot>
                            </td>
                        </tr>
                    </transition>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script>
import { formatDate } from '@/date_utils'
import {titleCase} from '@/utils'

/**
 * 
 */
export default {
    name: 'DataTable',
    emits: [
        'rowClick',
        'update:sort',
        'sort',
        'sorted'
    ],
    props: {
        data: {
            required: false,
            // type: [Array|Function]
        },
        /**
         * An array of field objects with the following structure:
         * {
         *      name: <String>attribute_name // defines the field in the item object that will be used to get value
         *      type: <Type> // Number, String, Object, Date, etc. Determines sort default behavior and default display,
         *      sortable: <Boolean> // True: column can be sorted; False: can't be sorted. Default: false
         *      sortName: <String>item_attribute_name // Item's attribute on which to sort. Default: null - uses 'name'
         *      resolveValue: <Function> // custom function to resolve item's value for this column. 
         *                                  Effects sort if no 'sortName' provided.
         *      sortFunction: <Function>(a, b) => -1|0|1 // Used to sort column instead of default stort algorithm.
         * }
         */
        fields: {
            required: true,
            type: Array,
        },
        paginated: {
            type: Boolean,
            default: false,
        },
        filterTerm: {
            required: false,
            type: String
        },
        filter: {
            required: false,
            type: Function
        },
        rowClickHandler: {
            required: false,
            type: Function
        },
        rowClass: {
            required: false,
            type: [String, Function, null],
            default: null
        },
        sort: {
            required: false,
            type: Object,
            default: () => {
                return {
                    field: 'id',
                    desc: false
                }
            }
        },
        detailRows: {
            type: Boolean,
            required: false,
            default: false
        },
        pageSize: {
            type: [Number, null],
            default: 20
        },
    },
    data() {
        return {
            realSort: {
                field: {},
                desc: false
            },
            resolvedItems: [],
            currentPage: 1,
            totalItems: 0
        }
    },
    watch: {
        sort: {
            immediate: true,
            handler: function() {
                if (!this.sort) {
                    this.realSort = {
                        field: this.fields[0],
                        desc: false
                    }
                    this.getItems()
                    return;
                }

                this.realSort = {
                    field: this.fields.find(i => i.name == this.sort.field),
                    desc: this.sort.desc
                }
                this.resetCurrentPage();
                this.getItems()
            }
        },
        filterTerm() {
            this.getItems();
        },
        data: {
            immediate: true,
            deep: true,
            handler: function () {
                this.resetCurrentPage();
                this.getItems()
            }
        },
        currentPage: {
            immedate: true,
            handler () {
                this.getItems();
            }
        }
    },
    computed: {
        shouldPaginate () {
            return this.paginated;
        },
        dataIsFunction () {
            return typeof this.data == 'function'
        },
        filterFunction () {
            return this.filter ? this.filter : this.defaultFilter;
        },
        sortField() {
            return this.realSort.field;
        },
        sortFieldName() {
            if (this.realSort.field.sortName) {
                return this.realSort.field.sortName
            }
            return this.realSort.field.Name;
        }
    },
    methods: {
        async getItems () {
            if (this.dataIsFunction) {
                this.resolvedItems = await this.data(this.currentPage, this.pageSize, this.realSort, this.setTotalItems);
                return;
            }

            const allItems = this.sortData(this.filterData([...this.data]));
            this.resolvedItems = this.paginated ? this.paginate(allItems) : allItems;
            this.setTotalItems(allItems.length);
        },
        setTotalItems(totalItemCount) {
            this.totalItems = totalItemCount;
        },
        setCurrentPage(currentPage) {
            this.currentPage = currentPage;
        }, 
        paginate (data) {
            if (this.dataIsFunction) {
                this.getItems();
                return;
            }
            const startIndex = (this.currentPage-1) * this.pageSize;
            const endIndex = startIndex + this.pageSize - 1;
            return data.slice(startIndex, endIndex);
        },
        sortData (data) {
            const sortType = this.realSort.field.type;

            if (this.dataIsFunction) {
                this.getItems();
                return;
            }

            if (this.realSort.field.sortFunction) {
                const sorted = data.sort(this.realSort.field.sortFunction)
                if (this.realSort.desc) {
                    return sorted.reverse();
                }

                return sorted;
            }
            
            if (sortType == Date) {
                return data.sort(this.dateSort)
            }

            return data.sort(this.textAndNumberSort)
        },
        filterData (data) {
            if (this.dataIsFunction) {
                this.getItems();
                return;
            }

            if (!this.filterTerm) {
                return data;
            }

            return data.filter(i => (i !== null))
                    .filter(item => this.filterFunction(item, this.filterTerm.trim()));
        }, 
        findFieldByName(name) {
            return this.fields.find(i => i.name == name)
        },
        getAttributeValue (item, attr) {
            if (attr && typeof attr != 'undefined' && attr.includes('.')) {
                const pathParts = attr.split('.');
                let val = item;
                for(const i in pathParts) {
                    val = val[pathParts[i]]
                    if (!val) {
                        return null;
                    }
                }
                return val
            }
            return item[attr];
        },
        resolveDisplayAttribute (item, field) {
            if (field.resolveValue) {
                return field.resolveValue(item);
            }

            const value = this.getAttributeValue(item, field.name);

            if (field.type == Date) {
                return (value) ? formatDate(value) : null;
            }
            return value;
        },
        
        resolveSortAttribute (item, field){
            if (field.resolveSort) {
                return field.resolveSort(item);
            }

            if (field.sortName) {
                return this.getAttributeValue(item, field.sortName);
            }

            return this.resolveDisplayAttribute(item, field)
        },
        
        updateSort(field) {
            const oldField = this.realSort.field;
            const newSort = {
                field: field.name,
                desc: !this.realSort.desc
            }
            
            if (oldField.name != field.name) {
                newSort.desc = false
            }

            this.$emit('update:sort', newSort)
            this.$emit('sorted', newSort);

            this.$nextTick(() => {
                this.getItems();
            })
        },
        textAndNumberSort(a, b) {
            const coefficient = this.realSort.desc ? -1 : 1;
            let aVal = this.resolveSortAttribute(a, this.sortField);
            let bVal = this.resolveSortAttribute(b, this.sortField);

            // Handle cases where operand is null
            if (aVal && !bVal) {
                return -1 * coefficient;
            }
            if (bVal && !aVal) {
                return 1 * coefficient;
            }

            // Normalize strings
            if (this.realSort.field.type == String || (typeof aVal == 'string' && typeof bVal == 'string')) {
                aVal = aVal ? aVal.toLowerCase() : aVal;
                bVal = bVal ? bVal.toLowerCase() : bVal;
            }

            if (aVal == bVal) {
                if (a.id > b.id) {
                    return 1*coefficient;
                }
                return -1*coefficient;
            }
            return coefficient*((aVal > bVal) ? 1 : -1);
        },
        dateSort(a, b) {
            const coefficient = this.realSort.desc ? -1 : 1;

            let aVal = Date.parse(this.resolveSortAttribute(a, this.sortField));
            if (isNaN(parseFloat(aVal))) {
                aVal = 0;
            }

            let bVal = Date.parse(this.resolveSortAttribute(b, this.sortField));
            if (isNaN(parseFloat(bVal))) {
                bVal = 0;
            }
            
            if (aVal === bVal) {
                return 0;
            }

            return coefficient * ((aVal > bVal) ? 1 : -1);
        },
        defaultFilter(item, term) {
            const lowerTerm = term.toLowerCase();

            for (let f in this.fields) {
                let itemAttr = this.resolveDisplayAttribute(item, this.fields[f]);
                if (itemAttr === null || typeof itemAttr === 'undefined') {
                    continue;
                }
                itemAttr = itemAttr.toString().toLowerCase();
                if (itemAttr && itemAttr.includes(lowerTerm)) {
                    return true;
                }
            }
            
            return false;
        },
        handleRowClick(item) {
            this.$emit('rowClick', item);
            if (this.rowClickHandler) {
                this.rowClickHandler(item);
            }
        },
        getFieldLabel(field) {
            if (typeof field.label == 'undefined' || field.label === null ) {
                return titleCase(field.name)
            }
            return field.label
        },
        getHeaderClass(field) {
            const classes = field.class ? [...field.class] : [];
            if (field.sortable) {
                classes.push('cursor-pointer underline hover:bg-gray-300');
            }
            if (field.colspan == 1) {
                if (this.realSort.field == field) {
                    classes.push(field)
                }
            }
            if (field.headerClass) {
                classes.push(...field.headerClass)
            }

            return classes.join(' ');
        },

        getCellClass(field) {
            return field.class;
        },

        getSlotName(field) {
            return `cell-${field.name.replace(' ', '_').replace('.', '_')}`
        },

        resolveRowClass(item) {
            if (typeof this.rowClass == 'function') {
                return this.rowClass(item);
            }

            return this.rowClass;
        },
        resetCurrentPage () {
            this.currentPage = 1;
        },
        updateCurrentPage (currentPage) {
            this.currentPage = currentPage;
        } 
    }
}
</script>