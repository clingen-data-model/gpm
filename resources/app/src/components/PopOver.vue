<template>
    <popper v-bind="$attrs" class="popover">
        <slot />
        <template #content="props">
            <slot name="content" v-bind="props"></slot>
        </template>
    </popper>
</template>
<script>
import Popper from "vue3-popper"

export default {
    name: 'PopOver',
    components: {
        Popper
    }
}
</script>