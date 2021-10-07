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
    .dropdown-items-container {
        @apply absolute bg-transparent border w-48 shadow rounded z-50;
    }
    .dropdown.right > .dropdown-items-container {
        @apply right-0;
    }
    .dropdown.left > .dropdown-items-container {
        @apply left-0;
    }
    .dropdown-items {
        @apply bg-transparent shadow-md w-48;
        z-index: 1000;
    },
    .dropdown-items > li {
        @apply bg-white hover:bg-blue-100 cursor-pointer py-2 px-2;
        @apply border border-b-0 border-gray-300;
    }
    .dropdown-items > li:first-child {
        @apply rounded-t
    }
    .dropdown-items > li:last-child {
        @apply border-b rounded-b
    }
</style>
<script>
import CheveronDown from './icons/IconCheveronDown'
import {Teleport, Transition} from 'vue'

export default {
    name: 'DropdownMenu',
    components: {
        CheveronDown,
        Teleport,
        Transition
    },
    props: {
        label: {
            type: String,
            required: false,
            default: '...'
        },
        orientation: {
            type: String,
            required: false,
            default: 'right'
        },
        hideCheveron: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    data() {
        return {
            menuOpen: false,
            menuItems: [],
            menuX: 0,
            menuY: 0,
        }
    },
    computed: {
    },
    watch: {
        menuOpen () {
            this.positionMenu();
        }
    },
    methods: {
        addItem (item) {
            this.menuItems.push(item)
        },
        handleOutsideClick() {
            this.menuOpen = false;
        },
        toggleMenu () {
            this.menuOpen = !this.menuOpen
            if (this.menuOpen) {
                this.$refs.dropdownMenu.focus()
            }
        },
        renderItems () {
            return this.menuItems.map(i => i.render());
        },
        positionMenu () {
            const dropdownWidth = this.$refs.dropdownMenu.offsetWidth; 
            const labelWidth = this.$refs.menuButton.offsetWidth;
            const labelHeight = this.$refs.menuButton.offsetHeight;
            const rect = this.$el.getBoundingClientRect();
            this.menuX = rect.left - parseFloat(getComputedStyle(document.documentElement).fontSize)*12 + labelWidth;
            this.menuY = rect.top + labelHeight - 14;
        }
    },
    render () {
        const items = this.renderItems;
        const containerClass = [];
        return (
            <div class={containerClass.join(' ')}>
                <div class="dropdown" class={this.orientation}>
                    <div class="dropdown-label"
                        ref="menuButton" 
                        onClick={this.toggleMenu}
                    >
                        <span v-show={!this.hideCheveron}><cheveron-down></cheveron-down></span>
                        {this.$slots.label ? this.$slots.label() : this.label}
                    </div>
                        <teleport to="body">
                        <transition name="slide-fade-down">
                            <div 
                                v-show={this.menuOpen}
                                v-click-outside={{exclude: ['menuButton'], handler: this.handleOutsideClick}}
                                ref="dropdownMenu"
                                class="dropdown-items-container"
                                style={`top: ${this.menuY}px; left: ${this.menuX}px`}
                            >
                                <ul class="dropdown-items">
                                    {this.$slots.default()}
                                </ul>
                            </div>
                        </transition>
                        </teleport>
                </div>
            </div>
        )
    },
    mounted () {
        this.positionMenu()
    }
}
</script>