<template>
    <div>
        <p class="text-lg font-bold">Please fill out your profile</p>
        <data-form 
            :fields="fields" 
            v-model="profile" 
            :errors="errors"
        ></data-form>
        <div class="flex flex-row-reverse">
            <button class="btn blue" @click="save">Next ></button>
        </div>
    </div>
</template>
<script>
import Person from '@/domain/person'

export default {
    name: 'ProfileForm',
    props: {
        person: {
            type: Person,
            required: true
        }
    },
    data() {
        return {
            errors: {},
            profile: {},
            fields: [
                { name: 'first_name'},
                { name: 'last_name'},
                { name: 'email'},
                { name: 'phone'},
                {
                    name: 'institution_id',
                    label: 'Institution',
                    type: 'select',
                    options: [
                        {value: 1, label: 'beans'},
                        {value: 2, label: 'monkeys'},
                        {value: 3, label: 'beer'}
                    ]
                },
                { name: 'credentials', type: 'textarea'},
                { name: 'biography', type: 'textarea'},
            ]
        }
    },
    computed: {

    },
    methods: {
        initProfile () {
            this.profile = {...this.person.attributes};
        },
        save () {
            this.$emit('saved');
        }
    },
    mounted () {
        this.initProfile()
    }
}
</script>