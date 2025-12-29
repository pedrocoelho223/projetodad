New-Item -ItemType Directory -Force src\pages\games\multiplayer | Out-Null

@'
<template>
  <div class="p-6">
    <h1 class="text-xl font-bold">Multiplayer Room</h1>
    <p>TODO: implementar</p>
  </div>
</template>
'@ | Set-Content -Encoding UTF8 src\pages\games\multiplayer\MultiplayerRoom.vue
