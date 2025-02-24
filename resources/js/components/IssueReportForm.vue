<script>
import configs from "@/configs";
import { api, isValidationError } from "@/http";

const feedback = configs.feedback;

export default {
  name: "ComponentName",
  props: {},
  data() {
    return {
      showReportIssue: false,
      errors: {},
      url: null,
      type: "Bug",
      severity: "15555",
      description: "What I expected:\n\nSteps to reproduce:\n",
      summary: null,
      saving: false
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
    initForm() {
      this.severity = null;
      this.description = "What I expected:\n\n\nSteps to reproduce:\n\n\nAdditional information:\n\n";
      this.summary = null;
      this.url = this.$route.path;

      this.errors = {};
      this.saving = false;
    },
    async submitIssue() {
      try {
        this.saving = true;
        await api.post("/api/feedback", {
          url: this.url,
          type: this.type,
          severity: this.severity,
          description: this.description,
          summary: this.summary,
        });
        this.saving = false;
        this.$store.commit("pushSuccess", "Your problem has been reported.");
        this.showReportIssue = false;
      } catch (error) {
        if (isValidationError(error)) {
          this.errors = error.response.data.errors;
          this.saving = false;
          return;
        }
        throw error;
      }
    },
    cancelSubmission() {
      this.initForm();
      this.showReportIssue = false;
    },
  },
};
</script>
<template>
  <div v-if="$store.getters.isAuthed">
    <popover hover arrow placement="left">
        <template #content>
            <div class="whitespace-no-wrap w-28 text-xs">Report a problem</div>
        </template>
            <button
                class="
                    text-white
                    bg-red-600
                    border border-red-700 border-r-0
                    pl-3
                    pr-3
                    py-2
                    rounded-l-lg
                    shadow-lg
                    print:hidden
                "
                @click="initReportIssue"
            >
                <icon-bug />
            </button>
    </popover>
    <teleport to="body">
      <modal-dialog title="Report a problem" v-model="showReportIssue">
        <input-row
          v-model="url"
          label="URL"
          :errors="errors.url"
          input-class="w-full"
        />
        <input-row label="Type">
          <select v-model="type" class="w-full">
            <option value="Bug">Bug</option>
            <option value="Task">Enhancement</option>
          </select>
        </input-row>
        <input-row
          v-if="type == 'Bug'"
          label="Severity"
          :errors="errors.severity"
        >
          <select name="" id="" v-model="severity">
            <option :value="sv.id" v-for="sv in severities" :key="sv.id">
              {{ sv.name }}
            </option>
          </select>
        </input-row>
        <input-row
          v-model="summary"
          label="Summary"
          :errors="errors.summary"
          input-class="w-full"
        />
        <input-row label="Description" :errors="errors.description">
          <textarea
            v-model="description"
            class="w-full"
            rows="10"
            placeholder="Please tell us what you expected to happen, and what actually happened, and the steps we should take to reproduce the problem."
          ></textarea>
        </input-row>
        <button-row
          @submitted="submitIssue"
          @canceled="cancelSubmission"
          v-if="!saving"
        ></button-row>
        <div v-if="saving" class="pt-2 mt-2 border-t text-gray-500">Saving&hellip;</div>
      </modal-dialog>
    </teleport>
  </div>
</template>