<template>
    <div>
        <header class="pb-4">
            <note>People</note>
            <h1 class="flex justify-between items-center">
                {{person.name}}
                <router-link 
                    :to="`/people/${uuid}/edit`"
                    class="btn btn-xs flex-grow-0"
                    v-if="(hasPermission('people-manage') || userIsPerson(person))"
                >
                    <edit-icon width="16" heigh="16"></edit-icon>
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
                coi
            </tab-item>
            <tab-item label="Documents">
                docs
            </tab-item>
            <tab-item label="Training &amp; Attestations">
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
import EditIcon from '@/components/icons/IconEdit'
import TabsContainer from '../TabsContainer.vue'
import MembershipList from './MembershipList.vue'
import PersonProfile from '@/components/people/PersonProfile'

export default {
    name: 'PersonDetail',
    components: { 
        EditIcon, 
        TabsContainer,
        MembershipList,
        PersonProfile,
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    data() {
        return {
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
        }
    },
    methods: {
    },
    setup() {
        return {
            formatDate: formatDate
        }
    },
    mounted() {
        this.$store.dispatch('people/getPerson', {uuid: this.uuid})
    }
}
</script>