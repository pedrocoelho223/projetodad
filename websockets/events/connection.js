export const handleConnectionEvents = (io, socket) => {
  
  // 1. Criar ou Entrar num Jogo (Sala)
  socket.on("join_game", (gameId) => {
    // O socket "entra" na sala com o ID do jogo
    socket.join(gameId); 
    console.log(`Socket ${socket.id} entrou no jogo ${gameId}`);
    
    // Avisa APENAS quem está nessa sala que alguém entrou
    io.to(gameId).emit("player_joined", { 
      socketId: socket.id, 
      message: "Novo jogador entrou!" 
    });
  });

  // 2. Jogada (Virar Carta)
  socket.on("play_move", (data) => {
    // data deve trazer { gameId, cardIndex, value }
    
    // Reenvia a jogada para o outro jogador na mesma sala
    socket.to(data.gameId).emit("opponent_move", data);
  });
  
  // 3. Sair
  socket.on("disconnect", () => {
    console.log("Cliente desconectado:", socket.id);
  });
};