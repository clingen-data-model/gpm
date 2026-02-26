<script>
export default {
    name: 'ApplicationSummary',
}
</script>
<template>
  <div>
    <h1>Expert Panel Application Summary</h1>
    <div class="mb-2 mt-4">
      <div class="tabs justify-between">
        <div>
          <router-link :to="{name: 'VcepsSummary'}" class="tab">
            VCEPS
          </router-link>
          <router-link :to="{name: 'GcepsSummary'}" class="tab">
            GCEPS
          </router-link>
          <router-link :to="{name: 'ScvcepsSummary'}" class="tab">
            SC-VCEPS
          </router-link>
        </div>
        <note>Historical records of EP applications have not yet been back entered.</note>
      </div>
      <div class="p-4 border rounded-tr-lg rounded-b-lg bg-white">
        <router-view />
      </div>
    </div>
  </div>
</template>
<style lang="postcss" scoped>
.tabs {
    @apply flex space-x-2;
}

.tabs .tab {
    @apply border border-b-0 px-4 py-1 rounded-t-lg bg-gray-200 -mb-px;
}

.tabs .tab.router-link-active,
.tabs .tab.active {
    @apply bg-white no-underline;
}

</style>