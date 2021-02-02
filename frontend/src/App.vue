<template>
  <div>
    <div id="nav" class="border-b">
      <div class="container mx-auto py-3 flex">
        <div id="main-menu" class="flex-grow">
          <div class="inline-block pr-3">
            <router-link to="/" class="text-black hover:text-black"> EPAM</router-link>
          </div>
          
          <router-link to="/" class="text-blue-500 hover:underline">Aplications</router-link> 
          |
          <router-link to="/about" class="text-blue-500 hover:underline">About</router-link> 
          <!-- |
          <router-link to="/guides-and-documentation" class="text-blue-500 hover:underline">Guides &amp; documentation</router-link> | -->
        </div>
        <user-menu></user-menu>
      </div>
    </div>

    <div class="container mx-auto my-3">
      <router-view/>
    </div>
  </div>
</template>

<script>
import UserMenu from './components/UserMenu'
export default {
  components: {
    UserMenu
  }
}
</script>

<style src="./assets/styles/app.css" />
