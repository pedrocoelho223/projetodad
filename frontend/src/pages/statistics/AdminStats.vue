<template>
  <div class="p-6">
    <h2 class="text-3xl font-bold mb-6 text-slate-800">📊 Painel de Estatísticas (Admin)</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
      <div class="bg-blue-100 p-4 rounded-lg shadow">
        <p class="text-blue-600 font-semibold">Total de Jogos</p>
        <p class="text-2xl font-bold">{{ stats.summary?.total_games || 0 }}</p>
      </div>
      <div class="bg-green-100 p-4 rounded-lg shadow">
        <p class="text-green-600 font-semibold">Receita Total (€)</p>
        <p class="text-2xl font-bold">{{ stats.summary?.total_revenue?.toFixed(2) || 0 }} €</p>
      </div>
      <div class="bg-purple-100 p-4 rounded-lg shadow">
        <p class="text-purple-600 font-semibold">Novos Jogadores (Semana)</p>
        <p class="text-2xl font-bold">{{ stats.summary?.new_users_week || 0 }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div class="bg-white p-4 rounded-lg shadow border">
        <h3 class="font-bold mb-4 text-gray-700">Evolução de Receita Diária</h3>
        <Line v-if="loaded" :data="revenueChartData" />
        <p v-else class="text-gray-400 italic">A carregar gráfico...</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border">
        <h3 class="font-bold mb-4 text-gray-700">Volume por Variante (3 vs 9)</h3>
        <Bar v-if="loaded" :data="variantsChartData" />
        <p v-else class="text-gray-400 italic">A carregar gráfico...</p>
      </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow border">
      <h3 class="text-xl font-bold mb-4">Últimas Compras de Moedas</h3>
      <table class="w-full text-left">
        <thead>
          <tr class="border-b bg-gray-50">
            <th class="py-3 px-2">Jogador</th>
            <th class="py-3 px-2">Valor (€)</th>
            <th class="py-3 px-2">Data/Hora</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="purchase in stats.recent_purchases" :key="purchase.id" class="border-b hover:bg-gray-50">
            <td class="py-3 px-2 font-medium text-blue-700">{{ purchase.user?.nickname }}</td>
            <td class="py-3 px-2 font-bold">{{ purchase.euros }} €</td>
            <td class="py-3 px-2 text-gray-600">
              {{ new Date(purchase.purchase_datetime).toLocaleString() }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import http from '../../lib/axios'
// Importação para os gráficos G6
import { Line, Bar } from 'vue-chartjs'
import { 
  Chart as ChartJS, Title, Tooltip, Legend, LineElement, 
  CategoryScale, LinearScale, PointElement, BarElement 
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, BarElement)

const stats = ref({})
const loaded = ref(false)

// Configuração dos dados para o Gráfico de Linha (Receita)
const revenueChartData = computed(() => ({
  labels: stats.value.revenue_history?.map(h => h.date).reverse() || [],
  datasets: [{
    label: 'Receita (€)',
    borderColor: '#10b981',
    backgroundColor: '#10b981',
    data: stats.value.revenue_history?.map(h => h.total).reverse() || []
  }]
}))

// Configuração dos dados para o Gráfico de Barras (Variantes)
const variantsChartData = computed(() => ({
  labels: stats.value.game_variants?.map(v => v.type == '3' ? 'Bisca 3' : 'Bisca 9') || [],
  datasets: [{
    label: 'Total de Jogos',
    backgroundColor: ['#3b82f6', '#f59e0b'],
    data: stats.value.game_variants?.map(v => v.total) || []
  }]
}))

onMounted(async () => {
  try {
    const response = await http.get('/admin/statistics')
    stats.value = response.data
    loaded.value = true
  } catch (error) {
    console.error("Erro ao carregar estatísticas de admin:", error)
  }
})


</script>