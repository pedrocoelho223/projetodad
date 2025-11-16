import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      // Rota Raiz: Redireciona para a página de login
      path: '/',
      redirect: '/login'
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginPage.vue')
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterPage.vue')
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfilePage.vue'),
      // Mais tarde, vamos adicionar aqui a proteção de rota
      // meta: { requiresAuth: true }
    }
  ]
})

// (Aqui podes ter o teu 'router.beforeEach' para proteger o profile)

export default router
