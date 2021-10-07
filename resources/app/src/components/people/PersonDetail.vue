<template>
    <div>
        <card :title="cardTitle">
            <template v-slot:header-right>
                <router-link 
                    :to="`/people/${uuid}/edit`"
                    class="btn btn-xs"
                >
                    Edit
                </router-link>
            </template>
            <dictionary-row 
                v-for="key in ['name', 'email', 'phone']"
                :key="key"
                :label="key"
            >
                {{person[key]}}
            </dictionary-row>
            <dictionary-row 
                v-for="key in ['created_at', 'updated_at']"
                :key="key"
                :label="key"
            >
                {{formatDate(person[key])}}
            </dictionary-row>
        </card>
        <modal-dialog v-model="showModal">
            <router-view name="modal"></router-view>
        </modal-dialog>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import {formatDateTime as formatDate} from '@/date_utils'

export default {
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            // showModal: false
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
        cardTitle () {
            return 'Person: '+this.person.name
        },
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