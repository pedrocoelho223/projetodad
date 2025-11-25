<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useGameStore } from '@/stores/game'
import { useAPIStore } from '@/stores/api'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const gameStore = useGameStore()
const apiStore = useAPIStore()

const router = useRouter()

const highScores = ref([]) // Singleplayer
const multiplayerScores = ref([]) // Multiplayer

const spDifficulty = ref('medium') // Pre-selecionado para Single Player
const mpDifficulty = ref('medium') // Pre-selecionado para Multiplayer

// Função para iniciar Multiplayer direto
const startMultiplayer = () => {
  if (!mpDifficulty.value) {
    // Podes usar um toast aqui se quiseres
    alert('Por favor escolhe uma dificuldade (Easy, Medium ou Hard)!')
    return
  }
  // Guarda a dificuldade na store para o Lobby saber que tem de criar jogo
  gameStore.autoCreateDifficulty = mpDifficulty.value
  router.push({ name: 'multiplayer-lobby' })
}

onMounted(async () => {
  try {
    const response = await apiStore.getGames()
    const allGames = response.data.data

    // --- 1. FILTRAR SINGLEPLAYER (Type 'S') ---
    highScores.value = allGames
      .filter((game) => game.type === 'S')
      .map((item) => ({
        moves: item.total_moves_played,
        username: item.player1?.name ?? item.user?.name ?? 'Anónimo'
      }))
      .sort((a, b) => a.moves - b.moves) // Menos jogadas = Melhor
      .slice(0, 3)

    // --- 2. FILTRAR MULTIPLAYER (Type 'M') ---
    multiplayerScores.value = allGames
      .filter((game) => game.type === 'M' && game.status === 'E') // Só terminados
      .map((item) => ({
        moves: item.total_moves_played,
        // Tenta mostrar o nome, senão mostra o ID
        winner: item.winner?.name ?? (item.winner_id ? 'User ' + item.winner_id : 'Empate'),
        // ADICIONA ESTA LINHA SE AINDA NÃO TIVERES:
        time: item.total_time_played,
      }))
      .sort((a, b) => a.moves - b.moves)
      .slice(0, 3)
  } catch (error) {
    console.error('Erro ao carregar jogos:', error)
  }
})

const startSinglePlayer = () => {
  // Validação simples para não começar sem dificuldade
  if (!spDifficulty.value) return
  gameStore.difficulty = spDifficulty.value
  router.push({ name: 'singleplayer' })
}

/*const goToLobby = () => {
  router.push({ name: 'multiplayer-lobby' })
}*/

onMounted(async () => {
  try {
    // Obtém TODOS os jogos
    const response = await apiStore.getGames()
    const allGames = response.data.data

    // --- Lógica Singleplayer (Tipo 'S') ---
    highScores.value = response.data.data
      .filter((game) => game.type === 'S')
      .map((item) => ({
        moves: item.player1_moves,
        time: item.total_time,
        username: item.player1?.name,
      }))

      .sort((a, b) => {
        if (a.moves === b.moves) return a.time - b.time
        return a.moves - b.moves
      })
      .slice(0, 3)

    // --- Lógica Multiplayer (Tipo 'M') ---
    // --- MULTIPLAYER (CORREÇÃO AQUI) ---
    multiplayerScores.value = allGames
      .filter((game) => game.type === 'M' && game.status === 'E')
      .map((item) => ({
        // Tenta 'total_moves_played', se não der tenta 'total_moves', se não der assume 0
        moves: item.player1_moves,

        // Tenta 'total_time_played', se não der assume 0
        time: item.total_time,

        // Nome do vencedor
        winner: item.winner?.name ?? (item.winner_id ? 'User ' + item.winner_id : 'Empate'),
      }))
      .sort((a, b) => a.moves - b.moves)
      .slice(0, 3)
  } catch (error) {
    console.error('Erro ao carregar jogos:', error)
  }
})
</script>

