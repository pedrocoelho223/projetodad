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
import AdminUsers from '@/pages/admin/AdminUsers.vue';

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
    },
    {
      path: '/statistics',
      name: 'PublicStats',
      component: PublicStats,
      meta: { requiresAuth: false },
    },
    {
      path: '/leaderboard',
      name: 'Leaderboard',
      component: () => import('@/pages/leaderboard/Leaderboard.vue'),
    },

    /* =====================
       AUTENTICADAS
    ====================== */
    {
      path: '/profile',
      name: 'profile', // Mantive minúsculo para consistência
      component: Profile,
      meta: { requiresAuth: true },
    },
     {
      path: '/register',
      name: 'Register',
      component: Register,
    },

    {
      path: '/coins',
      name: 'CoinsPage',
      component: CoinsPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
    },
    {
      path: '/my/games',
      name: 'MyHistory',
      component: MyHistory,
      meta: { requiresAuth: true },
    },
    {
      path: '/games/single',
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
    {
        path: '/admin/users',
        name: 'AdminUsers',
        component: AdminUsers,
        meta: { requiresAuth: true }
    },

    /* =====================
       TESTING
    ====================== */
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

  // 1. Verificar Autenticação
  if (to.meta.requiresAuth && !authStore.isLoggedIn) { // Assumindo que verifica se o user existe
    next({ name: 'login' })
    return
  }

  // 2. Verificar Admin
  // Nota: Usa authStore.user ou authStore.currentUser conforme a tua store
  if (to.meta.requiresAdmin && authStore.currentUser?.type !== 'A') {
    next({ name: 'home' })
    return
  }

  // 3. Prosseguir
  next()
})

export default router
