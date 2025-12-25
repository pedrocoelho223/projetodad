<template>
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
  loading.value = true;
  api.gameQueryParameters.page = page;

  try {
    const response = await api.getGames();
    games.value = response.data.data;
    meta.value = response.data.meta;
  } catch (e) {
    error.value = 'Erro ao carregar jogos.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

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
</style>
