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
                        :class="{'cursor-pointer underline hover:bg-gray-300': field.sortable, 'sorted': sort.field == field}"
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
                                        v-if="sort.field != field"
                                    ></icon-cheveron-up>
                                    <icon-cheveron-up icon-color="#333" 
                                        v-if="sort.field == field && !sort.desc"
                                    ></icon-cheveron-up>
                                    <icon-cheveron-down icon-color="#333" 
                                        v-if="sort.field == field && sort.desc"
                                    ></icon-cheveron-down>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                </slot>
            </thead>
            <tbody>
                <tr v-for="item in sortedFilteredData" :key="item.id" :class="rowClass" @click="handleRowClick(item)">
                    <td 
                        v-for="field in fields" 
                        :key="field.name"
                        class="text-left p-1 px-3 border"
                    >
                        <slot name="`cell-${field.name}`" :item="item" :field="field" :value="resolveAttribute(item, field)">
                            {{resolveAttribute(item, field)}}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import IconCheveronDown from '../components/icons/IconCheveronDown'
import IconCheveronUp from '../components/icons/IconCheveronUp'

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
        fields: {
            required: true,
            type: Array
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
        }
    },
    data() {
        return {
            sort: {
                field: this.fields[0],
                desc: false
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
                let data = this.filteredData;//JSON.parse(JSON.stringify(this.data))
                const sortType = this.sort.field.type;
                switch (sortType) {
                    case String:
                    case Number:
                        return data.sort(this.textAndNumbeSort)
                    case Date:
                        return data.sort(this.dateSort)
                    default:
                        console.error('unsupported column type: '+sortType);
                        break;
                }                
            } else {
                console.log('no data');
            }
            return []
        }
    },
    methods: {
        resolveAttribute (item, field) {
            if (field.resolveValue) {
                return field.resolveValue(item);
            }

            const attr = field.name;
            if (attr && typeof attr != 'undefined' && attr.includes('.')) {
                const pathParts = attr.split('.');
                let val = JSON.parse(JSON.stringify(item));
                for(const i in pathParts) {
                    val = val[pathParts[i]]
                    if (!val) {
                        return null;
                    }
                }
                return val
            }
            return item[attr]
        },
        updateSort(field) {
            console.log(field);
            const oldField = this.sort.field;
            this.sort.field = field
            this.sort.desc = !this.sort.desc
            
            if (oldField != field) {
                this.sort.desc = false
            }
            this.$emit('sort', this.sort);
        },
        textAndNumbeSort(a, b) {
            const coefficient = this.sort.desc ? -1 : 1;
            const aVal = this.resolveAttribute(a, this.sort.field);
            const bVal = this.resolveAttribute(b, this.sort.field);
            if (aVal == bVal) {
                return 0;
            }
            return coefficient*((aVal > bVal) ? 1 : -1);
        },
        dateSort(a, b) {
            const coefficient = this.sort.desc ? -1 : 1;
            const aVal = new Date(a[this.sort.field]);
            const bVal = new Date(b[this.sort.field]);
            if (aVal == bVal) {
                console.log('dates equal')
                return 0;
            }
            return coefficient * ((aVal > bVal) ? 1 : -1);
        },
        defaultFilter(item, term) {
            const lowerTerm = term.toLowerCase();

            for (let f in this.fields) {
                let itemAttr = this.resolveAttribute(item, this.fields[f]);
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