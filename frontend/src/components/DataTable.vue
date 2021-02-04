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
                                {{field.name}}
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
                    <td v-for="field in fields" :key="field.name"
                        class="text-left p-1 px-3 border"
                    >
                        <slot name="`cell-${field.name}`">
                            {{item[field.name]}}
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
        
    },
    data() {
        return {
            fields: [
                {
                    name: 'id',
                    label: 'ID',
                    type: Number,
                    sortable: true
                },
                {
                    name: 'name',
                    label: 'Name',
                    type: String,
                    sortable: true
                },
                {
                    name: 'email',
                    label: 'email',
                    type: String,
                    sortable: true
                },
                {
                    name: 'birthday',
                    label: 'DOB',
                    type: Date,
                    sortable: false
                }
            ],
            data: [
                {
                    id: 1,
                    name: 'Elenor',
                    email: 'elenor@medplace.com',
                    birthday: '1987-01-01'
                },
                {
                    id: 2,
                    name: 'Chidi',
                    email: 'chidi@ethics.org',
                    birthday: '1984-02-01'
                },
                {
                    id: 3,
                    name: 'Tehani',
                    email: 'tehani@namedrop.com',
                    birthday: '1990-02-01'
                },
                {
                    id: 4,
                    name: 'Jason',
                    email: 'jason@stupidnickswinghut.com',
                    birthday: '1992-02-01'
                }
            ],
            sort: {
                field: 'id',
                desc: false
            },
            filter: null
        }
    },
    computed: {
        sortedFilteredData() {
            let data = JSON.parse(JSON.stringify(this.data))
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
            return this.data
        }
    },
    methods: {
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
        }
    }
}
</script>