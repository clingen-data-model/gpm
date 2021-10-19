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
        <tabs-container class="mt-8">
            <tab-item label="Your Groups">
                <div class="border border-gray-300 text-gray-600 bg-gray-200 rounded p-2" v-if="!groups.length">You are not assigned to any groups.</div>
                <data-table
                    v-else
                    :data="groups"
                    :fields="groupFields"
                    v-model:sort="groupSort"
                    @rowClick="navigateToGroup"
                    row-class="cursor-pointer"
                >
                    <template v-slot:cell-status_name="{value}">
                        <badge color="green">{{value}}</badge>
                    </template>
                </data-table>
            </tab-item>
            <tab-item label="COIs">
                <data-table 
                    :fields="coiFields" 
                    :data="cois" 
                    v-model:sort="coiSort"
                >
                    <template v-slot:cell-actions="{item}">
                        <button class="btn btn-xs" v-if="item.completed_at">View response</button>
                        <button class="btn btn-xs blue" v-else>Complete COI</button>
                    </template>
                </data-table>
            </tab-item>
        </tabs-container>
        <collapsible title="User Data">
            <pre>{{user}}</pre>
        </collapsible>
    </div>
</template>
<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
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
        const router = useRouter();
        const user = computed(() => {
            return store.getters['currentUser']
        });

        // NOTIFICATIONS
        // TODO: Extract to modules
        const loadingNotifications = ref(false);
        const notifications = ref([]);
        const getNotifications = async () => {
            console.log('getNotifiations...');
            loadingNotifications.value = true;
            await setTimeout(() => {
                notifications.value.push({id: 1, message: 'This is a dummy notification.'});
                notifications.value.push({id: 2, message: 'Start getting notificaitons from the server!'});
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


        // GROUPS
        // TODO: Get groups by search with TONS of info.
        // TODO: Extract that work to a module.
        const groups = computed(() => user.value.memberships.map(m => m.group).filter(g => g !== null));
        const groupFields = ref([
            {
                name: 'name',
                sortable: true,
                type: String
            },
            {
                name: 'status.name',
                label: 'Status',
                sortable: true,
                type: String
            },
            {
                name: 'info',
                sortable: false,
                type: String
            }
        ]);
        const groupSort = ref({
            field: 'name',
            desc: false
        });


        // TODO: extract to module.
        // TODO: Get coi data on demand
        const cois = computed(() => {
            return user.value.memberships
                .filter(m => m.cois !== null && m.cois.length > 0)
                .map(m => {
                    return m.cois.map(coi => {
                        coi.group = m.group;
                        return coi;
                    })
                })
                .flat()
        });
        const coiFields = [
            {
                name: 'group.name',
                label: 'Group',
                type: String,
                sortable: true
            },
            {
                name: 'completed_at',
                label: 'Completed',
                sortable: false,
                type: Date,
            },
            {
                name: 'actions',
                sortable: false,
            }
        ];
        const coiSort = ref({
            field: 'group.name',
            desc: false
        })

        onMounted(() => {
            getNotifications();
        })

        return {
            user,
            loadingNotifications,
            notifications,
            groups,
            groupSort,
            groupFields,
            cois,
            coiFields,
            coiSort,
            getNotifications,
            removeNotification,
            navigateToGroup: (item) => {
                console.log(item);
                router.push({
                    name: 'GroupDetail',
                    params: {uuid: item.uuid}
                })
            }
        }
        
    }
}
</script>