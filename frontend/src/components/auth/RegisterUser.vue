<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const form = ref({
  name: '',
  nickname: '',
  email: '',
  password: '',
  photo: null
})

const isLoading = ref(false)
const errorMessage = ref('')

const onFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.value.photo = file
  }
}

const submitRegister = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    // Usamos FormData para suportar envio de ficheiros
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('email', form.value.email)
    formData.append('password', form.value.password)
    formData.append('nickname', form.value.nickname)

    // Só anexa se o utilizador escolheu foto
    if (form.value.photo) {
      formData.append('photo_file', form.value.photo)
    }

    await authStore.register(formData)
    router.push({ name: 'home' }) // Redireciona para a página principal ou dashboard
  } catch (e) {
    if (e.response && e.response.status === 422) {
      // Formata erros de validação vindos do Laravel (ex: email já existe)
      const errors = e.response.data.errors || e.response.data
      errorMessage.value = Object.values(errors).flat().join('\n')
    } else {
      errorMessage.value = 'Ocorreu um erro ao registar. Tente novamente.'
      console.error(e)
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
        Criar Nova Conta
      </h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
      <!-- Mensagem de Erro -->
      <div v-if="errorMessage" class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm whitespace-pre-line border border-red-200">
        {{ errorMessage }}
      </div>

      <form class="space-y-6" @submit.prevent="submitRegister">
        <!-- Nome -->
        <div>
          <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Nome</label>
          <div class="mt-2">
            <input
              v-model="form.name"
              id="name"
              name="name"
              type="text"
              required
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 pl-3"
            >
          </div>
        </div>

        <!-- Nickname -->
        <div>
          <label for="nickname" class="block text-sm font-medium leading-6 text-gray-900">Nickname</label>
          <div class="mt-2">
            <input
              v-model="form.nickname"
              id="nickname"
              name="nickname"
              type="text"
              required
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 pl-3"
            >
          </div>
        </div>

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium leading-6 text-gray-900">Email</label>
          <div class="mt-2">
            <input
              v-model="form.email"
              id="email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 pl-3"
            >
          </div>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-sm font-medium leading-6 text-gray-900">Password</label>
          <div class="mt-2">
            <input
              v-model="form.password"
              id="password"
              name="password"
              type="password"
              required
              minlength="3"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 pl-3"
            >
          </div>
        </div>

        <!-- Foto Upload -->
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900">Foto de Perfil (Opcional)</label>
          <div class="mt-2">
            <input
              @change="onFileChange"
              type="file"
              accept="image/*"
              class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
            />
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="isLoading"
            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isLoading">A criar conta...</span>
            <span v-else>Registar (+10 Moedas)</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
