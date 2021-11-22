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
        </data-table>
    </div>
</template>
<script>
import {ref, onMounted} from 'vue'
import api from '@/http/api'

export default {
    name: 'InviteAdmin',
    props: {
        
    },
    setup () {
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
        ];

        let tableSort = {field: 'id', desc: false}

        return {
            invites,
            getInvites,
            fields,
            tableSort
        }
    }
}
</script>