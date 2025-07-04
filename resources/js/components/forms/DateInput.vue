<script>
export default {
    props: {
        modelValue: {
            required: false,
            default: null
        },
        disabled: {
            required: false,
            default: false
        }
    },
    emits: [
        'update:modelValue',
        'change'
    ],
    data() {
        return {
        }
    },
    computed: {
        date: {
            get () {
                let date = null;
                if (!this.modelValue) {
                    date = null;
                } else if (this.modelValue instanceof Date) {
                    date = new Date(this.modelValue.getTime());
                } else {
                    date = new Date(Date.parse(this.modelValue));
                }
                return date && date.toISOString().split('T')[0];
            },
        } 
    },
    mounted() {
        // normalize date to be start of day and account for user timezone.
        this.accountForTimezone(this.formatDate(this.modelValue))
    },
    methods: {
        handleInput (event) {
            const value = event.target.valueAsDate;
            if (!value) {
                this.$emit('update:modelValue', value);
                return;
            }

            if (value.valueOf() > new Date(Date.parse('1900-01-01'))) { 
                this.$emit('update:modelValue', this.accountForTimezone(value).toISOString());
            }
        },
        accountForTimezone(dateString) {
            if (dateString === null) return dateString;
            
            const dateObj = new Date(Date.parse(dateString));
            const adjustedDate = new Date(dateObj.getTime() + dateObj.getTimezoneOffset()*60*1000);
            return adjustedDate;
        },
        formatDate(date) {
            if (date === null) {
                return null;
            }
            const d = new Date(date);
            // old-fashioned zero-padding
            const month = `0${(d.getMonth() + 1)}`.slice(-2);
            const day = `0${d.getDate()}`.slice(-2);
            const year = d.getFullYear();

            return [year, month, day].join('-');
        },
        focus() {
            this.$nextTick(() => {
                this.$refs.input.focus();
            });
        }
    }
}
</script>
<template>
  <div>
    <input 
      ref="input"
      type="date"
      class="date-input"
      :value="date" 
      :disabled="disabled"
      @input="handleInput"
      @change="$emit('change')"
    >
  </div>
</template>