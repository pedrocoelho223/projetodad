<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold mb-6">📊 Estatísticas Globais</h2>

    <div v-if="!stats">A carregar...</div>

    <div v-else>
      <!-- RESUMO -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="card">Jogadores: {{ stats.summary.total_players }}</div>
        <div class="card">Jogos: {{ stats.summary.total_games }}</div>
        <div class="card">Partidas: {{ stats.summary.total_matches }}</div>
        <div class="card">Atividade 24h: {{ stats.summary.recent_activity_24h }}</div>
      </div>

      <!-- GRÁFICOS -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded shadow">
          <h3 class="font-bold mb-3">Jogos por Dia</h3>
          <Line :data="gamesChart" />
        </div>

        <div class="bg-white p-4 rounded shadow">
          <h3 class="font-bold mb-3">Distribuição por Variante</h3>
          <Bar :data="variantsChart" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import http from '@/lib/axios'
import { Line, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Tooltip,
  Legend
)

const stats = ref(null)

onMounted(async () => {
  const res = await http.get('/statistics/public')
  stats.value = res.data
})

const gamesChart = computed(() => ({
  labels: stats.value?.games_history.map(g => g.date).reverse(),
  datasets: [{
    label: 'Jogos',
    data: stats.value?.games_history.map(g => g.total).reverse(),
    borderColor: '#3b82f6',
    backgroundColor: '#3b82f6'
  }]
}))

const variantsChart = computed(() => ({
  labels: stats.value?.game_variants.map(v => v.type == '3' ? 'Bisca 3' : 'Bisca 9'),
  datasets: [{
    label: 'Total',
    data: stats.value?.game_variants.map(v => v.total),
    backgroundColor: ['#10b981', '#f59e0b']
  }]
}))
</script>

<style scoped>
.card {
  background: white;
  padding: 20px;
  border-radius: 10px;
  font-weight: bold;
  text-align: center;
}
</style>
