<script>
import Popper from "vue3-popper"

export default {
    name: 'PopOver',
    components: {
        Popper
    }
}
</script>
<template>
  <Popper v-bind="$attrs" class="popover">
    <slot />
    <template #content="props">
      <slot name="content" v-bind="props" />
    </template>
  </Popper>
</template>