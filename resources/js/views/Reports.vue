<script setup>
    import {api} from '@/http'
    import {ref, onMounted} from 'vue'

    const summaryData = ref([]);
    const reports = ref([
        {
            url: '/api/report/basic-summary',
            name: 'Summary'
        },
        {
            url: '/api/report/vcep-application-summary',
            name: 'VCEP Application'
        },
        {
            url: '/api/report/gcep-genes',
            name: 'GCEP Genes'
        },
        {
            url: '/api/report/vcep-genes',
            name: 'VCEP Genes'
        },
        {
            url: '/api/report/countries',
            name: 'Countries'
        },
        {
            url: '/api/report/institutions',
            name: 'Institutions'
        },
        {
            url: '/api/report/people',
            name: 'All people'
        },
        {
            url: '/api/report/people-in-multiple-eps',
            name: 'People in >1 EP'
        }
    ])

    const getSummaryReport = async () => {
        api.get('/api/report/basic-summary')
            .then(response => {
                summaryData.value = response.data;
            })
    }

    onMounted(() => {
        getSummaryReport()
    })
</script>

<template>
    <div>
        <h1>Reports</h1>
        <div class="flex space-x-4">
            <ul v-remaining-height class="item-list space-y-2 bg-gray-100 p-4 w-1/4">
                <li v-for="rpt in reports" :key="rpt.url">
                    <download-link :url="rpt.url" :title="`Download ${rpt.name} Report`">{{ rpt.name }}</download-link>
                </li>
            </ul>
            <div class="border-left pl-4 flex-grow">
                <h2 class="mb-2">Summary Report</h2>
                <table>
                    <tr v-for="row in summaryData" :key="row[0]">
                        <th>
                            {{ row[0] }}
                        </th>
                        <td class="text-right">
                            {{ row[1] }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</template>

<style lang="postcss" scoped>
    th, td {
        border: none;
        border-bottom: solid 1px #ddd;
        padding-bottom: .5rem;
        padding-top: .5rem;
    }
    .item-list li {
        @apply border-b pb-2;
    }
    .item-list li:last-child {
        @apply border-none;
    }
</style>
