<template>
    <div v-if="filteredObj">
        <dictionary-row v-for="(value, key) in filteredObj" :key="key" :label="key">
            {{value}}
        </dictionary-row>
    </div>
</template>
<script>
import { formatDate } from '@/date_utils'

export default {
    name: 'ObjectDictionary',
    props: {
        obj: {
            type: Object,
            required: true
        },
        except: {
            type: Array,
            required: false
        },
        only: {
            type: Array,
            required: false
        },
        order: {
            type: Array,
            required: false,
        },
        show: {
            type: Array,
            required: false
        },
        dates: {
            type: Array,
            required: false,
            default: () => []
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        filteredObj() {
            if (!this.obj) {
                return {}
            }
            if (this.show) {
                return this.getShow()
            }
            if (this.except) {
                return this.getExcept();
            }
            if (this.only) {
                return this.getOnly(this.only)
            }
            return this.obj
        }
    },
    methods: {
        getOnly(only) {
            const show = {};
            only.forEach(key => {
                const formatted = this.format(key, this.obj[key]);
                show[this.titleCase(key)] =  formatted || '--'
            })
            return show
        },
        getShow() {
            return this.getOnly(this.show);
        },
        getExcept() {
            const except = {}
            Object.keys(this.obj)
                .forEach(key => {
                    if (!this.except.includes(key)) {
                        except[key] = this.format(this.obj[key]);
                    }
                })
            return except;
        },
        titleCase(label) {
            return label.split('_').map(word => {
                    return word.charAt(0).toUpperCase()+word.substr(1)
                }).join(' ');
        },
        format(key, value) {
            if (!value) {
                return value;
            }

            if (this.dates.includes(key)) {
                return formatDate((new Date(Date.parse(value))))
            }


            return value;
        }
    }
}
</script>