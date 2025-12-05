<template>
    <div>
        <NavigationMenu>
            <NavigationMenuList class="justify-around gap-20">
                <!-- MENU DE TESTES (Mantido) -->
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

                <!-- UTILIZADOR NÃO LOGADO -->
                <NavigationMenuItem v-if="!authStore.isLoggedIn">
                    <div class="flex gap-4">
                        <NavigationMenuLink as-child>
                            <RouterLink to="/login" class="font-medium hover:text-blue-600">
                                Entrar
                            </RouterLink>
                        </NavigationMenuLink>
                        <NavigationMenuLink as-child>
                            <RouterLink to="/register" class="font-medium text-blue-600 hover:text-blue-800">
                                Criar Conta
                            </RouterLink>
                        </NavigationMenuLink>
                    </div>
                </NavigationMenuItem>

                <!-- UTILIZADOR LOGADO -->
                <NavigationMenuItem v-else>
                    <div class="flex items-center gap-6">
                        <!-- Link para o Perfil com Nome e Moedas -->
                        <NavigationMenuLink as-child>
                            <RouterLink to="/profile" class="flex items-center gap-2 hover:bg-slate-100 px-3 py-1 rounded transition-colors">
                                <!-- Avatar pequeno (opcional) -->
                                <img
                                    v-if="authStore.userPhotoUrl"
                                    :src="authStore.userPhotoUrl"
                                    class="w-6 h-6 rounded-full border border-gray-200"
                                />
                                <div class="text-sm">
                                    <span class="font-bold">{{ authStore.currentUser?.nickname }}</span>
                                    <span class="ml-2 text-yellow-600 font-bold">{{ authStore.currentUser?.coins_balance }} 💰</span>
                                </div>
                            </RouterLink>
                        </NavigationMenuLink>

                        <!-- Botão Logout -->
                        <NavigationMenuLink as-child>
                            <a
                                @click.prevent="handleLogout"
                                class="cursor-pointer text-red-600 hover:text-red-800 text-sm font-medium"
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
