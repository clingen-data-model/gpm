<template>
    <static-alert :variant="variant">
        <div class="flex items-center justify-between px-4">
            <div class="">
                <markdown-block v-if="notification.data.markdown" :markdown="notification.data.message" />
                <div class="font-bold" v-else>
                    {{notification.data.message}}
                </div>
            </div>
            <button @click="markRead" class="block">
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
        variant () {
            return this.notification.data.type || 'info';
        }
    },
    methods: {
        async markRead () {
            await api.put(`/api/notifications/${this.notification.id}`)
            this.$emit('removed');
        }
    }
}
</script>