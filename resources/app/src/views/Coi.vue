<template>
    <div>
        <pre>{{lastCoiResponse}}</pre>
        <card :title="verifying ? `Loading COI Form` : `COI Form not found`" v-if="!codeIsValid">
            <div v-if="!verifying">We couldn't find this COI.</div>
        </card>
        <card title="There's a problem" v-if="!groupMemberId">
            We can't seem to find your membership id.  Please try refreshing.
        </card>
        <card :title="coiTitle"  class="max-w-xl mx-auto relative" v-if="codeIsValid">
            <p>
                Review ClinGen’s <coi-policy-link />
            </p>
            <div class="relative">
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
        <note class="container">GroupMemberId: {{groupMemberId}}</note>
    </div>
</template>
<script>
import coiDef from '../../../surveys/coi.json'
import Survey from '@/survey'
import api from '@/http/api'
import is_validation_error from '@/http/is_validation_error';

const survey = new Survey(coiDef);

export default {
    name: 'Coi',
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
        membership () {
            return this.$store.getters
                    .currentUser
                    .person.memberships.find(m => {
                        return m.group.expert_panel
                            && m.group.expert_panel.coi_code === this.code
                    });
        },
        groupMemberId() {
            if (this.membership) {
                return this.membership.id;
            }

            return null;
        },
    },
    watch: {
        code: {
            immediate: true,
            handler () {
                this.initResponseValues()
            }
        }
    },
    methods: {
        initResponseValues() {
            if (this.membership && this.membership.cois.length > 0) {
                this.response = {...this.membership.cois[this.membership.cois.length -1].data};
            }

            return {}
        },

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
                    this.epName = response.data.display_name
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
                await this.$store.dispatch('forceGetCurrentUser');
                this.$router.push({name: 'Dashboard'});
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                } else {
                    this.$store.commit('pushError', `You can not complete a COI for ${this.epName} because you are not a member.`)
                }
            }
            this.saving = false;
        },
    },
    async mounted() {
        this.verifyCode();
        await this.$store.dispatch('getCurrentUser')
    }
}
</script>
