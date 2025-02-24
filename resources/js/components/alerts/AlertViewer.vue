<script>
import Alert from './Alert.vue'
export default {
    name: 'AlertViewer',
    components: {
      Alert
    }
}
</script>
<template>
    <div class="fixed right-4 top-8 w-64" style="z-index: 100">
      <ul>
        <transition-group name="slide-fade" tag="li">
          <Alert v-for="alert in $store.state.alerts.alerts" :key="alert.uuid" :alert="alert"></Alert>
        </transition-group>
      </ul>
    </div>

</template>
