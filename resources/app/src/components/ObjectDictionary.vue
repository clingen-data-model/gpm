<template>
    <div v-if="filteredObj">
        <dict-row v-for="(value, key) in filteredObj" :key="key" :label="key">
            {{value}}
        </dict-row>
    </div>
</template>
<script>
import { formatDate } from '../date_utils'

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
            return this.obj
        }
    },
    methods: {
        getShow() {
            const show = {};
            this.show.forEach(key => {
                const formatted = this.format(key, this.obj[key]);
                show[this.titleCase(key)] =  formatted || '--'
            })
            return show
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