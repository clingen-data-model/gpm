<template>
    <div>
        <note>Admin</note>
        <h1>Invites</h1>
        <data-table
            :data="invites"
            :fields="fields"
            v-model:sort="tableSort"
            class="text-sm"
            v-remaining-height
        >
            <template v-slot:cell-actions="{item}">
                <!-- <pre>{{item}}</pre> -->
                <button  
                    v-if="!item.redeemed_at"
                    class="btn btn-xs"
                    @click="goToOnboarding(item)"
                >
                    Redeem
                </button>
            </template>
        </data-table>
    </div>
</template>
<script>
import {ref, computed, onMounted} from 'vue'
import {useRouter} from 'vue-router'
import api from '@/http/api'

export default {
    name: 'InviteAdmin',
    props: {
        
    },
    setup () {
        const router = useRouter();

        let invites = ref([]);
        const getInvites = async () => {
            invites.value = await api.get('/api/people/invites').then(rsp => rsp.data.data);     
        }

        onMounted(() => {
            getInvites();
        })

        const fields = [
            {
                name: 'id',
                sortable: true,
                type: Number
            },
            {
                name: 'person.name',
                label: 'Name',
                sortable: true,
                type: String
            },
            {
                name: 'person.email',
                label: 'Email',
                sortable: true,
                type: String
            },
            {
                name: 'redeemed_at',
                sortable: true,
                type: Date
            },
            {
                name: 'inviter',
                sortable: true,
                type: String,
                resolveValue: (invite) => {
                    return invite.inviter ? invite.inviter.name : null
                }
            },
            {
                name: 'code',
                sortable: false,
                type: String
            }
            // {
            //     name: 'actions',
            //     label: '',
            //     sortable: false,
            // },
        ];

        let tableSort = {field: 'id', desc: false}

        const goToOnboarding = (invite) => {
            console.log(invite)
            console.log(`/people/invites/${invite.code}`)
            // console.log(router.resolve({name: 'InviteWithCode', params: {code: invite.coi_code}}).href);
        }

        return {
            invites,
            getInvites,
            fields,
            tableSort,
            goToOnboarding
        }
    }
}
</script>