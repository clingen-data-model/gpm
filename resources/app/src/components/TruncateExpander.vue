<template>
    <div>
        <div v-if="shouldBeTruncated" :class="{'xl:flex justify-between': !showComplete}">
            <div>
                {{truncatedValue}}<span v-show="!showComplete">...&nbsp;&nbsp;</span><span v-show="showComplete">{{restOfValue}}</span>
            </div>
            <button 
                class="border-0 text-blue-500 underline text-xs" 
                @click="showComplete=!showComplete"
            >
                {{buttonLabel}}
            </button>
        </div>
        <div v-else>
            {{value}}
        </div>
    </div>
</template>
<script>
export default {
    name: 'TruncateExpander',
    props: {
        value: {
            type: String,
            required: true
        },
        truncateLength: {
            type: Number,
            default: 150
        }
    },
    data() {
        return {
            showComplete: false
        }
    },
    computed: {
        shouldBeTruncated() {
            return this.value.length > this.truncateLength;
        },
        truncatedValue () {
            return this.value.substring(0, (this.truncateLength-15));
        },
        restOfValue() {
            return this.value.substring((this.truncateLength-15+1));
        },
        buttonLabel() {
            return this.showComplete ? 'Less' : 'More'
        }
    },
    methods: {

    }
}
</script>