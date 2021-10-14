<template>
    <div>
        <header>
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
        </header>
        <tabs-container>
            <tab-item label="groups">
                <membership-list :person="person"></membership-list>
            </tab-item>
            <tab-item label="Info">
                <dictionary-row 
                    v-for="key in ['name', 'email']"
                    :key="key"
                    :label="key"
                >
                    {{person[key]}}
                </dictionary-row>
                <hr>
                <dict-row label="Institution">{{person.institution ? person.institution.name : null}}</dict-row>
                <dict-row label="Credentials">{{person.credentials}}</dict-row>
                <dict-row label="Primary Occupation">{{person.primary_occupation ? person.primary_occupation.name : null}}</dict-row>
                <dict-row label="Biography">{{person.biography}}</dict-row>
                <dict-row label="ORCID">{{person.orcid_id}}</dict-row>
                <dict-row label="Hypothes.is ID">{{person.hypothesis_id}}</dict-row>
                <hr>
                <dict-row label="Address">
                    <p v-if="person.street1">{{person.street1}}</p>
                    <p v-if="person.street2">{{person.street2}}</p>
                    <p>
                        <span v-if="person.city">{{person.city}},</span>
                        <span v-if="person.state">{{person.state}}</span>
                        <span v-if="person.zip">{{person.zip}}</span>
                    </p>
                    <p v-if="person.country">{{country.name}}</p>
                </dict-row>
                <dict-row label="Phone">{{person.phone}}</dict-row>
                <hr>
                <dictionary-row 
                    v-for="key in ['created_at', 'updated_at']"
                    :key="key"
                    :label="key"
                >
                    {{formatDate(person[key])}}
                </dictionary-row>
            </tab-item>
            <tab-item label="Conflict of Interest"></tab-item>
            <tab-item label="Documents"></tab-item>
            <tab-item label="Training &amp; Attestations"></tab-item>
        </tabs-container>
        <modal-dialog v-model="showModal">
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

export default {
  components: { 
      EditIcon, 
      TabsContainer,
      MembershipList
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
                    this.$router.push({name: 'person-edit', params: {uuid: this.person.uuid}})
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