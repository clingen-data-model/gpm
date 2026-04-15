<script setup>
import { api } from '@/http'
import { computed, onMounted, ref } from 'vue'

const summaryData = ref([])
const loading = ref(false)
const loadError = ref('')

const reports = [
  {
    url: '/api/report/basic-summary',
    name: 'Summary',
  },
  {
    url: '/api/report/vcep-application-summary',
    name: 'VCEP Application',
  },
  // {
  //   url: '/api/report/scvcep-application-summary',
  //   name: 'SC-VCEP Application',
  // },
  {
    url: '/api/report/gcep-genes',
    name: 'GCEP Genes',
  },
  {
    url: '/api/report/vcep-genes',
    name: 'VCEP Genes',
  },
  // {
  //   url: '/api/report/scvcep-genes',
  //   name: 'SC-VCEP Genes',
  // },
  {
    url: '/api/report/countries',
    name: 'Countries',
  },
  {
    url: '/api/report/institutions',
    name: 'Institutions',
  },
  {
    url: '/api/report/people',
    name: 'All people',
  },
  {
    url: '/api/report/people-in-multiple-eps',
    name: 'People in >1 EP',
  },
  // {
  //   url: '/api/report/publications',
  //   name: 'Publications',
  // },
]

const toNumber = (value) => {
  const normalized = Number(String(value ?? '0').replace(/,/g, ''))
  return Number.isFinite(normalized) ? normalized : 0
}

const formatNumber = (value) => {
  return new Intl.NumberFormat('en-US').format(toNumber(value))
}

const metricMap = computed(() => {
  return summaryData.value.reduce((acc, row) => {
    acc[row.Metric] = toNumber(row.Value)
    return acc
  }, {})
})

const getMetric = (name, fallback = 0) => {
  return metricMap.value[name] ?? fallback
}

const formatPercent = (part, total) => {
  if (!total) {
    return null
  }

  return `${((part / total) * 100).toFixed(1)}%`
}

const totalIndividuals = computed(() => getMetric('All Individuals'))

const kpis = computed(() => [
  {
    label: 'Total groups',
    value: getMetric('Groups'),
    // note: 'Across WG, CDWG, SC-CDWG, VCEP, SC-VCEP, and GCEP',
    note: 'Across WG, CDWG, VCEP, and GCEP',
  },
  {
    label: 'Active group members',
    value: getMetric('Active Individuals (has active group membership)'),
    note: `${formatNumber(getMetric('All Individuals'))} total individuals, ${formatPercent(getMetric('Active Individuals (has active group membership)'), getMetric('All Individuals'))} active`,
  },
  {
    label: 'Countries represented',
    value: getMetric('Countries represented'),
    note: `${formatNumber(getMetric('Institutions represented'))} institutions represented`,
  },
  // {
  //   label: 'Publications',
  //   value: getMetric('Number of Publications'),
  //   note: `${formatNumber(getMetric("Individuals has taken Code of Conduct attestation and not expire per today's date"))} individuals have current Code of Conduct attestation`,
  // },
])

const groupPortfolio = computed(() => [
  { label: 'Working Groups', value: getMetric('Working Groups') },
  { label: 'CDWGs', value: getMetric('CDWGs') },
  // { label: 'SC-CDWGs', value: getMetric('SC-CDWGs') },
  { label: 'VCEPs', value: getMetric('VCEPs') },
  // { label: 'SC-VCEPs', value: getMetric('SC-VCEPs') },
  { label: 'GCEPs', value: getMetric('GCEPS') },
  { label: 'Total groups', value: getMetric('Groups'), total: true },
])

const pipelineRows = computed(() => [
  {
    status: 'Definition',
    vcep: getMetric('VCEP applications in definition'),
    // scvcep: getMetric('SC-VCEP applications in definition'),
    gcep: null,
  },
  {
    status: 'Draft Specifications',
    vcep: getMetric('VCEP applications in draft specifications'),
    // scvcep: getMetric('SC-VCEP applications in draft specifications'),
    gcep: null,
  },
  {
    status: 'Pilot Specifications',
    vcep: getMetric('VCEP applications in pilot specifications'),
    // scvcep: getMetric('SC-VCEP applications in pilot specifications'),
    gcep: null,
  },
  {
    status: 'Sustained Curation',
    vcep: getMetric('VCEP applications in sustained curation'),
    // scvcep: getMetric('SC-VCEP applications in sustained curation'),
    gcep: null,
  },
  {
    status: 'Applying',
    vcep: getMetric('VCEPs applying'),
    // scvcep: getMetric('SC-VCEPs applying'),
    gcep: getMetric('GCEPs applying'),
  },
  {
    status: 'Approved',
    vcep: getMetric('VCEPs approved'),
    // scvcep: getMetric('SC-VCEPs approved'),
    gcep: getMetric('GCEPs approved'),
  },
])

