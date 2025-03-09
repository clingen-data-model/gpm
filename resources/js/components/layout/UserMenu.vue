<script>
export default {
    name: 'UserMenu',
    components: {
        ChevronDown
    },
    props: {
        
    },
    data() {
        return {
            menuOpen: false,
            user: {
                name: 'Test user',
            },
            isAuthed: true
        }
    },
    computed: {
        // ...mapGetters({
        //     user: 'currentUser',
        //     isAuthed: 'isAuthed'
        // })
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
                    .then( () => {
                        this.$router.push('/login')
                    })
            } catch (error) {
                // eslint-disable-next-line no-alert
                alert(error)
            }
        }
    }
}
</script>
<template>
    <div class="relative top-0 right-0 text-right">
        <div v-show="isAuthed">
            <div class="flex flex-row-reverse align-middle -mb-3 pb-3 pr-2 relative z-20 cursor-pointer"
                :class="{'w-48': menuOpen}"
                ref="menuButton" 
                @click.stop="toggleMenu"
            >
                <ChevronDown></ChevronDown>
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
                        <!-- <li class="hover:bg-blue-100 cursor-pointer border-b border-t">
                            <router-link :to="{name: 'me'}" @click="showMenu = false" class="p-3 block">My info</router-link>
                        </li>
                        <li class="hover:bg-blue-100 cursor-pointer border-t border-gray-300">
                            <button class="p-3 block w-full cursor-pointer text-right" @click="logout">
                                Log out
                            </button>
                        </li>
                        <li class="hover:bg-blue-100 cursor-pointer border-t-4 border-gray-300">
                            <router-link :to="{name: 'mail-log'}" @click="showMenu = false" class="p-3 block">Mail log</router-link>
                        </li>
                        <li class="hover:bg-blue-100 cursor-pointer border-t border-gray-300">
                            <a href="/admin/logs" class="p-3 block">System Log</a>
                        </li> -->
                    </ul>
                </div>
            </transition>
        </div>
    </div>
</template>