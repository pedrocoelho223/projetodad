<template>
  <div>
    <NavigationMenu>
      <NavigationMenuList class="justify-around gap-20">
        
        <NavigationMenuItem>
          <NavigationMenuLink as-child>
            <RouterLink to="/leaderboard" class="font-medium hover:text-blue-600 px-3">
              🏆 Ranking
            </RouterLink>
          </NavigationMenuLink>
        </NavigationMenuItem>

        <NavigationMenuItem v-if="authStore.currentUser?.type === 'A'">
          <NavigationMenuLink as-child>
            <RouterLink to="/admin/statistics" class="font-medium text-purple-600 hover:text-purple-800 px-3">
              📊 Estatísticas
            </RouterLink>
          </NavigationMenuLink>
        </NavigationMenuItem>

        <NavigationMenuItem>
          <NavigationMenuTrigger>Testing</NavigationMenuTrigger>
          <NavigationMenuContent>
            <ul class="p-4 w-[200px]">
              <li>
                <NavigationMenuLink as-child>
                  <RouterLink to="/testing/laravel" class="block p-2 hover:bg-slate-100 rounded">
                    Laravel
                  </RouterLink>
                </NavigationMenuLink>
              </li>
              <li>
                <NavigationMenuLink as-child>
                  <RouterLink to="/testing/websockets" class="block p-2 hover:bg-slate-100 rounded">
                    Web Sockets
                  </RouterLink>
                </NavigationMenuLink>
              </li>
            </ul>
          </NavigationMenuContent>
        </NavigationMenuItem>

        <NavigationMenuItem v-if="!authStore.isLoggedIn">
          <div class="flex gap-4">
            <NavigationMenuLink as-child>
              <RouterLink to="/login" class="font-medium hover:text-blue-600"> Entrar </RouterLink>
            </NavigationMenuLink>
            <NavigationMenuLink as-child>
              <RouterLink to="/register" class="font-medium text-blue-600 hover:text-blue-800">
                Criar Conta
              </RouterLink>
            </NavigationMenuLink>
          </div>
        </NavigationMenuItem>

        <NavigationMenuItem v-else>
          <div v-if="authStore.currentUser" class="flex items-center gap-4">

            <NavigationMenuLink as-child>
              <RouterLink
                to="/coins"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-slate-100 transition-colors"
                :class="{
                  'font-bold text-yellow-600': authStore.currentUser.type === 'P',
                  'text-blue-600': authStore.currentUser.type === 'A'
                }"
              >
                <span v-if="authStore.currentUser.type === 'A'" class="flex items-center gap-2">
                    🛡️ <span class="hidden md:inline">Transações</span>
                </span>
                <span v-else class="flex items-center gap-2">
                    💰 {{ authStore.currentUser.coins_balance ?? 0 }} <span class="hidden md:inline">Moedas</span>
                </span>
              </RouterLink>
            </NavigationMenuLink>

            <NavigationMenuLink as-child>
              <RouterLink
                to="/profile"
                class="flex items-center gap-2 hover:bg-slate-100 px-3 py-1 rounded transition-colors"
              >
                <img
                  v-if="authStore.userPhotoUrl"
                  :src="authStore.userPhotoUrl"
                  class="w-8 h-8 rounded-full border border-gray-200 object-cover"
                  alt="Avatar"
                />
                <div v-else class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                    👤
                </div>
                <span class="text-sm font-medium">{{ authStore.currentUser.nickname }}</span>
              </RouterLink>
            </NavigationMenuLink>

            <NavigationMenuLink as-child>
              <RouterLink to="/my/games" class="text-sm font-medium hover:text-blue-600 px-2">
                📜 Histórico
              </RouterLink>
            </NavigationMenuLink>

            <NavigationMenuLink as-child>
              <a
                @click.prevent="handleLogout"
                class="cursor-pointer text-red-600 hover:text-red-800 text-sm font-medium px-2"
              >
                Sair
              </a>
            </NavigationMenuLink>
          </div>
        </NavigationMenuItem>
      </NavigationMenuList>
    </NavigationMenu>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
} from '@/components/ui/navigation-menu'

const authStore = useAuthStore()
const router = useRouter()

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>