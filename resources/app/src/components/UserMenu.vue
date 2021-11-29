<template>
    <div class="relative top-0 right-0 text-right">
        <div v-show="isAuthed">
            <div class="flex flex-row-reverse align-middle -mb-3 pb-3 pr-2 relative z-20 cursor-pointer"
                :class="{'w-48': menuOpen}"
                ref="menuButton" 
                @click.stop="toggleMenu"
            >
                <icon-cheveron-down></icon-cheveron-down>
                {{user.name}}
            </div>
            <transition name="slide-fade-down">            
                <div 
                    v-show="menuOpen" 
                    v-click-outside="{exclude: ['menuButton'], handler: handleOutsideClick}"
                    ref="dropdownMenu"
                    class="absolute right-0 -top-3 pt-11 bg-white border w-48 z-10 shadow" 
                >
                    <ul>
                        <li class="menu-item">
                            <router-link :to="{name: 'Dashboard'}" @click="showMenu = false">Dashboard</router-link>
                        </li>
                        <li class="menu-item">
                            <button @click="logout">
                                Log out
                            </button>
                        </li>
                    </ul>
                    <ul>
                        <li class="menu-item">
                            <router-link :to="{name: 'InviteAdmin'}">
                                Invites
                            </router-link>
                        </li> 
                        <li class="menu-item">
                            <router-link :to="{name: 'mail-log'}" @click="showMenu = false">Mail log</router-link>
                        </li>
                        <li class="menu-item">
                            <a href="/dev/logs" class="p-3 block">System Log</a>
                        </li>
                    </ul>
                </div>
            </transition>
        </div>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'


export default {
    name: 'UserMenu',
    props: {
        
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
                alert(error)
            }
        }
    }
}
</script>
<style lang="postcss" scoped>
    ul {
        @apply border-t-4 first:border-none
    }
    .menu-item {
        @apply hover:bg-blue-100 cursor-pointer border-t border-gray-300;
    }
    .menu-item:first-child {
        @apply border-b;
    }
    .menu-item > a,
    .menu-item > button {
         @apply p-3 block;
    }

    .menu-item > button {
        @apply block w-full cursor-pointer text-right text-blue-500;
    }
</style>