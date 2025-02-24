<template>
    <div>
        <ProfileForm :person="person"
            v-model="person"
            @saved="goBack()"
            @canceled="goBack()"
        ></ProfileForm>
    </div>
</template>
<script>
import ProfileForm from '@/components/people/ProfileForm.vue'

export default {
    name: 'PersonEdit',
    components: {
        ProfileForm
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    computed: {
        person () {
            return this.$store.getters['people/currentItem'];
        }
    },
    watch: {
        uuid: {
            immediate: true,
            handler () {
                this.$store.dispatch('people/getPerson', {uuid: this.uuid});
            }
        }
    },
    methods: {
        goBack () {
            this.$router.go(-1);
        }
    }
}
</script>