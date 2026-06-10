<script setup>
import { ref, computed } from 'vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Search, Download, PackagePlus, PackageMinus, XCircle, Calendar, Filter, ArrowUpDown, Building2, ChevronLeft, ChevronRight
} from 'lucide-vue-next';
import TitleHeader from '@/components/ui/title-header/Header.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import TransactionTable from './TransactionTable.vue';


const page = usePage();
const breadcrumbs = [{ title: "Transactions", href: "#" }];
const userRole = computed(() => (page.props.auth.user?.role || 'Viewer').toLowerCase());

const props = defineProps({
    transactions: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] }
});

const searchQuery = ref('');
const activeTab = ref('all');
const filterDept = ref('');
const filterCategory = ref('');
const startDate = ref('');
const endDate = ref('');
const sortBy = ref('latest');
const currentPage = ref(1);
const itemsPerPage = 10;

const filteredTransactions = computed(() => {
    let result = [...props.transactions];

    // 1. Search Filter
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(t =>
            (t.id && t.id.toString().includes(q)) ||
            (t.item?.name && t.item.name.toLowerCase().includes(q)) ||
            (t.item?.product_code && t.item.product_code.toLowerCase().includes(q)) ||
            (t.department && t.department.toLowerCase().includes(q)) ||
            (t.received_by && t.received_by.toLowerCase().includes(q)) ||
            (t.released_to && t.released_to.toLowerCase().includes(q)) ||
            (t.note && t.note.toLowerCase().includes(q))
        );
    }

    if (activeTab.value === 'in') result = result.filter(t => t.type === 'In');
    else if (activeTab.value === 'out') result = result.filter(t => t.type === 'Out');

    if (filterDept.value) result = result.filter(t => t.department === filterDept.value);
    if (filterCategory.value) result = result.filter(t => t.item?.category_id == filterCategory.value);

    if (startDate.value && endDate.value) {
        const start = new Date(startDate.value).setHours(0,0,0,0);
        const end = new Date(endDate.value).setHours(23,59,59,999);
        result = result.filter(t => {
            const trxDate = new Date(t.created_at).getTime();
            return trxDate >= start && trxDate <= end;
        });
    }

    return result.sort((a, b) => {
        if (sortBy.value === 'latest' || sortBy.value === 'oldest') {
            const dateA = new Date(a.created_at).getTime();
            const dateB = new Date(b.created_at).getTime();

            if (dateA !== dateB) {
                return sortBy.value === 'latest' ? dateB - dateA : dateA - dateB;
            }
            return sortBy.value === 'latest' ? b.id - a.id : a.id - b.id;
        }
        if (sortBy.value === 'az') return (a.item?.name || '').localeCompare(b.item?.name || '');
        if (sortBy.value === 'za') return (b.item?.name || '').localeCompare(a.item?.name || '');
        return 0;
    });
});

const totalPages = computed(() => {
    return Math.ceil(filteredTransactions.value.length / itemsPerPage);
});

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredTransactions.value.slice(start, end);
});

const pageInfo = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage + 1;
    const end = Math.min(currentPage.value * itemsPerPage, filteredTransactions.value.length);
    return `Showing ${start} to ${end} of ${filteredTransactions.value.length} transactions`;
});

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};



const exportDailyInReport = () => {
    if (!startDate.value) return;
    window.open(route('web.transactions.export-daily-in', { date: startDate.value }), '_blank');
};

const exportDepartmentReport = () => {
    if (!filterDept.value) return;
    window.open(route('web.transactions.export-by-department', { department: filterDept.value }), '_blank');
};

const resetFilters = () => {
    searchQuery.value = '';
    filterDept.value = '';
    filterCategory.value = '';
    startDate.value = '';
    endDate.value = '';
    activeTab.value = 'all';
    sortBy.value = 'latest';
    currentPage.value = 1;
};

const tabs = [
    { value: 'all', label: 'All', bgColor: 'bg-purple-600' },
    { value: 'in', label: 'In', bgColor: 'bg-emerald-600' },
    { value: 'out', label: 'Out', bgColor: 'bg-slate-900' }
];
</script>

