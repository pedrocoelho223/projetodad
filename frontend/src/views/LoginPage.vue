<template>
  <div class="login-page">
    <div class="login-box">
      <h1>
        <span class="suit-icon">♦</span>
        Bisca Login
        <span class="suit-icon">♠</span>
      </h1>

      <form @submit.prevent="doLogin">
        <div class="form-group">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="email"
            placeholder="o_teu_email@exemplo.com"
            type="email"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="password"
            placeholder="••••••••"
            type="password"
            required
          />
        </div>

        <p v-if="error" class="error-message">{{ error }}</p>

        <button type="submit" class="login-button">Entrar</button>
      </form>

      <div class="register-link">
        <p>
          Não tem conta?
          <router-link to="/register">Registe-se aqui</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useAuthStore } from "../stores/auth";
import { useRouter } from "vue-router";

const auth = useAuthStore();
const router = useRouter();

const email = ref("");
const password = ref("");
const error = ref("");

const doLogin = async () => {
  try {
    error.value = "";
    await auth.login({
      email: email.value,
      password: password.value
    });

    // (auth.token foi atualizado pelo auth.login)
    localStorage.setItem('token', auth.token)

    // Redireciona para o profile ou dashboard após login
    router.push("/profile");
  } catch {
    error.value = "Credenciais inválidas. Tente novamente.";
  }
};
</script>

<style scoped>
/* Estilo principal da página - "Mesa de Jogo" */
.login-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  /* Cor de "feltro" escuro ou mesa */
  background-color: #1a3a1a;
  font-family: Arial, sans-serif;
}

/* A "caixa" onde o formulário está */
.login-box {
  width: 100%;
  max-width: 400px;
  padding: 2.5rem;
  /* Fundo da "carta" */
  background: #2c3e50;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
  color: #ecf0f1;
}

h1 {
  text-align: center;
  margin-bottom: 2rem;
  color: #f1c40f; /* Cor "Dourada" (como o Ás) */
  font-size: 2rem;
}

.suit-icon {
  color: #e74c3c; /* Cor de Copas/Ouros */
}
/* Alternar a cor do segundo naipe */
h1 .suit-icon:last-child {
  color: #bdc3c7; /* Cor de Espadas/Paus (Claro) */
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
  border-color: #f1c40f; /* Destaque Dourado */
}

.login-button {
  width: 100%;
  padding: 0.8rem;
  font-size: 1.1rem;
  font-weight: bold;
  color: #2c3e50;
  background-color: #f1c40f; /* Botão Dourado */
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.login-button:hover {
  background-color: #d4ac0d; /* Dourado mais escuro */
}

.error-message {
  color: #e74c3c; /* Vermelho para erros */
  text-align: center;
  margin-bottom: 1rem;
  font-weight: bold;
}

.register-link {
  margin-top: 1.5rem;
  text-align: center;
  color: #bdc3c7;
}

.register-link p {
  margin: 0;
}

/* Estilo do RouterLink */
.register-link a {
  color: #f1c40f; /* Dourado */
  font-weight: bold;
  text-decoration: none;
  transition: text-decoration 0.3s;
}

.register-link a:hover {
  text-decoration: underline;
}

</style>