const geneRows = computed(() => [
  { label: 'VCEP', value: getMetric('VCEP genes') },
  // { label: 'SC-VCEP', value: getMetric('SC-VCEP genes') },
  { label: 'GCEP', value: getMetric('GCEP genes') },
])

const membershipRows = computed(() => [
  { label: 'All individuals', value: getMetric('All Individuals') },
  {
    label: 'Active individuals',
    value: getMetric('Active Individuals (has active group membership)'),
    meta: formatPercent(
      getMetric('Active Individuals (has active group membership)'),
      totalIndividuals.value,
    )
      ? `${formatPercent(getMetric('Active Individuals (has active group membership)'), totalIndividuals.value)} of all individuals`
      : null,
  },
  { label: 'Individuals in 1+ EPs', value: getMetric('Individuals in 1+ EPs') },
  { label: 'Individuals in 1+ GCEPs', value: getMetric('Individuals in 1+ GCEPs') },
  { label: 'Individuals in 1+ VCEPs', value: getMetric('Individuals in 1+ VCEps') },
  // { label: 'Individuals in 1+ SC-VCEPs', value: getMetric('Individuals in 1+ SC-VCEps') },
  { label: 'Individuals in 1+ WGs', value: getMetric('Individuals in 1+ WGs') },
  { label: 'Individuals in 1+ CDWGs', value: getMetric('Individuals in 1+ CDWGs') },
  // { label: 'Individuals in 1+ SC-CDWGs', value: getMetric('Individuals in 1+ SC-CDWGs') },
  { label: 'People in 2+ EPs', value: getMetric('People in 2+ EPs') },
])

const qualityRows = computed(() => [
  { label: 'Countries represented', value: getMetric('Countries represented') },
  { label: 'Institutions represented', value: getMetric('Institutions represented') },
  {
    label: 'Individuals with demographics info',
    value: getMetric('Individuals with demographics info'),
    meta: formatPercent(getMetric('Individuals with demographics info'), totalIndividuals.value)
      ? `${formatPercent(getMetric('Individuals with demographics info'), totalIndividuals.value)} of all individuals`
      : null,
  },
  {
    label: 'Individuals with current demographics info',
    value: getMetric('Individuals with current demographics info (within last year)'),
    meta: 'Updated within the last year',
  },
  {
    label: 'Current Code of Conduct attestation',
    value: getMetric("Individuals has taken Code of Conduct attestation and not expire per today's date"),
    meta: 'Valid as of today',
  },
  // {
  //   label: 'Publications',
  //   value: getMetric('Number of Publications'),
  //   meta: 'Consider adding a reporting period label later',
  // },
])

const getSummaryReport = async () => {
  loading.value = true
  loadError.value = ''

  try {
    const response = await api.get('/api/report/basic-summary')
    summaryData.value = response.data
  } catch (error) {
    loadError.value = 'Unable to load summary report.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  getSummaryReport()
})
</script>

