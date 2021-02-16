<template>
    <div>
        <button class="btn mb-2">Upload a new version</button>
        <data-table :fields="filteredFields" :data="data" :sort="{field: fields[0], desc: true}"></data-table>
    </div>
</template>
<script>
export default {
    props: {
        application: {
            type: Object,
            required: true
        },
        documentTypeId: {
            type: Number,
            required: true
        },
        getsReviewed: {
            type: Boolean,
            required: false,
            default: true
        }
    },
    data() {
        return {
            fields: [
                {
                    name: 'version',
                    type: Number
                },
                {
                    name: 'filename',
                    type: String
                },
                {
                    name: 'date_received',
                    type: Date
                },
                {
                    name: 'date_reviewed',
                    type: Date
                }
            ],
            data: [
                {
                    version: 1,
                    filename: 'test 1.doc',
                    date_received: new Date(Date.parse('2021-01-01')),
                    date_reviewed: new Date(Date.parse('2021-01-03'))
                },
                {
                    version: 2,
                    filename: 'test 2.doc',
                    date_received: new Date(Date.parse('2021-02-01'))
                },
            ]
            
        }
    },
    computed: {
        filteredFields () {
            let clonedFields = [...this.fields]
            if (!this.getsReviewed) {
                const idx = clonedFields.findIndex(f => f && f.name == 'date_reviewed')
                clonedFields = clonedFields.slice(0,3)
            }
            return clonedFields
        }
    },
    methods: {

    }
}
</script>