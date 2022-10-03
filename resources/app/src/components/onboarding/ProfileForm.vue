<template>
    <div>
        <p class="text-lg font-bold">Please fill out your profile</p>
        <PeopleProfileForm :person="person" @saved="handleSaved"
            :allowCancel="false"
            :showTitle="false"
            saveButtonText="Next"
        />
        <dev-component class="mt-4">
            <collapsible>{{person}}</collapsible>
        </dev-component>
    </div>
</template>
<script>
import {onMounted} from 'vue'
import {getLookups} from '@/forms/profile_form';
import PeopleProfileForm from '@/components/people/ProfileForm.vue'

export default {
    name: 'ProfileForm',
    components: {
        PeopleProfileForm
    },
    props: {
        person: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            errors: {},
            profile: {},
            page: 'profile'
        }
    },
    methods: {
        initProfile () {
            this.profile = {...this.$store.getters['people/currentItem'].attributes};
        },
        async handleSaved () {
            this.$store.dispatch('forceGetCurrentUser');
            this.$emit('saved');
        },
    },
    setup () {
        onMounted(() => getLookups());
    },
    async mounted () {
        await this.$store.dispatch('people/getPerson', {uuid: this.person.uuid})
        this.initProfile()
    }
}
</script>
