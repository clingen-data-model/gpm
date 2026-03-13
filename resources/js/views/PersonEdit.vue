<script>
import ProfileForm from '@/components/people/ProfileForm.vue'
import { usePeopleStore } from '@/stores/people';

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
            return usePeopleStore().currentItem;
        }
    },
    watch: {
        uuid: {
            immediate: true,
            handler () {
                usePeopleStore().getPerson({uuid: this.uuid});
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
<template>
  <div>
    <ProfileForm
      v-model="person"
      :person="person"
      @saved="goBack()"
      @canceled="goBack()"
    />
  </div>
</template>