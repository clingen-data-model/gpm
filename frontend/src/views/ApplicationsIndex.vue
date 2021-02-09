<template>
  <div class="home">

    <!-- <create-application-model></create-application-model> -->
    <button @click="showModal = true" class="bg-blue-500 rounded text-white px-2 py-1">Initiate Application</button>

    <div class="mb-2 mt-4">
      <div class="tabs">
        <router-link to="/vceps" class="tab">VCEPS</router-link>
        <router-link to="/gceps" class="tab">GCEPS</router-link>
      </div>
      <div class="p-4 border rounded-tr-lg rounded-b-lg bg-white">
        <router-view></router-view>
      </div>
    </div>


      <modal-dialog v-model="showModal" size='md'>
        <router-view name="modal" @canceled="showModal = false" @saved="showModal = false"></router-view>
      </modal-dialog>
  </div>
</template>

<script>
// @ is an alias to /src
export default {
  name: 'ApplicationsIndex',
  data() {
    return {
    }
  },
  watch: {
    'this.$route.hash': function (to, from) {
      console.log('to, from', [to, from]);
    }
  },
  computed: {
    showModal: {
      set (value) {
        const currentHash = this.$route.hash == '' ? '#' : this.$route.hash;
        const hashSet = new Set(currentHash.substr(1).split('&').filter(i => i));
        if (value) {
          hashSet.add('initiate');
        } else {
          hashSet.delete('initiate');
        }

        const newHash = hashSet.size > 0 ? '#'+[...hashSet].join('&') : ''

        this.$router.replace({path: this.$route.path, query: this.$route.query, hash: newHash});
      },
      get () {
        console.log(this.$route.hash);
        return this.$route.hash.includes('initiate');
      },
      immediate: true
    }
  }
}
</script>
