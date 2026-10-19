<script setup>
import { computed } from 'vue'

const props = defineProps({
  section: {
    type: Object,
    required: true,
  },
  showHeading: {
    type: Boolean,
    default: true,
  },
})

const normalize = value => String(value ?? '').replace(/[._-]+/g, ' ').replace(/\s+/g, ' ').replace(/:$/, '').trim().toLowerCase()
const sectionLabel = computed(() => normalize(props.section.label))
const sectionKey = computed(() => normalize(props.section.key))

const matches = (...names) => {
  const normalized = names.map(normalize)
  return normalized.some(name => sectionLabel.value === name || sectionKey.value === name || sectionKey.value.endsWith(` ${name}`))
}
const isEvidenceSummaries = computed(() => matches('Evidence Summaries'))
const isCoreApprovalMembers = computed(() => matches('Core Approval Members'))
const isBiocuratorTrainers = computed(() => matches('Biocurator Trainers'))
const isTrainedBiocurators = computed(() => matches('Trained Biocurators'))
const value = computed(() => props.section.value)
const heading = computed(() => String(props.section.label ?? '').replace(/:\s*$/, ''))
const items = computed(() => Array.isArray(value.value) ? value.value : [])
const field = (item, ...names) => {
  if (!item || typeof item !== 'object') {
    return null
  }
  const wanted = names.map(normalize)
  const entry = Object.entries(item).find(([key]) => wanted.includes(normalize(key)))
  return entry?.[1] ?? null
}

const hasValue = value => value !== null && value !== undefined && String(value).trim() !== ''
const emptyValue = computed(() => value.value == null ? 'Not recorded' : 'None recorded.')
const names = computed(() => items.value.map(item => field(item, 'name')).filter(hasValue))
const yesNo = value => {
  if (value === true || value === 1 || value === '1') {
    return 'Yes'
  }
  if (value === false || value === 0 || value === '0') {
    return 'No'
  }
  return 'Not recorded'
}

const formatDate = value => {
  if (!hasValue(value)) {
    return null
  }
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return String(value)
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC',
  }).format(date)
}

// Render only captured URLs; unsafe or invalid values remain readable as plain text.
const evidenceLinks = summary => ['VCI', 'CIViC'].map(label => {
  const value = field(summary, `${label} URL`, `${label}_url`)
  let href = null
  try {
    const url = new URL(value)
    if (['http:', 'https:'].includes(url.protocol)) href = url.href
  } catch { /* Preserve unparseable historical values as text. */ }
  return { label, value, href }
}).filter(link => hasValue(link.value))

const responses = member => {
  const result = field(member, 'attestation_responses', 'attestation responses')

  return Array.isArray(result) ? result : []
}
</script>

<template>
  <section>
    <template v-if="!section.available">
      <h5 v-if="showHeading" class="font-semibold">
        {{ heading }}
      </h5>

      <p>Historical section unavailable.</p>
    </template>

    <!-- Evidence Summaries -->
    <template v-else-if="isEvidenceSummaries">
      <h5 v-if="showHeading" class="font-semibold mb-2">
        {{ heading }}
      </h5>

      <p v-if="!items.length">
        {{ emptyValue }}
      </p>

      <div
        v-for="(summary, index) in items"
        :key="index"
        class="mb-3 flex items-start gap-2"
      >
        <div class="pt-2 text-sm text-gray-500">
          {{ index + 1 }}
        </div>

        <div class="flex-1 rounded border p-3">
          <!-- Metadata -->
          <div class="flex flex-wrap items-center gap-2">
            <strong
              v-if="hasValue(field(summary, 'gene'))"
              class="text-base"
            >
              {{ field(summary, 'gene') }}
            </strong>

            <span
              v-if="
                hasValue(field(summary, 'mondo_id', 'mondo id'))
                || hasValue(field(summary, 'disease'))
              "
              class="rounded bg-gray-100 px-2 py-1 text-xs"
            >
              <span v-if="hasValue(field(summary, 'mondo_id', 'mondo id'))">
                {{ field(summary, 'mondo_id', 'mondo id') }}
              </span>

              <span v-if="hasValue(field(summary, 'disease'))">
                {{ field(summary, 'disease') }}
              </span>
            </span>

            <span
              v-if="hasValue(field(summary, 'variant'))"
              class="rounded bg-gray-100 px-2 py-1 text-xs"
            >
              Variant: {{ field(summary, 'variant') }}
            </span>

            <span
              v-if="hasValue(field(summary, 'assertion_id', 'assertion id'))"
              class="rounded bg-gray-100 px-2 py-1 text-xs"
            >
              Assertion ID: {{ field(summary, 'assertion_id', 'assertion id') }}
            </span>

            <span
              v-if="hasValue(field(summary, 'moi'))"
              class="rounded bg-gray-100 px-2 py-1 text-xs"
            >
              MOI: {{ field(summary, 'moi') }}
            </span>
          </div>

          <!-- Summary -->
          <p
            v-if="hasValue(field(summary, 'summary'))"
            class="mt-2 whitespace-pre-wrap"
          >
            {{ field(summary, 'summary') }}
          </p>

          <div v-for="link in evidenceLinks(summary)" :key="link.label" class="mt-2">
            <a v-if="link.href" :href="link.href" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">
              View {{ link.label }}
            </a>
            <span v-else>{{ link.label }}: {{ link.value }}</span>
          </div>
        </div>
      </div>
    </template>

    <!-- Core Approval Members -->
    <template v-else-if="isCoreApprovalMembers">
      <h5 v-if="showHeading" class="font-semibold mb-2">
        {{ heading }}
      </h5>

      <p v-if="!items.length">
        {{ emptyValue }}
      </p>

      <div
        v-for="(member, index) in items"
        :key="index"
        class="mb-3"
      >
        <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
          <strong>
            {{ field(member, 'name') ?? 'Name unavailable' }}
          </strong>

          <span>
            <strong>Attestation:</strong>
            {{ yesNo(field(member, 'attestation_completed', 'attestation completed')) }}
          </span>

          <span
            v-if="hasValue(field(
              member,
              'attestation_completion_date',
              'attestation completion date'
            ))"
          >
            <strong>Completed:</strong>
            {{
              formatDate(field(
                member,
                'attestation_completion_date',
                'attestation completion date'
              ))
            }}
          </span>
        </div>

        <div
          v-if="responses(member).length"
          class="mt-1"
        >
          <strong>Responses:</strong>

          <ul class="list-disc pl-5">
            <li
              v-for="(response, responseIndex) in responses(member)"
              :key="responseIndex"
            >
              {{ response }}
            </li>
          </ul>
        </div>
      </div>
    </template>

    <!-- Biocurator Trainers -->
    <template v-else-if="isBiocuratorTrainers">
      <p>
        <strong v-if="showHeading">{{ heading }}:</strong>
        {{ names.length ? names.join(', ') : emptyValue }}
      </p>
    </template>

    <!-- Trained Biocurators -->
    <template v-else-if="isTrainedBiocurators">
      <p>
        <strong v-if="showHeading">{{ heading }}:</strong>
        {{ names.length ? names.join(', ') : emptyValue }}
      </p>
    </template>

    <!-- Other Step 4 fields -->
    <template v-else>
      <p>
        <strong v-if="showHeading">
          {{ heading }}:
        </strong>

        <span class="whitespace-pre-wrap">
          {{
            value === null || value === undefined || value === ''
              ? 'Not recorded'
              : value
          }}
        </span>
      </p>
    </template>
  </section>
</template>