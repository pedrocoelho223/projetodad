<template>
  <div class="p-6">
    <h2 class="text-3xl font-bold mb-6">🛡️ Administração de Transações</h2>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold">Histórico de Transações</h3>
        <button
          @click="fetchTransactions"
          class="text-blue-600 hover:underline text-sm"
        >
          🔄 Atualizar Dados
        </button>
      </div>

      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b bg-gray-50">
            <th class="py-3 px-2">Data</th>
            <th class="py-3 px-2">Utilizador</th>
            <th class="py-3 px-2">Tipo</th>
            <th class="py-3 px-2">Valor (€)</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="t in transactions"
            :key="t.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="py-3 px-2">
              {{ new Date(t.purchase_datetime).toLocaleString() }}
            </td>
            <td class="py-3 px-2 font-medium text-blue-700">
              {{ t.user?.nickname }}
            </td>
            <td class="py-3 px-2">
              {{ t.payment_type || 'Compra de Moedas' }}
            </td>
            <td class="py-3 px-2 font-bold">
              {{ t.euros }} €
            </td>
          </tr>

          <tr v-if="transactions.length === 0">
            <td colspan="4" class="text-center text-gray-400 py-6">
              Sem registos encontrados.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAPIStore } from '@/stores/api'

const api = useAPIStore()
const transactions = ref([])
const loading = ref(false)

const fetchTransactions = async () => {
  loading.value = true
  try {
    const res = await api.getAdminTransactions()

    // API pode devolver { data: [...] }
    transactions.value = res.data.data ?? res.data ?? []
  } catch (error) {
    console.error('Erro ao carregar transações:', error)
    transactions.value = []
  } finally {
    loading.value = false
  }
}

onMounted(fetchTransactions)
</script>
