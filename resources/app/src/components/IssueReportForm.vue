<style lang="postcss" scoped>
    .report-issue-container {
        position: fixed;
        right: 0;
        top: 75px
    }
</style>
<template>
  <div>
        <popper hover arrow class="report-issue-container" placement="left">
            <template v-slot:content>
                <div class="whitespace-no-wrap w-28 text-xs">
                    Report a problem
                </div>
            </template>
            <button
            class="text-white bg-red-600 border border-red-700 border-r-0 pl-3 pr-3 py-2 rounded-l-lg shadow-lg"
            @click="initReportIssue"
            >
            <icon-bug />
            </button>
        </popper>
    <teleport to="body">
      <modal-dialog title="Report a problem" v-model="showReportIssue">
        <input-row v-model="url" label="URL" :errors="errors.url" input-class="w-full" />
        <input-row label="Type">
          <select v-model="type" class="w-full">
            <option value="Bug">Bug</option>
            <option value="Story">Enhancement</option>
          </select>
        </input-row>
        <input-row v-if="type == 'Bug'" label="Severity" :errors="errors.severity">
            <select name="" id="" v-model="severity">
                <option :value="sv.id" v-for="sv in severities" :key="sv.id">
                    {{sv.name}}
                </option>
            </select>
        </input-row>
        <input-row v-model="summary" label="Summary" :errors="errors.summary" input-class="w-full"/>
        <input-row label="Description" :errors="errors.description">
          <textarea v-model="description" class="w-full" rows="10" placeholder="Please tell us what you were doing, what you expected to happen, and what actually happened."></textarea>
        </input-row>
        <button-row @submitted="submitIssue" @canceled="cancelSubmission"></button-row>
      </modal-dialog>
    </teleport>
  </div>
</template>
<script>
import {api, isValidationError} from '@/http'
import { feedback } from "@/configs";

export default {
  name: "ComponentName",
  props: {},
  data() {
    return {
      showReportIssue: false,
      errors: {},
      url: null,
      type: 'Bug',
      severity: "15555",
      description: null,
      summary: null
    };
  },
  computed: {
    severities() {
      return feedback.severities;
    },
  },
  methods: {
    initReportIssue() {
      this.showReportIssue = true;
      this.initForm();
    },
    initForm () {
        this.severity = null;
        this.description = null;
        this.summary = null;
        this.url = this.$route.path

    },
    async submitIssue () {
        try {
            await api.post('/api/feedback', {
                url: this.url,
                type: this.type,
                severity: this.severity,
                description: this.description,
                summary: this.summary
            });
            this.$store.commit('pushSuccess', 'Your problem has been reported.');
            this.showReportIssue = false;
        } catch (error) {
            if (isValidationError(error)) {
                this.errors = error.response.data.errors
                return;
            }
            throw error;
        }

    },
    cancelSubmission () {
        this.initForm();
        this.showReportIssue = false;
    }
  },
};
</script>