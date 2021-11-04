<template>
    <div>
        <input-row 
            label="Long Base Name" 
            v-model="ep.long_base_name" 
            placeholder="Long base name"
            :errors="errors.long_base_name"
            @change="saveChanges()"
        ></input-row>
        <input-row 
            label="Short Base Name" 
            v-model="ep.short_base_name" 
            placeholder="Short base name"
            :errors="errors.short_base_name"
            @change="saveChanges()"
        ></input-row>
        <input-row 
            label="Affiliation ID" 
            v-model="ep.affiliation_id" 
            :placeholder="affiliationIdPlaceholder"
            :errors="errors.affiliation_id"
            @change="saveChanges()"
        ></input-row>
        <input-row label="CDWG" :errors="errors.cdwg_id">
            <select name="" id="" v-model="ep.cdwg_id" @change="saveChanges()">
                <option :value="cdwg.id" v-for="cdwg in cdwgs" :key="cdwg.id">{{cdwg.name}}</option>
            </select>
        </input-row>

    </div>
</template>
<script>
export default {
    name: 'BasicInfoForm',
    props: {
        
    },
    data() {
        return {
            errors: {},
            ep: {
                long_base_name: 'Brain Malformations',
                short_base_name: 'Brain Malformations',
                affiliation_id: 50020,
                cdwg_id: 1
            },
            cdwgs: [
                {id: 1, name: 'Cardiomyopathy'},
                {id: 2, name: 'ID/Autism'}
            ]
        }
    },
    computed: {
        affiliationIdPlaceholder () {
            return 50000
        }
    },
    methods: {
        saveChanges(){
            console.log('save')
        }
    }
}
</script>