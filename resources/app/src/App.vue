<template>
  <div>
    <div id="nav" class="border-b bg-gray-100">
      <div class="container mx-auto py-3 flex">
        <div id="main-menu" class="flex-grow" v-if="$store.getters.authed">
          <div class="inline-block pr-3">
            <router-link to="/" class="text-black hover:text-black"> EPAM</router-link>
          </div>
          
          <router-link to="/" class="text-blue-500 hover:underline">Aplications</router-link> 
          |
          <router-link to="/cdwgs" class="text-blue-500 hover:underline">CDWGs</router-link> 
          |
          <router-link to="/people" class="text-blue-500 hover:underline">people</router-link> 
          |
          <router-link to="/about" class="text-blue-500 hover:underline">About</router-link> 
          <!-- |
          <router-link to="/guides-and-documentation" class="text-blue-500 hover:underline">Guides &amp; documentation</router-link> | -->
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
export default {
  components: {
    UserMenu
  },
  methods: {
    getLookupResources() {
      this.$store.dispatch('cdwgs/getAll');
    }
  },
  mounted() {
    this.getLookupResources()
    this.$store.dispatch('getCurrentUser');
  }
}
</script>

<style src="./assets/styles/app.css" />
