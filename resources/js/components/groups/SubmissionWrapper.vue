<script>
export default {
    name: 'SubmissionWrapper',
    props: {
        showControls: {
            type: Boolean,
            required: false,
            default: true
        }
    },
    emits: [
        'submitted',
        'canceled'
    ],
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
<template>
    <div>
        <slot></slot>
        <transition name="fade-slide-down">
            <button-row 
                v-show="showControls"
                submit-text="Save" 
                @submitted="$emit('submitted')"
                @canceled="$emit('canceled')"
            />
        </transition>
    </div>
</template>