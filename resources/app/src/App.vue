<template>
  <div id="epam-app-root">
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
                to="/annual-reviews" 
                class="link nav-item" 
                v-if="hasPermission('annual-reviews-manage')"
              >Annual Reviews</router-link> 
              <router-link 
                to="/people" 
                class="link nav-item"
              >People</router-link>
              <router-link 
                :to="{name: 'GroupList'}" 
                class="link nav-item"
              >Groups</router-link>
              <router-link 
                :to="{name: 'UserList'}" 
                class="link nav-item"
                v-if="hasPermission('users-manage')"
              >Users</router-link>
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

    <div class="my-3 container mx-auto">
      <div class="md:flex space-x-4 mb-4">
        <div class='flex-1'>
          <router-view />
        </div>
      </div>
      <impersonate-control />
    </div>


    <alert-viewer></alert-viewer>
    <issue-report-form />

    <!-- <teleport to="body">
      <footer class="w-full border-t mt-4 bg-gray-100">
        <div class="container mx-auto py-3 flex" id="footer-content">
            
        </div>
        <div class="container mx-auto py-3" id="debug-info" v-if="hasRole('super-user')">
        </div>
      </footer>
    </teleport> -->
  </div>
  
</template>

<script>
import UserMenu from './components/UserMenu'
import AlertViewer from './components/alerts/AlertViewer'
import IssueReportForm from '@/components/IssueReportForm'
import ImpersonateControl from '@/components/ImpersonateControl'
import { mapGetters } from 'vuex'

export default {
  components: {
    UserMenu,
    AlertViewer,
    IssueReportForm,
    ImpersonateControl,
  },
  data() {
    return {
      showLogin: false,
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
    },
  },
  mounted() {
    this.getLookupResources()
    this.$store.dispatch('getCurrentUser');
  }
}
</script>

<style src="./assets/styles/app.css" />