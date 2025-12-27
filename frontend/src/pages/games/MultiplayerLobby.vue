<script setup>
import { ref, inject } from "vue";
import { useAuthStore } from "@/stores/auth"; // se o teu store tiver outro nome diz-me e ajusto

const socket = inject("socket");
const auth = useAuthStore();

const mode = ref("game"); // game | match
const type = ref("3");
const stake = ref(3);
const roomIdToJoin = ref("");

const createdRoomId = ref("");

function createRoom() {
  socket.emit("create_room", {
    mode: mode.value,
    type: type.value,
    stake: Number(stake.value),
    player1_user_id: auth.user.id,
  }, (res) => {
    if (!res?.ok) return alert(res?.error ?? "Erro");
    createdRoomId.value = res.roomId;
  });
}

function joinRoom() {
  socket.emit("join_room", {
    roomId: roomIdToJoin.value,
    player2_user_id: auth.user.id,
  }, (res) => {
    if (!res?.ok) return alert(res?.error ?? "Erro");
    createdRoomId.value = roomIdToJoin.value;
  });
}
</script>

<template>
  <div class="max-w-xl mx-auto p-6 space-y-4">
    <h1 class="text-2xl font-bold">Multiplayer</h1>

    <div class="border rounded p-4 space-y-3">
      <div class="flex gap-3">
        <label>Modo</label>
        <select v-model="mode" class="border rounded p-1">
          <option value="game">Jogo</option>
          <option value="match">Partida</option>
        </select>

        <label class="ml-4">Tipo</label>
        <select v-model="type" class="border rounded p-1">
          <option value="3">Bisca 3</option>
          <option value="9">Bisca 9</option>
        </select>
      </div>

      <div v-if="mode==='match'" class="flex gap-2 items-center">
        <label>Stake</label>
        <input type="number" min="3" max="100" v-model="stake" class="border rounded p-1 w-24" />
      </div>

      <button class="border rounded px-3 py-2" @click="createRoom">Criar sala</button>

      <div v-if="createdRoomId" class="text-sm">
        Sala: <b>{{ createdRoomId }}</b>
        <router-link class="underline ml-2" :to="`/games/multiplayer/${createdRoomId}`">Entrar</router-link>
      </div>
    </div>

    <div class="border rounded p-4 space-y-2">
      <h2 class="font-semibold">Entrar numa sala</h2>
      <input v-model="roomIdToJoin" class="border rounded p-2 w-full" placeholder="roomId" />
      <button class="border rounded px-3 py-2" @click="joinRoom">Entrar</button>
    </div>
  </div>
</template>
