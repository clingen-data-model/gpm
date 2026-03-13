<script>
import Alert from './Alert.vue'
import { useAlertsStore } from '@/stores/alerts'
export default {
    name: 'AlertViewer',
    components: {
      Alert
    },
    setup() {
        return {
            alertsStore: useAlertsStore(),
        }
    },
}
</script>
<template>
  <div class="fixed right-4 top-8 w-64" style="z-index: 100">
    <ul>
      <transition-group name="slide-fade" tag="li">
        <Alert v-for="alert in alertsStore.alerts" :key="alert.uuid" :alert="alert" />
      </transition-group>
    </ul>
  </div>
</template>
