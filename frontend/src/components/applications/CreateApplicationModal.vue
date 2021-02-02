<template>
    <div>
        <div>
            <button class="bg-blue-500 text-white rounded px-2 py-1 inline-block mb-2 hover:bg-blue-600" @click="open">
            Initiate an Expert Panel Application
            </button>
        </div>
        <div class="absolute top-0 left-0 right-0 bottom-0 bg-black opacity-50 flex justify-center content-center" 
            @keyup.esc="close"
            v-show="visible"
        >
            <div class="bg-white p-4 border opacity-100 h-1/4 my-auto">
                <button @click="close">Close</button>
                I am a modal
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        
    },
    data() {
        return {
            visible: false
        }
    },
    computed: {

    },
    methods: {
        open() {
            this.visible = true
        },
        close() {
            this.visible = false
        }
    }
}
</script>   