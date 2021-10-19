<template>
    <static-alert variant="warning">
        <div class="flex items-center justify-between px-4">
            <strong>
                {{notification.message}}
            </strong>
            <button @click="$emit('removeClicked')">
                <close-icon></close-icon>
            </button>
        </div>
    </static-alert>
</template>
<script>
import CloseIcon from '@/components/icons/IconClose'

export default {
    name: 'NotificationItem',
    components: {
        CloseIcon
    },
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