<template>
  <div>
    <header id="nav" class="border-b bg-gray-100">
      <div class="container mx-auto py-3 flex">
        <div id="main-menu" class="flex-grow">
          <div class="inline-block pr-3">
            <router-link to="/" class="text-black hover:text-black"> EPAM</router-link>
          </div>
          
          <span v-if="$store.getters.isAuthed">
          <router-link to="/applications" class="link">Applications</router-link> 
          |
          <!-- <router-link to="/cdwgs" class="link">CDWGs</router-link>  -->
          <!-- | -->
          <router-link to="/people" class="link">People</router-link> 
          |
          <router-link :to="{name: 'GroupList'}" class="link">Groups</router-link>
          <!-- <router-link to="/about" class="link">About</router-link> -->
          <!-- |
          <router-link to="/guides-and-documentation" class="link">Guides &amp; documentation</router-link> | -->
          </span>
        </div>
        <user-menu></user-menu>
      </div>
    </header>

    <router-view class="container mx-auto my-3"/>

    <alert-viewer></alert-viewer>

    <footer class="absolute bottom-0 left-0 w-full">
      <div id="dev-info" class="container mx-auto py-3 flex"></div>
      <div class="container mx-auto py-3 flex" id="footer-content">
        
      </div>
    </footer>
  </div>
</template>

<script>
import UserMenu from './components/UserMenu'
import AlertViewer from './components/alerts/AlertViewer'
import { mapGetters } from 'vuex'

export default {
  components: {
    UserMenu,
    AlertViewer
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
      if (this.$route.meta && this.$route.meta.protected && this.isAuthed == false && this.$route.name != 'login') {
        this.$router.push({name: 'login'});
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
