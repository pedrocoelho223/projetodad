import { triggerFlipDelay } from "../events/game.js";

const games = new Map();
let currentGameID = 0;

export const createGame = (difficulty, user) => {
  currentGameID++;
  const game = {
    id: currentGameID,
    difficulty,
    creator: user.id,
    player1: user.id,
    player2: null,
    winner: null,
    currentPlayer: user.id,
    cards: generateBoard(difficulty),
    flippedCards: [],
    matchedPairs: [],
    started: false,
    complete: false,
    moves: 0,
    beganAt: null,
    endedAt: null,
  };

  games.set(currentGameID, game);
  return game;
};

export const getGames = () => {
  return games.values().toArray();
};

const options = [1, 2, 3, 4, 5, 6, 7, 8].map((i) => {
  return { face: i, matched: false, flipped: false };
});

const generateBoard = (difficulty) => {
  const cards = [];
  let numPairs = 4;

  if (difficulty === "medium") numPairs = 6;
  if (difficulty === "hard") numPairs = 8;

  const boardOptions = options.slice(0, numPairs);
  let idCounter = 0;

  boardOptions.forEach((option) => {
    cards.push({ id: idCounter++, ...option });
    cards.push({ id: idCounter++, ...option });
  });

  for (let i = cards.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [cards[i], cards[j]] = [cards[j], cards[i]];
  }

  return cards;
};

export const joinGame = (gameID, player2) => {
  games.get(gameID).player2 = player2;
};

export const flipCard = (gameID, card) => {
  const game = games.get(gameID);

  if (!game.beganAt) {
    game.beganAt = new Date();
    game.started = true;
  }

  const gameCard = game.cards.find((c) => c.id == card.id);

  // --- CORREÇÃO AQUI ---
    // Antes tinhas apenas "return", agora tens de devolver o "game"
    if (game.flippedCards.includes(gameCard.id)) return game 
    if (game.matchedPairs.includes(gameCard.id)) return game
    if (game.flippedCards.length >= 2) return game 
    // ---------------------

  game.flippedCards.push(gameCard.id);
  gameCard.flipped = true;
  
  if (game.flippedCards.length == 2) {
    game.moves++;
    checkForMatch(game);
    checkForGameComplete(game);
  }

  return game;
};

const checkForMatch = (game) => {
  if (game.flippedCards.length !== 2) return;

  const [first, second] = game.flippedCards;
  const firstCard = game.cards.find((c) => c.id === first);
  const secondCard = game.cards.find((c) => c.id === second);

  if (firstCard.face === secondCard.face) {
    game.matchedPairs.push(first, second);
    firstCard.matched = true;
    secondCard.matched = true;
    game.flippedCards = [];
  } else {
    firstCard.flipped = true;
    secondCard.flipped = true;
    setTimeout(() => {
      triggerFlipDelay(game);
    }, 1000);
  }
};

const checkForGameComplete = (game) => {
  if (game.matchedPairs.length === game.cards.length) {
    game.complete = true;
    game.winner = game.currentPlayer;
    game.endedAt = new Date();

    // CHAMAR AQUI A FUNÇÃO
    saveGameToDatabase(game);
  }
};

export const clearFlippedCard = (game) => {
  if (game.flippedCards.length !== 2) return;

  const [first, second] = game.flippedCards;
  const firstCard = game.cards.find((c) => c.id === first);
  const secondCard = game.cards.find((c) => c.id === second);

  firstCard.flipped = false;
  secondCard.flipped = false;
  game.flippedCards = [];
  game.currentPlayer =
    game.currentPlayer == game.player1 ? game.player2 : game.player1;

  return game;
};

export const removeGame = (gameID) => {
  //Verificar se o jogo existe ou se quem pede a remoção é o criador
  const id = Number(gameID);
  if (games.has(id)) {
    games.delete(id);
    return true;
  }
  return false;
};

const saveGameToDatabase = async (game) => {
  try {
    const response = await fetch("http://localhost:8000/api/games", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        type: "M",
        status: "E",
        winner_id: game.winner,
        created_by: game.creator,
        total_moves: game.moves,
        began_at: game.beganAt,
        ended_at: game.endedAt,

        // --- AQUI ESTÁ A CORREÇÃO ---
        // A API exige 'player1_id' e 'player2_id'
        player1_id: game.player1,
        player2_id: game.player2,
      }),
    });

    if (!response.ok) {
      // ... o resto do teu código de erro ...
      console.error(
        "[Game] Erro ao guardar jogo na BD:",
        await response.text()
      );
    } else {
      console.log("[Game] Jogo guardado com sucesso na BD.");
    }
  } catch (error) {
    console.error("[Game] Erro de conexão à API:", error);
  }
};
