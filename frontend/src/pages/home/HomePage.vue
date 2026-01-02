<!-- <template>
  <div>
    <h1>Bem-vindo ao Jogo da Bisca</h1>
    <div class="game-list-container">
    <div class="header">
      <h2>Lobby de Jogos (Bisca)</h2>
      <div class="actions">
        <button class="btn-primary" @click="onClickCreate" :disabled="creating">
  {{ creating ? 'A criar...' : '+ Novo Jogo' }}
</button>
        <button class="btn-secondary" @click="fetchGames(api.gameQueryParameters.page)">
          ↻ Atualizar
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading">A carregar jogos...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else>
      <table class="styled-table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Tipo</th>
            <th>Jogadores</th>
            <th class="text-center">Placar</th>
            <th>Vencedor</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="game in games" :key="game.id">
            <td>{{ formatDate(game.began_at) }}</td>

            <td>
              <span :class="['badge', getTypeName(game.type).class]">
                {{ getTypeName(game.type).label }}
              </span>
            </td>

            <td>
              <div class="players-grid">
                <div class="player-row">
                    <span v-if="game.player1" class="player-name">{{ game.player1.name }}</span>
                    <span v-else class="text-muted">User #{{ game.player1_user_id }}</span>
                </div>
                <div class="vs-small">vs</div>
                <div class="player-row">
                    <span v-if="game.player2" class="player-name">{{ game.player2.name }}</span>
                    <span v-else class="text-muted">User #{{ game.player2_user_id }}</span>
                </div>
              </div>
            </td>

            <td class="text-center">
              <div v-if="game.status === 'Ended'" class="score-display">
                <span class="score-val">{{ game.player1_points }}</span>
                <span class="score-sep">-</span>
                <span class="score-val">{{ game.player2_points }}</span>
              </div>
              <span v-else class="text-muted">-</span>
            </td>

            <td>
              <div v-if="game.winner" class="winner-info">
                <img
                  v-if="game.winner.photo_avatar_filename"
                  :src="getPhotoUrl(game.winner.photo_avatar_filename)"
                  class="avatar"
                  alt="Avatar"
                  @error="onImgError"
                />
                <span class="winner-name">🏆 {{ game.winner.name }}</span>
              </div>
              <span v-else class="text-muted">-</span>
            </td>

            <td>
              <button
                v-if="game.status === 'Pending' || game.status === 'P'"
                class="btn-join"
                @click="joinGame(game.id)"
              >
               Entrar
              </button>
              <span v-else class="status-label">{{ game.status }}</span>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="meta.total > 0">
        <button :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)"> &laquo; Anterior </button>
        <span>Página {{ meta.current_page }} de {{ meta.last_page }}</span>
        <button :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)"> Próximo &raquo; </button>
      </div>
    </div>
  </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAPIStore } from '@/stores/api';
// Não precisas de importar axios, usamos a api store!


import { useRouter } from 'vue-router';

const router = useRouter();


const api = useAPIStore();
const games = ref([]);
const meta = ref({});
const loading = ref(false);
const creating = ref(false);
const error = ref(null);

// --- FUNÇÃO CRÍTICA PARA AS IMAGENS ---
const API_DOMAIN = import.meta.env.VITE_API_DOMAIN || 'http://localhost:8000'

const getPhotoUrl = (filename) => {
  if (!filename) return `${API_DOMAIN}/storage/photos/anonymous.png`
  return `${API_DOMAIN}/storage/photos/${filename}`
}

// --- FORMATADORES ---
const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
  }).format(date);
};

const getTypeName = (type) => {
  if (type == '3') return { label: 'Normal (3)', class: 'type-normal' };
  if (type == '9') return { label: 'Rápido (9)', class: 'type-fast' };
  return { label: `Tipo ${type}`, class: 'type-other' };
};

// --- AÇÕES ---
const onImgError = (e) => { e.target.src = `${API_DOMAIN}/storage/photos/anonymous.png` }

