<template>
    <div class="flex my-3">
        <div class="w-36">
            <slot name="label" v-if="label">
                <label>{{label}}:</label>
            </slot>
        </div>
        <slot></slot>
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
}
</script>