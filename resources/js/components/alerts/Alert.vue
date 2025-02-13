<template>
    <div
        class="flex justify-between rounded px-3 py-2 my-2 alert-item shadow-lg" 
        :class="`alert-${alert.type}`"
        >
        {{alert.message}}
        <button @click="clearAlertAndTimer" class="hover:underline">x</button>
    </div>
</template>
<script>
import {Alert} from '@/store/alerts'
export default {
    name: 'Alert',
    props: {
        alert: {
            required: true,
            type: Alert
        },
        timeout: {
            type: Number,
            required: false,
            default: 10
        }
    },
    data() {
        return {
            timer: null,
            countdown: 0,
            interval: null,
        }
    },
    computed: {

    },
    methods: {
        startTimer () {
            this.interval = setInterval( () => this.countdown -= 1, 1000)
            this.timer = setTimeout( () => {
                this.clearAlert();
            }, this.timeout*1000)
        },
        clearAlert () {
            this.$store.commit('removeAlert', this.alert.uuid);
            clearInterval(this.interval);
        },
        clearTimer () {
            if (this.timer) {
                clearTimeout(this.timer);
                clearInterval(this.interval);
            }
        },
        clearAlertAndTimer() {
            this.clearTimer();
            this.clearAlert();
        }
    },
    mounted() {
        this.startTimer();
        this.countdown = this.timeout;
    }
}
</script>
<style lang="postcss">
  .alert-info {
      @apply bg-blue-200 text-blue-800 border-blue-300 border;
  }
  .alert-success {
      @apply bg-green-200 text-green-800 border-green-300 border;
  }
  .alert-warning {
      @apply bg-yellow-200 text-yellow-800 border-yellow-300 border;
  }
  .alert-error {
      @apply bg-red-200 text-red-800 border-red-300 border;
  }
</style>