<template>
  <div class="flex flex-row justify-center items-stretch gap-5 mt-10">
    <Card class="w-full max-w-md">
      <CardHeader>
        <CardTitle class="text-3xl font-bold text-center"> Single Player </CardTitle>
        <CardDescription class="text-center">
          Test your memory by finding matching pairs!
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <div class="space-y-2">
          <label class="text-sm font-medium">Choose Difficulty</label>
          <div class="grid grid-cols-3 gap-2">
            <Button
              v-for="level in gameStore.difficulties"
              :key="level.value"
              size="sm"
              :variant="spDifficulty === level.value ? 'default' : 'outline'"
              class="flex flex-col py-3 h-16"
              @click="spDifficulty = level.value"
            >
              <span class="font-semibold">{{ level.label }} </span>
              <span class="text-xs opacity-70">{{ level.description }}</span>
            </Button>
          </div>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium">High Scores (local)</label>
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="max-h-64 overflow-y-auto">
              <div
                v-if="highScores.length === 0"
                class="p-6 text-center text-sm text-muted-foreground"
              >
                No high scores yet. Be the first!
              </div>
              <div v-else class="divide-y">
                <div
                  v-for="(score, index) in highScores"
                  :key="index"
                  class="flex items-center justify-between p-3 hover:bg-muted/50 transition-colors"
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
                      :class="{
                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300':
                          index === 0,
                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300':
                          index === 1,
                        'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300':
                          index === 2,
                        'bg-muted text-muted-foreground': index > 2,
                      }"
                    >
                      {{ index + 1 }}
                    </div>
                    <div>
                      <div class="font-medium text-sm">
                        {{ score.moves }} Moves -- {{ score.username }}
                      </div>
                      <div class="text-xs text-muted-foreground">{{ score.time }} /s</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-center">
          <Button
            @click="startSinglePlayer"
            size="lg"
            variant="secondary"
            class="hover:bg-purple-500 hover:text-slate-200"
          >
            Start Game
          </Button>
        </div>
      </CardContent>
    </Card>
    <Card class="w-full max-w-md">
      <CardHeader>
        <CardTitle class="text-3xl font-bold text-center"> Multiplayer </CardTitle>
        <CardDescription class="text-center"
          >Test your memory by finding matching pairs with friends.</CardDescription
        >
      </CardHeader>
      <CardContent class="space-y-6">
        <div class="space-y-2">
          <label class="text-sm font-medium">Choose Difficulty</label>
          <div class="grid grid-cols-3 gap-2">
            <Button
              v-for="level in gameStore.difficulties"
              :key="level.value"
              size="sm"
              :variant="mpDifficulty === level.value ? 'default' : 'outline'"
              class="flex flex-col py-3 h-16"
              @click="mpDifficulty = level.value"
            >
              <span class="font-semibold">{{ level.label }} </span>
              <span class="text-xs opacity-70">{{ level.description }}</span>
            </Button>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">High Scores (local)</label>
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="max-h-64 overflow-y-auto">
              <div
                v-if="multiplayerScores.length === 0"
                class="p-6 text-center text-sm text-muted-foreground"
              >
                No high scores yet. Be the first!
              </div>
              <div v-else class="divide-y">
                <div
                  v-for="(score, index) in multiplayerScores"
                  :key="index"
                  class="flex items-center justify-between p-3 hover:bg-muted/50 transition-colors"
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
                      :class="{
                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300':
                          index === 0,
                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300':
                          index === 1,
                        'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300':
                          index === 2,
                        'bg-muted text-muted-foreground': index > 2,
                      }"
                    >
                      {{ index + 1 }}
                    </div>

                    <div>
                      <div class="font-medium text-sm">
                        {{ score.moves }} Moves -- {{ score.winner }}
                      </div>
                      <div class="text-xs text-muted-foreground">
                        {{ score.time ? parseFloat(score.time).toFixed(2) : '0.00' }} /s
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <Button @click="startMultiplayer" class="w-full font-bold"> Start Game </Button>
      </CardContent>
    </Card>
  </div>
</template>
