<style lang="postcss" scoped>
    li a {
        @apply text-black no-underline;
        color: black !important;
        text-decoration: none !important;
    }
    .item .label {
        @apply px-2 py-2 cursor-pointer border-t border-b border-transparent;
    }
    .item .label:hover {
        @apply bg-blue-50 border-blue-200
    }
    .cheveron {
        transition: 250ms all;
    }

    .open .cheveron {
        transform: rotate(90deg);
    }
    .text-smaller { 
        font-size: .95em;
    }
</style>
<template>
    <div class="item">
        <div
            v-if="item.hasContents"
        >
            <header 
                class="flex label"
                :class="{open: !collapsed}"
                @click="toggleContents"
            >
                <div>{{label}}</div>
                <icon-cheveron-right class="cheveron" />
            </header>
            <transition name="slide-fade-down">
                <ul v-if="this.item.hasContents" class="ml-4 text-smaller" v-show="!collapsed">
                    <li v-for="(item, idx) in contents" :key="idx">
                        <menu-item :item="item"></menu-item>
                    </li>
                </ul>
            </transition>
        </div>
        <div v-else class="label" @click="handleClick()">
            {{label}}
        </div>
    </div>
</template>
<script>

export default {
    name: "MenuItem",
    props: {
        item: {
            required: true,
            type: Object
        },
        collapsible: {
            type: Boolean,
            required: false,
            default: true
        }
    },
    data() {
        return {
            collapsed: false
        }
    },
    computed: {
        label () {
            return this.item.label
        },
        contents () {
            return this.item.contents
        },
        route () {
            return this.item.route
        }
    },
    methods: {
        collapse() {
            this.collapsed = true;
        },
        expand() {
            this.collapsed = false;
        },
        toggleContents() {
            this.collapsed = !this.collapsed
            this.handleClick()
        },
        handleClick () {
            if (this.item.hasHandler) {
                this.item.handler();
                return;
            }
            if (this.item.hasRoute) {
                this.$router.push(this.item.route);
            }
        }
    },
    mounted() {
        if (this.collapsible) {
            this.collapsed = true;
        }
    }
}
</script>