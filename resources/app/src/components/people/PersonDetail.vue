<template>
    <div>
        <header class="pb-4">
            <router-link class="note" to="/people">People</router-link>
            <h1 class="flex justify-between items-center">
                <div>
                    {{person.name}}
                    <note>ID: {{person.id}}</note>
                </div>
                <router-link 
                    :to="`/people/${uuid}/edit`"
                    class="btn btn-xs flex-grow-0"
                    v-if="(hasPermission('people-manage') || userIsPerson(person)) || coordinatesPerson(person)"
                >
                    <icon-edit width="16" heigh="16" />
                </router-link>

            </h1>
            <dictionary-row label="Email">
                <template v-slot:label><strong>Email:</strong></template>
                {{person.email}}
            </dictionary-row>
            <dictionary-row label="Institution">
                <template v-slot:label><strong>Institution:</strong></template>
                {{person.institution ? person.institution.name : null}}
            </dictionary-row>
        </header>
        <tabs-container>
            <tab-item label="groups">
                <h2>Groups</h2>
                <membership-list :person="person"></membership-list>
            </tab-item>
            <tab-item label="Info">
                <h2>Profile</h2>
                <person-profile :person="person"></person-profile>
            </tab-item>
            <tab-item label="Conflict of Interest">
                <coi-list :person="person"></coi-list>
            </tab-item>
            <!-- <tab-item label="Documents">
                docs
            </tab-item> -->
            <!-- <tab-item label="Training &amp; Attestations">
            </tab-item> -->

            <tab-item label="Email Log" :visible="hasPermission('people-manage') || userIsPerson(person) || coordinatesPerson(person)">
                <header class="flex space-x-4 items-center">
                    <h2>Mail sent to {{person.first_name}}</h2>
                    <refresh-button :loading="mailLoading" @click="getMailLog"/>
                </header>
                <person-mail-log :person="person" :mail="sortedMailLog"></person-mail-log>
            </tab-item>

            <tab-item label="Log" :visible="hasPermission('people-manage') || coordinatesPerson(person)">
                <div class="flex space-x-4 items-center">
                    <h2>Log Entries</h2>
                    <refresh-button :loading="logsLoading" @click="getLogEntries"/>
                </div>
                <activity-log
                    :log-entries="logEntries"
                    :api-url="`/api/people/${person.uuid}/activity-logs`"
                    v-bind:log-updated="getLogEntries"
                ></activity-log>
            </tab-item> 

            <tab-item label="Admin" :visible="hasPermission('people-manage')">
                <section class="border my-4 p-4 bg-red-100 border-red-200 rounded">
                <h2 class="mb-4 text-red-800">
                    Here be dragons. Proceed with caution.
                </h2>
                <p><button class="btn btn red" @click="initMerge">Merge Person into another</button></p>
                <p><button class="btn btn red" @click="initDelete">Delete Person</button></p>
                </section>
            </tab-item>

        </tabs-container>

        <teleport to="body">
            <modal-dialog v-model="showModal" :title="$route.meta.title">
                <router-view name="modal"></router-view>
            </modal-dialog>
            <modal-dialog :title="`You are about to delete ${person.name}`" v-model="showDeleteConfirmation">
                <p>You are about to delete this person.  All related data will also be deleted including:</p>
                <ul class="list-disc pl-6">
                    <li>User record, system roles, and system permissions (if account is activated)</li>
                    <li>Invite</li>
                    <li>Group Memberships and group roles (if any)</li>
                </ul>
                <div class="border my-4 px-2 py-1 font-bold bg-red-100 border-red-200 rounded text-red-800">
                    This cannot be undone.
                </div>
                <button-row 
                    :submit-text="`Delete ${person.name}`" 
                    @submitted="commitDelete"  
                    @canceled="cancelDelete" 
                    submit-variant="red"
                />
            </modal-dialog>
            <modal-dialog :title="`Merge ${person.name} into another person`" v-model="showMergeForm">
                <person-merge-form :obsolete="person" @saved="handleMerged" @canceled="handleMergeCanceled"></person-merge-form>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import {formatDateTime as formatDate} from '@/date_utils'
import { logEntries, fetchEntries } from "@/adapters/log_entry_repository";

import TabsContainer from '../TabsContainer.vue'
import MembershipList from './MembershipList.vue'
import PersonProfile from '@/components/people/PersonProfile'
import PersonMergeForm from '@/components/people/PersonMergeForm'
import CoiList from '@/components/people/CoiList'
import ActivityLog from "@/components/log_entries/ActivityLog";
import PersonMailLog from "@/components/people/PersonMailLog";


export default {
    name: 'PersonDetail',
    components: { 
        TabsContainer,
        MembershipList,
        PersonProfile,
        PersonMergeForm,
        CoiList,
        ActivityLog,
        PersonMailLog,
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            showDeleteConfirmation: false,
            showMergeForm: false,
            mailLoading: false,
            logsLoading: false,
        }
    },
    watch: {
        uuid: {
            immediate: true,
            handler: async function () {
                await this.$store.dispatch('people/getPerson', {uuid: this.uuid});
                if (this.coordinatesPerson(this.person)) {
                    this.getLogEntries();
                    this.getMailLog();
                }
            }
        }
    },
    computed: {
        ...mapGetters({
            person: 'people/currentItem'
        }),
        showModal: {
            get() {
                return this.$route.meta.showModal
            },
            set(value) {
                if (value) {
                    this.$router.push({name: 'PersonEdit', params: {uuid: this.person.uuid}})
                }
                this.$router.push({name: 'PersonDetail', params: {uuid: this.person.uuid}})
            }
        },
        sortedMailLog () {
            return [...this.person.mailLog].sort((a,b) => {
                if (a.created_at == b.created_at) {
                    return 0;
                }
                return (Date.parse(a.created_at) > Date.parse(b.created_at))
                    ? -1 : 1;
            })
        },
    },
    methods: {
        initDelete () {
            this.showDeleteConfirmation = true;
        },
        async commitDelete () {
            await this.$store.dispatch('people/deletePerson', this.person);
            this.showDeleteConfirmation = false;
            this.$router.go(-1);
        },
        cancelDelete () {
            this.showDeleteConfirmation = false;
        },
        initMerge() {
            this.showMergeForm = true;
        },
        handleMerged () {
            this.showMergeForm = false;
            this.$router.go(-1);
        },
        handleMergeCanceled () {
            this.showMergeForm = false;
        },
        async getMailLog() {
            this.mailLoading = true;
            await this.$store.dispatch('people/getMail', this.person);
            this.mailLoading = false;
        },
        async getLogEntries () {
            this.logsLoading = true;
            await this.fetchEntries(`/api/people/${this.uuid}/activity-logs`);
            this.logsLoading = false;
        }
    },
    setup() {
        return {
            formatDate: formatDate,
            logEntries,
            fetchEntries
        }
    },
}
</script>