const fetchGames = async (page = 1) => {
  loading.value = true
  error.value = null

  try {
    const res = await api.getGames(page)
    const payload = res.data

    games.value = res.data.data

    const m = payload?.meta ?? {}
    meta.value = {
      page: m.current_page ?? page,
      last_page: m.last_page ?? 1,
      total: m.total ?? 0,
      per_page: m.per_page ?? 15,
    }
  } catch (e) {
    console.error('fetchGames failed:', e)
    games.value = []
    meta.value = { page: 1, last_page: 1, total: 0, per_page: 15 }
    error.value = e
  } finally {
    loading.value = false
  }
}


const changePage = (newPage) => {
    fetchGames(newPage);
};

const onClickCreate = async () => {
  console.log('[UI] click Novo Jogo')
  await createGame()
}


const createGame = async () => {
    creating.value = true;
    error.value = null;
    try {
        // CORREÇÃO CRÍTICA: Usar api.postGame
        // Isto usa a Store, que já tem o URL certo e o Token de Auth
        await api.postGame({
            type: '3',
            status: 'pending',
            began_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
        });

        // Atualiza a lista e volta à página 1
        await fetchGames(1);
    } catch (e) {
        console.error(e);
        error.value = 'Erro ao criar: ' + (e.response?.data?.message || e.message);
    } finally {
        creating.value = false;
    }
};

const joinGame = (gameId) => {
    console.log("A entrar no jogo:", gameId);
    router.push(`/game/${gameId}`);
};

onMounted(() => {
  fetchGames();
});
</script>

