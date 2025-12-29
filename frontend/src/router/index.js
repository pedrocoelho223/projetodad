import HomePage from '@/pages/home/HomePage.vue'
import LoginPage from '@/pages/login/LoginPage.vue'
import LaravelPage from '@/pages/testing/LaravelPage.vue'
import WebsocketsPage from '@/pages/testing/WebsocketsPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import Register from '@/components/auth/RegisterUser.vue'
import Profile from '@/pages/users/ProfileUser.vue'

import BiscaGame from '@/components/game/BiscaGame.vue'

//import MultiplayerRoom from '@/pages/games/multiplayer/MultiplayerRoom.vue'

//import MultiplayerLobby from '@/pages/games/MultiplayerLobby.vue'

import CoinsPage from '@/pages/coins/CoinsPage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage,
      meta: { requiresAuth: false },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
      meta: { requiresAuth: false },
    },
    {
      path: '/register',
      name: 'Register',
      component: Register,
      meta: { requiresAuth: false },
    },
    {
      path: '/profile',
      name: 'Profile',
      component: Profile,
      meta: { requiresAuth: true },
    },
    {
      path: '/coins',
      name: 'coins',
      component: CoinsPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/testing',
      meta: { requiresAuth: false },
      children: [
        { path: 'laravel', component: LaravelPage },
        { path: 'websockets', component: WebsocketsPage },
      ],
    },
    {
      path: '/game/single',
      name: 'SinglePlayer',
      component: BiscaGame,
    },
    /*{
      path: '/games/multiplayer',
      component: MultiplayerLobby,
    },
    {
      path: '/games/multiplayer/:roomId',
      component: MultiplayerRoom,
    },*/
  ],
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    next({ name: 'login' })
  } else {
    next()
  }
})

export default router
