<template>
    <div>
        <input 
            ref="input"
            type="date"
            class="date-input"
            :value="formattedDate" 
            @input="handleDateInput"
            @change="$emit('change')"
            :disabled="disabled"
        >
    </div>
</template>
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
        formattedDate () {
            if (!this.modelValue) {
                return null;
            }
            const fmtdt = this.formatDate(this.modelValue)
            return fmtdt
        }
    },
    methods: {
        handleDateInput(event) {
            return this.accountForTimezone(event.target.value);
        },
        accountForTimezone(dateString) {
            if (dateString === null) {
                return dateString;
            }
            const date = new Date(Date.parse(dateString));
            const adjustedDate = new Date(date.getTime() + date.getTimezoneOffset()*60*1000);
            this.$emit('update:modelValue', adjustedDate.toISOString())
        },
        formatDate(date) {
            if (date === null) {
                return null;
            }
            let d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2) 
                month = '0' + month;
            if (day.length < 2) 
                day = '0' + day;

            return [year, month, day].join('-');
        },
        focus() {
            this.$nextTick(() => {
                this.$refs.input.focus();
            });
        }
    },
    mounted() {
        // normalize date to be start of day and account for user timezone.
        this.accountForTimezone(this.formatDate(this.modelValue))
    }
}
</script>