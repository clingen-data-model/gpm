<template>
    <static-alert variant="warning">
        <div class="flex items-center justify-between px-4">
            <strong>
                {{notification.message}}
            </strong>
            <button @click="$emit('removeClicked')">
                <icon-close></icon-close>
            </button>
        </div>
    </static-alert>
</template>
<script>


export default {
    name: 'NotificationItem',
    emits: ['removeClicked'],
    props: {
        notification: {
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