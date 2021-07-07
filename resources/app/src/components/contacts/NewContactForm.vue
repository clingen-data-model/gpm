<template>
    <form-container @keyup.enter="save">
        <h2 class="block-title">Add a contact</h2>
        <div class="flex space-x-4">
            <div class="flex-1">
                <input-row 
                    type="text" 
                    v-model="newContact.email" 
                    :errors="errors.email"
                    placeholder="elenor@medplace.com" 
                    label="Email"
                ></input-row>
                <input-row 
                    type="text" 
                    v-model="newContact.first_name" 
                    :errors="errors.first_name"
                    placeholder="Elenor" 
                    label="First name"
                ></input-row>
                <input-row 
                    type="text" 
                    v-model="newContact.last_name" 
                    :errors="errors.last_name"
                    placeholder="Shelstrop" 
                    label="Last name"
                ></input-row>
                <input-row 
                    type="text" 
                    v-model="newContact.phone" 
                    :errors="errors.phone"
                    placeholder="1-555-867-5309" 
                    label="Phone"
                ></input-row>
                <button-row>
                    <button class="btn" @click="cancel">Cancel</button>
                    <button class="btn blue" @click="save">Save</button>
                </button-row>
            </div>
            <transition name="slide-fade">            
                <div class="pt-2 border-l pl-2 flex-1" v-if="suggestedPeople.length > 0">
                    <h5 class="font-bold border-b mb-1 pb-1">Matching people</h5>
                    <ul>
                        <li 
                            v-for="person in suggestedPeople" 
                            :key="person.uuid" class="flex justify-between my-2"
                            :class="{'text-gray-400': isAlreadyContact(person)}"
                            :title="isAlreadyContact(person) ? `This person is already a contact for this application` : ``"
                        >
                            {{person.name}} 
                            <button 
                                class="btn btn-xs" 
                                @click="addExistingPersonAsContact(person)"
                                v-if="!isAlreadyContact(person)"
                            >
                                Add as contact
                            </button>
                            <span v-else class="text-xs">Already a contact</span>
                        </li>
                    </ul>
                </div>
            </transition>
        </div>
    </form-container>
</template>
<script>
import {mapGetters} from 'vuex'
import is_validation_error from '../../http/is_validation_error'
import { v4 as uuid4 } from 'uuid';

export default {
    props: {
        
    },
    data() {
        return {
            createNew: false,
            newContact: {
                first_name: null,
                last_name: null,
                email: null,
                phone: null,
                role: null,
                uuid: null
            },
            errors: {}
        }
    },
    emits: [
        'canceled',
        'saved',
        'done'
    ],
    computed: {
        ...mapGetters({
            application: 'applications/currentItem',
            people: 'people/all',
        }),
        suggestedPeople() {
            if (this.newContact.email) {
                return this.people
                        .filter(p => p.email.includes(this.newContact.email));
            }
            return [];
        }
        
    },
    methods: {
        clearForm () {
            this.newContact = {
                first_name: null,
                last_name: null,
                email: null,
                phone: null,
                role: null,
                uuid: null
            }
        },
        clearErrors () {
            this.errors = {};
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
            this.$emit('done');
        },
        async save () {
            this.clearErrors();
            try {
                const data = {...this.newContact};
                data.uuid = uuid4();
                await this.$store.dispatch('people/createPerson', data);

                const getter = this.$store.getters['people/personWithUuid'];
                // const addedPerson = getter(data.uuid);


                await this.$store.dispatch('applications/addContact', {
                    application: this.application, 
                    contact: getter(data.uuid)
                });

                this.clearForm();
                this.$emit('saved');
                this.$emit('done');
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                    return;
                }
                throw error
            }
        },
        async addExistingPersonAsContact(person) {
            try {
                await this.$store.dispatch('applications/addContact', {application: this.application, contact: person});
                this.clearForm();
                this.$emit('saved');
                this.$emit('done');
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                    return;
                }
                throw error
            }
        },
        isAlreadyContact(person) {
            return this.application.contacts.map(c => c.id).includes(person.id)
        }
    },
    mounted () {
        this.$store.dispatch('people/getAll', {});
    }
}
</script>