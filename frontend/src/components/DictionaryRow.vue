<template>
    <div>
        <div class="flex my-2">
            <div class="w-36">
                <slot name="label" v-if="label">
                    <label>{{label}}:</label>
                </slot>
            </div>
            <slot>
            </slot>
        </div>
    </div>

</template>
<script>
export default {
    props: {
        label: {
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