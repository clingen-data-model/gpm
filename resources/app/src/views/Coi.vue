<template>
    <div>
        <card :title="verifying ? `Loading COI Form` : `COI Form not found`" v-if="!codeIsValid">
            <div v-if="!verifying">We couldn't find this COI.</div>
        </card>
        <card :title="coiTitle"  class="w-3/4 mx-auto relative" v-if="codeIsValid">
            <div v-if="saved">
                Thanks for completing the conflict of interest form for {{epName}}!
                <small v-if="$store.getters.isAuthed">
                    <p>You'll be redirected back in {{redirectCountdown}} seconds.</p>
                    <button @click="$router.go(-1)" class="text-blue-500">Go back</button>
                </small>
            </div>
            <div v-else class="relative">
                <div 
                    v-for="question in survey.questions"
                    :key="question.name"
                            :class="question.class"
                >
                    <transition name="slide-fade-down">
                        <input-row
                            :label="question.question_text"
                            :errors="errors[question.name]"
                            :vertical="true"
                            v-show="showQuestion(question)"
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
                <button-row :show-cancel="false" @submitClicked="storeResponse()">
                    <slot v-if="saving">
                        Saving...
                    </slot>
                </button-row>
            </div>
        </card>
    </div>
</template>
<script>
import coiDef from '../../../surveys/coi.json'
import Survey from '@/survey'
import api from '@/http/api'
import is_validation_error from '@/http/is_validation_error';

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
            saving: false,
            redirectCountdown: 5
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
        showQuestion(question) {
            if (!question.show) {
                return true;
            }
            if (Array.isArray(question.show.value)) {
                console.log(question.show.value);
                return question.show.value.indexOf(this.response[question.show.name]) > -1;
            }
            return (this.response[question.show.name] == question.show.value);
        },
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
        async storeResponse() {
            this.saving = true;
            try {
                await this.$store.dispatch('storeCoi', {code: this.code, coiData: this.response});
                this.saved = true;
                if (this.$store.getters.isAuthed) {
                    setInterval(() => {this.redirectCountdown--}, 1000)
                    setTimeout(() => {
                        this.$router.go(-1)
                    }, 5000)
                }
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                }
            }
            this.saving = false;
        }
    },
    mounted() {
        this.verifyCode();
    }
}
</script>