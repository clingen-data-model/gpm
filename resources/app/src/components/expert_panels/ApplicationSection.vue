<script>
export default {
    name: 'ApplicationSection',
    props: {
        title: {
            type: String,
            required: false
        }
    }
}
</script>
<template>
    <div class="application-section">
        <header class="mb-2">
            <slot name="title">
                <h2>{{title}}</h2>
            </slot>
        </header>
        <div>
            <slot></slot>
        </div>
    </div>
</template>

<style lang="postcss" scoped>
    .application-section {
        @apply my-4 border border-gray-300 bg-white p-4
    }
    .application-section:first-child {
        @apply mt-0;
    }
</style>