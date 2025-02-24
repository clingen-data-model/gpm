<script>
export default {
    name: 'MailDetail',
    props: {
        mail: {
            type: Object,
            required: true
        }
    },
    emits: [
      'resend',
    ],
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>
<template>
  <div>
    <dictionary-row label="To" label-class="font-bold w-36" class="mb-1 border-b">
      <div class="flex-none">
        <div v-for="recipient in mail.to" :key="recipient.address">
          {{ recipient.address }}
        </div>
      </div>
    </dictionary-row>
    <dictionary-row label="From" label-class="font-bold w-36" class="mb-1 border-b">
      <div v-for="recipient in mail.from" :key="recipient.address">
        {{ recipient.address }}
      </div>
    </dictionary-row>
    <dictionary-row label="Cc" label-class="font-bold w-36" class="mb-1 border-b">
      <note v-if="!mail.cc">
        --
      </note>
      <ul v-else>
        <li v-for="recipient in mail.cc" :key="recipient.address">
          {{ recipient.address }}
        </li>
      </ul>
    </dictionary-row>
    <dictionary-row label="Bcc" label-class="font-bold w-36" class="mb-1 border-b">
      <note v-if="!mail.bcc">
        --
      </note>
      <ul v-else>
        <li v-for="recipient in mail.bcc" :key="recipient.address">
          {{ recipient.address }}
        </li>
      </ul>
    </dictionary-row>
    <dictionary-row label="Subject" label-class="font-bold w-36" class="mb-1 border-b">
      {{ mail.subject }}
    </dictionary-row>
    <dictionary-row label="Body" label-class="font-bold w-36" class="mb-1">
      <div class="email-body w-3/4" v-html="mail.body" />
    </dictionary-row>
    <div class="mt-2 border-t pt-2 text-right">
      <button class="btn " @click="$emit('resend')">
        Resend
      </button>
    </div>
  </div>
</template>