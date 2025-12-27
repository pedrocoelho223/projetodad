/* global process */
import crypto from "crypto";
import { newDeck, suit, points, trickWinner, marksFromWinPoints } from "./bisca.js";

const rooms = new Map();

function ms(){ return Date.now(); }

async function callApi(path, payload) {
  const base = process.env.API_INTERNAL_URL; // ex: http://api:8000 (service do k8s)
  const token = process.env.WS_SERVICE_TOKEN;

  const res = await fetch(`${base}${path}`, {
    method: "POST",
    headers: { "Content-Type":"application/json", "X-WS-TOKEN": token },
    body: JSON.stringify(payload),
  });

  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json?.message ?? `API ${res.status}`);
  return json;
}

function makeState(type){
  const deck = newDeck();
  const handSize = type === "9" ? 9 : 3;

  const trumpCard = deck[deck.length - 1];
  const trumpSuit = suit(trumpCard);

  const p1 = deck.splice(0, handSize);
  const p2 = deck.splice(0, handSize);

  return {
    type,
    trumpSuit,
    trumpCard,
    stock: deck,
    hands: { P1: p1, P2: p2 },
    table: [],     // [{player, card}]
    turn: "P1",
    wonPoints: { P1: 0, P2: 0 },
    ended: false,
    endedReason: null,
    finalPhase: false,
  };
}

function publicState(st, viewer){
  // ✅ não mostra a mão do adversário
  const copy = structuredClone(st);
  if (viewer === "P1") copy.hands.P2 = copy.hands.P2.map(() => "??");
  else copy.hands.P1 = copy.hands.P1.map(() => "??");
  return copy;
}

function emitToPlayers(io, room){
  for (const p of ["P1","P2"]) {
    const sid = room.sockets[p];
    if (!sid) continue;
    io.to(sid).emit("state", { roomId: room.id, state: publicState(room.state, p), meta: room.meta });
  }
}

function startTimer(io, room){
  clearTimeout(room.timer);
  room.deadline = ms() + 20000;

  room.timer = setTimeout(async () => {
    // ✅ timeout = forfeit do jogador do turno
    await doForfeit(io, room, room.state.turn, `timeout:${room.state.turn}`);
  }, 20000);
}

function resolveTrick(st){
  if (st.table.length < 2) return;

  const lead = st.table[0];
  const reply = st.table[1];

  const winSide = trickWinner(lead.card, reply.card, st.trumpSuit);
  const winner = winSide === "LEAD" ? lead.player : reply.player;
  const loser  = winner === "P1" ? "P2" : "P1";

  st.wonPoints[winner] += points(lead.card) + points(reply.card);

  st.table = [];

  // ✅ comprar (winner compra primeiro)
  if (st.stock.length > 0) {
    const c1 = st.stock.shift();
    const c2 = st.stock.shift();
    if (c1) st.hands[winner].push(c1);
    if (c2) st.hands[loser].push(c2);
  }

  if (st.stock.length === 0) st.finalPhase = true;
  st.turn = winner;

  if (st.hands.P1.length === 0 && st.hands.P2.length === 0) {
    st.ended = true;
    st.endedReason = "normal";
  }
}

function canFollowSuit(hand, leadSuit){
  return hand.some(c => suit(c) === leadSuit);
}

function play(st, player, card){
  if (st.ended) throw new Error("Jogo terminou");
  if (st.turn !== player) throw new Error("Não é o teu turno");

  const hand = st.hands[player];
  const idx = hand.indexOf(card);
  if (idx < 0) throw new Error("Carta inválida");

  // ✅ fase final: tem de assistir se puder
  if (st.finalPhase && st.table.length === 1) {
    const leadSuit = suit(st.table[0].card);
    if (canFollowSuit(hand, leadSuit) && suit(card) !== leadSuit) {
      throw new Error("Na fase final tens de assistir ao naipe");
    }
  }

  hand.splice(idx, 1);
  st.table.push({ player, card });
  st.turn = player === "P1" ? "P2" : "P1";

  resolveTrick(st);
}

async function finalizeIfEnded(io, room){
  if (!room.state.ended) return;

  clearTimeout(room.timer);

  const p1Pts = room.state.wonPoints.P1;
  const p2Pts = room.state.wonPoints.P2;

  const isDraw = p1Pts === p2Pts;
  const winPlayer = isDraw ? null : (p1Pts > p2Pts ? "P1" : "P2");
  const losePlayer = winPlayer ? (winPlayer === "P1" ? "P2" : "P1") : null;

  // ✅ persistência e coins no backend
  if (room.meta.mode === "game") {
    await callApi("/api/ws/multiplayer/game/end", {
      game_id: room.meta.gameId,
      player1_points: p1Pts,
      player2_points: p2Pts,
      is_draw: isDraw,
      winner_user_id: winPlayer ? room.users[winPlayer] : null,
      loser_user_id: losePlayer ? room.users[losePlayer] : null,
      ended_reason: room.state.endedReason,
    });
  } else {
    // match: soma marks e decide se termina
    if (!isDraw) {
      const winPts = winPlayer === "P1" ? p1Pts : p2Pts;
      const marks = marksFromWinPoints(winPts);
      room.meta.marks[winPlayer] += marks;
    }

    // termina quando alguém chega a 4 marks (bandeira) ou marks>=4
    if (room.meta.marks.P1 >= 4 || room.meta.marks.P2 >= 4) {
      const mWinner = room.meta.marks.P1 > room.meta.marks.P2 ? "P1" : "P2";
      const mLoser  = mWinner === "P1" ? "P2" : "P1";

      await callApi("/api/ws/multiplayer/match/end", {
        match_id: room.meta.matchId,
        player1_marks: room.meta.marks.P1,
        player2_marks: room.meta.marks.P2,
        winner_user_id: room.users[mWinner],
        loser_user_id: room.users[mLoser],
        ended_reason: room.state.endedReason,
      });
    } else {
      // ✅ próxima mão (novo game dentro do match)
      room.state = makeState(room.meta.type);
      emitToPlayers(io, room);
      startTimer(io, room);
      return;
    }
  }

  emitToPlayers(io, room);
  io.to(room.id).emit("ended", { roomId: room.id, meta: room.meta });

  // ✅ limpa a sala após 2 min
  setTimeout(() => rooms.delete(room.id), 120000);
}

