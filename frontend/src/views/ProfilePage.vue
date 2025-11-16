<script setup>
import { ref, onMounted } from 'vue'
import api from '@/axios' // O teu ficheiro de configuração do Axios
import { useRouter } from 'vue-router'
import { computed } from 'vue'

const router = useRouter()
// Ref para o <input type="file"> escondido
const avatarInput = ref(null)

const avatarUrl = computed(() => {
  // 1. Define o avatar anónimo que o TEU SEEDER copiou
  const anonymousAvatar = 'http://127.0.0.1:8000/storage/avatars/anonymous.png'

  // 2. Verifica se o seeder atribuiu uma foto ao user (se não é NULL)
  if (user.value && user.value.photo_avatar_filename) {

    // 3. Se SIM, usa a foto que o seeder atribuiu
    return `http://127.0.0.1:8000/storage/avatars/${user.value.photo_avatar_filename}`
  }

  // 4. Se NÃO (está NULL), usa o 'anonymous.png'
  return anonymousAvatar
})

// 1. Função chamada pelo botão "Alterar Avatar"
const triggerAvatarUpload = () => {
  avatarInput.value.click() // Clica no input escondido
}

// 2. Função chamada quando o user escolhe um ficheiro
const handleAvatarUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // O upload de ficheiros usa 'FormData'
  const formData = new FormData()
  formData.append('photo_avatar', file)

  try {
    const { data } = await api.post('/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    // 4. Atualiza os dados do user localmente com a resposta
    // (o backend deve devolver o user atualizado com o novo nome da foto)
    user.value = data
    alert('Avatar atualizado!')
  } catch (error) {
    console.error('Erro ao fazer upload do avatar', error)
    alert('Erro ao guardar o avatar.')
  }
}

// --- Refs para os dados do utilizador ---
const user = ref(null)
const name = ref('')
const email = ref('')
const nickname = ref('')

// --- formulário de Password ---
const current_password = ref('')
const new_password = ref('')
const new_password_confirmation = ref('')

// --- formulário de Remoção ---
const delete_password = ref('')

// --- mensagens de feedback ---
const profileMessage = ref('')
const passwordMessage = ref('')
const deleteMessage = ref('')

// --- Carregar dados do utilizador quando a página abre ---
onMounted(async () => {
  try {
    const { data } = await api.get('/profile') // <-- CORRETO
    user.value = data
    name.value = data.name
    email.value = data.email
    nickname.value = data.nickname
  } catch (error) {
    console.error('Erro ao carregar perfil', error)
    if (error.response?.status === 401) router.push('/login')
  }
})

// --- 1. Atualizar Perfil (Nome, Email, Nickname) ---
const handleProfileUpdate = async () => {
  profileMessage.value = { text: 'A atualizar...', type: '' }
  try {
    await api.put('/profile', {
      name: name.value,
      email: email.value,
      nickname: nickname.value,
    })

    profileMessage.value = { text: 'Perfil atualizado com sucesso!', type: 'success' }
  } catch (error) {
    console.error(error)
    profileMessage.value = {
      text: error.response?.data?.message || 'Erro ao atualizar perfil.',
      type: 'error',
    }
  }
}

// --- 2. Atualizar Password ---
const handlePasswordUpdate = async () => {
  passwordMessage.value = 'A atualizar...'

  // Adiciona uma validação simples no frontend
  if (new_password.value !== new_password_confirmation.value) {
    passwordMessage.value = 'A nova password e a confirmação não coincidem.'
    return
  }

  if (!current_password.value) {
    passwordMessage.value = 'Insira a password atual.'
    return
  }

  try {
    await api.put('/profile/password', {
      current_password: current_password.value,
      new_password: new_password.value,
      new_password_confirmation: new_password_confirmation.value,
    })

    passwordMessage.value = 'Password atualizada!'
    current_password.value = ''
    new_password.value = ''
    new_password_confirmation.value = ''
  } catch (error) {
    console.error(error)
    passwordMessage.value = error.response?.data?.message || 'Erro ao atualizar password.'
  }
}

// --- 3. Logout ---
const handleLogout = async () => {
  try {
    await api.post('/logout')
    localStorage.removeItem('token')
    delete api.defaults.headers.common['Authorization']
    router.push('/login')
  } catch (error) {
    console.error('Erro no logout', error)
  }
}

