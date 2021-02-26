<template>
    <div>
        <data-table class="mb-4"></data-table>
        <ul>
            <li v-for="application in applications" :key="application.uuid">
                <router-link :to="`/applications/${application.uuid}`">{{application.workingName}} - {{application.uuid}}</router-link>
            </li>
        </ul>
    </div>
</template>
<script>
export default {
    props: {
        
    },
    data() {
        return {
            applications: [
                {
                    uuid: '1234-1234-1234-1234-1234',
                    workingName: 'Test Application 1'
                }
            ]         
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>