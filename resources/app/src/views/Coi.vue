<template>
    <div>
        <card :title="verifying ? `Loading COI Form` : `COI Form not found`" v-if="!codeIsValid">
            <div v-if="!verifying">We couldn't find this COI.</div>
        </card>
        <card :title="coiTitle"  class="max-w-xl mx-auto relative" v-if="codeIsValid">
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
                                <label v-for="option in question.options" :key="option.value" class="mb-1">
                                    <input type="radio" 
                                        :value="option.value" 
                                        :name="question.name"
                                        v-model="response[question.name]"
                                    >
                                    <div>{{option.label}}</div>
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
        },
        groupMemberId() {
            const membership = this.$store.getters.currentUser.memberships.find(m => {
                return m.group.expert_panel
                    && m.group.expert_panel.coi_code === this.code
            });

            console.log(membership);

            if (membership) {
                return membership.id;
            }

            throw new Error('Could not find membership to EP that matches the code.');
        }
    },
    methods: {
        showQuestion(question) {
            if (!question.show) {
                return true;
            }
            if (Array.isArray(question.show.value)) {
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
                await this.$store.dispatch(
                    'storeCoi', 
                    {
                        code: this.code, 
                        groupMemberId: this.groupMemberId,
                        coiData: this.response, 
                    }
                );
                this.saved = true;
                this.$store.dispatch('forceGetCurrentUser');
                if (this.$store.getters.isAuthed) {
                    this.countDownToRedirect()
                }
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                } else {
                    this.$store.commit('pushError', `You can not complete a COI for ${this.epName} because you are not a member.`)
                }
            }
            this.saving = false;
        },
        countDownToRedirect () {
            setInterval(() => {this.redirectCountdown--}, 1000)
            setTimeout(() => {
                this.$router.go(-1)
            }, 5000)
        }
    },
    async mounted() {
        this.verifyCode();
        await this.$store.dispatch('getCurrentUser')
    }
}
</script>