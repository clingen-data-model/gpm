<template>
    <div>
        <h4 class="text-md font-bold">Conflict of Interest</h4>
        <dictionary-row label="COI URL">
            {{application.survey_monkey_url}}
        </dictionary-row>
        <div class="px-3 py-2 rounded border border-gray-300 text-gray-500 bg-gray-200">
            No Conflict of interest surveys completed
        </div>
    </div>

</template>
<script>
export default {
    props: {
        application: {
            required: true,
            type: Object
        }
    },
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>