import { Server } from "socket.io";
import { handleConnectionEvents } from "./events/connection.js";

export const server = {
  io: null,
};

export const serverStart = (port) => {
  server.io = new Server(port, {
    cors: {
      origin: [
        "http://web-dad-group-5-172.22.21.253.sslip.io",
        "http://localhost:5173",
        "http://localhost"
      ],
      methods: ["GET", "POST"],
      credentials: true
    }
  });

  server.io.on("connection", (socket) => {
    console.log("New connection:", socket.id);

    handleConnectionEvents(server.io, socket);
  });

  console.log(`Socket.io server running on port ${port}`);
};
