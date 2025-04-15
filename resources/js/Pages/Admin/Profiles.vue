<script lang="ts" setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
defineProps<{
    users: User[];
}>();

function formatDate(date: Date): string {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
}
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Liste des profiles" />
        <table class="min-w-full border-collapse border border-gray-200">
            <thead>
                <tr>
                    <th class="border border-gray-300 px-4 py-2">Nom</th>
                    <th class="border border-gray-300 px-4 py-2">Email</th>
                    <th class="border border-gray-300 px-4 py-2">Rôle</th>
                    <th class="border border-gray-300 px-4 py-2">Actif</th>
                    <th class="border border-gray-300 px-4 py-2">Date de Création</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td class="border border-gray-300 px-4 py-2">{{ user.name }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ user.email }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ user.role }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ user.is_active ? 'Oui' : 'Non' }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ formatDate(user.created_at) }}</td>
                </tr>
            </tbody>
        </table>
    </AuthenticatedLayout>
</template>
