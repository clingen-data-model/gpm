<style lang="postcss">
    .btn-delete {
       @apply cursor-pointer inline-block p-0.5 rounded-full border border-red-500 text-red-500 align-text-top outline-none;
    }
    .btn-delete:active, .btn-delete:focus {
        @apply outline-none;
    }
    .btn-delete:active{
        @apply border-red-900 text-red-900 bg-gray-100 outline-none;
        transform: scale(.95)
    }
</style>
<template>
    <div>
        {{contact.name}}
        -
        <a :href="`mailto:${contact.email}`">{{contact.email}}</a>
        -
        {{contact.phone}}
        
        <button class="btn-delete">
            <icon-close @click="destroy" :height="8" :width="8"></icon-close>
        </button>
    </div>
</template>
<script>
import IconClose from '../icons/IconClose'

export default {
    components: {
        IconClose
    },
    props: {
        contact: {
            type: Object,
            required: true
        }
    },
    emits: [
        'deleted'
    ],
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {
        destroy () {
            if(confirm('Do you really want to delete this contact?')) {
                console.log('delete contact!!!')
            }
        }
    }
}
</script>