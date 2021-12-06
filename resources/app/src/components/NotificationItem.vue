<template>
    <static-alert variant="warning">
        <div class="flex items-center justify-between px-4">
            <strong>
                {{notification.data.message}}
            </strong>
            <button @click="markRead">
                <icon-close></icon-close>
            </button>
        </div>
    </static-alert>
</template>
<script>
import {api} from '@/http'

export default {
    name: 'NotificationItem',
    emits: ['removed'],
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
        async markRead () {
            await api.put(`/api/notifications/${this.notification.id}`)
            this.$emit('removed');
        }
    }
}
</script>