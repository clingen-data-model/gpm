<template>
    <form-container @keyup.enter="save">
        <h2 class="border-b py-2">Edit - {{person.name}}</h2>
        <input-row label="First Name" :errors="errors.first_name" v-model="workingPerson.first_name" ref="firstname"></input-row>
        <input-row label="Last Name" :errors="errors.last_name" v-model="workingPerson.last_name"></input-row>
        <input-row label="Email" :errors="errors.email" v-model="workingPerson.email"></input-row>
        <input-row label="Phone" :errors="errors.phone" v-model="workingPerson.phone"></input-row>

        <button-row @cancelClicked="handleCancel" @submitClicked="save" submitText="Save"></button-row>
    </form-container>
</template>
<script>
import {mapGetters} from 'vuex'
import is_validation_error from '@/http/is_validation_error';

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
        backToDetail () {
            this.$router.push({name: 'PersonDetail', params: {uuid: this.person.uuid}});
        },
        handleCancel () {
            this.syncWorkingPerson();
            this.backToDetail()
        },
        async save () {
            try {
                await this.$store.dispatch('people/updateAttributes', {uuid: this.person.uuid, attributes: this.workingPerson})
                this.backToDetail();
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                }
            }
        }
    },
    mounted() {
        this.$refs.firstname.focus();
    }
}
</script>