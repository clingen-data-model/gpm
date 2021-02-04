<template>
    <div>
        <h4 class="text-xl font-semibold pb-2 border-b mb-4">Initiate Application</h4>
        <input-row label="Working Name">
            <input type="text" v-model="this.app.working_name" placeholder="A recognizable name">
        </input-row>
        <input-row label="CDWG">
            <select v-model="app.cdwg_id">
                <option :value="null">Select...</option>
                <option v-for="cdwg in cdwgs" :key="cdwg.id" :value="cdwg.id">{{cdwg.name}}</option>
            </select>
        </input-row>
        <input-row label="EP Type">
            <div>
                <div
                    v-for="epType in epTypes" 
                    :key="epType.id" 
                >
                    <input 
                        type="radio" 
                        :value="epType.id" 
                        v-model="app.ep_type_id"
                        :id="`ep-${epType.id}-radio`"
                    >
                    &nbsp;
                    <label :for="`ep-${epType.id}-radio`">{{epType.name}}</label>
                </div>
            </div>
        </input-row>
        <input-row>
            <div>
                <div>
                    <input type="checkbox" v-model="showInitiationDate" id="show-initiation-checkbox">
                    &nbsp;
                    <label for="show-initiation-checkbox">Backdate this initiation</label>
                </div>
                <input-row label="Initiation Date" v-show="showInitiationDate">
                    <input type="date" v-model="app.date_initiated">
                </input-row>
            </div>
        </input-row>
        <div class="mt-4 border-t pt-2 flex justify-between">
            <button class="bg-white text-black rounded border px-2 py-1" @click="cancel">Cancel</button>
            <button class="bg-blue-500 text-white rounded px-2 py-1 mr-2" @click="save">Initiate Application</button>
        </div>
    </div>
</template>
<script>
export default {
    name: 'CreateApplicationForm',
    props: {
        
    },
    emits: [
        'canceled',
        'saved',
    ],
    data() {
        return {
            visible: false,
            showInitiationDate: false,
            app: {
                working_name: null,
                cdwg_id: null,
                ep_type_id: null,
                date_initiated: new Date()
            },
            cdwgs: [
                { name: 'test', id: 1},
                { name: 'beans', id: 2},
            ],
            epTypes: [
                {name: 'GCEP', id: 1},
                {name: 'VCEP', id: 2}
            ]
        }
    },
    computed: {
    },
    methods: {
        cancel() {
            this.initAppData();
            this.$emit('canceled');
        },
        save() {
            this.initAppData();
            this.$emit('saved');
        },
        initAppData() {
            this.app = {
                working_name: null,
                cdwg_id: null,
                ep_type_id: null,
                date_initiated: new Date()
            };
        }
    }
}
</script>   