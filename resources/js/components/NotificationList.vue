<script setup>
    import NotificationItem from '@/components/NotificationItem.vue'
    import {api} from '@/http'
    import {onMounted, ref} from 'vue';

    const props = defineProps({
        user: {
            type: Object,
            required: true
        }
    })

    // NOTIFICATIONS
    const loadingNotifications = ref(false);
    const notifications = ref([]);
    const getNotifications = async () => {
        loadingNotifications.value = true;
        if (props.user.person && props.user.person.uuid) {
            notifications.value = await api.get(`/api/people/${props.user.person.uuid}/notifications/unread`)
                            .then(response => response.data)
        }
        loadingNotifications.value = false;
    }
    const removeNotification = (notification) => {
        const index = notifications.value.findIndex((item) => item.id === notification.id);
        if (index > -1) {
            notifications.value.splice(index, 1);
        }
    }

    onMounted(() => {
        getNotifications();
    });
</script>
<template>
  <transition-group tag="div" name="slide-fade-down">
    <NotificationItem
      v-for="notification in notifications" :key="notification.id"
      :notification="notification"
      class="mb-2"
      @removed="removeNotification(notification)"
    />
  </transition-group>
</template>