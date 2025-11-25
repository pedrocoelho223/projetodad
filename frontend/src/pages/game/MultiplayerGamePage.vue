<template>
  <div class="space-y-6">
    <div
      class="flex justify-between items-center p-4 bg-slate-50 rounded-lg border border-slate-200"
    >
      <div>
        <p class="text-sm text-slate-600">Game ID</p>
        <p class="text-lg font-semibold text-slate-900">
          {{ gameStore.multiplayerGame.id }} -- {{ gameStatus }}
          <span v-if="gameStore.multiplayerGame.complete"
            >Winner {{ gameStore.multiplayerGame.winner }}</span
          >
        </p>
      </div>
      <div v-if="gameStore.multiplayerGame && gameStore.multiplayerGame.winner" class="text-center mt-8">
    <button
        @click="backToLobby"
        class="px-6 py-3 bg-gray-800 text-white rounded-lg hover:bg-gray-700 font-bold transition">
        Voltar ao Lobby
    </button>
</div>
      <div>
        <p class="text-sm text-slate-600">Current Player</p>
        <p class="text-lg font-semibold text-slate-900">
          {{ gameStore.multiplayerGame.currentPlayer }}
          <span v-if="myTurn">Your Turn</span>
        </p>
      </div>
    </div>
    <GameBoard :cards="gameStore.multiplayerGame.cards" @flip-card="flipCard"></GameBoard>
  </div>
</template>

<script setup>
import { toast } from 'vue-sonner'
import { computed, watch } from 'vue'
import { useGameStore } from '@/stores/game'
import GameBoard from '@/components/game/GameBoard.vue'
import { useSocketStore } from '@/stores/socket'
import { useAuthStore } from '@/stores/auth'

import { useRouter } from 'vue-router'

const router = useRouter()

const gameStore = useGameStore()
const socketStore = useSocketStore()
const authStore = useAuthStore()

const myTurn = computed(() => {
  return gameStore.multiplayerGame.currentPlayer == authStore.currentUserID
})

const gameStatus = computed(() => {
  return gameStore.multiplayerGame.complete ? 'Ended' : 'Playing'
})

const flipCard = (card) => {
  console.log('card - ', card)
  console.log('myTurn.value - ', myTurn.value)
  console.log('card.flipped - ', card.flipped)
  if (!myTurn.value) return
  if (card.flipped) return
  socketStore.emitFlipCard(gameStore.multiplayerGame.id, card)
}

watch(gameStatus, () => {
  if (gameStore.multiplayerGame.winner == authStore.currentUserID) {
    toast.success('You Won!!')
  } else {
    toast.error('You Lose!!')
  }
})

const backToLobby = () => {
// Limpa o jogo atual para não aparecer o tabuleiro antigo se voltares a entrar  gameStore.multiplayerGame = null
  gameStore.multiplayerGame = null
  router.push({ name: 'multiplayer-lobby' })
}
</script>

<style scoped></style>
