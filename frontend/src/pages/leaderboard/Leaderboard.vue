<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold mb-4">🏆 Leaderboard Global</h2>

    <!-- Loading -->
    <div v-if="loading" class="text-blue-500">
      A carregar dados...
    </div>

    <!-- Sem dados -->
    <div v-else-if="topUsers.length === 0" class="text-gray-500">
      Ainda não há dados de jogos terminados para mostrar.
    </div>

    <!-- Tabela -->
    <table
      v-else
      class="w-full border-collapse border border-gray-300 shadow-sm rounded-lg overflow-hidden"
    >
      <thead class="bg-gray-100">
        <tr>
          <th class="border p-3 text-sm uppercase text-gray-600">Posição</th>
          <th class="border p-3 text-sm uppercase text-gray-600">Jogador</th>
          <th class="border p-3 text-sm uppercase text-gray-600">Vitórias</th>
          <th class="border p-3 text-sm uppercase text-gray-600">Capotes</th>
          <th class="border p-3 text-sm uppercase text-gray-600">Bandeiras</th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="(user, index) in topUsers"
          :key="user.user_id"
          :class="{
            'bg-yellow-50': index === 0,
            'bg-gray-50': index === 1,
            'bg-orange-50': index === 2
          }"
          class="text-center hover:bg-blue-50 transition-colors"
        >
          <!-- Posição -->
          <td class="border p-3 font-bold">
            <span v-if="index === 0">🥇</span>
            <span v-else-if="index === 1">🥈</span>
            <span v-else-if="index === 2">🥉</span>
            {{ index + 1 }}º
          </td>

          <!-- Jogador -->
          <td class="border p-3 font-semibold text-blue-700">
            {{ user.nickname }}
          </td>

          <!-- Vitórias -->
          <td class="border p-3 font-bold">
            {{ user.wins }}
          </td>

          <!-- Capotes -->
          <td class="border p-3">
            <span
              class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs font-bold"
            >
              {{ user.capotes ?? 0 }}
            </span>
          </td>

          <!-- Bandeiras -->
          <td class="border p-3">
            <span
              class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold"
            >
              {{ user.bandeiras ?? 0 }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAPIStore } from '@/stores/api'
defineOptions({
  name: 'LeaderboardPage'
})

const api = useAPIStore()
const topUsers = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await api.getTopPlayers()

    // a API pode devolver:
    // { data: [...] } OU diretamente [...]
    const data = response.data.data ?? response.data ?? []

    // Ordenação conforme regras
    topUsers.value = data
      .sort((a, b) => {
        if (b.wins !== a.wins) return b.wins - a.wins
        if ((b.capotes ?? 0) !== (a.capotes ?? 0)) {
          return (b.capotes ?? 0) - (a.capotes ?? 0)
        }
        if ((b.bandeiras ?? 0) !== (a.bandeiras ?? 0)) {
          return (b.bandeiras ?? 0) - (a.bandeiras ?? 0)
        }
        return new Date(b.last_win_at ?? 0) - new Date(a.last_win_at ?? 0)
      })
      .slice(0, 10)
  } catch (error) {
    console.error('Erro ao carregar a leaderboard:', error)
    topUsers.value = []
  } finally {
    loading.value = false
  }
})
</script>
