import HomePage from '@/pages/home/HomePage.vue'
import LoginPage from '@/pages/login/LoginPage.vue'
import LaravelPage from '@/pages/testing/LaravelPage.vue'
import WebsocketsPage from '@/pages/testing/WebsocketsPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import Register from '@/components/auth/RegisterUser.vue'
import Profile from '@/pages/users/ProfileUser.vue'

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
      component: () => import('@/pages/coins/CoinsPage.vue'),
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
      path: '/games/multiplayer',
      component: () => import('@/pages/games/MultiplayerLobby.vue'),
    },
    {
      path: '/games/multiplayer/:roomId',
      component: () => import('@/pages/games/MultiplayerRoom.vue'),
    },
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
