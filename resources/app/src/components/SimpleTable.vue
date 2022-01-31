<template>
    <table class="text-sm">
        <thead>
            <tr>
                <th v-for="heading in headings" :key="heading">{{titleCase(heading)}}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="item in data" :key="item[keyBy]">
                <td v-for="heading in headings" :key="`${item[keyBy]}-${heading}`">
                    {{item[heading]}}
                </td>
            </tr>
        </tbody>
    </table>
</template>
<script>
export default {
    name: 'SimpleTable',
    props: {
        data: {
            required: true,
            type: Array
        },
        keyBy: {
            required: true,
            type: String
        },
        hideColumns: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        headings () {
            if (this.data[0]) {
                return Object.keys(this.data[0]).filter(heading => !this.hideColumns.includes(heading));
            }
            return [];
        }
    },
    methods: {

    }
}
</script>