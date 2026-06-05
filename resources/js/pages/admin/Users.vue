<script setup lang="ts">
import { computed } from "vue";
import { router, usePage, Head } from "@inertiajs/vue3";

type UserRow = {
  id: number;
  name: string;
  email: string;
  role: string;
  active: boolean | number;
  created_at?: string;
};

const props = defineProps<{
  users: UserRow[];
}>();

const users = computed(() =>
    props.users.map(u => ({
      ...u,
      active: Boolean(u.active),
    }))
);

function toggleActive(userId: number) {
  router.patch(`/admin/users/${userId}/toggle-active`, {}, {
    preserveScroll: true,
  });
}

function setRole(userId: number, role: string) {
  router.patch(`/admin/users/${userId}/role`, { role }, {
    preserveScroll: true,
  });
}

</script>

<template>
  <Head title="Správa používateľov" />

  <div class="min-h-screen bg-zinc-950 text-zinc-100 p-6">
    <div class="max-w-5xl mx-auto">
      <h1 class="text-2xl font-semibold">Správa používateľov</h1>
      <p class="text-sm text-zinc-400 mt-1">
        Prepínajte, či je používateľ aktívny.
      </p>

      <div v-if="usePage().props.errors?.email" class="mt-4 rounded-xl border border-red-800 bg-red-950/40 p-3 text-red-200">
        {{ usePage().props.errors.email }}
      </div>

      <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-800">
        <table class="w-full text-sm">
          <thead class="bg-zinc-900/60 text-zinc-300">
          <tr>
            <th class="text-left p-3">ID</th>
            <th class="text-left p-3">Meno</th>
            <th class="text-left p-3">E-mail</th>
            <th class="text-left p-3">Rola</th>
            <th class="text-left p-3">Aktívny</th>
            <th class="text-right p-3">Akcia</th>
          </tr>
          </thead>

          <tbody>
          <tr v-for="u in users" :key="u.id" class="border-t border-zinc-800">
            <td class="p-3 text-zinc-300">{{ u.id }}</td>
            <td class="p-3">{{ u.name }}</td>
            <td class="p-3 text-zinc-300">{{ u.email }}</td>
            <td class="p-3">
              <select
                  class="bg-zinc-950/60 border border-zinc-700 rounded-xl px-3 py-1.5 text-xs text-zinc-100 hover:bg-zinc-900"
                  :value="u.role"
                  @change="setRole(u.id, ($event.target as HTMLSelectElement).value)"
              >
                <option value="student">študent</option>
                <option value="teacher">učiteľ</option>
                <option value="admin">administrátor</option>
              </select>
            </td>

            <td class="p-3">
                <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs"
                    :class="u.active ? 'bg-emerald-900/40 text-emerald-200 border border-emerald-800' : 'bg-zinc-800/40 text-zinc-300 border border-zinc-700'"
                >
                  {{ u.active ? 'Aktívny' : 'Neaktívny' }}
                </span>
            </td>
            <td class="p-3 text-right">
              <button
                  class="rounded-xl border border-zinc-700 px-3 py-1.5 hover:bg-zinc-900"
                  @click="toggleActive(u.id)"
              >
                Prepnúť
              </button>
            </td>
          </tr>

          <tr v-if="users.length === 0">
            <td colspan="6" class="p-6 text-center text-zinc-400">
              Nenašli sa žiadni používatelia.
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <p class="mt-4 text-xs text-zinc-500">
        URL: <span class="text-zinc-300">/admin/users</span>
      </p>
    </div>
  </div>
</template>
