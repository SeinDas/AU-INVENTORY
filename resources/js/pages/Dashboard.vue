<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import Card from '@/components/ui/card/Card.vue'; 
import { Head, Link, usePage } from '@inertiajs/vue3'; 
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { 
    FileText, 
    CircleAlert,
    History as HistoryIcon,
    Box, 
    LayoutGrid,
    TrendingUp
} from 'lucide-vue-next';
import { 
    Chart as ChartJS, 
    Title, Tooltip, Legend, 
    BarElement, CategoryScale, LinearScale 
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const page = usePage();
const breadcrumbs = [{ title: "Dashboard", href: "/users" }];
const userName = computed(() => page.props.auth.user?.name || 'Viewer');

const props = defineProps({
    stats: Object,
    low_stock_items: Array,
    recent_transactions: Array,
    top_stock_items: {
        type: Array,
        default: () => [] 
    }
});

const chartData = computed(() => {
    return {
        labels: props.top_stock_items?.map(item => item.name) || [], 
        datasets: [{
            label: 'Current Stock',
            backgroundColor: '#581c87', 
            borderRadius: 2,
            data: props.top_stock_items?.map(item => item.quantity) || [] 
        }]
    }
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: '#f1f5f9', drawBorder: false },
            ticks: { color: '#94a3b8', font: { size: 10, weight: 'bold' } }
        },
        x: {
            grid: { display: false },
            ticks: { color: '#64748b', font: { size: 10 } }
        }
    }
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="mt-8 mb-8 px-4 sm:px-6 lg:px-8 space-y-8 max-w-7xl mx-auto">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 pb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Administrative Overview</h1>
                    <p class="text-sm text-slate-500 mt-1 italic">Welcome back, {{ userName }}. Here is the current inventory status.</p>
                </div>
                
                <a
                    v-if="userRole !== 'Viewer'" 
                    :href="route('reports.download')" 
                    class="w-full md:w-auto justify-center inline-flex items-center px-4 py-3 md:py-2 bg-slate-900 hover:bg-purple-900 text-white text-[10px] font-bold rounded-sm shadow-sm transition-all uppercase tracking-[0.15em]"
                >
                    <FileText class="w-3.5 h-3.5 mr-2" />
                    Export Inventory Report
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <Card class="card-outline p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <Box class="w-4 h-4" />
                        <h3 class="heading-micro text-slate-400">Total Items</h3>
                    </div>
                    <p class="stat-value text-slate-900">{{ stats?.total_items || 0 }}</p>
                </Card>

                <Card class="card-outline p-5"> 
                    <div class="flex items-center gap-3 mb-3">
                        <CircleAlert class="w-4 h-4 text-amber-600" />
                        <h3 class="heading-micro text-slate-400">Low Stock</h3>
                    </div>
                    <p class="stat-value text-amber-600">{{ stats?.low_stock_count || 0 }}</p>
                </Card>

                <Card class="card-outline p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <LayoutGrid class="w-4 h-4" />
                        <h3 class="heading-micro text-slate-400">Categories</h3>
                    </div>
                    <p class="stat-value text-slate-900">{{ stats?.total_categories || 0 }}</p>
                </Card>

                <Card class="card-outline p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <HistoryIcon class="w-4 h-4" />
                        <h3 class="heading-micro text-slate-400">Updates Today</h3>
                    </div>
                    <p class="stat-value text-slate-900">{{ stats?.recent_updates || 0 }}</p>
                </Card>
            </div>

            <!-- Chart Section -->
            <Card class="card-outline p-6">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h3 class="heading-micro text-slate-800 flex items-center gap-2">
                            <TrendingUp class="w-4 h-4 text-purple-600" />
                            Stock Distribution
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-1 uppercase font-medium">Quantity per item nomenclature</p>
                    </div>
                </div>
                <div class="h-[280px] w-full">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card class="card-outline p-0 h-[400px] flex flex-col overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="heading-micro text-slate-500 flex items-center gap-2">
                            <div class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></div>
                            Attention Required
                        </h3>
                    </div>
                    <div class="overflow-y-auto overflow-x-auto flex-1">
                        <table class="w-full text-left border-collapse min-w-[400px]">
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="item in low_stock_items" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-slate-700 font-medium italic">{{ item.name }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-sm">
                                            {{ item.quantity }} {{ item.unit?.name }} remaining
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card class="card-outline p-0 h-[400px] flex flex-col overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="heading-micro text-slate-500">
                            Recent Activity
                        </h3>
                    </div>
                    <div class="overflow-y-auto overflow-x-auto flex-1">
                        <div class="min-w-[400px]">
                            <div v-for="trx in recent_transactions" :key="trx.id" 
                                class="px-6 py-4 flex justify-between items-center border-b border-slate-50 last:border-0 hover:bg-slate-50/50 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="icon-box p-1" :class="trx.type === 'In' ? 'text-emerald-500 bg-emerald-50' : 'text-slate-400 bg-slate-50'">
                                        <HistoryIcon class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 uppercase tracking-tight">{{ trx.item?.name }}</p>
                                        <p class="text-[10px] text-slate-400 font-medium">
                                            Recorded at {{ new Date(trx.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                        </p>
                                    </div>
                                </div>
                                <span :class="trx.type === 'In' ? 'text-emerald-600' : 'text-slate-600'" class="font-mono text-sm font-bold">
                                    {{ trx.type === 'In' ? '+' : '-' }}{{ trx.quantity }}
                                </span>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
            
        </div>
    </AppLayout>
</template>