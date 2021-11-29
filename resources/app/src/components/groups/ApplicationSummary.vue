<template>
    <div class="p-4 my-4 border rounded-xl" v-if="group.isEp()">
        <router-link v-if="group.uuid" :to="{name: 'GroupApplication', params: {uuid: group.uuid}}">
            <button class="btn blue btn-lg">Go to application</button>
        </router-link>
    </div>
</template>
<script>
export default {
    name: 'ApplicationSummary',
    props: {
        group: {
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