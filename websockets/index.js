import { serverStart } from "./server.js";

// Alterado para ler APP_PORT (definido no Dockerfile) ou PORT ou 3000
const PORT = process.env.APP_PORT || process.env.PORT || 3000;

serverStart(PORT);

console.log(`Socket.io server running on port ${PORT}`);
console.log("Waiting for connections...");
