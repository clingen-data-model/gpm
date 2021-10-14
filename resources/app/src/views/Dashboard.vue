<template>
    <div>
        <h1>Dashboard</h1>
        <transition-group tag="div" name="slide-fade-down">
            <notification-item 
                v-for="notification in notifications" :key="notification.id"
                :notification="notification"
                class="mt-2"
                @removeClicked="removeNotification(notification)"
            ></notification-item>
        </transition-group>
        <section class="mt-8 mb-4">
            <h2>Your Groups</h2>
            GROUPS TABLE HERE
        </section>
        <collapsible title="User Data">
            <pre>{{user}}</pre>
        </collapsible>
    </div>
</template>
<script>
import {useStore} from 'vuex'
import {ref, computed, watch, onMounted} from 'vue'
import NotificationItem from '@/components/NotificationItem'

export default {
    name: 'Dashboard',
    components: {
        NotificationItem
    },
    props: {
        
    },
    setup (props, context) {
        const store = useStore();
        const user = computed(() => {
            return store.getters['currentUser']
        });

        const loadingNotifications = ref(false);
        const notifications = ref([]);
        const getNotifications = async () => {
            console.log('getNotifiations...');
            loadingNotifications.value = true;
            await setTimeout(() => {
                notifications.value.push({id: 1, message: 'This is a message'});
                notifications.value.push({id: 2, message: 'This is another message'});
            }, 1000);
            loadingNotifications.value = false;
        }

        const removeNotification = (notification) => {
            console.log('removing notification', notification);
            const index = notifications.value.findIndex((item) => item.id == notification.id);
            if (index > -1) {
                notifications.value.splice(index, 1);
            }

        }

        onMounted(() => {
            getNotifications();
        })

        return {
            user,
            loadingNotifications,
            notifications,
            getNotifications,
            removeNotification
        }
        
    }
    // data() {
    //     return {
            
    //     }
    // },
    // computed: {
    //     ...mapGetters({
    //         user: 'currentUser'
    //     })
    // },
    // methods: {

    // }
}
</script>