<template>
    <div>
        <textarea rows="5" class="w-full" v-model="recipientsString"></textarea>
    </div>
</template>
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
    methods: {
    },
    created () {
        this.debounceInput = debounce((value) => {
            const pattern = new RegExp(/[\n,;]/);
            const newRecipients = value.split(pattern)
                                    .map(i => ({name: null, address: i.trim()}))
                                    .filter(i => {
                                        return i.address !== null
                                    });
            this.$emit('update:modelValue', newRecipients)
        }, 1000)
    }
}
</script>