// --- 4. Remover Conta ---
const handleDeleteAccount = async () => {
  if (!delete_password.value) {
    deleteMessage.value = { text: 'Insere a tua password.', type: 'error' }
    return
  }
  if (!confirm('Tens a certeza absoluta? Esta ação é permanente.')) return

  try {
    await api.delete('/profile', {
      // Envia a password no 'data' para o backend validar
      data: { password_confirm: delete_password.value },
    })

    alert('Conta removida.')
    // Faz logout completo
    localStorage.removeItem('token')
    delete api.defaults.headers.common['Authorization']
    router.push('/login')
  } catch (error) {
    console.error(error)
    deleteMessage.value = {
      text: error.response?.data?.message || 'Erro ao remover a conta.',
      type: 'error',
    }
  }
}
</script>

<template>
  <div class="profile-page-background">
    <div v-if="user" class="profile-container">
      <div class="header-info">
      <h1>
        <span class="suit-icon">♦</span>
        Perfil de {{ name }}
        <span class="suit-icon">♠</span>
      </h1>
      <p class="balance">
        Saldo: <strong>{{ user.coins_balance }} moedas</strong>
      </p>
      </div>

      <div class="header-avatar">
        <img :src="avatarUrl" alt="Avatar" class="avatar-image" />

        <button @click="triggerAvatarUpload" class="avatar-button">Alterar Avatar</button>

        <input
          type="file"
          @change="handleAvatarUpload"
          ref="avatarInput"
          accept="image/png, image/jpeg"
          style="display: none"
        />
      </div>

      <hr />

      <div class="form-grid-container">
        <form @submit.prevent="handleProfileUpdate" class="profile-form">
          <fieldset>
            <legend>Informação do Perfil</legend>
            <div>
              <label for="name">Nome:</label>
              <input id="name" type="text" v-model="name" />
            </div>
            <div>
              <label for="nickname">Nickname:</label>
              <input id="nickname" type="text" v-model="nickname" />
            </div>
            <div>
              <label for="email">Email:</label>
              <input id="email" type="email" v-model="email" />
            </div>
            <button type="submit">Atualizar Perfil</button>
            <p class="feedback" :class="profileMessage.type">{{ profileMessage.text }}</p>
          </fieldset>
        </form>

        <form @submit.prevent="handlePasswordUpdate" class="profile-form">
          <fieldset>
            <legend>Mudar Password</legend>
            <div>
              <label for="current_password">Password Atual:</label>
              <input
                id="current_password"
                type="password"
                v-model="current_password"
                autocomplete="current-password"
              />
            </div>
            <div>
              <label for="new_password">Nova Password:</label>
              <input
                id="new_password"
                type="password"
                v-model="new_password"
                autocomplete="new-password"
              />
            </div>
            <div>
              <label for="new_password_confirmation">Confirmar Nova Password:</label>
              <input
                id="new_password_confirmation"
                type="password"
                v-model="new_password_confirmation"
                autocomplete="new-password"
              />
            </div>
            <button type="submit">Mudar Password</button>
            <p class="feedback" :class="passwordMessage.type">{{ passwordMessage.text }}</p>
          </fieldset>
        </form>

        <form @submit.prevent="handleDeleteAccount" class="profile-form danger-zone">
          <fieldset>
            <legend>Remover Conta</legend>
            <p>Esta ação é permanente e vai remover todos os teus dados e moedas.</p>
            <div>
              <label for="delete_password">Insere a tua password para confirmar:</label>
              <input
                id="delete_password"
                type="password"
                v-model="delete_password"
                autocomplete="current-password"
              />
            </div>
            <button type="submit" class="danger-button">Remover a minha conta</button>
            <p class="feedback" :class="deleteMessage.type">{{ deleteMessage.text }}</p>
          </fieldset>
        </form>
      </div>
      <hr />

      <button @click="handleLogout" class="logout-button">Logout</button>
      <hr />
    </div>

    <div v-else class="loading">Carregando perfil...</div>
  </div>
</template>

<style scoped>
.profile-header {
  display: flex;
  justify-content: space-between; /* Põe info na esq. e avatar na dir. */
  align-items: center; /* Alinha-os verticalmente */
  margin-bottom: 1rem; /* Espaço antes do <hr> */
}

/* Coluna da Esquerda (Info) */
.header-info {
  flex: 1; /* Permite que cresça */
}

/* Tira o alinhamento centrado do H1 e P.balance originais */
.profile-header h1 {
  text-align: left;
  margin: 0;
}
.profile-header .balance {
  text-align: left;
  margin-top: 0.5rem;
}

/* Coluna da Direita (Avatar) */
.header-avatar {
  flex-shrink: 0; /* Impede que encolha */
  margin-left: 2rem; /* Espaço entre as colunas */
  display: flex;
  flex-direction: column; /* Põe a imagem em cima do botão */
  align-items: center; /* Centra o botão e imagem */
  gap: 0.75rem; /* Espaço entre imagem e botão */
}

