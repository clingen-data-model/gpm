<template>
  <div>
    <p class="text-lg text-center"><strong>Welcome to ClinGen!</strong></p>
    <p>
      <strong>
        You've been invited to join ClinGen<span v-if="invite.inviter">
          as part of the {{ invite.inviter.name }}
          {{ invite.inviter.type }}.</span
        ><span v-else>.</span>
      </strong>
    </p>

    <p>There are just a few steps to get you set up:</p>
    <ol class="list-decimal pl-8">
      <li>Create an account.</li>
      <li>Fill out your profile.</li>
      <li>Share some demographic information.</li>
      <li v-if="invite.inviter">Complete a Conflict of Interest Survey.</li>
    </ol>
    <p>
      <button class="btn btn-lg blue w-full mt-4" @click="$emit('ok')">
        Get Started
      </button>
    </p>
  </div>
</template>
<script>
export default {
  name: "OnboardingSteps",
  props: {
      invite: {
          type: Object,
          required: true
      }
  },
  emits: ['ok'],
  data() {
    return {};
  },
  computed: {},
  methods: {},
};
</script>