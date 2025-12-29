<template>
  <div class="bisca-table">

    <div class="hud">
      <div class="score-board opponent">
        <div class="avatar">🤖</div>
        <div class="details">
          <span>BOT</span>
          <strong>{{ gameState.scores?.bot || 0 }} pts</strong>
        </div>
      </div>

      <div class="game-status">
        <div v-if="gameState.gameOver" class="status-badge game-over">FIM DE JOGO</div>
        <div v-else class="status-badge" :class="{ 'my-turn': isPlayerTurn }">
          {{ isPlayerTurn ? '👇 TUA VEZ' : '⏳ BOT A JOGAR' }}
        </div>
      </div>

      <div class="score-board player">
        <div class="details">
          <span>TU</span>
          <strong>{{ gameState.scores?.player || 0 }} pts</strong>
        </div>
        <div class="avatar">👤</div>
      </div>
    </div>

    <div class="table-area">

      <div class="hand bot-hand">
        <div v-for="n in botCardCount" :key="n" class="card card-back"></div>
      </div>

      <div class="center-play">

        <div v-if="gameState.deckCount > 0" class="deck-container">
          <img :src="getCardUrl(gameState.trumpCard)" class="card trump" />
          <div class="card card-back deck">
            <span>{{ gameState.deckCount }}</span>
          </div>
        </div>

        <div class="slots">
          <div class="play-slot">
            <span v-if="gameState.table?.bot" class="label">Bot</span>
            <img v-if="gameState.table?.bot" :src="getCardUrl(gameState.table.bot)" class="card played" />
            <div v-else class="card-placeholder"></div>
          </div>

          <div class="play-slot">
            <span v-if="gameState.table?.player" class="label">Tu</span>
            <img v-if="gameState.table?.player" :src="getCardUrl(gameState.table.player)" class="card played" />
            <div v-else class="card-placeholder"></div>
          </div>
        </div>
      </div>

      <div class="hand player-hand">
        <div
          v-for="(card, index) in gameState.playerHand"
          :key="index"
          class="card-wrapper"
          @click="playCard(index)"
          :class="{ 'disabled': !isPlayerTurn }"
        >
          <img :src="getCardUrl(card)" class="card interactable" />
        </div>
      </div>

    </div>

    <div v-if="gameState.gameOver" class="modal-overlay">
      <div class="modal text-center">
        <h2>{{ finalResultText }}</h2>
        <div class="final-scores">
          <p>Bot: {{ gameState.scores.bot }}</p>
          <p>Tu: {{ gameState.scores.player }}</p>
        </div>
        <button @click="startGame" class="btn-primary">Novo Jogo</button>
        <button @click="quitGame" class="btn-secondary">Sair</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const loading = ref(false)
const gameToken = ref(null)
const gameState = ref({
  playerHand: [],
  table: { player: null, bot: null },
  scores: { player: 0, bot: 0 },
  trumpCard: null,
  deckCount: 0,
  turn: '',
  gameOver: false
})

const isPlayerTurn = computed(() => gameState.value.turn === 'player')
const botCardCount = computed(() => {
  if (gameState.value.gameOver) return 0;
  return gameState.value.deckCount > 0 ? 3 : Math.max(0, gameState.value.playerHand.length);
})

const finalResultText = computed(() => {
  const p = gameState.value.scores?.player || 0
  if (p > 60) return '🏆 GANHASTE!'
  return '💀 PERDESTE'
})

const API_URL = 'http://api-dad-group-5-172.22.21.253.sslip.io/api'

// TRADUTOR DE CARTAS
const getCardUrl = (card) => {
  if (!card) return ''
  const suitMap = { 'H': 'c', 'S': 'e', 'D': 'o', 'C': 'p' }
  const rankMap = { 'A': '1', 'K': '13', 'J': '11', 'Q': '12' }
  const suit = suitMap[card.suit] || 'c'
  const rank = rankMap[card.rank] || card.rank
  return `/cards/${suit}${rank}.png`
}

const startGame = async () => {
  loading.value = true
  try {
    const response = await fetch(`${API_URL}/games/single/start`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(authStore.token ? { 'Authorization': `Bearer ${authStore.token}` } : {})
      }
    })
    const data = await response.json()
    gameToken.value = data.game_token
    gameState.value = data.state
  } catch (e) {
    console.error(e)
  } finally { loading.value = false }
}

const playCard = async (index) => {
  if (!isPlayerTurn.value || loading.value) return
  loading.value = true
  try {
    const response = await fetch(`${API_URL}/games/single/play`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ game_token: gameToken.value, card_index: index })
    })
    if (response.ok) {
        const data = await response.json()
        gameState.value = data.state
    }
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

const quitGame = () => { if(confirm('Sair?')) router.push('/dashboard') }
onMounted(() => startGame())
</script>

<style scoped>
.bisca-table {
  background: #004d00; /* Verde Escuro Simples */
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: sans-serif;
  color: white;
  overflow: hidden;
}

.hud {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 20px; background: rgba(0,0,0,0.3);
}
.score-board { display: flex; align-items: center; gap: 10px; }
.avatar { font-size: 24px; }
.details { display: flex; flex-direction: column; }
.status-badge { padding: 5px 15px; border-radius: 20px; background: #555; font-weight: bold; }
.status-badge.my-turn { background: #ff9800; color: black; box-shadow: 0 0 10px #ff9800; }

/* MESA */
.table-area {
  flex: 1;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between; /* Bot topo, Jogo meio, Player baixo */
  padding: 20px 0;
}

.hand { display: flex; justify-content: center; gap: 10px; min-height: 120px; }

/* CARTAS - Tamanho Fixo para não haver bugs */
.card {
  width: 80px; height: 120px;
  background-color: white; border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.5);
  object-fit: contain;
}
.card-back {
  background: repeating-linear-gradient(45deg, #b71c1c, #b71c1c 10px, #c62828 10px, #c62828 20px);
  border: 2px solid white; display: flex; align-items: center; justify-content: center;
  font-size: 20px; font-weight: bold;
}

/* ÁREA CENTRAL */
.center-play {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 40px;
}

/* BARALHO E TRUNFO */
.deck-container { position: relative; width: 100px; height: 140px; }
.card.trump {
  position: absolute; top: 10px; left: 20px;
  transform: rotate(90deg); z-index: 0;
}
.card.deck {
  position: absolute; top: 0; left: 0; z-index: 10;
}

/* SLOTS DE JOGO */
.slots { display: flex; gap: 20px; }
.play-slot { width: 90px; height: 130px; display: flex; flex-direction: column; align-items: center; }
.card-placeholder {
  width: 80px; height: 120px; border: 2px dashed rgba(255,255,255,0.3); border-radius: 8px;
}
.label { font-size: 10px; text-transform: uppercase; margin-bottom: 4px; color: #aaa; }

/* INTERAÇÃO */
.interactable { cursor: pointer; transition: transform 0.2s; }
.interactable:hover { transform: translateY(-15px); }
.disabled { opacity: 0.5; pointer-events: none; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 100; }
.modal { background: white; color: black; padding: 20px; border-radius: 8px; width: 300px; }
.btn-primary { background: #2e7d32; color: white; border: none; padding: 10px; width: 100%; margin-top: 10px; cursor: pointer; }
.btn-secondary { background: #ddd; color: black; border: none; padding: 10px; width: 100%; margin-top: 5px; cursor: pointer; }
</style>
