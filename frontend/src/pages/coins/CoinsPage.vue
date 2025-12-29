<template>
  <div class="max-w-4xl mx-auto p-6 space-y-8">

    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold flex items-center gap-2">
        {{ isAdmin ? '🛡️ Administração de Transações' : '💰 Minhas Moedas' }}
      </h1>
      <button @click="refreshData" class="text-blue-600 hover:underline text-sm">
        ↻ Atualizar Dados
      </button>
    </div>

    <div v-if="!isAdmin" class="space-y-8">
      <section class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Saldo atual</h2>
        <p v-if="store.loadingBalance" class="text-gray-400">A carregar...</p>
        <p v-else class="text-4xl font-bold text-blue-600">
          {{ store.balance }} <span class="text-xl text-gray-400">coins</span>
        </p>
      </section>

      <section class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <h2 class="font-bold text-lg mb-4 text-gray-800">Comprar moedas</h2>

        <form @submit.prevent="submit" class="space-y-4 max-w-lg">
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Método de Pagamento</label>
            <select v-model="form.type" class="border border-gray-300 rounded-md p-2 w-full focus:ring-2 focus:ring-blue-500 outline-none" required>
              <option value="MBWAY">MBWAY</option>
              <option value="PAYPAL">PAYPAL</option>
              <option value="IBAN">IBAN</option>
              <option value="MB">MB</option>
              <option value="VISA">VISA</option>
            </select>
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Referência</label>
            <input v-model="form.reference" type="text" placeholder="Ex: 912345678" class="border border-gray-300 rounded-md p-2 w-full focus:ring-2 focus:ring-blue-500 outline-none" required />
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Valor (€)</label>
            <input type="number" min="1" max="99" v-model.number="form.value" class="border border-gray-300 rounded-md p-2 w-full focus:ring-2 focus:ring-blue-500 outline-none" required />
          </div>

          <button class="bg-black hover:bg-gray-800 text-white font-medium px-6 py-2 rounded-md transition-colors w-full sm:w-auto" :disabled="store.loadingPurchase">
            {{ store.loadingPurchase ? 'A processar...' : 'Comprar Moedas' }}
          </button>
        </form>

        <p v-if="store.success" class="text-green-600 mt-4 font-medium bg-green-50 p-2 rounded border border-green-200">
          ✅ {{ store.success }}
        </p>
        <p v-if="store.error" class="text-red-600 mt-4 font-medium bg-red-50 p-2 rounded border border-red-200">
          ❌ {{ store.error }}
        </p>
      </section>
    </div>

    <section class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
      <h2 class="font-bold text-lg mb-4 text-gray-800">Histórico de Transações</h2>

      <p v-if="store.loadingTransactions" class="text-gray-500 py-4 text-center">A carregar histórico...</p>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
              <th class="p-3">Data</th>
              <th v-if="isAdmin" class="p-3">Utilizador</th> <th class="p-3">Tipo</th>
              <th class="p-3 text-right">Coins</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="t in store.transactions" :key="t.id" class="hover:bg-gray-50 transition-colors">
              <td class="p-3 text-gray-700">
                {{ formatDate(t.transaction_datetime) }}
              </td>

              <td v-if="isAdmin" class="p-3">
                <div class="font-medium text-gray-900">{{ t.user ? t.user.nickname : 'Desconhecido' }}</div>
                <div class="text-xs text-gray-500">{{ t.user ? t.user.email : '' }}</div>
              </td>

              <td class="p-3 text-gray-600">
                {{ t.type ? t.type.name : 'Transação' }}
              </td>

              <td class="p-3 text-right font-bold font-mono" :class="t.coins > 0 ? 'text-green-600' : 'text-red-600'">
                {{ t.coins > 0 ? '+' : '' }}{{ t.coins }}
              </td>
            </tr>
            <tr v-if="store.transactions.length === 0">
                <td :colspan="isAdmin ? 4 : 3" class="p-6 text-center text-gray-400 italic">Sem registos encontrados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</template>

<script setup>
import { reactive, onMounted, computed } from 'vue'
import { useCoinsStore } from '@/stores/coins'
import { useAuthStore } from '@/stores/auth' // Importar AuthStore para saber se é Admin

const store = useCoinsStore()
const authStore = useAuthStore()

// Lógica de Admin
const isAdmin = computed(() => {
    const user = authStore.user || authStore.currentUser
    return user?.type === 'A'
})

const form = reactive({
  type: 'MBWAY', // Valor por defeito
  reference: '',
  value: 1
})

function submit() {
  store.purchaseCoins(form)
}

function refreshData() {
    if (!isAdmin.value) store.fetchBalance()
    store.fetchTransactions()
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleString('pt-PT')
}

onMounted(() => {
  refreshData()
})
</script>
