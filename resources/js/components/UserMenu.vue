<script>
import {mapGetters} from 'vuex'
import ImpersonateControl from '@/components/ImpersonateControl.vue'
import AnnouncementControl from '@/components/alerts/AnnouncementControl.vue'

export default {
    name: 'UserMenu',
    components: {
        ImpersonateControl,
        AnnouncementControl
    },
    data() {
        return {
            menuOpen: false
        }
    },
    computed: {
        ...mapGetters({
            user: 'currentUser',
            isAuthed: 'isAuthed'
        })
    },
    methods: {
        handleOutsideClick() {
            this.menuOpen = false;
        },
        toggleMenu () {
            this.menuOpen = !this.menuOpen
            if (this.menuOpen) {
                this.$refs.dropdownMenu.focus()
            }
        },
        logout () {
            try{
                this.$store.dispatch('logout')
            } catch (error) {
                // eslint-disable-next-line no-alert
                alert(error)
            }
        }
    }
}
</script>
<template>
  <div class="relative -top-3 right-0 text-right" style="z-index: 500">
    <div v-show="isAuthed">
      <div
        class="flex flex-row-reverse align-middle -mb-7 pt-3 pb-3 pr-2 relative z-20 cursor-pointer w-48"
        :class="{'w-48': menuOpen, 'bg-yellow-300': user.is_impersonating}"
        @click.stop="toggleMenu"
      >
        <icon-cheveron-down />
        {{ user.name }} <span v-if="user.is_impersonating">*</span>
      </div>
      <transition name="slide-fade-down">
        <div
          v-show="menuOpen"
          ref="dropdownMenu"
          v-click-outside="{exclude: ['menuButton'], handler: handleOutsideClick}"
          class="absolute right-0 top-0 pt-11 bg-white border w-48 z-10 shadow"
        >
          <ul>
            <li class="menu-item">
              <router-link :to="{name: 'Dashboard'}" @click="showMenu = false">
                Dashboard
              </router-link>
            </li>
            <li class="menu-item">
              <button v-if="!user.is_impersonating" @click="logout">
                Log out
              </button>
            </li>
            <li class="menu-item">
              <div class="py-4 mx-2">
                <ImpersonateControl />
              </div>
            </li>
          </ul>
          <ul>
            <li v-if="hasPermission('invites-view')" class="menu-item">
              <router-link :to="{name: 'InviteAdmin'}">
                Invites
              </router-link>
            </li>
            <li v-if="hasPermission('people-manage')" class="menu-item">
              <a href="/admin/institutions">Institutions</a>
            </li>
            <li v-if="hasPermission('people-manage')" class="menu-item">
              <a href="/admin/credentials">Credentials</a>
            </li>
            <li v-if="hasPermission('people-manage')" class="menu-item">
              <a href="/admin/expertises">Expertises</a>
            </li>
            <li v-if="hasPermission('mail-log-view')" class="menu-item">
              <router-link :to="{name: 'mail-log'}" @click="showMenu = false">
                Mail log
              </router-link>
            </li>
            <li v-if="hasPermission('announcements-manage')" class="menu-item">
              <AnnouncementControl />
            </li>
            <li v-if="hasPermission('logs-view')" class="menu-item">
              <a href="/dev/logs" class="p-3 block">System Log</a>
            </li>
          </ul>
          <div v-if="user.is_impersonating" class="border-t bg-yellow-300 text-center font-bold p-2">
            You are impersonating this user.
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>
<style lang="postcss" scoped>
    ul {
        @apply border-t-4 first:border-none;
    }
    .menu-item {
        @apply hover:bg-blue-100 cursor-pointer border-t border-gray-300;
    }
    .menu-item:first-child {
        @apply border-b;
    }
    .menu-item > *
    {
         @apply p-3 block;
    }

    .menu-item > button {
        @apply block w-full cursor-pointer text-right text-blue-500;
    }
</style>