<style scoped>
.game-list-container { padding: 20px; font-family: 'Segoe UI', sans-serif; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.actions { display: flex; gap: 10px; }

/* Tabela */
.styled-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
.styled-table th { background-color: #f8f9fa; color: #333; font-weight: 600; padding: 15px; text-align: left; border-bottom: 2px solid #eee; }
.styled-table td { padding: 12px 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
.text-center { text-align: center; }
.text-muted { color: #999; font-style: italic; }

/* Elementos Visuais */
.players-grid { display: flex; flex-direction: column; gap: 2px; }
.player-name { font-weight: 500; color: #2c3e50; }
.vs-small { font-size: 0.75em; color: #aaa; text-transform: uppercase; font-weight: bold; margin-left: 2px; }

.score-display { display: inline-flex; align-items: center; gap: 8px; background: #f1f2f6; padding: 4px 12px; border-radius: 20px; font-weight: bold; }
.score-val { font-size: 1.1em; color: #2c3e50; }
.score-sep { color: #aaa; }

.winner-info { display: flex; align-items: center; gap: 8px; color: #27ae60; font-weight: bold; }
.avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }

.badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8em; color: white; white-space: nowrap; }
.type-normal { background-color: #9b59b6; }
.type-fast { background-color: #e67e22; }
.type-other { background-color: #95a5a6; }

/* Botões */
button { cursor: pointer; padding: 8px 16px; border-radius: 6px; border: none; font-weight: 600; transition: all 0.2s; }
.btn-primary { background-color: #3498db; color: white; }
.btn-primary:hover { background-color: #2980b9; }
.btn-primary:disabled { background-color: #95a5a6; }
.btn-join { background-color: #2ecc71; color: white; }
.btn-join:hover { background-color: #27ae60; }
.btn-secondary { background-color: #ecf0f1; color: #2c3e50; }

.pagination { margin-top: 20px; display: flex; justify-content: center; gap: 15px; align-items: center; }
.error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
</style> -->

<template>
  <div class="home-container">
    <header class="mb-4">
      <h1 class="fw-bold">Bisca</h1>
      <p class="text-secondary">Joga, compete nos rankings e ganha moedas!</p>
    </header>

    <div class="main-grid">
      <section class="game-modes">
        <h3 class="section-title">Jogar</h3>
        <p class="text-muted text-sm">Escolhe um modo para iniciar uma partida</p>

        <div class="cards-grid">
          <div class="game-card" @click="createGame('3', 'single')">
            <div class="icon">🤖</div>
            <div class="details">
              <strong>Bisca 3 (Bot)</strong>
              <span>Singleplayer</span>
            </div>
          </div>

          <div class="game-card" @click="createGame('9', 'single')">
            <div class="icon">🧩</div>
            <div class="details">
              <strong>Bisca 9 (Bot)</strong>
              <span>Singleplayer</span>
            </div>
          </div>

          <div class="game-card multiplayer" @click="createGame('3', 'multi')">
            <div class="icon">⚔️</div>
            <div class="details">
              <strong>Bisca 3</strong>
              <span>Multiplayer</span>
            </div>
          </div>

          <div class="game-card multiplayer" @click="createGame('9', 'multi')">
            <div class="icon">🏆</div>
            <div class="details">
              <strong>Bisca 9</strong>
              <span>Multiplayer</span>
            </div>
          </div>
        </div>

        <div class="mt-5">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="section-title">Lobby de Jogos Ativos</h4>
            <button class="btn-refresh" @click="fetchGames(1)">↻ Atualizar</button>
          </div>

          <div v-if="loading" class="text-center p-3">A carregar lobby...</div>
          <div v-else-if="games.length === 0" class="empty-state">
            Não há jogos pendentes. Cria um novo!
          </div>

          <table v-else class="simple-table">
            <thead>
              <tr>
                <th>Criador</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="game in games" :key="game.id">
                <td>
                  {{ game.player1 ? game.player1.nickname : 'User #' + game.player1_user_id }}
                </td>
                <td>
                  <span class="badge">{{ game.type == '3' ? 'Bisca 3' : 'Bisca 9' }}</span>
                </td>
                <td>{{ game.status }}</td>
                <td>
                  <button v-if="isPending(game.status)" class="btn-join" @click="joinGame(game.id)">
                    Entrar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <aside class="sidebar">
        <div class="info-card mb-4">
          <h4>Quick Links</h4>
          <ul class="link-list">
            <li>📊 <a @click.prevent="router.push('/leaderboard')" href="#">Leaderboards</a></li>
            <li>
              📑 <a @click.prevent="router.push('/statistics')" href="#">Estatísticas Globais</a>
            </li>
          </ul>
        </div>

        <div class="info-card">
          <h4>Top Players</h4>
          <p class="text-xs text-muted">Melhores jogadores por vitórias</p>

          <div class="leaderboard-list">
            <div v-for="(player, index) in topPlayers" :key="player.id" class="lb-item">
              <div class="lb-rank" :class="'rank-' + (index + 1)">{{ index + 1 }}</div>
              <div class="lb-info">
                <span class="lb-name">{{ player.nickname }}</span>
                <span class="lb-stats">⭐ {{ player.wins }} Vitórias</span>
              </div>
              <div class="lb-icon">🏆</div>
            </div>

            <div v-if="topPlayers.length === 0" class="text-muted text-sm p-2">
              Sem dados de ranking.
            </div>
          </div>

          <div class="text-center mt-3">
            <a href="#" class="text-sm link-primary">Ver Ranking Completo →</a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAPIStore } from '@/stores/api'
import { useRouter } from 'vue-router'
import http from '@/lib/axios'

const router = useRouter()
const api = useAPIStore()

// State
const games = ref([])
const topPlayers = ref([]) // Novo estado para Leaderboard
const loading = ref(false)
const meta = ref({})



// --- API ACTIONS ---

const fetchGames = async (page = 1) => {
  loading.value = true
  try {
    // Certifica-te que api.getGames está a funcionar na store
    const res = await api.getGames(page)
    games.value = res.data.data || []
    // Mapeamento simples de paginação
    if (res.data.meta) meta.value = res.data.meta
  } catch (e) {
    console.error('Erro ao buscar jogos:', e)
  } finally {
    loading.value = false
  }
}

const isPending = (status) => status === 'P' || status === 'Pending'

/*const fetchTopPlayers = async () => {
  try {
    const res = await api.getTopPlayers({ scope: 'overall', limit: 3 })
    topPlayers.value = res.data.data ?? []
  } catch (e) {
    console.error("Erro leaderboard", e)
    topPlayers.value = []
  }
}*/


const createGame = async (type, mode) => {
  // 1. Se for Single Player, redireciona para a mesa de jogo
  if (mode === 'single') {
    // Nota: O nosso motor atual faz Bisca de 3 por defeito.
    // Futuramente podes passar o tipo como query param: router.push(`/game/single?type=${type}`)
    router.push('/game/single')
    return
  }

  // 2. Se for Multiplayer (G4 - Futuro)
  alert("O modo Multiplayer (Websockets) será implementado brevemente!")
}

const joinGame = (gameId) => {
  router.push(`/game/${gameId}`)
}

const fetchTopPlayers = async () => {
  try {
    // Usa o axios configurado que importaste
    const res = await http.get('/leaderboards/games')
    topPlayers.value = res.data.slice(0, 3)
  } catch (e) {
    console.error("Erro ao carregar leaderboard na sidebar:", e)
  }
}

onMounted(() => {
 //fetchGames()
 fetchTopPlayers()
})


</script>

<style scoped>
/* Variáveis e Fontes */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

.home-container {
  padding: 30px;
  max-width: 1200px;
  margin: 0 auto;
  font-family: 'Inter', sans-serif;
  color: #1e293b;
}

.main-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 40px;
}

@media (max-width: 768px) {
  .main-grid {
    grid-template-columns: 1fr;
  }
}

/* Section Titles */
.section-title {
  font-weight: 700;
  margin-bottom: 0.5rem;
}
.text-secondary {
  color: #64748b;
}
.text-muted {
  color: #94a3b8;
}
.text-sm {
  font-size: 0.875rem;
}
.text-xs {
  font-size: 0.75rem;
}

/* Cards Grid */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.game-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.game-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  border-color: #cbd5e1;
}

.game-card .icon {
  font-size: 2rem;
}
.game-card .details {
  display: flex;
  flex-direction: column;
}
.game-card .details strong {
  font-size: 1rem;
  color: #0f172a;
}
.game-card .details span {
  font-size: 0.8rem;
  color: #64748b;
}

/* Sidebar & Leaderboard */
.info-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.link-list {
  list-style: none;
  padding: 0;
  margin-top: 10px;
}
.link-list li {
  margin-bottom: 10px;
  font-weight: 600;
}
.link-list a {
  text-decoration: none;
  color: #334155;
}

.leaderboard-list {
  margin-top: 15px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.lb-item {
  display: flex;
  align-items: center;
  padding: 10px;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
}

.lb-rank {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #cbd5e1;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: bold;
  margin-right: 12px;
}
.rank-1 {
  background: #eab308;
} /* Ouro */
.rank-2 {
  background: #94a3b8;
} /* Prata */
.rank-3 {
  background: #b45309;
} /* Bronze */

.lb-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.lb-name {
  font-weight: 700;
  font-size: 0.9rem;
  color: #334155;
}
.lb-stats {
  font-size: 0.75rem;
  color: #64748b;
}

/* Tabela Simplificada */
.simple-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  font-size: 0.9rem;
}
.simple-table th {
  text-align: left;
  color: #64748b;
  padding: 8px;
  border-bottom: 1px solid #e2e8f0;
}
.simple-table td {
  padding: 12px 8px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}
.btn-join {
  background: #22c55e;
  color: white;
  border: none;
  padding: 4px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.8rem;
}
.btn-refresh {
  background: none;
  border: 1px solid #e2e8f0;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
  color: #64748b;
}
.badge {
  background: #e0f2fe;
  color: #0369a1;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}
</style>
