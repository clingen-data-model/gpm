<template>
  <div>
    <div id="nav" class="border-b bg-gray-100">
      <div class="container mx-auto py-3 flex">
        <div id="main-menu" class="flex-grow">
          <div class="inline-block pr-3">
            <router-link to="/" class="text-black hover:text-black"> EPAM</router-link>
          </div>
          
          <span v-if="$store.getters.isAuthed">
          <router-link to="/applications" class="text-blue-500 hover:underline">Aplications</router-link> 
          |
          <router-link to="/cdwgs" class="text-blue-500 hover:underline">CDWGs</router-link> 
          |
          <router-link to="/people" class="text-blue-500 hover:underline">People</router-link> 
          |
          <router-link to="/about" class="text-blue-500 hover:underline">About</router-link>
          <!-- |
          <router-link to="/guides-and-documentation" class="text-blue-500 hover:underline">Guides &amp; documentation</router-link> | -->
          </span>
        </div>
        <user-menu></user-menu>
      </div>
    </div>

    <div class="container mx-auto my-3">
      <router-view/>
    </div>

  </div>
</template>

<script>
import UserMenu from './components/UserMenu'
import LoginForm from './components/LoginForm'
import { mapGetters } from 'vuex'

export default {
  components: {
    UserMenu,
    LoginForm
  },
  data() {
    return {
      showLogin: false
    }
  },
  computed: {
    ...mapGetters(['isAuthed']),
  },
  watch: {
    isAuthed () {
      if (this.isAuthed == false && this.$route.name != 'login') {
        this.$router.push('login', {params: {redirect: this.$route.name}})
      }
    }
  },
  methods: {
    getLookupResources() {
      this.$store.dispatch('cdwgs/getAll');
    },
    refreshCurrentRoute() {
      this.$router.push(this.$route)
    }
  },
  mounted() {
    this.getLookupResources()
    this.$store.dispatch('getCurrentUser');
  }
}
</script>

<style src="./assets/styles/app.css" />
