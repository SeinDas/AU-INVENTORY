<script setup>
import { ref } from 'vue';
import { Eye, XCircle, History, Box, Building2, User } from 'lucide-vue-next';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableRow,
    TableHeader
} from '@/components/ui/table';

const props = defineProps({
    transactions: {
        type: Array,
        required: true
    },
    userRole: {
        type: String,
        default: 'viewer'
    }
});

const isModalOpen = ref(false);
const selectedTransaction = ref(null);

const openViewModal = (trx) => {
    selectedTransaction.value = trx;
    isModalOpen.value = true;
};

const closeViewModal = () => {
    isModalOpen.value = false;
    selectedTransaction.value = null;
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};
</script>

<template>
    <div>
        <div class="bg-white border border-slate-200 shadow-sm p-0 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="uppercase">
                            <TableHead>Ref / Date</TableHead>
                            <TableHead>Item Description</TableHead>
                            <TableHead>Office / Dept</TableHead>
                            <TableHead>Personnel</TableHead>
                            <TableHead>Qty</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="trx in transactions" :key="trx.id" class="transition-colors group">
                            <TableCell>
                                <span class="font-black">#{{ trx.id }}</span>
                                <span class="block text-[10px] font-bold text-slate-400">{{ formatDate(trx.created_at) }}</span>
                            </TableCell>
                            <TableCell>
                                <span class="font-bold">{{ trx.item?.name }}</span>
                                <span class="block text-[10px] font-mono text-slate-400">{{ trx.item?.product_code }}</span>
                            </TableCell>
                            <TableCell>
                                <span :class="trx.department ? 'text-emerald-600 bg-emerald-50' : 'text-slate-600 bg-slate-50'" class="text-[9px] font-black uppercase px-2 py-0.5 rounded border">
                                    {{ trx.department || 'N/A' }}
                                </span>
                            </TableCell>
                            <TableCell>
                                {{ trx.received_by || trx.released_to || 'System' }}
                            </TableCell>
                            <TableCell>
                                <span :class="trx.type === 'In' ? 'text-emerald-600' : 'text-purple-600'" class="font-black text-[12px]">
                                    {{ trx.type === 'In' ? '+' : '-' }}{{ trx.quantity }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <div>
                                    <button @click="openViewModal(trx)" class="text-slate-400 hover:text-blue-600 transition-colors">
                                        <Eye class="w-4 h-4" />
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <div v-if="isModalOpen"
            class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="closeViewModal">

            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div :class="selectedTransaction?.type === 'In' ? 'bg-emerald-100 text-emerald-600' : 'bg-purple-100 text-purple-600'" class="p-2 rounded-xl">
                            <History class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-tight">Log Details</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Ref: {{ selectedTransaction?.id }}</p>
                        </div>
                    </div>
                    <button @click="closeViewModal" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors">
                        <XCircle class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <div class="flex gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <div class="p-3 bg-white rounded-xl shadow-sm border border-slate-200"><Box class="w-6 h-6 text-slate-400" /></div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-black uppercase mb-1">Item Information</p>
                            <h4 class="text-xs font-black text-slate-900 uppercase">{{ selectedTransaction?.item?.name }}</h4>
                            <p class="text-[10px] font-mono text-slate-500">{{ selectedTransaction?.item?.product_code }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <p class="text-[9px] text-slate-400 font-black uppercase flex items-center gap-1.5"><Building2 class="w-3 h-3" /> Department</p>
                            <p class="text-[11px] font-bold text-slate-700 uppercase">{{ selectedTransaction?.department || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] text-slate-400 font-black uppercase flex items-center gap-1.5"><User class="w-3 h-3" /> Handler</p>
                            <p class="text-[11px] font-bold text-slate-700 uppercase">{{ selectedTransaction?.received_by || selectedTransaction?.released_to || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] text-slate-400 font-black uppercase">Quantity</p>
                            <p :class="selectedTransaction?.type === 'In' ? 'text-emerald-600' : 'text-purple-600'" class="text-lg font-black">
                                {{ selectedTransaction?.type === 'In' ? '+' : '-' }}{{ selectedTransaction?.quantity }}
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] text-slate-400 font-black uppercase">Date Recorded</p>
                            <p class="text-[11px] font-bold text-slate-700 uppercase">{{ formatDate(selectedTransaction?.created_at) }}</p>
                        </div>
                    </div>

                    <div v-if="selectedTransaction?.note" class="pt-4 border-t border-slate-100">
                        <p class="text-[9px] text-slate-400 font-black uppercase mb-2">Remarks</p>
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-900 italic">"{{ selectedTransaction.note }}"</div>
                    </div>
                </div>
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                </div>
            </div>
        </div>
    </div>
</template>
