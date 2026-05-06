<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useProfile } from '~/modules/social/hooks/useProfile';

definePageMeta({
    middleware: ['auth']
});

const { profile, loading, fetchProfile, updateProfile, uploadAvatar } = useProfile();
const isEditing = ref(false);
const form = ref({
    name: '',
    bio: ''
});

onMounted(async () => {
    await fetchProfile();
    if (profile.value) {
        form.value.name = profile.value.name;
        form.value.bio = profile.value.bio || '';
    }
});

const handleUpdate = async () => {
    const success = await updateProfile(form.value);
    if (success) {
        isEditing.value = false;
    }
};

const handleAvatarChange = async (event: any) => {
    const file = event.target.files[0];
    if (file) {
        await uploadAvatar(file);
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 py-12 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Loading State -->
            <div v-if="loading && !profile" class="flex justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500/20 border-t-blue-500"></div>
            </div>

            <div v-else-if="profile" class="space-y-8">
                <!-- Profile Header Card -->
                <div class="relative bg-white/5 backdrop-blur-xl border border-white/10 rounded-[2.5rem] overflow-hidden p-8 md:p-12">
                    <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-blue-600/20 to-purple-600/20 blur-3xl"></div>
                    
                    <div class="relative flex flex-col md:flex-row items-center md:items-start gap-8">
                        <!-- Avatar Section -->
                        <div class="group relative">
                            <div class="w-32 h-32 md:w-40 md:h-40 rounded-full bg-slate-800 border-4 border-white/10 overflow-hidden shadow-2xl transition-transform group-hover:scale-105">
                                <img v-if="profile.avatar_path" :src="profile.avatar_path" alt="" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center text-4xl text-slate-500 font-bold">
                                    {{ profile.name.charAt(0) }}
                                </div>
                            </div>
                            <label class="absolute bottom-2 right-2 p-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-full cursor-pointer shadow-lg transition-all active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                </svg>
                                <input type="file" class="hidden" accept="image/*" @change="handleAvatarChange">
                            </label>
                        </div>

                        <!-- Info Section -->
                        <div class="flex-1 text-center md:text-left">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight">{{ profile.name }}</h1>
                                    <p class="text-blue-400 font-semibold tracking-wider uppercase text-sm mt-1">{{ profile.role.name }}</p>
                                </div>
                                <button 
                                    @click="isEditing = !isEditing"
                                    class="px-6 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold transition-all border border-white/10"
                                >
                                    {{ isEditing ? 'Cancelar' : 'Editar Perfil' }}
                                </button>
                            </div>
                            
                            <p class="text-slate-400 mt-6 leading-relaxed max-w-2xl">
                                {{ profile.bio || 'Sem biografia definida.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div v-if="isEditing" class="bg-white/5 border border-white/10 rounded-[2rem] p-8 animate-in fade-in slide-in-from-top-4 duration-300">
                    <form @submit.prevent="handleUpdate" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-400 uppercase tracking-widest ml-1">Nome Completo</label>
                                <input 
                                    v-model="form.name"
                                    type="text"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                >
                            </div>
                            <div class="space-y-2 opacity-50 cursor-not-allowed">
                                <label class="text-sm font-bold text-slate-400 uppercase tracking-widest ml-1">Email (Não editável)</label>
                                <input 
                                    :value="profile.email"
                                    disabled
                                    type="email"
                                    class="w-full bg-slate-900 border border-white/5 rounded-2xl px-6 py-4 text-slate-500 cursor-not-allowed"
                                >
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-400 uppercase tracking-widest ml-1">Biografia</label>
                            <textarea 
                                v-model="form.bio"
                                rows="4"
                                class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none"
                            ></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button 
                                type="submit"
                                class="px-10 py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-blue-600/20 transition-all active:scale-95"
                            >
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Contacts Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white/5 border border-white/10 rounded-[2rem] p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-white">Contatos</h3>
                            <button class="text-blue-400 font-bold text-sm hover:underline">Gerenciar</button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="contact in profile.contacts" :key="contact.id" class="flex items-center gap-4 p-4 bg-white/5 border border-white/5 rounded-2xl">
                                <div class="w-10 h-10 flex items-center justify-center bg-blue-500/10 text-blue-400 rounded-xl">
                                    <!-- Icons based on type would go here -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-xs uppercase font-black tracking-widest">{{ contact.type }}</div>
                                    <div class="text-white font-medium">{{ contact.value }}</div>
                                </div>
                            </div>
                            <div v-if="profile.contacts.length === 0" class="text-center py-6">
                                <p class="text-slate-500 text-sm italic">Nenhum contato adicionado.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Stats or Info -->
                    <div class="bg-gradient-to-br from-blue-600 to-purple-600 rounded-[2.5rem] p-8 text-white flex flex-col justify-between shadow-2xl">
                        <div>
                            <h3 class="text-2xl font-black italic mb-2 tracking-tight">GO PRO</h3>
                            <p class="text-white/80 font-medium">Libere recursos exclusivos e aumente sua produtividade.</p>
                        </div>
                        <div class="mt-8">
                            <button class="w-full py-4 bg-white text-slate-900 rounded-2xl font-black uppercase tracking-widest transition-all hover:bg-slate-100 active:scale-95 shadow-xl">
                                Saiba Mais
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
