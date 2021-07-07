<template>
    <div>
        <slot name="header">
            <div class="bg-gray-200 px-4 py-2 sm:flex justify-between border border-gray-300 rounded-t" v-if="title">
                <slot name="title">
                     <h2>{{title}}</h2>
                </slot>
                <slot name="header-right"></slot>
            </div>
        </slot>
        <div class="bg-white border border-t-0 border-gray-300 rounded-b  px-4 py-3 overflow-x-auto">
            <slot></slot>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        title: {
            type: String,
            required: false
        }
    },
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>