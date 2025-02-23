<template>
    <div>
        <card :title="verifying ? `Loading COI Form` : `COI Form not found`" v-if="!codeIsValid">
            <div v-if="!verifying">We couldn't find this COI.</div>
        </card>
        <card title="There's a problem" v-if="!groupMemberId">
            We can't seem to find your membership for this group id.  Please try refreshing.
        </card>

        <card :title="coiTitle"  class="mx-auto relative" style="max-width:800px" v-else-if="codeIsValid">
            <CoiPolicy />
            <hr>
            <h2>COI</h2>
            <div class="relative">
                <div
                    v-for="question in survey.questions"
                    :key="question.name"
                    :class="question.class"
                >
                    <transition name="slide-fade-down">
                        <div v-if="question.type == 'content'">
                            <MarkdownBlock
                                :markdown="question.content"
                            />
                        </div>
                        <input-row
                            v-else
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
import CoiPolicy from '@/components/coi/CoiPolicy.vue'
import MarkdownBlock from '@/components/MarkdownBlock.vue'
import api from '@/http/api'
import is_validation_error from '@/http/is_validation_error';
import Survey from '@/survey'
import coiDef from '../../surveys/coi_v2.json'

const survey = new Survey(coiDef);

export default {
    name: 'Coi',
    components: {
        CoiPolicy,
        MarkdownBlock
    },
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
            groupName: null,
            verifying: false,
            saved: false,
            saving: false,
            redirectCountdown: 5
        }
    },
    computed: {
        codeIsValid() {
            return this.groupName !== null;
        },
        coiTitle() {
            return survey.name+' for '+this.groupName;
        },
        membership () {
            return this.$store.getters
                    .currentUser
                    .person.memberships.find(m => {
                        return m.group
                            && m.group.coi_code === this.code
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
                const lastResponse = {...this.membership.cois[this.membership.cois.length -1]};

                const v2ResponseData = (lastResponse.version == '1.0.0')
                    ? this.transformV1Response(lastResponse.data)
                    : {...lastResponse.data};

                v2ResponseData.coi_attestation = null
                v2ResponseData.data_policy_attestation = null
                this.response = v2ResponseData;
            }

            return {}
        },

        transformV1Response (lastResponse) {
            const v2Response = {
                work_fee_lab: lastResponse.work_fee_lab,
                contributions_to_gd_in_group: lastResponse.contributions_to_gd_in_ep,
                contributions_to_genes: lastResponse.contributions_to_genes,
                coi: (lastResponse.independent_efforts == 0 && lastResponse.coi == 0) ? 0 : 1,
                coi_details: [
                                lastResponse.independent_efforts_details,
                                lastResponse.coi_details
                            ].join(";\n")
            }

            if ((lastResponse.independent_efforts == 2 || lastResponse.coi == 2)) {
                v2Response.coi = 2;
            }

            return v2Response;
        },

        showQuestion(question) {
            if (!question.show) {
                return true;
            }
            if (Array.isArray(question.show.value)) {
                return question.show.value.includes(this.response[question.show.name]);
            }
            return (this.response[question.show.name] == question.show.value);
        },
        verifyCode() {
            this.verifying = true;
            api.get(`/api/coi/${this.code}/group`)
                .then(response => {
                    this.groupName = response.data.display_name
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
                    this.$store.commit('pushError', `You can not complete a COI for ${this.groupName} because you are not a member.`)
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