async function doForfeit(io, room, forfeiter, reason){
  if (room.state.ended) return;

  const winner = forfeiter === "P1" ? "P2" : "P1";

  // ✅ atribuir cartas restantes ao vencedor (como pede o enunciado)
  const remaining = [
    ...room.state.hands.P1,
    ...room.state.hands.P2,
    ...room.state.stock,
    ...room.state.table.map(x => x.card),
  ];

  const sum = remaining.reduce((s,c)=>s + points(c), 0);
  room.state.wonPoints[winner] += sum;

  room.state.hands.P1 = [];
  room.state.hands.P2 = [];
  room.state.stock = [];
  room.state.table = [];

  room.state.ended = true;
  room.state.endedReason = reason;
  room.state.turn = winner;

  await finalizeIfEnded(io, room);
}

export async function createRoom(io, socket, payload){
  const { mode, type, player1_user_id, stake } = payload;
  if (!["game","match"].includes(mode)) throw new Error("Modo inválido");
  if (!["3","9"].includes(type)) throw new Error("Tipo inválido");

  const id = crypto.randomUUID();
  const room = {
    id,
    sockets: { P1: socket.id, P2: null },
    users: { P1: player1_user_id, P2: null },
    ready: { P1:false, P2:false },
    meta: { mode, type, stake: mode==="match" ? Number(stake ?? 3) : null, gameId:null, matchId:null, marks:{P1:0,P2:0} },
    state: null,
    timer: null,
    deadline: null,
  };

  rooms.set(id, room);
  socket.join(id);
  return { roomId: id };
}

export async function joinRoom(io, socket, payload){
  const { roomId, player2_user_id } = payload;
  const room = rooms.get(roomId);
  if (!room) throw new Error("Sala não existe");
  if (room.sockets.P2) throw new Error("Sala cheia");

  room.sockets.P2 = socket.id;
  room.users.P2 = player2_user_id;
  socket.join(roomId);

  return { roomId };
}

export async function setReady(io, socket, payload){
  const { roomId } = payload;
  const room = rooms.get(roomId);
  if (!room) throw new Error("Sala não existe");

  const player = room.sockets.P1 === socket.id ? "P1" : (room.sockets.P2 === socket.id ? "P2" : null);
  if (!player) throw new Error("Não estás na sala");

  room.ready[player] = true;

  // ✅ iniciar quando ambos ready
  if (room.ready.P1 && room.ready.P2 && !room.state) {
    if (room.meta.mode === "game") {
      const { game_id } = await callApi("/api/ws/multiplayer/game/create", {
        type: room.meta.type,
        player1_user_id: room.users.P1,
        player2_user_id: room.users.P2,
      });
      room.meta.gameId = game_id;
    } else {
      const { match_id } = await callApi("/api/ws/multiplayer/match/create", {
        type: room.meta.type,
        stake: room.meta.stake,
        player1_user_id: room.users.P1,
        player2_user_id: room.users.P2,
      });
      room.meta.matchId = match_id;
    }

    room.state = makeState(room.meta.type);
    emitToPlayers(io, room);
    startTimer(io, room);
  }

  return { roomId };
}

export async function playCard(io, socket, payload){
  const { roomId, card } = payload;
  const room = rooms.get(roomId);
  if (!room || !room.state) throw new Error("Jogo ainda não começou");

  const player = room.sockets.P1 === socket.id ? "P1" : (room.sockets.P2 === socket.id ? "P2" : null);
  if (!player) throw new Error("Não estás na sala");

  play(room.state, player, card);

  emitToPlayers(io, room);
  if (room.state.ended) await finalizeIfEnded(io, room);
  else startTimer(io, room);

  return { ok:true };
}

export async function resign(io, socket, payload){
  const { roomId } = payload;
  const room = rooms.get(roomId);
  if (!room || !room.state) throw new Error("Jogo ainda não começou");

  const player = room.sockets.P1 === socket.id ? "P1" : (room.sockets.P2 === socket.id ? "P2" : null);
  if (!player) throw new Error("Não estás na sala");

  await doForfeit(io, room, player, `resign:${player}`);
  emitToPlayers(io, room);

  return { ok:true };
}
