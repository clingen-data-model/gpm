<template>
    <div>
        <table>
            <thead>
                <tr>
                    <th>Gene</th>
                    <th>In ClinVar</th>
                    <th>Approved in VCI<br><small>(Not in ClinVar)</small></th>
                    <th>Provisionally approved</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="gene in workingCopy.variant_counts" :key="gene.gene">
                    <td>
                        {{gene.gene}}
                    </td>
                    <td><input type="number" v-model="gene.in_clinvar"></td>
                    <td><input type="number" v-model="gene.gci_approved"></td>
                    <td><input type="number" v-model="gene.provisionaly_approved"></td>
                </tr>
                <tr>
                    <td><input type="text" v-model="newGene.gene" placeholder="HGNC gene symbol"/></td>
                    <td><input type="number" v-model="newGene.in_clinvar" placeholder="total #"></td>
                    <td><input type="number" v-model="newGene.gci_approved" placeholder="total #"></td>
                    <td><input type="number" v-model="newGene.provisionaly_approved" placeholder="total #"></td>
                </tr>
            </tbody>
        </table>
        <button class="btn btn-xs" @click="addGene">AddGene</button>
    </div>
</template>
<script>
import setupWorkingMirror from '@/composables/setup_working_mirror'
import {clone} from 'lodash'

export default {
    name: 'GeneCurationTotals',
    props: {
        modelValue: {
            required: true,
            type: Object
        },
        errors: {
            required: true,
            type: Object
        }
    },
    data () {
        return {
            newGene: {}
        }
    },
    computed: {
        lastYear () {
            return (new Date()).getFullYear() -1;
        }
    },
    methods: {
        addGene () {
            if (!this.newGene.gene) {
                return;
            }
            if (!this.workingCopy.variant_counts) {
                this.workingCopy.variant_counts = [];
            }
            this.workingCopy.variant_counts.push(clone(this.newGene));
            this.newGene = {};
        }
    },
    setup (props, context) {
        const { workingCopy } = setupWorkingMirror(props, context);
        return {
            workingCopy
        };
    },
}
</script>