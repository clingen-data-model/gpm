<template>
    <div>
        <card :title="verifying ? `Loading COI Form` : `COI Form not found`" v-if="!codeIsValid">
            <div v-if="!verifying">We couldn't find this COI.</div>
        </card>
        <card :title="coiTitle"  class="w-3/4 mx-auto relative" v-if="codeIsValid">
            <div v-if="saved">
                Thanks for completing the conflict of interest form for {{application.name}}!
            </div>
            <div v-else>
                <div 
                    v-for="question in survey.questions"
                    :key="question.name"
                >
                    <transition name="slide-fade-down">
                        <input-row
                            :label="question.question_text"
                            :errors="errors[question.name]"
                            :vertical="true"
                            v-show="!question.show || (response[question.show.name] == question.show.value)"
                        >
                        
                            <textarea  v-if="question.type == 'text'"
                                class="w-full h-24"
                                v-model="response[question.name]"
                                :name="question.name"
                            ></textarea>

                            <div v-if="question.type == 'multiple-choice'">
                                <label v-for="option in question.options" :key="option.value" class="block">
                                    <input type="radio" 
                                        :value="option.value" 
                                        :name="question.name"
                                        v-model="response[question.name]">
                                    {{option.label}}
                                </label>
                            </div>

                            <input 
                                type="text" 
                                v-if="question.type == 'string'"
                                v-model="response[question.name]"
                                :name="question.name"
                            >
                        </input-row>
                    </transition>
                </div>
            </div>
            <div v-if="saving" class="absolute top-0 left-0 right-0 bottom-0">
                Saving your response...
            </div>
            <button-row :show-cancel="false" @submitClicked="storeResponse()"></button-row>
        </card>
    </div>
</template>
<script>
import coiDef from '../../../surveys/coi.json'
import Survey from '../survey'
import api from '../http/api'
import is_validation_error from '../http/is_validation_error';

const survey = new Survey(coiDef);

export default {
    props: {
        code: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            coiDef: coiDef,
            survey: survey,
            response: survey.getResponseTemplate(),
            errors: {},
            epName: null,
            verifying: false,
            saved: false,
            saving: false
        }
    },
    computed: {
        codeIsValid() {
            return this.epName !== null;
        },
        coiTitle() {
            return survey.name+' for '+this.epName;
        }
    },
    methods: {
        verifyCode() {
            this.verifying = true;
            api.get(`/api/coi/${this.code}/application`)
                .then(response => {
                    this.epName = response.data.name

                })
                .then(() => {
                    this.verifying = false;
                })
        },
        storeResponse() {
            this.saving = true;
            api.post(`/api/coi/${this.code}`, this.response)
                .then(() => {
                    this.saved = true;
                    setTimeout(() => this.$router.push({'name': 'home'}), 3000)
                })
                .catch(e => {
                    if (is_validation_error(e)) {
                        this.errors = e.response.data.errors
                    }
                })
                .then(() => {
                    this.saving = false;
                })
        }
    },
    mounted() {
        this.verifyCode();
    }
}
</script>