.avatar-image {
  width: 100px;
  height: 100px;
  border-radius: 50%; /* Imagem redonda */
  border: 4px solid #f1c40f; /* Borda Dourada */
  object-fit: cover; /* Garante que a imagem preenche o círculo */
  background-color: #34495e; /* Cor de fundo */
}

.avatar-button {
  background-color: #566573; /* Cor Cinza (como o Logout) */
  color: white;
  font-size: 0.8rem; /* Botão mais pequeno */
  padding: 6px 12px;
  width: auto; /* Largura automática (não 100%) */
  margin: 0; /* Remove margem 'auto' dos outros botões */
}
.avatar-button:hover {
  background-color: #4a5562;
}


/* --- 1. Tema Principal (Fundo e Caixa) --- */
.profile-page-background {
  width: 100%;
  min-height: 100vh;
  background-color: #1a3a1a; /* Verde Feltro */
  padding: 2rem 1rem;
  box-sizing: border-box;
}
.profile-container {
  font-family: Arial, sans-serif;
  max-width: 1200px; /* Largura para 3 colunas */
  margin: 0 auto;
  padding: 2.5rem;
  background: #2c3e50; /* Azul Caixa */
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
  color: #ecf0f1;
}
.loading {
  color: white;
  text-align: center;
  padding-top: 40px;
  font-size: 1.5rem;
}

/* --- 2. Cabeçalho (Título e Saldo) --- */
h1 {
  margin-bottom: 0;
}
.suit-icon {
  color: #e74c3c;
}
h1 .suit-icon:last-child {
  color: #bdc3c7;
}
.balance {
  text-align: center;
  color: #bdc3c7;
  margin: 0;
  font-size: 1.2rem;
}
.balance strong {
  color: #f1c40f;
  font-size: 1.3rem;
}
hr {
  margin: 2.5rem 0;
  border: 0;
  border-top: 1px solid #34495e;
}

/* --- 3. Layout Flex (3 Colunas) --- */
.form-grid-container {
  display: flex;
  gap: 1.5rem;
}
.form-grid-container .profile-form {
  flex: 1; /* Faz as 3 colunas terem a mesma largura */
  margin: 0;
}
.form-grid-container .profile-form fieldset {
  height: 100%; /* Faz as 3 colunas terem a mesma altura */
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

/* --- 4. Formulários (Fieldset, Legend, Label, Input) --- */
fieldset {
  border: 2px solid #46a546;
  padding: 1.5rem 2rem;
  border-radius: 5px;
}
legend {
  font-size: 1.4em;
  font-weight: bold;
  padding: 0 10px;
  color: #f1c40f; /* Dourado */
}
.profile-form div {
  margin-bottom: 1.2rem;
}
.profile-form label {
  display: block;
  font-weight: bold;
  margin-bottom: 0.5rem;
  color: #bdc3c7;
}
.profile-form input {
  width: 100%;
  padding: 0.75rem;
  font-size: 1rem;
  background: #34495e;
  border: 2px solid #566573;
  border-radius: 4px;
  color: #ecf0f1;
  transition: border-color 0.3s;
  box-sizing: border-box;
}
.profile-form input:focus {
  outline: none;
  border-color: #f1c40f; /* Destaque Dourado */
}

/* --- 5. Botões (Base + Variantes) --- */
button {
  width: 100%;
  padding: 10px 20px;
  font-size: 1rem;
  font-weight: bold;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s ease;
  margin-top: auto; /* Empurra o botão para o fundo do fieldset */
}
button[type='submit'] {
  background-color: #f1c40f;
  color: #2c3e50;
}
button[type='submit']:hover {
  background-color: #d4ac0d;
}
.logout-button {
  background-color: #566573;
  color: white;
  padding: 12px;
}
.logout-button:hover {
  background-color: #4a5562;
}

/* --- 6. Zona de Perigo (Remover Conta) --- */
.danger-zone p {
  color: #bec7bd;
}
.danger-zone legend {
  color: #e74c3c; /* Vermelho */
}
.danger-zone fieldset {
  border-color: #e74c3c;
}
.danger-button {
  background-color: #e74c3c;
  color: white;
  padding: 12px;
}
.danger-button:hover {
  background-color: #c0392b;
}

/* --- 7. Mensagens de Feedback --- */
.feedback {
  margin-top: 15px;
  margin-bottom: 0;
  font-size: 0.9em;
  font-weight: bold;
  text-align: center;
  min-height: 1.2em;
}
.feedback.success {
  color: #2ecc71; /* Verde */
}
.feedback.error {
  color: #e74c3c; /* Vermelho */
}
</style>
