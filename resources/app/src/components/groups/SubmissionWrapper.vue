<template>
    <div>
        <slot></slot>
        <transition name="fade-slide-down">
            <button-row 
                v-show="showControls"
                @submitted="$emit('submitted')" 
                @canceled="$emit('canceled')"
                submit-text="Save"
            />
        </transition>
    </div>
</template>
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