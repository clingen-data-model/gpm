<template>
  <div class="hello">
    <h1>{{ msg }}</h1>
    <button class="bg-blue-500 rounded-lg px-6 py-3 leading-none text-white uppercase font-semibold text-sm shadow-sm hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0 5">
      Button!!
    </button>
  </div>
</template>

<script>
export default {
  name: 'HelloWorld',
  props: {
    msg: String
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
h3 {
  margin: 40px 0 0;
}
ul {
  list-style-type: none;
  padding: 0;
}
li {
  display: inline-block;
  margin: 0 10px;
}
a {
  color: #42b983;
}
</style>
