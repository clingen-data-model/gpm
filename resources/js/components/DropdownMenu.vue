<script>

import {Teleport, Transition} from 'vue'

export default {
    name: 'DropdownMenu',
    components: {
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
    // render () {
    //     const containerClass = [];
    //     return (
    //         <div class={containerClass.join(' ')}>
    //             <div class="dropdown" class={this.orientation}>
    //                 <div class="dropdown-label"
    //                     ref="menuButton"
    //                     onClick={this.toggleMenu}
    //                 >
    //                     <span v-show={!this.hideCheveron}>
    //                         <icon-cheveron-down />
    //                     </span>
    //                     {this.$slots.label ? this.$slots.label() : this.label}
    //                 </div>
    //                 <teleport to="body">
    //                     <transition name="slide-fade-down">
    //                         <div
    //                             v-show={this.menuOpen}
    //                             v-click-outside={{exclude: ['menuButton'], handler: this.handleOutsideClick}}
    //                             ref="dropdownMenu"
    //                             class="dropdown-items-container"
    //                             style={`top: ${this.menuY}px; left: ${this.menuX}px`}
    //                         >
    //                             <ul
    //                                 class="dropdown-items"
    //                                 onClick="{this.toggleMenu}"
    //                             >
    //                                 {this.$slots.default()}
    //                             </ul>
    //                         </div>
    //                     </transition>
    //                 </teleport>
    //             </div>
    //         </div>
    //     )
    // },
    mounted () {
        this.positionMenu();
        Array.from(this.$refs.dropdownMenu.getElementsByClassName('menu-item'))
            .forEach(item => {
                item.addEventListener('click', (event) => {
                    this.toggleMenu(event)
                });
            })
    },
    methods: {
        addItem (item) {
            this.menuItems.push(item)
        },
        handleOutsideClick() {
            this.menuOpen = false;
        },
        toggleMenu (event) {
            event.stopPropagation();
            this.menuOpen = !this.menuOpen
            if (this.menuOpen) {
                this.$refs.dropdownMenu.focus()
            }
        },
        renderItems () {
            return this.menuItems.map(i => i.render());
        },
        positionMenu () {
            const scrollLeft = window.pageXOffset || this.$refs.menuButton.scrollLeft;
            const scrollTop = window.pageYOffset || this.$refs.menuButton.scrollTop;
            const labelWidth = this.$refs.menuButton.offsetWidth;
            const labelHeight = this.$refs.menuButton.offsetHeight;
            const rect = this.$el.getBoundingClientRect();
            this.menuX = rect.left + scrollLeft - Number.parseFloat(getComputedStyle(document.documentElement).fontSize)*12 + labelWidth;
            this.menuY = rect.top + scrollTop + labelHeight - 14;
        }
    }
}
</script>

<template>
  <div>
    <div class="dropdown" :class="orientation">
      <div
        ref="menuButton"
        class="dropdown-label"
        @click.stop="toggleMenu"
      >
        <span v-show="!hideCheveron">
          <icon-cheveron-down />
        </span>
        <slot name="label">
          {{ label }}
        </slot>
      </div>
      <Teleport to="body">
        <Transition name="slide-fade-down">
          <div
            v-show="menuOpen"
            ref="dropdownMenu"
            v-click-outside="{exclude: ['menuButton'], handler: handleOutsideClick}"
            class="dropdown-items-container z-50"
            :style="{top: `${menuY}px`, left: `${menuX}px`}"
          >
            <ul
              class="dropdown-items"
              @click="toggleMenu"
            >
              <slot />
            </ul>
          </div>
        </Transition>
      </Teleport>
    </div>
  </div>
</template>

<style>
    .dropdown {
        @apply relative;
    }
    .dropdown.right {
        @apply top-0 right-0 text-right;
    }

    .dropdown > .dropdown-label {
        @apply flex flex-row-reverse align-middle -mb-3 pb-3 relative z-20 cursor-pointer outline-none;
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
        z-index: 100;
    }
    .dropdown-items > li {
        @apply bg-white hover:bg-blue-100 cursor-pointer py-2 px-2;
        @apply border border-b-0 border-gray-300;
    }
    .dropdown-items > li:first-child {
        @apply rounded-t;
    }
    .dropdown-items > li:last-child {
        @apply border-b rounded-b;
    }
</style>
