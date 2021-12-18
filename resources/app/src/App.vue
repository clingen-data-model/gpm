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

    <div class="my-3 md:flex space-x-4 container mx-auto mb-1">
      <!-- <div class="w-80 border">

      </div> -->
      <div class='flex-1'>
        <router-view />
      </div>
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
import { mapGetters } from 'vuex'

export default {
  components: {
    UserMenu,
    AlertViewer,
    IssueReportForm,
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

<!-- <style src="./assets/styles/app.css" /> -->

<style lang="postcss">
@tailwind base;
@tailwind components;
@tailwind utilities;
.router-link-active {
    @apply text-gray-500 underline
}

/**
 * "sticky" footer technique lifted from 
 * https://css-tricks.com/a-clever-sticky-footer-technique/
 **/
html, body {
    height: 100%;
}

body > footer {
    position: sticky;
    top: 100vh;
}

body {
    @apply bg-gray-50;
    font-size: .9rem;
}

h1, h2, h3, h4, h5 {
    @apply font-semibold;
}

h1 {
    @apply text-2xl pb-2 mb-4 border-b;
}

h2{
    @apply text-xl;
}

h3 { 
    @apply text-lg;
}

hr {
    @apply my-4
}

input[type=file]{
    @apply border-none p-0;
}

a[href]:not(.btn,.note) {
    @apply text-blue-500;
}
a[href]:not(.btn) {
    @apply hover:underline
}

input,
select,
textarea {
    @apply border border-gray-300 px-2 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed
}

p {
    @apply mb-2
}

input.sm,
select.sm,
textarea.sm {
    @apply px-1 py-0.5 text-sm;
}

label {
    @apply space-x-2;
    display: flex;
}

input[type="radio"],
input[type="checkbox"] {
  flex-grow: 0;
  flex-shrink: 0;
  color: #666;
  appearance: none;
  background-color: #fff;
  margin: 0;
  font: inherit;
  width: 1.1rem;
  height: 1.1rem;
  padding: 0;
  border: 0.125rem solid currentColor;
  transform: translateY(0.15rem);
  display: grid;
  place-content: center;
  /* margin-right: .5rem; */
}

input[type="radio"] {
    border-radius: 50%;
}

input[type="radio"]::before,
input[type="checkbox"]::before {
  content: "";
  width: .55rem;
  height: .55rem;
  background-color: currentColor;
  transform: scale(0);
  transition: 120ms transform ease-in-out;
  box-shadow: inset 1em 1em var(--form-control-color);
}

input[type="radio"]::before {
    border-radius: 50%;
}

input[type="radio"]:checked,
input[type="checkbox"]:checked {
        @apply text-blue-600;

}
input[type="radio"]:checked::before,
input[type="checkbox"]:checked::before  {
  transform: scale(1);
}


blockquote{
    @apply border-l-4 ml-2 pl-2;
    border-color: inherit;
}

.note {
    @apply text-xs text-gray-400
}
.link {
    @apply text-blue-500 hover:underline;
}

.nav-item { 
    @apply border-r-2 border-gray-400  pr-2 mr-2 align-middle;
}
.nav-item:last-child {
    @apply border-r-0;
}

.btn {
    @apply px-3 py-1 rounded border border-gray-300 focus:outline-none cursor-pointer;
    @apply bg-gradient-to-b from-white to-gray-100 hover:to-gray-200;
    @apply active:from-gray-200 active:to-gray-100;
    @apply disabled:opacity-60 disabled:cursor-not-allowed;

}

.btn-xs {
    @apply text-xs px-2 py-0.5;
}

.btn-sm {
    @apply text-sm;
}

.btn-lg {
    @apply px-4 py-1.5 text-lg;
}

.btn-xl {
    @apply px-6 py-2 text-2xl;
}

.btn.gray {
    @apply text-gray-500 hover:text-gray-700;
    @apply border-gray-400;
    @apply bg-gradient-to-b from-gray-200 to-gray-300 hover:to-gray-400;
    @apply active:from-gray-300 active:to-gray-200;
}

.btn.blue {
    @apply text-white;
    @apply bg-gradient-to-b from-blue-500 to-blue-600 hover:to-blue-700;
    @apply active:from-blue-600 to-blue-500;
    @apply border-blue-600;
}

.btn.red {
    @apply text-white;
    @apply bg-gradient-to-b from-red-500 to-red-600 hover:to-red-700;
    @apply active:from-red-600 to-red-500;
    @apply border-red-600;
}

.btn.light-red {
    @apply bg-gradient-to-b from-red-100 to-red-200 hover:to-red-300;
    @apply active:from-red-300 to-red-200;
    @apply border-red-800;
}


.alert-item {
    transition: transform 0.8s ease;
}

.well {
    @apply border border-gray-300 text-gray-600 bg-gray-200 rounded p-2;
}

.markdown {

}
.markdown a {
    @apply text-blue-500 underline
}

.markdown p {
    @apply mb-2;
}

.markdown ul {
    list-style: disc;
}

.markdown ol {
    list-style: decimal;
    @apply mb-2
}

.markdown ul > li,
.markdown ol > li {
    @apply ml-4;
}

/* Transitions */
.fade-enter-active {
    transition: all 0.3s ease-out;
}
  
.fade-leave-active {
    transition: all 0.3s ease-out;
}
  
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}
  
.slide-fade-leave-active {
    transition: all 0.3s ease-out;
}
  
.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}

.fade-slide-down-enter-active,
.slide-fade-down-enter-active {
    transition: all 0.3s ease-out;
}
  
.fade-slide-down-leave-active,
.slide-fade-down-leave-active {
    transition: all 0.3s ease-out;
}
  
.fade-slide-down-enter-from,
.fade-slide-down-leave-to,
.slide-fade-down-enter-from,
.slide-fade-down-leave-to {
    transform: translateY(-20px);
    opacity: 0;
}

.block-title {
    @apply pb-2 border-b mb-2;
}

.application-section {
    @apply py-2 border-b my-2;
}

.muted {
    @apply text-gray-400;
}
</style>
