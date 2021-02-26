<style lang="postcss" module>
    table {
        @apply w-full;
    }
    tbody tr {
        @apply odd:bg-gray-100 hover:bg-blue-100
    }
    th.sorted, td.sorted  {
        @apply bg-blue-100 hover:bg-blue-100
    }
</style>

<template>
    <div>
        <table>
            <thead>
                <slot name="thead">
                <tr class="bg-gray-200">
                    <th v-for="field in fields.filter(f => !f.hideHeader)" :key="field.name"
                        class="text-left border border-gray-300 px-3"
                        :title="field.sortable ? `Click to sort` : ``"
                        :class="{
                            'cursor-pointer underline hover:bg-gray-300': field.sortable, 
                            'sorted': sortClone.field == field
                        }"
                        @click="field.sortable && updateSort(field)"
                        :colspan="(field.colspan ? field.colspan : 1)"
                    >
                        <div class="block py-1 flex justify-between place-items-center">
                            <div>
                                <slot :name="`header-${field.name}`" :item="{field}">
                                    {{field.label ? field.label : field.name}}
                                </slot>
                            </div>
                            <div>
                                <div v-if="field.sortable">
                                    <icon-cheveron-up icon-color="#ccc" 
                                        v-if="sortClone.field != field"
                                    ></icon-cheveron-up>
                                    <icon-cheveron-up icon-color="#333" 
                                        v-if="sortClone.field == field && !sortClone.desc"
                                    ></icon-cheveron-up>
                                    <icon-cheveron-down icon-color="#333" 
                                        v-if="sortClone.field == field && sortClone.desc"
                                    ></icon-cheveron-down>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                </slot>
            </thead>
            <tbody>
                <tr v-for="item in sortedFilteredData" :key="item.uuid" :class="rowClass" @click="handleRowClick(item)">
                    <td 
                        v-for="field in fields" 
                        :key="field.name"
                        class="text-left p-1 px-3 border"
                    >
                        <slot :name="`cell-${field.name}`" :item="item" :field="field" :value="resolveDisplayAttribute(item, field)">
                            {{resolveDisplayAttribute(item, field)}}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import { formatDate } from '../date_utils'
import IconCheveronDown from '../components/icons/IconCheveronDown'
import IconCheveronUp from '../components/icons/IconCheveronUp'

/**
 * 
 */
export default {
    name: 'DataTable',
    components: {
        IconCheveronDown,
        IconCheveronUp
    },
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
            type: [String,null],
            default: null
        },
        sort: {
            required: false,
            type: Object
        }
    },
    data() {
        return {
            sortClone: {
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
                    this.sortClone = {
                        field: this.fields[0],
                        desc: false
                    }
                    return;
                }

                this.sortClone = {...this.sort}
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
                const sortType = this.sortClone.field.type;

                if (this.sortClone.field.sorfFunction) {
                    return data.sort(this.sortClone.field.sortFunction)
                }
                
                switch (sortType) {
                    case Date:
                        return data.sort(this.dateSort)
                    default:
                        return data.sort(this.textAndNumberSort)
                }                
            } else {
                console.log('no data');
            }
            return []
        },
        sortField() {
            return this.sortClone.field;
        },
        sortFieldName() {
            if (this.sortClone.field.sortName) {
                return this.sortClone.field.sortName
            }
            return this.sortClone.field.Name;
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
            const oldField = this.sortClone.field;
            const newSort = {
                field: field,
                desc: !this.sortClone.desc
            }
            
            if (oldField != field) {
                newSort.desc = false
            }
            this.$emit('update:sort', newSort)
            this.$emit('sorted', newSort);
        },
        textAndNumberSort(a, b) {
            const coefficient = this.sortClone.desc ? -1 : 1;
            let aVal = this.resolveSortAttribute(a, this.sortField);
            let bVal = this.resolveSortAttribute(b, this.sortField);
            if (this.sortClone.field.type == String) {
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
            const coefficient = this.sortClone.desc ? -1 : 1;
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
            if (this.rowClickHandler) {
                this.rowClickHandler(item);
            }
        }
    }
}
</script>