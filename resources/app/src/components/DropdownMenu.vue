<style lang="postcss">
    .dropdown {
        @apply relative
    }
    .dropdown.right {
        @apply top-0 right-0 text-right
    }

    .dropdown > .dropdown-label {
        @apply flex flex-row-reverse align-middle -mb-3 pb-3 pr-2 relative z-20 cursor-pointer outline-none;
    }
    .dropdown > .dropdown-items-container {
        @apply absolute bg-transparent border w-48 z-10 shadow rounded;
    }
    .dropdown.right > .dropdown-items-container {
        @apply right-0;
    }
    .dropdown.left > .dropdown-items-container {
        @apply left-0;
    }
    .dropdown-items {
        @apply bg-transparent rounded;
    },
    .dropdown-items > li {
        @apply hover:bg-blue-100 cursor-pointer border-b border-t border-gray-300 py-2 px-2;
        @apply first:rounded-t last:rounded-b bg-white;
    }
</style>
<template>
    <div class="dropdown" :class="orientation">
        <!-- <div> -->
            <div class="dropdown-label"
                :class="{'w-48': menuOpen}"
                ref="menuButton" 
                @click.stop="toggleMenu"
            >
                <slot name="label">
                    <chevron-down></chevron-down>
                    {{label}}
                </slot>
            </div>
            <transition name="slide-fade-down">            
                <div 
                    v-show="menuOpen" 
                    v-click-outside="{exclude: ['menuButton'], handler: handleOutsideClick}"
                    ref="dropdownMenu"
                    class="dropdown-items-container" 
                >
                    <ul class="dropdown-items">
                        <slot></slot>
                    </ul>
                </div>
            </transition>
        <!-- </div> -->
    </div>
</template>
<script>
import ChevronDown from './icons/IconCheveronDown'

export default {
    name: 'DropdownMenu',
    components: {
        ChevronDown
    },
    props: {
        label: {
            required: false,
            type: String,
            default: '...'
        },
        orientation: {
            type: String,
            required: false,
            default: 'right'
        }
    },
    data() {
        return {
            menuOpen: false
        }
    },
    computed: {
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
    }
}
</script>