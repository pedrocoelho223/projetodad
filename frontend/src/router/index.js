import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Páginas públicas
import HomePage from '@/pages/home/HomePage.vue'
import LoginPage from '@/pages/login/LoginPage.vue'
import Register from '@/components/auth/RegisterUser.vue'
import PublicStats from '@/pages/views/PublicStats.vue'

// Páginas autenticadas
import Profile from '@/pages/users/ProfileUser.vue'
import CoinsPage from '@/pages/coins/CoinsPage.vue'
import MyHistory from '@/pages/history/MyHistory.vue'
import BiscaGame from '@/components/game/BiscaGame.vue'

// Testing
import LaravelPage from '@/pages/testing/LaravelPage.vue'
import WebsocketsPage from '@/pages/testing/WebsocketsPage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    /* =====================
       PÚBLICAS
    ====================== */
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
      path: '/statistics',
      name: 'PublicStats',
      component: PublicStats,
      meta: { requiresAuth: false },
    },

    /* =====================
       AUTENTICADAS
    ====================== */
    {
      path: '/profile',
      name: 'profile',
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
      path: '/my/games',
      name: 'MyHistory',
      component: MyHistory,
      meta: { requiresAuth: true },
    },
    {
      path: '/game/single',
      name: 'SinglePlayer',
      component: BiscaGame,
      meta: { requiresAuth: true },
    },

    /* =====================
       ADMIN
    ====================== */
    {
      path: '/admin/statistics',
      name: 'AdminStats',
      component: () => import('@/pages/statistics/AdminStats.vue'),
      meta: { requiresAdmin: true },
    },
    {
      path: '/admin/transactions',
      name: 'AdminTransactions',
      component: () => import('@/pages/statistics/AdminTransactions.vue'),
      meta: { requiresAdmin: true },
    },

    /* =====================
       OUTROS
    ====================== */
    {
      path: '/leaderboard',
      name: 'Leaderboard',
      component: () => import('@/pages/leaderboard/Leaderboard.vue'),
    },
    {
      path: '/testing',
      meta: { requiresAuth: false },
      children: [
        { path: 'laravel', component: LaravelPage },
        { path: 'websockets', component: WebsocketsPage },
      ],
    },
  ],
})

/* =====================
   GUARDS
====================== */
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // precisa estar autenticado
  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    next({ name: 'login' })
    return
  }

  // precisa ser admin
  if (to.meta.requiresAdmin && authStore.currentUser?.type !== 'A') {
    next({ name: 'home' })
    return
  }

  next()
})

export default router
