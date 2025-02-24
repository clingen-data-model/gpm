<script>
import configs from "@/configs";
import { api } from "@/http";

const feedback = configs.feedback;

export default {
  name: "ComponentName",
  props: {},
  data() {
    return {
      showFaq: false,
      faqMarkdown: null,
      errors: {}
    };
  },
  computed: {
    severities() {
      return feedback.severities;
    },
  },
  methods: {
    showTheFaq() {
      this.fetchFaq();
      this.showFaq = true;
    },
    fetchFaq () {
      api.get('/api/docs/faq')
        .then(response => this.faqMarkdown = response.data)
    }
  },
};
</script>
<template>
  <div v-if="$store.getters.isAuthed">
    <popover hover arrow placement="left">
        <template #content>
            <div class="whitespace-no-wrap w-28 text-xs">Read the GPM FAQ</div>
        </template>
        <faq-link
            class="
                block
                custom-text
                text-white
                bg-blue-600
                border border-blue-700 border-r-0
                pl-3
                pr-3
                py-2
                rounded-l-lg
                shadow-lg
                print:hidden
            "
        >
            <icon-question />
        </faq-link>
    </popover>
    <teleport to="body">
      <modal-dialog v-model="showFaq" title="ClinGen GPM Frequently Asked Questions">
        <div v-if="!faqMarkdown">Loading FAQ...</div>
        <markdown-block :markdown="faqMarkdown" class="faq" />
      </modal-dialog>
    </teleport>
  </div>
</template>
<style lang="postcss">
  .faq h3,
  .faq h2
  {
    @apply mt-4 border-t pt-4;
  }
  .faq h3:first-child,
  .faq h2:first-child
  {
    @apply mt-0 border-none;
  }
</style>