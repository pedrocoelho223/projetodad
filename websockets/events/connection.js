import { createRoom, joinRoom, setReady, playCard, resign } from "../state/roomManager.js";

export const handleConnectionEvents = (io, socket) => {

  socket.on("create_room", async (payload, cb) => {
    try { cb?.({ ok:true, ...(await createRoom(io, socket, payload)) }); }
    catch (e) { cb?.({ ok:false, error: e?.message ?? "Erro" }); }
  });

  socket.on("join_room", async (payload, cb) => {
    try { cb?.({ ok:true, ...(await joinRoom(io, socket, payload)) }); }
    catch (e) { cb?.({ ok:false, error: e?.message ?? "Erro" }); }
  });

  socket.on("ready", async (payload, cb) => {
    try { cb?.({ ok:true, ...(await setReady(io, socket, payload)) }); }
    catch (e) { cb?.({ ok:false, error: e?.message ?? "Erro" }); }
  });

  socket.on("play_card", async (payload, cb) => {
    try { cb?.({ ok:true, ...(await playCard(io, socket, payload)) }); }
    catch (e) { cb?.({ ok:false, error: e?.message ?? "Erro" }); }
  });

  socket.on("resign", async (payload, cb) => {
    try { cb?.({ ok:true, ...(await resign(io, socket, payload)) }); }
    catch (e) { cb?.({ ok:false, error: e?.message ?? "Erro" }); }
  });
};
