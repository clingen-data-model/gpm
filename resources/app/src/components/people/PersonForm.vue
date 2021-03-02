<template>
    <div>
        <h3 class="text-lg border-b py-2">Edit - {{person.name}}</h3>
        <input-row label="First Name" :errors="errors.first_name" v-model="workingPerson.first_name"></input-row>
        <input-row label="Last Name" :errors="errors.last_name" v-model="workingPerson.last_name"></input-row>
        <input-row label="Email" :errors="errors.email" v-model="workingPerson.email"></input-row>
        <input-row label="Phone" :errors="errors.phone" v-model="workingPerson.phone"></input-row>

        <button-row @cancelClicked="handleCancel" @submitClicked="handleSaved" submitText="Save"></button-row>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'

export default {
    props: {
    },
    data() {
        return {
            workingPerson: {
                first_name: null,
                last_name: null,
                email: null,
                phone: null
            },
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            person: 'people/currentItem'
        })
    },
    watch: {
        person: {
            deep: true,
            immediate: true, 
            handler: function () {
                this.syncWorkingPerson();
            }
        }
    },
    methods: {
        syncWorkingPerson() {
            this.workingPerson = this.person.attributes
        },
        handleCancel () {
            this.syncWorkingPerson();
            this.$router.push({name: 'person-detail', params: {uuid: this.person.uuid}});
        },
        handleSaved () {
            console.log('save!!')
        }
    },
    mounted() {
    }
}
</script>