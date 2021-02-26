<template>
    <div class="relative top-0 right-0 text-right">
        <div v-if="isUser">
            <div class="flex flex-row-reverse align-middle -mb-3 pb-3 pr-2 relative z-20 cursor-pointer" 
                @click="toggleMenu"
            >
                <chevron-down></chevron-down>
                {{user.name}} 
            </div>
            <transition name="slide-fade-down">            
                <div class="absolute right-0 -top-3 pt-11 bg-white border w-48 z-10 shadow" v-if="menuOpen">
                    <ul>
                        <li class="p-3 hover:bg-blue-100 cursor-pointer border-b border-t">
                            <router-link to="me">My info</router-link>
                        </li>
                        <li class="p-3 hover:bg-blue-100 cursor-pointer border-t-2 border-gray-400">
                            <button class="text-blue-500 cursor-pointer" @click="logout">Log out</button>
                        </li>
                    </ul>
                </div>
            </transition>
        </div>
        <div v-else>
            <router-link to="/login">Login</router-link>
        </div>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import ChevronDown from './icons/IconCheveronDown'

export default {
    components: {
        ChevronDown
    },
    props: {
        
    },
    data() {
        return {
            menuOpen: false
        }
    },
    computed: {
        ...mapGetters({
            user: 'currentUser'
        }),
        isUser () {
            return (this.user && this.user.id)
        }
    },
    methods: {
        toggleMenu () {
            this.menuOpen = !this.menuOpen
        },
        logout () {
            try{
                this.$store.dispatch('logout')
                    .then( () => {
                        this.$router.push('/login')
                    })
            } catch (error) {
                alert(error)
            }
        }
    }
}
</script>