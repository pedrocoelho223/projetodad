<template>
  <div class="register-page">
    <div class="register-box">
      <h1>
        <span class="suit-icon">♣</span>
        Criar Conta
        <span class="suit-icon">♥</span>
      </h1>

      <form @submit.prevent="doRegister">
        <div class="form-group">
          <label for="name">Nome</label>
          <input id="name" v-model="name" placeholder="O teu nome" required />
        </div>

        <div class="form-group">
          <label for="nickname">Nickname</label>
          <input id="nickname" v-model="nickname" placeholder="O teu nickname" required />
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" v-model="email" type="email" placeholder="email@exemplo.com" required />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" v-model="password" type="password" placeholder="•••••••" required />
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirmar Password</label>
          <input
            id="password_confirmation"
            v-model="password_confirmation"
            type="password"
            placeholder="•••••••"
            required
          />
        </div>

        <p v-if="error" class="error-message">{{ error }}</p>

        <button type="submit" class="register-button">Criar Conta</button>
      </form>

      <div class="login-link">
        <p>
          Já tem conta?
          <router-link to="/login">Faça login</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const name = ref('')
const nickname = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const error = ref('')

const doRegister = async () => {
  error.value = ''

  try {
    await auth.register({
      name: name.value,
      nickname: nickname.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    })

    // Se não usas interceptor, podes guardar token localmente
    localStorage.setItem('token', auth.token)

    router.push('/profile')
  } catch (err) {
    console.error(err)
    error.value = auth.error || 'Erro ao criar conta. Verifique os dados.'
  }
}
</script>

<style scoped>
.register-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background-color: #1a3a1a;
  font-family: Arial, sans-serif;
}

.register-box {
  width: 100%;
  max-width: 450px;
  padding: 2.5rem;
  background: #2c3e50;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
  color: #ecf0f1;
}

h1 {
  text-align: center;
  margin-bottom: 2rem;
  color: #f1c40f;
  font-size: 2rem;
}

.suit-icon {
  color: #e74c3c;
}

h1 .suit-icon:last-child {
  color: #2ecc71;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: bold;
  color: #bdc3c7;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  font-size: 1rem;
  background: #34495e;
  border: 2px solid #566573;
  border-radius: 4px;
  color: #ecf0f1;
  transition: border-color 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: #f1c40f;
}

.register-button {
  width: 100%;
  padding: 0.8rem;
  font-size: 1.1rem;
  font-weight: bold;
  color: #2c3e50;
  background-color: #f1c40f;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 1rem;
  transition: background-color 0.3s ease;
}

.register-button:hover {
  background-color: #d4ac0d;
}

.error-message {
  color: #e74c3c;
  text-align: center;
  margin-bottom: 1rem;
  font-weight: bold;
}

.login-link {
  margin-top: 1.5rem;
  text-align: center;
  color: #bdc3c7;
}

.login-link a {
  color: #f1c40f;
  font-weight: bold;
  text-decoration: none;
}

.login-link a:hover {
  text-decoration: underline;
}
</style>
