<script>
import {mapGetters} from 'vuex'
export default {
    computed: { 
        ...mapGetters({
            currentUser: 'currentUser'
        })
    },
    mounted () {
        this.$store.dispatch('getCurrentUser');
    }
}
</script>
<template>
  <div>
    <pre>{{ currentUser }}</pre>
  </div>
</template>