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
        },
        isCurrentStep: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    data() {
        return {
            collapsed: true
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
        },
        group () {
            return this.$store.getters['groups/currentItemOrNew']
        },
        isStep () {
            return this.item.sections;
        },
        hasManySections () {
            return this.item.sections.length > 0;
        }
    },
    watch: {
        isCurrentStep: {
            immediate: true,
            handler (to) {
                if (this.collapsible) {
                    this.collapsed = true;
                }

                if (to) {
                    this.collapsed = false;
                }
            }            
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
            this.goToItem()
        },
        goToItem () {
            location.href = "#";
            location.href = `#${this.item.name}`
        }
    },
}
</script>
<template>
    <div class="item">
        <div
            v-if="isStep"
        >
            <header v-if="item.title"
                class="flex justify-between label"
                :class="{open: !collapsed, 'current-step': isCurrentStep}"
                @click="toggleContents"
            >
                <div class="font-bold">
                    {{ item.title }}
                    <icon-cheveron-right v-if="hasManySections" class="cheveron inline -mt-1" />
                </div>
                <badge v-if="item.isComplete(group)" color="green">
                    <icon-checkmark width="12" height="12"></icon-checkmark>
                </badge>
            </header>
            <transition name="slide-fade-down">
                <ul 
                    v-if="item.sections && hasManySections" 
                    v-show="!collapsed" :class="{'ml-4 text-smaller': item.title}"
                >
                    <li v-for="(section, idx) in item.sections" :key="idx">
                        <menu-item :item="section"></menu-item>
                    </li>
                </ul>
            </transition>
        </div>
        <div v-else class="label" @click="goToItem()">
            {{ item.title }}
        </div>
    </div>
</template>
<style scoped>
    li a {
        @apply text-black no-underline;
        color: black !important;
        text-decoration: none !important;
    }
    .item .label {
        @apply px-2 py-2 cursor-pointer border-t border-b border-transparent;
    }
    .item .label:hover {
        @apply bg-blue-50 border-blue-200;
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
    .item .label.current-step {
        @apply  bg-blue-100;
    }
</style>