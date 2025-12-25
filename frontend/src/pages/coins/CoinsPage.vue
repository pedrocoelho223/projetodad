<template>
  <div class="max-w-3xl mx-auto p-6 space-y-10">

    <h1 class="text-2xl font-bold">Coins</h1>

    <!-- Saldo -->
    <section class="bg-white p-4 rounded shadow">
      <h2 class="font-semibold mb-2">Saldo atual</h2>
      <p v-if="store.loadingBalance">A carregar...</p>
      <p v-else class="text-xl font-bold">
        {{ store.balance }} coins
      </p>
    </section>

    <!-- Compra -->
    <section class="bg-white p-4 rounded shadow">
      <h2 class="font-semibold mb-4">Comprar moedas</h2>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1">Método</label>
          <select v-model="form.type" class="border p-2 w-full" required>
            <option disabled value="">Selecionar</option>
            <option>MBWAY</option>
            <option>PAYPAL</option>
            <option>IBAN</option>
            <option>MB</option>
            <option>VISA</option>
          </select>
        </div>

        <div>
          <label class="block mb-1">Referência</label>
          <input v-model="form.reference" class="border p-2 w-full" required />
        </div>

        <div>
          <label class="block mb-1">Valor (€)</label>
          <input
            type="number"
            min="1"
            max="99"
            v-model.number="form.value"
            class="border p-2 w-full"
            required
          />
        </div>

        <button
          class="bg-black text-white px-4 py-2 rounded"
          :disabled="store.loadingPurchase"
        >
          {{ store.loadingPurchase ? 'A processar…' : 'Comprar' }}
        </button>
      </form>

      <p v-if="store.success" class="text-green-600 mt-2">
        {{ store.success }}
      </p>
      <p v-if="store.error" class="text-red-600 mt-2">
        {{ store.error }}
      </p>
    </section>

    <!-- Histórico -->
    <section class="bg-white p-4 rounded shadow">
      <h2 class="font-semibold mb-2">Histórico</h2>

      <p v-if="store.loadingTransactions">A carregar...</p>

      <table v-else class="w-full border-collapse">
        <thead>
          <tr class="border-b">
            <th class="text-left p-2">Data</th>
            <th class="text-left p-2">Tipo</th>
            <th class="text-right p-2">Coins</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="t in store.transactions"
            :key="t.id"
            class="border-b"
          >
            <td class="p-2">
              {{ formatDate(t.transaction_datetime) }}
            </td>
            <td class="p-2">
              {{ t.type.name }}
            </td>
            <td
              class="p-2 text-right"
              :class="t.coins > 0 ? 'text-green-600' : 'text-red-600'"
            >
              {{ t.coins }}
            </td>
          </tr>
        </tbody>
      </table>
    </section>

  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useCoinsStore } from '@/stores/coins'

const store = useCoinsStore()

const form = reactive({
  type: '',
  reference: '',
  value: 1
})

function submit() {
  store.purchaseCoins(form)
}

function formatDate(date) {
  return new Date(date).toLocaleString()
}

onMounted(() => {
  store.fetchBalance()
  store.fetchTransactions()
})
</script>
