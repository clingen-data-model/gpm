<script setup>
    import { computed, ref, watch } from 'vue'
    import axios from 'axios'
import {hasPermission} from '../../auth_utils';

    const props = defineProps({
        members: {
            required: true,
            type: Array
        },
    });

    const loadPubmed = computed(() => hasPermission('ep-applications-approve'))

    const fields = ref(['name', 'credentials', 'expertise', 'institution']);
    if (hasPermission('ep-applications-manage')) {
        fields.value.push('coi_completed');
    }

    const members = ref([]);
    const chairs = computed(() => members.value.filter(m => m.roles.includes('chair')));
    const experts = computed(() => members.value.filter(m => m.roles.includes('expert')));

    const memberGroups = computed(() => [
        {
            title: 'Leadership',
            members: chairs.value
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
            members: experts.value
        }
    ])

    const delay = (ms = 1000) => new Promise((r) => setTimeout(r, ms));
    const getPublications = async member => {
        const baseUri = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils';
        const searchUrl = `${baseUri}/esearch.fcgi?db=pubmed&term=${member.last_name},+${member.first_name}[author]&retmode=json&retmax=0`;
        const entriesUrl = `${baseUri}/esummary.fcgi?db=pubmed&retmode=json`

        return axios.get(searchUrl)
            .then(rsp => {
                member.pubCount = rsp.data.esearchresult.count
            })
            .catch(async error => {
                console.log(error);
                if (error.response.status == 429) {
                    console.log('wait and try again')
                    await delay();
                    getPublications(member);
                }
            });
    }

    watch(() => props.members, async to => {
        if (!to) {
            return;
        }

        members.value = [...to];

        members.value.sort((a, b) => {
            if (a.roles.includes('chair') && !b.roles.includes('chair')) {
                return -1;
            }
            if (!a.roles.includes('chair') && b.roles.includes('chair')) {
                return 1;
            }

            if (a.roles.includes('expert') && !b.roles.includes('expert')) {
                return -1;
            }
            if (!a.roles.includes('expert') && b.roles.includes('expert')) {
                return 1;
            }

            return 0;
        })

        for(let idx in members.value.filter(m => m.roles.includes('chair') || m.roles.includes('expert'))) {
            if (loadPubmed.value) {
                getPublications(members.value[idx])
                await delay(500);
            }
        }
    }, {immediate: true})

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
                            <!-- <popper v-if="m.pubCount" arrow hover class="cursor-pointer">
                                <template v-slot:content>
                                    <h5>Publications</h5>
                                    <div v-if="m.pubCount == 0">
                                        None.
                                    </div>
                                    <div v-else>
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
                                    </div>
                                </template>
                                <badge size="xxs">{{m.pubCount}}</badge>
                            </popper> -->
                            <div v-if="m.pubCount">
                                <popper v-if="m.pubCount > 0" content="Go to PubMed results." hover arrow placement="left">
                                    <a :href="`https://pubmed.ncbi.nlm.nih.gov/?term=${m.last_name},+${m.first_name}%5BAuthor%5D`"
                                        target="pubmed"
                                        >
                                        <badge size="xxs">{{m.pubCount}}</badge>
                                    </a>
                                </popper>
                                <badge v-else size="xxs">{{m.pubCount}}</badge>
                            </div>
                            <button v-else class="btn btn-xs" @click="getPublications(m)">Get</button>
                        </td>
                    </tr>
                </tbody>
            </template>
        </table>
    </div>
</template>