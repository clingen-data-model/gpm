<template>
    <div class="px-2 py-2 bg-blue-200 rounded flex justify-between text-blue-800">
        <div>
            <strong>Next Action:</strong> {{nextAction.entry}}
        </div>
        <button class="btn blue btn-xs">Mark completed</button>
    </div>
</template>
<script>
export default {
    props: {
        nextAction: {
            type: Object,
            required: true
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