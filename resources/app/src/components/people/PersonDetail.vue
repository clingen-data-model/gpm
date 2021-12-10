<template>
    <div>
        <header class="pb-4">
            <note>People</note>
            <h1 class="flex justify-between items-center">
                <div>
                    {{person.name}}
                    <note>ID: {{person.id}}</note>
                </div>
                <router-link 
                    :to="`/people/${uuid}/edit`"
                    class="btn btn-xs flex-grow-0"
                    v-if="(hasPermission('people-manage') || userIsPerson(person))"
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
                <membership-list :person="person"></membership-list>
            </tab-item>
            <tab-item label="Info">
                <person-profile :person="person"></person-profile>
            </tab-item>
            <tab-item label="Conflict of Interest">
                <coi-list :person="person"></coi-list>
            </tab-item>
            <tab-item label="Documents">
                docs
            </tab-item>
            <!-- <tab-item label="Training &amp; Attestations">
            </tab-item> -->
            <tab-item label="Email Log">
                <div class="w-3/4 my-4 p-4 border" v-for="email in sortedMailLog" :key="email.id">
                    <dictionary-row label="Date/Time">
                        {{formatDate(email.created_at)}}
                    </dictionary-row>
                    <dictionary-row label="Subject">
                        {{email.subject}}
                    </dictionary-row>
                    <dictionary-row label="Body">
                        <div v-html="email.body"></div>
                    </dictionary-row>
                </div>
            </tab-item>
        </tabs-container>
        <modal-dialog v-model="showModal" :title="$route.meta.title">
            <router-view name="modal"></router-view>
        </modal-dialog>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import {formatDateTime as formatDate} from '@/date_utils'

import TabsContainer from '../TabsContainer.vue'
import MembershipList from './MembershipList.vue'
import PersonProfile from '@/components/people/PersonProfile'
import CoiList from '@/components/people/CoiList'

export default {
    name: 'PersonDetail',
    components: { 
        TabsContainer,
        MembershipList,
        PersonProfile,
        CoiList,
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            emails: []
        }
    },
    watch: {
        uuid: {
            immediate: true,
            handler: function () {
                this.$store.dispatch('people/getPerson', {uuid: this.uuid})
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
        }
    },
    methods: {
    },
    setup() {
        return {
            formatDate: formatDate
        }
    },
    async mounted() {
        await this.$store.dispatch('people/getPerson', {uuid: this.uuid})
        this.$store.dispatch('people/getMail', this.person);
    }
}
</script>