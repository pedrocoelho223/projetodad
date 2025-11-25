import { defineStore } from 'pinia'
import { ref, computed, watch, inject } from 'vue'
import { useAPIStore } from './api'
import { useAuthStore } from './auth'
import { toast } from 'vue-sonner'

export const useGameStore = defineStore('game', () => {
  const apiStore = useAPIStore()
  const authStore = useAuthStore()
  const socket = inject('socket')

  const autoCreateDifficulty = ref(null) // Variável nova

  const difficulties = ref([
    { value: 'easy', label: 'Easy', description: '4x2 grid' },
    { value: 'medium', label: 'Medium', description: '4x3 grid' },
    { value: 'hard', label: 'Hard', description: '4x4 grid' },
  ])
  const difficulty = ref('medium')

  const options = [1, 2, 3, 4, 5, 6, 7, 8].map((i) => {
    return { face: i, matched: false, flipped: false }
  })
  const cards = ref([])
  const flippedCards = ref([])
  const matchedPairs = ref([])
  const moves = ref(0)
  const beganAt = ref(undefined)
  const endedAt = ref(undefined)
  const selectedTheme = ref(null)

  const isGameComplete = computed(() => {
    if (cards.value.length === 0) return false
    return matchedPairs.value.length === cards.value.length
  })

  const setBoard = () => {
    cards.value = []
    flippedCards.value = []
    matchedPairs.value = []

    moves.value = 0

    let numPairs = 4
    if (difficulty.value === 'medium') numPairs = 6
    if (difficulty.value === 'hard') numPairs = 8

    const boardOptions = options.slice(0, numPairs)

    let idCounter = 0
    boardOptions.forEach((option, index) => {
      const imageUrl = selectedTheme.value?.cards?.[index]?.face_image_url || null

      cards.value.push({ id: idCounter++, ...option, imageUrl })
      cards.value.push({ id: idCounter++, ...option, imageUrl })
    })

    for (let i = cards.value.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1))
      ;[cards.value[i], cards.value[j]] = [cards.value[j], cards.value[i]]
    }

    beganAt.value = new Date()
  }

  const flipCard = (card) => {
    if (flippedCards.value.includes(card.id)) return
    if (matchedPairs.value.includes(card.id)) return
    if (flippedCards.value.length >= 2) return

    flippedCards.value.push(card.id)
    card.flipped = true
    if (flippedCards.value.length == 2) {
      moves.value++
      checkForMatch()
    }
  }

  const checkForMatch = () => {
    if (flippedCards.value.length !== 2) return

    const [first, second] = flippedCards.value
    const firstCard = cards.value.find((c) => c.id === first)
    const secondCard = cards.value.find((c) => c.id === second)
    if (firstCard.face === secondCard.face) {
      matchedPairs.value.push(first, second)
      firstCard.matched = true
      secondCard.matched = true
      flippedCards.value = []
    } else {
      setTimeout(() => {
        firstCard.flipped = false
        secondCard.flipped = false
        flippedCards.value = []
      }, 1000)
    }
  }

  const saveGame = async () => {
    const formatDateMySQL = (date) => {
      if (!date) return null
      const d = new Date(date)
      return d.toISOString().slice(0, 19).replace('T', ' ')
    }

    const now = new Date()
    const totalSeconds = beganAt.value ? Math.floor((now - beganAt.value) / 1000) : 0

    // VERIFICAÇÃO CRÍTICA:
    // Confirma no teu ficheiro 'auth.js' se a variável é 'user' ou 'currentUser'.
    // Pelo código do multiplayer abaixo, parece ser 'currentUser'.
    const userId = authStore.currentUser?.id || authStore.user?.id;

    const gameData = {
      type: 'S',
      status: 'E',
      created_by: userId,
      began_at: formatDateMySQL(beganAt.value),
      ended_at: formatDateMySQL(now),
      total_time: totalSeconds,

      // --- ALTERAÇÃO AQUI ---
      // De: player1_moves: moves.value,
      // Para:
      total_moves_played: moves.value,
      // ----------------------

      difficulty: difficulty.value,
    }

    try {
      // Confirma se o apiStore.postGame aponta para a rota correta (ex: /games)
      await apiStore.postGame(gameData)
      toast.success('Jogo guardado com sucesso!')
    } catch (error) {
      console.error('Erro ao gravar jogo:', error)
      toast.error('Erro ao gravar o jogo.')
    }
  }

  watch(isGameComplete, (value) => {
    if (value) {
      endedAt.value = new Date()
      saveGame() // <--- Adiciona isto para guardar automático quando ganhas
    }
  })

  // ----- ---------------------------- -----------
  // ----- Added for multiplayer games: -----------
  // ----- ---------------------------- -----------

  const games = ref([])

  const createGame = (difficulty = 'medium') => {
    if (!authStore.currentUser) {
      toast.error('You must be logged in to create a game')
      return
    }
    if (!socket || !socket.connected) {
      toast.error('Not connected to server. Please refresh the page.')
      return
    }
    socket.emit('create-game', difficulty)
  }

  const setGames = (newGames) => {
    games.value = newGames
    console.log(`[Game] Games changed | game count ${games.value.length}`)
  }

  const myGames = computed(() => {
    return games.value.filter((game) => game.creator == authStore.currentUser.id)
  })

  const availableGames = computed(() => {
    return games.value.filter((game) => game.creator != authStore.currentUser.id)
  })

  const multiplayerGame = ref({})

  const setMultiplayerGame = (game) => {
    multiplayerGame.value = game
    console.log(`[Game] Multiplayer Game changed | game moves ${game.moves}`)
  }

  return {
    difficulties,
    difficulty,
    cards,
    moves,
    isGameComplete,
    setBoard,
    flipCard,
    checkForMatch,
    saveGame,
    selectedTheme,

    // ----- ---------------------------- -----------
    // ----- Added for multiplayer games: -----------
    // ----- ---------------------------- -----------

    games,
    createGame,
    setGames,
    myGames,
    availableGames,
    multiplayerGame,
    setMultiplayerGame,
    autoCreateDifficulty,
  }
})
