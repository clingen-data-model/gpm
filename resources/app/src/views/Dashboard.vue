<template>
    <div>
        <h1>
            Dashboard
            <div class="note font-normal">
                User ID: {{user.id}}
                |
                Person ID: {{user.person ? user.person.id : 'no person!!'}}
            </div>
        </h1>
        <transition-group tag="div" name="slide-fade-down">
            <notification-item 
                v-for="notification in notifications" :key="notification.id"
                :notification="notification"
                class="mt-2"
                @removed="removeNotification(notification)"
                :variant="notification.data.type"
            ></notification-item>
        </transition-group>

        <sustained-curation-review-alert
            v-for="task in sustainedCurationReviews" 
            :key="task.id"
            :group="task.assignee"
            class="mb-2"
        />

        <annual-review-alert 
            v-for="group in coordinatingGroups" :key="group.id" 
            :group="group"
            :show-group-name="true"
            class="mb-2"
        />

        <coi-alert
            v-for="membership in user.person.membershipsWithPendingCois" 
            :key="membership.id"
            :membership="membership"
            class="mb-2"
        />

        <tabs-container class="mt-8">
            <tab-item label="Your Groups">
                <div class="well" v-if="!groups.length">You are not assigned to any groups.</div>
                <data-table
                    v-else
                    :data="groups"
                    :fields="groupFields"
                    v-model:sort="groupSort"
                    @rowClick="navigateToGroup"
                    row-class="cursor-pointer"
                >
                    <template v-slot:cell-status_name="{value}">
                        <badge :color="groupBadgeColor(value)">{{value}}</badge>
                    </template>
                </data-table>
            </tab-item>

            <tab-item label="Your Info">
                <person-profile :person="personFromStore"></person-profile>
            </tab-item>

            <tab-item label="COIs">
                <coi-list :person="user.person"></coi-list>
            </tab-item>

        </tabs-container>
    </div>
</template>
<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import {ref, computed, onMounted, watch} from 'vue'
import {api, queryStringFromParams} from '@/http'
import AnnualReviewAlert from '@/components/groups/AnnualReviewAlert';
import NotificationItem from '@/components/NotificationItem'
import CoiList from '@/components/people/CoiList'
import PersonProfile from '@/components/people/PersonProfile'
import Person from "@/domain/person"
import Group from "@/domain/group"
import configs from '@/configs'

export default {
    name: 'Dashboard',
    components: {
        CoiList,
        NotificationItem,
        PersonProfile,
        AnnualReviewAlert
    },
    data() {
        return {
        }
    },
    props: {
        
    },
    setup () {
        const store = useStore();
        const router = useRouter();
        const user = computed(() => {
            return store.getters['currentUser']
        });
        const personFromStore = computed(() => {
            return store.getters['people/currentItem'] || new Person();
        })
        const loadPersonInStore = () => {
            if (user.value.id && user.value.person && user.value.person.id) {
                store.commit('people/addItem', user.value.person);
                store.commit('people/setCurrentItemIndex', user.value.person);
            }
        }
        watch(() => user, () => {
            loadPersonInStore();
        });
        onMounted(() => {
            loadPersonInStore();
        });

        // NOTIFICATIONS
        // TODO: Extract to modules
        const loadingNotifications = ref(false);
        const notifications = ref([]);
        const getNotifications = async () => {
            loadingNotifications.value = true;
            notifications.value = await api.get(`/api/people/${user.value.person.uuid}/notifications/unread`)
                                .then(response => response.data)
            loadingNotifications.value = false;
        }
        const removeNotification = (notification) => {
            const index = notifications.value.findIndex((item) => item.id == notification.id);
            if (index > -1) {
                notifications.value.splice(index, 1);
            }
        }


        // GROUPS
        // TODO: Get groups by search with TONS of info.
        // TODO: Extract that work to a module.
        const groups = computed(() => {
            return user.value.memberships
                    .map(m => m.group)
                    .filter(g => g !== null)
                    .map(group => new Group(group))
        });

        const coordinatingGroups = computed(() => {
            return user.value.memberships
                    .filter(m => m.hasPermission('annual-review-manage'))
                    .map(m => m.group)
                    .filter(g => g !== null)
                    .map(group => new Group(group))
        });

        const groupFields = ref([
            {
                name: 'displayName',
                sortable: true,
                type: String
            },
            {
                name: 'status.name',
                label: 'Status',
                sortable: true,
                resolveValue: (item) => {
                    if (item.status.id == configs.groups.statuses['applying'].id) {
                        return item.status.name+' - '+item.expert_panel.currentStepName;
                    }
                    return item.status.name;
                },
                type: String
            },
        ]);
        const groupSort = ref({
            field: 'displayName',
            desc: false
        });
        const groupBadgeColor = (status) => {
            const map = {
                Active: 'green',
                Applying: 'blue',
                Retired: 'yellow',
                Removed: 'red'
            }
            return map[status] || 'blue'
        };

        const sustainedCurationReviews = ref([]);
        const getSustainedCurationReviewTasks = async () => {
            const params = {
                with: ['assignee'],
                where: {
                    task_type_id: 1,
                    assignee_type: 'App\\Modules\\Group\\Models\\Group',
                    assignee_id: [...(new Set(coordinatingGroups.value.map(cg => cg.id)))],
                    pending: 1
                }
            }
            const queryString = queryStringFromParams(params);
            const url = `/api/tasks${queryString}`;
            sustainedCurationReviews.value = await api.get(url)
                                                .then(response => {
                                                    const uniqueTasks = {};
                                                    response.data.forEach(task => {
                                                        uniqueTasks[task.assignee_id] = task;
                                                    })
                                                    return uniqueTasks;
                                                });
        }

        // TODO: extract to module.
        // TODO: Get coi data on demand

        onMounted(async () => {
            getNotifications();
            await store.dispatch('forceGetCurrentUser');
            await getSustainedCurationReviewTasks();
        })

        return {
            user,
            sustainedCurationReviews,
            personFromStore,
            loadingNotifications,
            notifications,
            groups,
            coordinatingGroups,
            groupSort,
            groupFields,
            groupBadgeColor,
            getNotifications,
            removeNotification,
            navigateToGroup: (item) => {
                router.push({
                    name: 'GroupDetail',
                    params: {uuid: item.uuid}
                })
            }
        }
        
    }
}
</script>