<template>
    <Head title="Transaction History" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!--
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-purple-600 rounded-xl text-white shadow-lg shadow-slate-200"><History class="w-5 h-5" /></div>
                <div>
                    <h1 class="text-lg font-black text-slate-900 leading-none uppercase tracking-tight">Transaction History</h1>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Inventory Flow Control</p>
                </div>
            </div>  
            
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full lg:w-auto">
                <template v-if="userRole !== 'viewer'">
                    <button v-if="activeTab === 'in' && startDate" @click="exportDailyInReport" class="w-full sm:w-auto justify-center py-3 sm:py-2 px-4 bg-emerald-600 text-white text-[10px] font-black rounded-lg hover:bg-emerald-700 uppercase flex items-center gap-2 shadow-md shadow-emerald-100 transition-colors">
                        <Download class="w-3.5 h-3.5" /> Export Daily In
                    </button>
                    <button v-if="filterDept" @click="exportDepartmentReport" class="w-full sm:w-auto justify-center py-3 sm:py-2 px-4 bg-blue-600 text-white text-[10px] font-black rounded-lg hover:bg-blue-700 uppercase flex items-center gap-2 shadow-md shadow-blue-100 transition-colors">
                        <Download class="w-3.5 h-3.5" /> Dept Report
                    </button>
                    <Link :href="route('web.transactions.stock-in')" class="w-full sm:w-auto justify-center py-3 sm:py-2 px-4 bg-emerald-600 text-white text-[10px] font-black rounded-lg uppercase  flex items-center gap-2 hover:bg-emerald-700 shadow-md transition-colors">
                        <PackagePlus class="w-3.5 h-3.5" /> Stock In
                    </Link>
                    <Link :href="route('web.transactions.stock-out')" class="w-full sm:w-auto justify-center py-3 sm:py-2 px-4 bg-slate-900 text-white text-[10px] font-black rounded-lg uppercase  flex items-center gap-2 hover:bg-slate-800 shadow-md transition-colors">
                        <PackageMinus class="w-3.5 h-3.5" /> Stock Out
                    </Link>
                </template>
            </div>
        </div>
        -->
        <div class="flex justify-between items-center gap-2 border-b border-slate-200 pb-6 mb-6">
            <TitleHeader title="Transaction History" description="List of Inventory Transactions" />

            <div class="space-x-2 flex items-center">
                    <div class="relative w-full max-w-xs">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="searchQuery" type="text" placeholder="Search"
                        class="w-full pl-9 h-9"/>
                </div>
            </div>            
        </div>
        
        <!-- Filters Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200 mt-0">
            <div class="bg-white p-1 rounded-lg border border-slate-200 flex h-9 shadow-sm">
                <button v-for="tab in tabs" :key="tab.value" @click="activeTab = tab.value" :class="activeTab === tab.value ? `${tab.bgColor} text-white` : 'text-slate-400 hover:bg-slate-50'" class="flex-1 text-[9px] font-black uppercase rounded-md transition-all">{{ tab.label }}</button>
            </div>
            <div class="relative">
                <Building2 class="absolute left-3 top-2.5 w-3.5 h-3.5 text-slate-400" />
                <select v-model="filterDept" class="w-full h-9 pl-9 pr-3 bg-white border-slate-200 rounded-lg text-[10px] font-bold uppercase focus:ring-slate-900">
                    <option value="">Filter Dept</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.department_name">{{ dept.department_name }}</option>
                </select>
            </div>
            <div class="relative">
                <Filter class="absolute left-3 top-2.5 w-3.5 h-3.5 text-slate-400" />
                <select v-model="filterCategory" class="w-full h-9 pl-9 pr-3 bg-white border-slate-200 rounded-lg text-[10px] font-bold uppercase focus:ring-slate-900">
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
            </div>
            <div class="relative">
                <ArrowUpDown class="absolute left-3 top-2.5 w-3.5 h-3.5 text-slate-400" />
                <select v-model="sortBy" class="w-full h-9 pl-9 pr-3 bg-white border-slate-200 rounded-lg text-[10px] font-bold uppercase focus:ring-slate-900">
                    <option value="latest">Latest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="az">Item (A-Z)</option>
                    <option value="za">Item (Z-A)</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex gap-2 items-center">
                <div class="flex-1 relative"><Calendar class="absolute left-2.5 top-2.5 w-3 h-3 text-slate-400" /><input type="date" v-model="startDate" class="w-full h-9 pl-8 pr-2 bg-white border-slate-200 rounded-lg text-[10px] font-bold"></div>
                <div class="flex-1 relative"><Calendar class="absolute left-2.5 top-2.5 w-3 h-3 text-slate-400" /><input type="date" v-model="endDate" class="w-full h-9 pl-8 pr-2 bg-white border-slate-200 rounded-lg text-[10px] font-bold"></div>
                <button @click="resetFilters" class="p-2 text-slate-300 hover:text-red-500"><XCircle class="w-5 h-5" /></button>
            </div>
        </div>

        <TransactionTable :transactions="paginatedTransactions" :userRole="userRole" />

        <!-- Pagination Controls -->
        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-slate-600">
                {{ pageInfo }}
            </div>
            <div class="flex gap-2">
                <Button
                    @click="previousPage"
                    :disabled="currentPage === 1"
                    variant="outline"
                    size="sm">
                    <ChevronLeft class="w-4 h-4 mr-1" />
                    Previous
                </Button>
                <div class="flex items-center gap-2 px-3 text-sm text-slate-600">
                    Page <span class="font-semibold">{{ currentPage }}</span> of <span class="font-semibold">{{ totalPages }}</span>
                </div>
                <Button
                    @click="nextPage"
                    :disabled="currentPage === totalPages"
                    variant="outline"
                    size="sm">
                    Next
                    <ChevronRight class="w-4 h-4 ml-1" />
                </Button>
            </div>
        </div>
    </AppLayout>
</template>