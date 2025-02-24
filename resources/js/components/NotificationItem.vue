<script>
import {api} from '@/http'

export default {
    name: 'NotificationItem',
    props: {
        notification: {
            required: true,
            type: Object
        }
    },
    emits: ['removed'],
    data() {
        return {
            
        }
    },
    computed: {
        variant () {
            return this.notification.data.type || 'info';
        }
    },
    methods: {
        async markRead () {
            if (this.notification.id) {
                await api.put(`/api/notifications/${this.notification.id}`)
                this.$emit('removed');
            }
        }
    }
}
</script>
<template>
    <static-alert :variant="variant">
        <div class="flex items-center justify-between px-4">
            <div class="">
                <markdown-block v-if="notification.data.markdown" :markdown="notification.data.message" />
                <div v-else class="font-bold">
                    {{ notification.data.message }}
                </div>
            </div>
            <button class="block" @click="markRead">
                <icon-close></icon-close>
            </button>
        </div>
    </static-alert>
</template>