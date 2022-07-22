<script setup>
    const props = defineProps({
        breadcrumbs: {
            type: Array,
            default: () => []
        },
        title: {
            type: String || null,
            default: null
        }
    })
</script>
<template>
    <div class="screen -mt-6">
        <header class="screen-header bg-white pt-6">
            <breadcrumbs class="mx-4" :items="breadcrumbs" v-if="breadcrumbs.length > 0"/>
            <div class="flex justify-between items-end border-b pb-2 px-4">
                <slot name="header">
                    <div>
                        <h1>{{title}}</h1>
                        <slot name="header-dev"></slot>
                    </div>
                </slot>
                    <slot name="header-right"></slot>
            </div>
        </header>

        <div class="bg-gray-100 pt-8 p-4 flex flex-col space-y-4 bg-gray-100">
            <slot></slot>
        </div>
    </div>
</template>

<style scoped>
    h1 {
        @apply border-none m-0 p-0;
    }
</style>