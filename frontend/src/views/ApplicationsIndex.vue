<template>
  <div class="home">

    <create-application-model></create-application-model>
    <div class="flex border-b pb-2 mb-2">
      <div class="tabs flex-grow">
        <router-link to="/vceps">VCEPS</router-link>
        |
        <router-link to="/gceps">GCEPS</router-link>
      </div>
      <div>
        <a href="#view=summary" class="px-2 py-1 border border-r-0 text-xs">Summary</a>
        <a href="#view=all-info" class="px-2 py-1 border text-xs">All Info</a>
      </div>
    </div>

    <router-view></router-view>
  </div>
</template>

<script>
// @ is an alias to /src
import CreateApplicationModel from '../components/applications/CreateApplicationModal'

export default {
  name: 'Home',
  components: {
    CreateApplicationModel
  }
}
</script>
