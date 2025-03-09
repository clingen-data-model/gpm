<script>

const baseUrl = 'https://docs.google.com/document/d/1adqRiW8UYTOKpIfBPWDcA-tO7I9Q5UGGYHVpTP9n-6E';

export default {
    name: 'FAQLink',
    props: {
        hash: {
            default: () => null
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        url () {
            if (this.hash) {
                return baseUrl+this.hash
            }
            return baseUrl;
        }
    },
    methods: {

    }
}
</script>
<template>
  <a
    v-bind="$attrs"
    :href="url" 
    target="FAQ"
  >
    <slot>FAQ</slot>  
  </a>
</template>