<script setup>
import { ref } from 'vue'
import { initializeApp } from 'firebase/app'
import { getAuth, GoogleAuthProvider, signInWithPopup } from 'firebase/auth'
import axios from 'axios'

const firebaseConfig = {
	apiKey: "AIzaSyDBdjR8Qz1L245d1YlpmUCaEoLoNKYnLNE",
	authDomain: "poc-gene-tracker.firebaseapp.com",
	projectId: "poc-gene-tracker",
	storageBucket: "poc-gene-tracker.firebasestorage.app",
	messagingSenderId: "535802170323",
	appId: "1:535802170323:web:54ea0c6f98730ecaf9fd26",
	measurementId: "G-67S1SY9GBP"
}

// Initialize Firebase only once
const firebaseApp = initializeApp(firebaseConfig)
const auth = getAuth(firebaseApp)
const message = ref('')

const loginWithGoogle = async () => {
  try {
    const provider = new GoogleAuthProvider()
    const result = await signInWithPopup(auth, provider)
    const idToken = await result.user.getIdToken()

    await axios.post('/api/firebase-login', { token: idToken }, {
      	withCredentials: true
    })

    // Redirect or navigate manually
    window.location.href = '/'
  } catch (error) {
    console.error('Login failed:', error)
  }
}

</script>

<template>
  	<div class="w-full max-w-md mx-auto mt-12">
		<!-- Firebase login button -->
		<button @click="loginWithGoogle" class="bg-blue-500 text-white px-4 py-2 rounded">
		Login with Google
		</button>
		<p class="mt-4 text-sm text-gray-600">{{ message }}</p>
	</div>
</template>
