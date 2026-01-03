<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const users = ref([]);
const authStore = useAuthStore();
const isLoading = ref(false);

// Carregar utilizadores
const fetchUsers = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/users');
        // O Laravel devolve ['data' => [...]]
        users.value = response.data.data; 
    } catch (error) {
        console.error("Erro ao carregar users:", error);
        alert("Não foi possível carregar a lista de utilizadores.");
    } finally {
        isLoading.value = false;
    }
};

// Bloquear / Desbloquear
const toggleBlock = async (user) => {
    const endpoint = user.blocked 
        ? `/api/users/${user.id}/unblock` 
        : `/api/users/${user.id}/block`;

    try {
        const response = await axios.patch(endpoint);
        // Atualiza a UI localmente com a resposta do servidor
        user.blocked = response.data.data.blocked;
    } catch (error) {
        console.error(error);
        alert("Erro ao alterar o estado do utilizador.");
    }
};

// Apagar
const deleteUser = async (user) => {
    if (!confirm(`Tem a certeza que deseja apagar ${user.name}? Esta ação é irreversível.`)) {
        return;
    }

    try {
        await axios.delete(`/api/users/${user.id}`);
        // Remove da lista visualmente
        users.value = users.value.filter(u => u.id !== user.id);
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || "Erro ao apagar utilizador.");
    }
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Administração de Utilizadores (G5)</h1>

        <div v-if="isLoading" class="text-center py-4">A carregar...</div>

        <div v-else class="bg-white shadow-md rounded my-6 overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">Nome</th>
                        <th class="py-3 px-6 text-left">Email</th>
                        <th class="py-3 px-6 text-center">Tipo</th>
                        <th class="py-3 px-6 text-center">Estado</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <tr v-for="user in users" :key="user.id" class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap">{{ user.id }}</td>
                        <td class="py-3 px-6 text-left">{{ user.name }}</td>
                        <td class="py-3 px-6 text-left">{{ user.email }}</td>
                        <td class="py-3 px-6 text-center">
                            <span :class="user.type === 'A' ? 'bg-purple-200 text-purple-600' : 'bg-blue-200 text-blue-600'" class="py-1 px-3 rounded-full text-xs">
                                {{ user.type === 'A' ? 'Admin' : 'Jogador' }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <span :class="user.blocked ? 'bg-red-200 text-red-600' : 'bg-green-200 text-green-600'" class="py-1 px-3 rounded-full text-xs">
                                {{ user.blocked ? 'Bloqueado' : 'Ativo' }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center space-x-2" v-if="user.id !== authStore.user.id">
                                <button 
                                    @click="toggleBlock(user)"
                                    class="w-4 h-4 transform hover:text-purple-500 hover:scale-110"
                                    :title="user.blocked ? 'Desbloquear' : 'Bloquear'"
                                >
                                    <span v-if="user.blocked">🔓</span>
                                    <span v-else>🔒</span>
                                </button>
                                
                                <button 
                                    @click="deleteUser(user)"
                                    class="w-4 h-4 transform hover:text-red-500 hover:scale-110"
                                    title="Apagar"
                                >
                                    🗑️
                                </button>
                            </div>
                            <div v-else class="text-xs text-gray-400">Você</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>