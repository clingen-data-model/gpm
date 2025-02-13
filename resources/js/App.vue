<template>
  <div id="epam-app-root">
    <header id="nav" class="border-b bg-gray-100 print:hidden">
      <div class="container mx-auto py-3 flex">
        <div id="main-menu" class="flex-grow">
          <div class="inline-block pr-4">
            <router-link to="/" class="text-black hover:text-black">{{appName}}</router-link>
          </div>
          <span v-if="$store.getters.isAuthed">
              <router-link 
                to="/applications" 
                class="link nav-item" 
                v-if="hasPermission('ep-applications-manage')"
              >Applications</router-link> 
              <router-link 
                to="/annual-updates" 
                class="link nav-item" 
                v-if="hasPermission('annual-updates-manage')"
              >Annual Updates</router-link> 
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
              <router-link 
                to="/reports" 
                class="link nav-item"
                v-if="hasPermission('reports-pull')"
              >Reports
              </router-link>
              <!-- <router-link 
                to="/guides-and-documentation" 
                class="link nav-item"
              >
                Guides &amp; documentation
              </router-link> -->

          </span>
        </div>
        <a href="mailto:gpm_support@clinicalgenome.org?subject=New ExpertPanel Request" class="btn btn-xs mr-4">Request a new group</a>
        <user-menu></user-menu>
      </div>
    </header>

    <div class="my-6 container mx-auto">
      <div>
        <div>
          <router-view />
        </div>
      </div>
      <!-- <impersonate-control class="print:hidden" /> -->
    </div>


    <alert-viewer></alert-viewer>

    <div class="fixed right-0 top-20 space-y-2">
      <help-button />
      <issue-report-form class="print:hidden" />
    </div>

    <teleport to="body">
      <!-- <footer class="w-full border-t mt-4 bg-gray-100">
        <div class="container mx-auto py-3 flex" id="footer-content">
            
        </div>
        <div class="container mx-auto py-3" id="debug-info" v-if="hasRole('super-user')">
        </div>
      </footer> -->
      <div v-if="hasRole('super-user')" class="container mx-auto note border-t mt-4 pt-4">
          Build: {{$store.state.systemInfo.build.name}}
          |
          Commit: {{$store.state.systemInfo.build.commit || '--'}}
          |
          Env: {{$store.state.systemInfo.env}}
      </div>
 
    </teleport>
    <!-- This is a comment test -->
  </div>
</template>

<script>
import UserMenu from '@/components/UserMenu.vue'
import AlertViewer from '@/components/alerts/AlertViewer.vue'
import IssueReportForm from '@/components/IssueReportForm.vue'
import { mapGetters } from 'vuex'
import configs from '@/configs';

export default {
  components: {
    UserMenu,
    AlertViewer,
    IssueReportForm,
  },
  data() {
    return {
      showLogin: false,
      systemInfo: configs.systemInfo
    }
  },
  computed: {
    ...mapGetters(['isAuthed']),
    appName () {
      return this.$store.state && this.$store.state.systemInfo && this.$store.state.systemInfo.app? 
              this.$store.state.systemInfo.app.name
              : 'GPM'
    }
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