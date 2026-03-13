<script>
import { useAuthStore } from '@/stores/auth';
export default {
    computed: {
        currentUser() {
            return useAuthStore().currentUser;
        }
    },
    mounted () {
        useAuthStore().getCurrentUser();
    }
}
</script>
<template>
  <div>
    <pre>{{ currentUser }}</pre>
  </div>
</template>