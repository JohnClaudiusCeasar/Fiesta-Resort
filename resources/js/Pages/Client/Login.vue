<template>
  <div class="min-h-screen flex flex-col font-sans">
    <!-- Header -->
    <header class="bg-white py-4 px-8 shadow-sm z-10 relative flex items-center">
      <h1 class="text-[2.5rem] font-bold tracking-tight">
        <span class="text-[#00B4FF]">Fiesta</span> <span class="text-black">Resort</span>
      </h1>
    </header>

    <!-- Main Content -->
    <main class="flex-1 relative flex items-center justify-center p-4">
      <!-- Background Image -->
      <div 
        class="absolute inset-0 bg-cover bg-center z-0"
        style="background-image: url('https://images.unsplash.com/photo-1540541338287-41700207dee6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')"
      >
      </div>

      <!-- Login Card -->
      <div class="relative z-10 bg-white rounded-[3rem] p-10 sm:p-12 w-full max-w-[540px] shadow-2xl border-[6px] border-[#BEE6FF]">
        <div class="text-center mb-8">
          <h2 class="text-[1.75rem] text-black mb-1">Welcome to</h2>
          <h1 class="text-[3.5rem] font-bold leading-tight mb-2">
            <span class="text-[#00B4FF]">Fiesta</span> <span class="text-black">Resort!</span>
          </h1>
          <p class="text-[#D1D1D1] font-light text-xl">Where Simplicity meets Luxury</p>
        </div>

        <!-- Error Message -->
        <div v-if="form.errors.email || form.errors.password || hasError" class="bg-[#FFD6D6] text-[#FF6B6B] text-sm p-4 rounded-lg mb-6 flex items-start">
          <span class="mr-2 mt-1 text-[8px]">●</span>
          <p class="leading-snug">The email and password you entered did not match our records. Please double-check and try again.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label class="block text-black font-bold mb-2 text-lg">Email Address:</label>
            <input 
              type="email" 
              v-model="form.email"
              class="w-full bg-[#F5F5F5] border border-[#E0E0E0] rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#00B4FF] text-lg"
              required
            />
          </div>

          <div>
            <label class="block text-black font-bold mb-2 text-lg">Password:</label>
            <input 
              type="password" 
              v-model="form.password"
              class="w-full bg-[#F5F5F5] border border-[#E0E0E0] rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#00B4FF] text-lg"
              required
            />
          </div>

          <div class="flex items-center pt-1">
            <input 
              type="checkbox" 
              id="remember" 
              v-model="form.remember"
              class="w-5 h-5 border-gray-300 rounded text-[#00B4FF] focus:ring-[#00B4FF]"
            />
            <label for="remember" class="ml-2 text-gray-500">Remember me</label>
          </div>

          <button 
            type="submit" 
            :disabled="form.processing"
            class="w-full bg-[#00B4FF] hover:bg-[#009CE0] text-white font-semibold text-2xl py-4 rounded-xl transition-colors mt-4 disabled:opacity-50"
          >
            Login
          </button>

          <div class="flex justify-between mt-6">
            <a href="#" class="text-[#68D391] hover:underline text-base">Forgot Password?</a>
            <a href="#" class="text-[#68D391] hover:underline text-base">Sign Up</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const hasError = ref(false);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onError: () => {
      hasError.value = true;
      form.reset('password');
    },
    onSuccess: () => {
      hasError.value = false;
    }
  });
};
</script>