<template>
    <div>
        <table>
            <thead>
                <slot name="thead">
                <tr class="bg-gray-200">
                    <th v-for="field in fields" :key="field.name"
                        class="text-left border border-gray-300"
                    >
                        <div class="block p-1 px-3 border-0"
                            :class="{'cursor-pointer underline hover:bg-gray-300': field.sortable}"
                            @click="field.sortable && updateSort(field.name)"
                        >
                            <slot :name="`header-${field.name}`" :item="{field}">
                                {{field.label ? field.label : field.name}}
                            </slot>
                            <span v-if="sort.field == field.name">
                                {{this.sort.desc ? '-' : '+'}}
                            </span>
                        </div>
                    </th>
                </tr>
                </slot>
            </thead>
            <tbody>
                <tr v-for="item in sortedFilteredData" :key="item.id" class="odd:bg-gray-100">
                    <td 
                        v-for="field in fields" 
                        :key="field.name"
                        class="text-left p-1 px-3 border"
                    >
                        <slot name="`cell-${field.name}`" :item="item" :field="field" :value="resolveAttribute(item, field.name)">
                            {{resolveAttribute(item, field.name)}}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
export default {
    name: 'DataTable',
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

    },
    data() {
        return {
            sort: {
                field: 'id',
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
                    .filter(this.filterFunction);
        },
        sortedFilteredData() {
            if (this.data) {
                let data = this.filteredData;//JSON.parse(JSON.stringify(this.data))
                const sortType = this.fields.find(element => element.name == this.sort.field).type;
                switch (sortType) {
                    case String:
                    case Number:
                        return data.sort(this.textAndNumbeSort)
                    case Date:
                        return data.sort(this.dateSort)
                    default:
                        console.error('unsupported column type');
                        break;
                }                
            } else {
                console.log('no data');
            }
            return []
        }
    },
    methods: {
        resolveAttribute (item, attr) {
            console.log(attr);
            if (attr && typeof attr != 'undefined' && attr.includes('.')) {
                const pathParts = attr.split('.');
                let val = JSON.parse(JSON.stringify(item));
                for(const i in pathParts) {
                    val = val[pathParts[i]]
                    if (!val) {
                        return null;
                    }
                }
                console.info('val', val);
                return val
            }
            return item[attr]
        },
        updateSort(field) {
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
            const aVal = a[this.sort.field];
            const bVal = b[this.sort.field];
            if (aVal == bVal) {
                return 0;
            }
            return coefficient*((aVal > bVal) ? 1 : -1);
        },
        dateSort(a, b) {
            const coefficient = this.sort.desc ? -1 : 1;
            const aVal = Date.parse(a[this.sort.field]);
            const bVal = Date.parse(b[this.sort.field]);
            if (aVal == bVal) {
                return 0;
            }
            return coefficient * ((aVal > bVal) ? 1 : -1);
        },
        defaultFilter(item) {
            console.info('item', item);
            // TODO: filter on values shown
            const lowerTerm = this.filterTerm.toLowerCase();
            const attributes = this.fields.map(field => field.name);

            for (let j in attributes) {
                const attr = attributes[j]
                console.log(attr)
                let itemAttr = this.resolveAttribute(item, attr);
                if (itemAttr === null || typeof itemAttr === 'undefined') {
                    continue;
                }
                console.log(itemAttr)
                itemAttr = itemAttr.toString().toLowerCase();
                if (itemAttr && itemAttr.includes(lowerTerm)) {
                    return true;
                }
            }
            
            return false;
        }
    }
}
</script>