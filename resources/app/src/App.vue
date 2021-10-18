<template>
  <div>
    <header id="nav" class="border-b bg-gray-100">
      <div class="container mx-auto py-3 flex">
        <div id="main-menu" class="flex-grow">
          <div class="inline-block pr-3">
            <router-link to="/" class="text-black hover:text-black"> EPAM</router-link>
          </div>
          <span v-if="$store.getters.isAuthed">
              <router-link 
                to="/applications" 
                class="link nav-item" 
                v-if="hasPermission('ep-applications-manage')"
              >Applications</router-link> 
              <router-link 
                to="/people" 
                class="link nav-item"
              >People</router-link>
              <router-link 
                :to="{name: 'GroupList'}" 
                class="link nav-item"
              >Groups</router-link>
              <!-- <router-link 
                to="/guides-and-documentation" 
                class="link nav-item"
              >
                Guides &amp; documentation
              </router-link> -->
          </span>
        </div>
        <user-menu></user-menu>
      </div>
    </header>

    <div class="my-3">
      <router-view class="container mx-auto mb-1"/>
      <div class="container mx-auto" id="debug-info"></div>
    </div>

    <alert-viewer></alert-viewer>

    <div class="container mx-auto py-3">
      <dev-component id="dev-info" class="">
        <h3>Developer Info</h3>
        <collapsible title="Current User">
          <pre>{{$store.state.user.attributes}}</pre>
        </collapsible>
      </dev-component>
    </div>

    <footer class="w-full">
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
