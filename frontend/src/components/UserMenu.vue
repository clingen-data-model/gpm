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
                            menu item 1
                        </li>
                        <li class="p-3 hover:bg-blue-100 cursor-pointer">
                            menu item 2
                        </li>
                    </ul>
                </div>
            </transition>
        </div>
        <div v-else>
            Log in
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
        }
    }
}
</script>