<template>
  <Sonner richColors />

  <nav class="max-w-full p-5 flex flex-row justify-between align-middle">
    <div class="align-middle text-xl">
      <RouterLink to="/"> {{ pageTitle }} </RouterLink>
      <span class="text-xs" v-if="authStore.currentUser">
        &nbsp;&nbsp;&nbsp; ({{ authStore.currentUser?.name }})
      </span>
    </div>

    <NavBar @logout="logout" :userLoggedIn="authStore.isLoggedIn" />
  </nav>

  <div>
    <main class="container m-auto">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from "vue-router";
import { ref, onMounted } from "vue";
import { toast } from "vue-sonner";
import "vue-sonner/style.css";
import Sonner from "@/components/ui/sonner/AppSonner.vue";
import NavBar from "./components/layout/NavBar.vue";
import { useAuthStore } from "./stores/auth";

// COMENTADO: Não precisamos disto para G1 e G2
// import { useSocketStore } from "./stores/socket";

const authStore = useAuthStore();
// const socketStore = useSocketStore(); // COMENTADO

const year = new Date().getFullYear();
const pageTitle = ref(`DAD ${year}/${String(year + 1).slice(-2)}`);

const logout = () => {
  toast.promise(authStore.logout(), {
    loading: "Calling API",
    success: () => "Logout Sucessfull",
    error: (data) => `[API] Error saving game - ${data?.response?.data?.message}`,
  });
};

onMounted(() => {
  // COMENTADO: Evita o erro "socket is undefined"
  // socketStore.handleConnection();
});
</script>

<style></style>
