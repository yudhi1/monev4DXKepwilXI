<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Lencana from '@/components/pm/Lencana.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { TriangleAlert } from '@lucide/vue';

const props = defineProps({
    tasks: { type: Array, required: true },
    filter: { type: Object, required: true },
    opsi: { type: Object, required: true },
});

const status = ref(props.filter.status || 'semua');
const prioritas = ref(props.filter.prioritas || 'semua');

watch([status, prioritas], () =>
    router.get(
        '/pm/tugas-saya',
        {
            status: status.value === 'semua' ? undefined : status.value,
            prioritas: prioritas.value === 'semua' ? undefined : prioritas.value,
        },
        { preserveState: true, replace: true }
    )
);

const tanggal = (nilai) =>
    nilai ? new Date(nilai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Tugas Saya" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Tugas Saya</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Seluruh task yang ditugaskan kepada Anda, lintas project. Yang paling dekat
                    deadline-nya berada di atas.
                </p>
            </div>
        </template>

        <div class="mb-4 flex flex-wrap items-center gap-3">
            <Select v-model="status">
                <SelectTrigger class="w-40"><SelectValue placeholder="Status" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua status</SelectItem>
                    <SelectItem v-for="(meta, kunci) in opsi.statusTask" :key="kunci" :value="kunci">
                        {{ meta.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="prioritas">
                <SelectTrigger class="w-40"><SelectValue placeholder="Prioritas" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua prioritas</SelectItem>
                    <SelectItem v-for="(meta, kunci) in opsi.prioritas" :key="kunci" :value="kunci">
                        {{ meta.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <span class="text-muted-foreground ml-auto text-sm">{{ tasks.length }} task</span>
        </div>

        <Card class="overflow-hidden py-0">
            <CardContent class="p-0">
                <p v-if="tasks.length === 0" class="text-muted-foreground p-12 text-center text-sm">
                    Tidak ada task yang ditugaskan kepada Anda dengan filter ini.
                </p>

                <Table v-else>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="pl-4">Task</TableHead>
                            <TableHead class="w-56">Project</TableHead>
                            <TableHead class="w-32">Status</TableHead>
                            <TableHead class="w-28">Prioritas</TableHead>
                            <TableHead class="w-40">Progress</TableHead>
                            <TableHead class="w-36 pr-4">Deadline</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="t in tasks" :key="t.id">
                            <TableCell class="pl-4">
                                <p class="font-medium">{{ t.judul }}</p>
                                <p v-if="t.milestone" class="text-muted-foreground text-xs">{{ t.milestone }}</p>
                            </TableCell>
                            <TableCell>
                                <Link :href="`/pm/projects/${t.project.id}`" class="text-primary text-sm">
                                    {{ t.project.nama }}
                                </Link>
                            </TableCell>
                            <TableCell><Lencana :nilai="t.status" :peta="opsi.statusTask" /></TableCell>
                            <TableCell><Lencana :nilai="t.prioritas" :peta="opsi.prioritas" /></TableCell>
                            <TableCell><BilahProgress :nilai="t.progress" /></TableCell>
                            <TableCell class="pr-4">
                                <span
                                    :class="[
                                        'flex items-center gap-1 text-sm whitespace-nowrap',
                                        t.terlambat ? 'font-medium text-rose-600' : 'text-muted-foreground',
                                    ]"
                                >
                                    <TriangleAlert v-if="t.terlambat" class="size-3.5" />
                                    {{ tanggal(t.deadline) }}
                                </span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </AppLayout>
</template>
