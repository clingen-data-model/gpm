<style lang="postcss" scope>
    table {
        @apply w-full;
    }
    tbody tr:not(.details){
        @apply bg-white odd:bg-gray-100 hover:bg-blue-50 hover:border-blue-300
    }
    tbody tr:hover + tr.details {
        @apply border-blue-300 bg-blue-50;
    }
    tr:first-child > th{
        @apply border-t-0
    }
    th:first-child,
    td:first-child {
        @apply border-l-0;
    }

    /* tr:last-child > td{
        @apply border-b-0
    } */
    th:last-child,
    td:last-child {
        @apply border-r-0
    }

    th.sorted, td.sorted  {
        @apply bg-blue-100 hover:bg-blue-100
    }
</style>

<template>
    <div class="shadow-inner border bg-gray-50">
        <table class="border-none">
            <thead>
                <slot name="thead">
                <tr class="bg-gray-200">
                    <th v-for="field in fields.filter(f => !f.hideHeader)" :key="field.name"
                        class="text-left border border-gray-300 px-3"
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
                                        v-if="realSort.field != field"
                                    ></icon-cheveron-up>
                                    <icon-cheveron-up icon-color="#333" 
                                        v-if="realSort.field == field && !realSort.desc"
                                    ></icon-cheveron-up>
                                    <icon-cheveron-down icon-color="#333" 
                                        v-if="realSort.field == field && realSort.desc"
                                    ></icon-cheveron-down>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                </slot>
            </thead>
            <tbody v-for="item in sortedFilteredData" :key="item.uuid">
                <tr :class="resolveRowClass(item)" @click="handleRowClick(item)">
                    <td 
                        v-for="field in fields" 
                        :key="field.name"
                        class="text-left p-1 px-3 border align-top"
                        :class="getCellClass(field)"
                    >
                        <slot :name="getSlotName(field)" :item="item" :field="field" :value="resolveDisplayAttribute(item, field)">
                            {{resolveDisplayAttribute(item, field)}}
                        </slot>
                    </td>
                </tr>
                <transition name="fade-slide-down">
                    <tr class="details" :class="rowClass(item)" v-if="detailRows && item.showDetails">
                        <td :colspan="fields.length" class="border-none p-0">
                            <slot name="detail" :item="item">
                                <object-dictionary :obj="item"></object-dictionary>
                            </slot>
                        </td>
                    </tr>
                </transition>
            </tbody>
        </table>
    </div>
</template>
<script>
import { formatDate } from '@/date_utils'
import IconCheveronDown from '@/components/icons/IconCheveronDown'
import IconCheveronUp from '@/components/icons/IconCheveronUp'

/**
 * 
 */
export default {
    name: 'DataTable',
    components: {
        IconCheveronDown,
        IconCheveronUp
    },
    emits: [
        'rowClick',
        'update:sort',
        'sort'
    ],
    props: {
        data: {
            required: false,
            type: Array
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
        }
    },
    data() {
        return {
            realSort: {
                field: {},
                desc: false
            }
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
                    return;
                }

                this.realSort = {
                    field: this.fields.find(i => i.name == this.sort.field),
                    desc: this.sort.desc
                }
            }
        }
    },
    computed: {
        filterFunction () {
            return this.filter ? this.filter : this.defaultFilter;
        },
        filteredData() {
            if (!this.filterTerm) {
                return this.data;
            }
            return this.data.filter(i => (i !== null))
                    .filter(item => this.filterFunction(item, this.filterTerm.trim()));
        },
        sortedFilteredData() {
            if (this.data) {
                let data = this.filteredData;
                const sortType = this.realSort.field.type;

                if (this.realSort.field.sorfFunction) {
                    return data.sort(this.realSort.field.sortFunction)
                }
                
                switch (sortType) {
                    case Date:
                        return data.sort(this.dateSort)
                    default:
                        return data.sort(this.textAndNumberSort)
                }                
            }
            return []
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
            
            if (oldField != field) {
                newSort.desc = false
            }
            this.$emit('update:sort', newSort)
            this.$emit('sorted', newSort);
        },
        textAndNumberSort(a, b) {
            const coefficient = this.realSort.desc ? -1 : 1;
            let aVal = this.resolveSortAttribute(a, this.sortField);
            let bVal = this.resolveSortAttribute(b, this.sortField);
            if (this.realSort.field.type == String && aVal !== null && bVal !== null) {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
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
            const aVal = new Date(Date.parse(a[this.sortField.name])).getTime();
            const bVal = new Date(Date.parse(b[this.sortField.name])).getTime();
            if (aVal == bVal) {
                if (a.id > b.id) {
                    return 1*coefficient;
                }
                return -1*coefficient;
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
                return field.name
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
                // console.log((this.rowClass)(item));
                return this.rowClass(item);
            }

            return this.rowClass;

        }
    },
}
</script>