<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
      <h2 class="text-2xl font-bold italic">📜 O Meu Histórico de Jogos</h2>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-500"
          >Utilizador: <strong>{{ authStore.currentUser?.nickname }}</strong></span
        >
        <RouterLink to="/coins" class="text-sm bg-yellow-100 text-yellow-700 px-3 py-1 rounded">
          💰 {{ authStore.currentUser?.coins_balance ?? 0 }} Moedas
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="flex items-center gap-2 text-blue-500">
      <span class="animate-spin">⏳</span> A carregar o teu histórico...
    </div>

    <div v-else>
      <div
        v-if="games.length === 0"
        class="bg-yellow-50 p-6 rounded-lg border border-yellow-200 text-yellow-700"
      >
        <p class="font-medium">Ainda não tens jogos registados.</p>
        <p class="text-sm">
          Participa em partidas multijogador para veres aqui os teus resultados!
        </p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full bg-white border rounded-lg shadow-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left">Data</th>
              <th class="px-4 py-3 text-left">Variante</th>
              <th class="px-4 py-3 text-left">Resultado</th>
              <th class="px-4 py-3 text-left text-center">Pontuação</th>
              <th class="px-4 py-3 text-left">Tipo de Vitória</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="game in games"
              :key="game.id"
              class="border-t hover:bg-gray-50 transition-colors"
            >
              <td class="px-4 py-2 text-sm">{{ new Date(game.ended_at).toLocaleString() }}</td>
              <td class="px-4 py-2">
                <span
                  :class="
                    game.type === '3'
                      ? 'bg-purple-100 text-purple-700'
                      : 'bg-orange-100 text-orange-700'
                  "
                  class="px-2 py-1 rounded text-xs font-bold"
                >
                  {{ game.type === '3' ? 'Bisca de 3' : 'Bisca de 9' }}
                </span>
              </td>

              <td class="px-4 py-2">
                <span v-if="isWinner(game)" :class="getWinType(game).class">
                  {{ getWinType(game).label }}
                </span>
                <span v-else class="text-gray-400 text-xs italic">-</span>
              </td>
              <td class="px-4 py-2 text-center font-mono">
                <span :class="{ 'font-bold text-blue-600': isWinner(game) }">{{
                  game.player1_points
                }}</span>
                <span class="mx-1 text-gray-400">-</span>
                <span :class="{ 'font-bold text-blue-600': !isWinner(game) }">{{
                  game.player2_points
                }}</span>
              </td>
              <td class="px-4 py-2">
                <span
                  v-if="isWinner(game)"
                  :class="getWinType(game).class"
                  class="px-3 py-1 rounded-full text-xs font-bold shadow-sm"
                >
                  {{ getWinType(game).label }}
                </span>
                <span v-else class="text-gray-400 text-xs italic">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import http from '@/lib/axios' // Garante que o caminho para o axios está correto

const authStore = useAuthStore()
const games = ref([])
const loading = ref(true)

const isWinner = (game) => game.winner_user_id === authStore.currentUser.id

const getWinType = (game) => {
  const points =
    game.player1_user_id === authStore.currentUser.id ? game.player1_points : game.player2_points

  // Lógica de pontos para G4: 120 = Bandeira, >= 91 = Capote
  if (points === 120) {
    return {
      label: '🚩 BANDEIRA',
      class: 'bg-red-600 text-white px-3 py-1 rounded-full text-xs font-bold',
    }
  }
  if (points >= 91) {
    return {
      label: '🧥 CAPOTE',
      class: 'bg-indigo-600 text-white px-3 py-1 rounded-full text-xs font-bold',
    }
  }
  return {
    label: 'VITÓRIA',
    class: 'bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold',
  }
}

onMounted(async () => {
  try {
    const response = await http.get('/my/games') // Rota definida no teu api.php
    games.value = response.data
  } catch (error) {
    console.error('Erro ao carregar histórico:', error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped></style>