<template>
  <div class="space-y-4">
    <h1 class="text-2xl font-semibold text-gray-900">
      Reports
    </h1>

    <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
      <aside v-remaining-height class="space-y-4 rounded-lg bg-gray-100 p-4">
        <div>
          <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">
            Downloads
          </h2>
        </div>

        <ul class="item-list space-y-2">
          <li v-for="rpt in reports" :key="rpt.url">
            <download-link :url="rpt.url" :title="`Download ${rpt.name} Report`" class="block rounded-md px-2 py-2 transition hover:bg-white">
              {{ rpt.name }}
            </download-link>
          </li>
        </ul>
      </aside>

      <main class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
              <h2 class="text-2xl font-semibold text-gray-900">
                Summary Report
              </h2>
            </div>
          </div>
        </section>

        <div v-if="loadError" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {{ loadError }}
        </div>

        <div v-if="loading" class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-500 shadow-sm">
          Loading summary report...
        </div>

        <template v-else>
          <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
              v-for="card in kpis"
              :key="card.label"
              class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
              <p class="text-sm text-gray-500">
                {{ card.label }}
              </p>
              <p class="mt-2 text-3xl font-semibold text-gray-900">
                {{ formatNumber(card.value) }}
              </p>
              <p class="mt-3 text-xs text-gray-500">
                {{ card.note }}
              </p>
            </article>
          </section>

          <section class="grid gap-6 xl:grid-cols-[minmax(18rem,24rem)_minmax(0,1fr)]">
            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
              <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                  Group portfolio
                </h3>
              </div>

              <div class="overflow-hidden rounded-md border border-gray-200">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 text-left text-gray-700">
                    <tr>
                      <th class="px-4 py-3 font-semibold">Group type</th>
                      <th class="px-4 py-3 text-right font-semibold">Count</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in groupPortfolio" :key="row.label" class="border-t border-gray-200">
                      <th class="px-4 py-3 text-left font-medium text-gray-800" :class="{ 'font-semibold': row.total }">
                        {{ row.label }}
                      </th>
                      <td class="px-4 py-3 text-right font-medium text-gray-900">
                        {{ formatNumber(row.value) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </article>

            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
              <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                  Expert panel application pipeline
                </h3>
              </div>

              <div class="overflow-x-auto rounded-md border border-gray-200">
                <table class="w-full min-w-[32rem] text-sm">
                  <thead class="bg-gray-50 text-gray-700">
                    <tr>
                      <th class="px-4 py-3 text-left font-semibold">Status</th>
                      <th class="px-4 py-3 text-right font-semibold">VCEP</th>
                      <!-- <th class="px-4 py-3 text-right font-semibold">SC-VCEP</th> -->
                      <th class="px-4 py-3 text-right font-semibold">GCEP</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in pipelineRows" :key="row.status" class="border-t border-gray-200">
                      <th class="px-4 py-3 text-left font-medium text-gray-800">
                        {{ row.status }}
                      </th>
                      <td class="px-4 py-3 text-right text-gray-900">
                        {{ row.vcep ?? '—' }}
                      </td>
                      <!-- <td class="px-4 py-3 text-right text-gray-900">{{ row.scvcep ?? '—' }}</td> -->
                      <td class="px-4 py-3 text-right text-gray-900">
                        {{ row.gcep ?? '—' }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </article>
          </section>

          <section class="grid gap-6 xl:grid-cols-3">
            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
              <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                  Genes by EP type
                </h3>
              </div>

              <div class="overflow-hidden rounded-md border border-gray-200">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 text-gray-700">
                    <tr>
                      <th class="px-4 py-3 text-left font-semibold">EP Type</th>
                      <th class="px-4 py-3 text-right font-semibold">Genes</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in geneRows" :key="row.label" class="border-t border-gray-200">
                      <th class="px-4 py-3 text-left font-medium text-gray-800">
                        {{ row.label }}
                      </th>
                      <td class="px-4 py-3 text-right font-medium text-gray-900">
                        {{ formatNumber(row.value) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </article>

            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
              <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                  Membership
                </h3>
              </div>

              <div>
                <div v-for="row in membershipRows" :key="row.label" class="metric-row">
                  <div>
                    <div class="text-sm font-medium text-gray-800">
                      {{ row.label }}
                    </div>
                    <div v-if="row.meta" class="mt-1 text-xs text-gray-500">
                      {{ row.meta }}
                    </div>
                  </div>
                  <div class="text-right text-sm font-semibold text-gray-900">
                    {{ formatNumber(row.value) }}
                  </div>
                </div>
              </div>
            </article>

            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
              <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                  Representation &amp; data quality
                </h3>
              </div>

              <div>
                <div v-for="row in qualityRows" :key="row.label" class="metric-row">
                  <div>
                    <div class="text-sm font-medium text-gray-800">
                      {{ row.label }}
                    </div>
                    <div v-if="row.meta" class="mt-1 text-xs text-gray-500">
                      {{ row.meta }}
                    </div>
                  </div>
                  <div class="text-right text-sm font-semibold text-gray-900">
                    {{ formatNumber(row.value) }}
                  </div>
                </div>
              </div>
            </article>
          </section>
        </template>
      </main>
    </div>
  </div>
</template>

<style lang="postcss" scoped>
.item-list li {
  @apply border-b border-gray-200 pb-2;
}

.item-list li:last-child {
  @apply border-b-0 pb-0;
}

.metric-row {
  @apply flex items-start justify-between gap-4 border-b border-gray-200 py-3 last:border-b-0;
}
</style>
