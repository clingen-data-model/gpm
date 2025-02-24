<script>
import {debounce} from 'lodash-es'

export default {
    name: 'RecipientInput',
    props: {
        modelValue: {
            type: Array,
            default: () => []
        }
    },
    emits: [
        'update:modelValue',
    ],
    data() {
        return {
            newRecipients: []
        }
    },
    computed: {
        recipientsString: {
            get () {
                if (!this.modelValue) {
                    return '';
                }
                return this.modelValue.map(i => i.address).join("\n")
            },
            set (value) {
                this.debounceInput(value);
            }
        }

    },
    created () {
        this.debounceInput = debounce((value) => {
            const pattern = /[\n,;]/;
            const newRecipients = value.split(pattern)
                                    .map(i => ({name: null, address: i.trim()}))
                                    .filter(i => {
                                        return i.address !== null
                                    });
            this.$emit('update:modelValue', newRecipients)
        }, 1000)
    },
    methods: {
    }
}
</script>
<template>
  <div>
    <textarea v-model="recipientsString" rows="5" class="w-full" />
  </div>
</template>
