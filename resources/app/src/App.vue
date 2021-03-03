<template>
  <div>
    <div id="nav" class="border-b bg-gray-100">
      <div class="container mx-auto py-3 flex">
        <div id="main-menu" class="flex-grow">
          <div class="inline-block pr-3">
            <router-link to="/" class="text-black hover:text-black"> EPAM</router-link>
          </div>
          
          <span v-if="$store.getters.isAuthed">
          <router-link to="/" class="text-blue-500 hover:underline">Aplications</router-link> 
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

    <!-- <div v-if="$route.meta.protected">
      <modal-dialog 
        v-model="unauthenticated" 
        :hide-close="true"
      >
        <h3 class="text-lg border-b pb-2 mb-4">Log In</h3>
        <login-form @authenticated="refreshCurrentRoute"></login-form>
      </modal-dialog>
    </div> -->
  </div>
</template>

<script>
import UserMenu from './components/UserMenu'
import LoginForm from './components/LoginForm'

export default {
  components: {
    UserMenu,
    LoginForm
  },
  data() {
    return {
      beans: true
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
  computed: {
    unauthenticated () {
      return !this.$store.getters.isAuthed
    }
  },
  mounted() {
    this.getLookupResources()
    this.$store.dispatch('getCurrentUser');
  }
}
</script>

<style src="./assets/styles/app.css" />
