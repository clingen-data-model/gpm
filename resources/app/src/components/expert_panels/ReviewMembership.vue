<script setup>
    import { computed, ref, watch } from 'vue'
    import axios from 'axios'
    import PubmedCitation from '../PubmedCitation.vue';

    const props = defineProps({
        members: {
            required: true,
            type: Array
        }
    });

    const fields = ['name', 'credentials', 'expertise', 'institution']

    const members = ref([]);

    watch(() => props.members, to => {
        members.value = [...to];
    }, {immediate: true})

    const getPublications = async member => {
        const baseUri = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils';
        const searchUrl = `${baseUri}/esearch.fcgi?db=pubmed&term=${member.last_name},+${member.first_name}[author]&retmode=json&retmax=10`;
        const entriesUrl = `${baseUri}/esummary.fcgi?db=pubmed&retmode=json`

        return axios.get(searchUrl)
            .then(rsp => {
                member.pubCount = rsp.data.esearchresult.count
                if (rsp.data.esearchresult.count > 0) {
                    axios.get(`${entriesUrl}&id=${rsp.data.esearchresult.idlist.join(',')}`)
                        .then(sumRsp => {
                            member.publications = [];
                            if (!sumRsp.data.result) {
                                console.log(sumRsp.data)
                                return;
                            }
                            member.publications = Object.values(sumRsp.data.result).filter(i => !Array.isArray(i));
                        })
                }
            });
    }

    const memberGroups = computed(() => [
        {
            title: 'Leadership',
            members: members.value.filter(m => m.roles.includes('chair'))
        },
        {
            title: 'Coordination',
            members: members.value.filter(m => m.roles.includes('coordinator'))
        },
        {
            title: 'Biocuration',
            members: members.value.filter(m => m.roles.includes('biocurator'))
        },
        {
            title: 'Expertise',
            members: members.value.filter(m => m.roles.includes('expert'))
        }
    ])
</script>
<template>
    <div>
        <table>
            <template v-for="g in memberGroups" :key="g.title">
                <thead>
                    <tr>
                        <th colspan="6" class="bg-white border-0 pl-0 pb-1 pt-3">
                            <span class="text-lg">{{g.title}}</span>
                            &nbsp;
                            <badge size="xxs">{{g.members.length}}</badge>
                        </th>
                    </tr>
                    <tr class="text-sm">
                        <th v-for="key in fields" :key="key">
                            {{key}}
                        </th>
                        <th>Publications</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <tr v-for="m in g.members" :key="m.id">
                        <td v-for="key in fields" :key="key">
                            {{m[key]}}
                        </td>
                        <td>
                            <popper v-if="m.pubCount" arrow hover class="cursor-pointer">
                                <template v-slot:content>
                                    <h5>Publications</h5>
                                    <ul class="list-disc ml-4">
                                        <li v-for="pub in m.publications" :key="pub.uid" class="mb-1">
                                            <a :href="`https://pubmed.ncbi.nlm.nih.gov/${pub.uid}`" target="pubmed" class="text-black">
                                                <PubmedCitation :summary="pub" />
                                            </a>
                                        </li>
                                    </ul>
                                    <a v-if="m.pubCount > 10"
                                        :href="`https://pubmed.ncbi.nlm.nih.gov/?term=${m.last_name},+${m.first_name}%5BAuthor%5D`"
                                        target="pubmed"
                                    >+{{m.pubCount - 10}} more.</a>
                                </template>
                                <badge size="xxs">{{m.pubCount}}</badge>
                            </popper>
                            <button v-else class="btn btn-xs" @click="getPublications(m)">See Publications</button>
                        </td>
                    </tr>
                </tbody>
            </template>
        </table>
    </div>
</template>
