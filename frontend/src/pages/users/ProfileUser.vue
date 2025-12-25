<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter, RouterLink } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const isEditing = ref(false)
const showDeleteModal = ref(false)
const deletePassword = ref('')
const message = ref({ text: '', type: '' })

// Formulário de Edição
const form = ref({
  name: '',
  nickname: '',
  email: '',
  password: '',
  photo: null,
})

onMounted(async () => {
  if (!authStore.isLoggedIn) {
    router.push({ name: 'login' })
    return
  }

  await authStore.fetchCurrentUser(true)
  resetForm()
})

const resetForm = () => {
  if (authStore.currentUser) {
    form.value = {
      name: authStore.currentUser.name,
      nickname: authStore.currentUser.nickname,
      email: authStore.currentUser.email,
      password: '',
      photo: null,
    }
  }
}

const onFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.value.photo = file
  }
}

const handleUpdate = async () => {
  // Limpar mensagens anteriores
  message.value = { text: '', type: '' }

  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('nickname', form.value.nickname)
    formData.append('email', form.value.email)

    // Só envia password se o user preencheu (para alterar)
    if (form.value.password) {
      formData.append('password', form.value.password)
    }
    // Só envia foto se o user escolheu uma nova
    if (form.value.photo) {
      formData.append('photo_file', form.value.photo)
    }

    await authStore.updateProfile(formData)

    message.value = { text: 'Perfil atualizado com sucesso!', type: 'success' }
    isEditing.value = false

    // Limpar mensagem após 3 segundos
    setTimeout(() => (message.value = { text: '', type: '' }), 3000)
  } catch (e) {
    console.error(e)
    // Se for erro de validação (422), mostra os detalhes
    if (e.response && e.response.status === 422) {
      const errors = e.response.data.errors || e.response.data
      message.value = { text: Object.values(errors).flat().join('\n'), type: 'error' }
    } else {
      message.value = { text: 'Erro ao atualizar. Verifique os dados.', type: 'error' }
    }
  }
}

const handleDeleteAccount = async () => {
  if (!deletePassword.value) return

  try {
    await authStore.deleteAccount(deletePassword.value)
    router.push({ name: 'home' })
  } catch (e) {
    if (e.response && e.response.data && e.response.data.message) {
      alert(e.response.data.message)
    } else {
      alert('Erro ao apagar conta. A password está correta?')
    }
  }
}
</script>

<template>
  <div class="max-w-4xl mx-auto py-10 px-4">
    <div v-if="message.text" class="mb-4 p-4 rounded whitespace-pre-line"
      :class="message.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
      {{ message.text }}
    </div>

    <div class="md:grid md:grid-cols-3 md:gap-6">
      <div class="md:col-span-1">
        <div class="bg-white shadow rounded-lg p-6 text-center">
          <div class="relative w-32 h-32 mx-auto mb-4">
            <img :src="authStore.userPhotoUrl || '/anonymous.png'" alt="Avatar"
              class="rounded-full w-full h-full object-cover border-4 border-indigo-50" />
          </div>
          <h3 class="text-xl font-bold text-gray-900">{{ authStore.currentUser?.name }}</h3>
          <p class="text-sm text-gray-500">@{{ authStore.currentUser?.nickname }}</p>

          <div v-if="authStore.currentUser?.type === 'P'"
            class="mt-6 flex justify-center items-center gap-2 bg-yellow-50 p-3 rounded-lg border border-yellow-200">
            <span class="text-2xl">💰</span>
            <div class="text-left">
              <p class="text-xs text-yellow-800 font-semibold uppercase">Saldo</p>
              <p class="text-lg font-bold text-yellow-900 leading-none">
                {{ authStore.currentUser?.coins_balance ?? 0 }} Moedas
              </p>

              <RouterLink to="/coins"
                class="mt-2 inline-flex items-center justify-center rounded bg-yellow-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-yellow-700">
                Moedas
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5 md:col-span-2 md:mt-0">
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Informações Pessoais</h3>
            <button @click="isEditing = !isEditing; resetForm()"
              class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
              {{ isEditing ? 'Cancelar' : 'Editar Dados' }}
            </button>
          </div>

          <form v-if="isEditing" @submit.prevent="handleUpdate" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input v-model="form.name" type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Nickname</label>
                <input v-model="form.nickname" type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input v-model="form.email" type="email"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Nova Password (opcional)</label>
                <input v-model="form.password" type="password" placeholder="Deixe vazio para manter"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Alterar Foto</label>
                <input @change="onFileChange" type="file" accept="image/*"
                  class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
              </div>
            </div>
            <div class="flex justify-end pt-4">
              <button type="submit"
                class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Guardar Alterações
              </button>
            </div>
          </form>

          <div v-else class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ authStore.currentUser?.email }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Tipo de Conta</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ authStore.currentUser?.type === 'A' ? 'Administrador' : 'Jogador' }}
                </dd>
              </div>
            </div>

            <div class="border-t pt-6 mt-6">
              <button @click="showDeleteModal = true"
                class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                  stroke="currentColor" class="w-4 h-4">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Apagar a minha conta
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showDeleteModal"
      class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-w-lg w-full">
        <div class="sm:flex sm:items-start">
          <div
            class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
          </div>
          <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Apagar Conta Permanentemente</h3>
            <div class="mt-2">
              <p class="text-sm text-gray-500">Tem a certeza? Todos os seus dados, moedas e histórico de jogos serão
                perdidos.</p>
              <input v-model="deletePassword" type="password" placeholder="Confirme a sua password"
                class="mt-4 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 border p-2" />
            </div>
          </div>
        </div>
        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
          <button @click="handleDeleteAccount" type="button"
            class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Apagar</button>
          <button @click="showDeleteModal = false" type="button"
            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</template>
