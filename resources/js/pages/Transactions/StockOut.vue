<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { 
    ArrowLeft, Save, AlertCircle, Plus, Trash2, 
    Building2, AlertTriangle, Download, RefreshCw
} from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { Button } from '@/components/ui/button';

const props = defineProps({ 
    items: Array, 
    departments: Array 
});

const page = usePage();
const recentlySubmitted = ref(false);
const submittedRef = ref('');
const submittedDept = ref('');
const toast = useToast();

const form = useForm({
    department: '',
    released_to: '',
    released_by: page.props.auth.user?.name || 'Authorized Personnel', 
    purpose: '',
    date_released: new Date().toISOString().substr(0, 10),
    line_items: [{ item_id: '', quantity: 1 }]
});

const generatedRefNo = computed(() => form.date_released);

const addItemRow = () => form.line_items.push({ item_id: '', quantity: 1 });
const removeItemRow = (index) => form.line_items.length > 1 && form.line_items.splice(index, 1);

const triggerExport = () => {
    const url = route('web.transactions.export-by-department', { 
        department: submittedDept.value,
        date: submittedRef.value 
    });
    window.open(url, '_blank');
};

const resetForm = () => {
    form.reset();
    form.clearErrors();

    form.department = '';
    form.released_to = '';
    form.purpose = '';
    form.date_released = new Date().toISOString().substr(0, 10);
    form.line_items = [{ item_id: '', quantity: 1 }];

    recentlySubmitted.value = false;
    submittedRef.value = '';
    submittedDept.value = '';
};

const submit = () => {
    form.post(route('web.transactions.store_bulk_out'), {
        onBefore: () => { 
            submittedRef.value = form.date_released; 
            submittedDept.value = form.department;
        },
        onSuccess: () => { 
            recentlySubmitted.value = true; 
            toast.success("Stock out record submitted successfully!");
        },
    });
};

const isExceedingAvailableStock = computed(() => {
    return form.line_items.some(line => {
        const item = props.items.find(i => i.id === line.item_id);
        if (!item) return false;
        return Number(line.quantity) > Number(item.quantity);
    });
});
</script>

<template>
    <Head title="STOCK OUT" />
    <AppLayout :breadcrumbs="[{ title: 'Transactions', href: route('web.transactions.index') }, { title: 'Stock Out', href: '#' }]">
        <div class="flex items-center gap-4 border-b border-slate-300 pb-6">
            <Link :href="route('web.transactions.index')" class="p-2 bg-white border border-slate-300 rounded-sm hover:bg-slate-100 text-slate-400 transition-colors">
                <ArrowLeft class="w-4 h-4" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight uppercase">STOCK OUT</h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase  mt-1">
                    Reference No: <span class="text-purple-600">#{{ generatedRefNo }}</span>
                </p>
            </div>
        </div>

        <div v-if="Object.keys(form.errors).length > 0" class="p-4 bg-red-50 border-l-4 border-red-500 flex gap-3 items-center mt-6">
            <AlertCircle class="w-5 h-5 text-red-500" />
            <div class="text-xs font-bold text-red-700 uppercase">
                Error saving transaction. Please check quantities or item selection.
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6 mt-6">
            <div class="p-2 bg-white border border-slate-300 rounded-xl">
                <div class="px-3 py-2 border-b border-slate-100 bg-slate-100 flex items-center gap-2 mb-4 rounded-t-lg">
                    <Building2 class="w-3.5 h-3.5 text-slate-400" />
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase ">Requisition Details</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-3">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ">Department</label>
                        <select v-model="form.department" class="w-full border border-slate-300 rounded-sm text-sm h-10 px-3 bg-white focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted">
                            <option value="" disabled>Select</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ">Released To (Receiver)</label>
                        <input v-model="form.released_to" type="text" placeholder="Full Name" class="w-full border border-slate-300 rounded-sm text-sm h-10 px-3 font-semibold focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ">Release Date</label>
                        <input v-model="form.date_released" type="date" class="w-full border border-slate-300 rounded-sm text-sm h-10 px-3 font-semibold focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ">Purpose / Remarks</label>
                        <input v-model="form.purpose" type="text" placeholder="Optional" class="w-full border border-slate-300 rounded-sm text-sm h-10 px-3 focus:ring-slate-900 focus:border-slate-900" :disabled="recentlySubmitted" />
                    </div>
                </div>
            </div>

            <div class="p-2 bg-white border border-slate-300 rounded-xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300">
                            <th class="px-6 py-3 text-[10px] font-bold uppercase  text-slate-500">Property / Item Description</th>
                            <th class="px-6 py-3 text-[10px] font-bold uppercase  text-slate-500 w-48 border-l border-slate-300 text-center">Qty to Issue</th>
                            <th class="px-4 py-3 w-12 text-center text-slate-300">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <tr v-for="(line, index) in form.line_items" :key="index">
                            <td class="px-4 py-3">
                                <select v-model="line.item_id" class="w-full border border-slate-300 rounded-sm text-sm h-9 uppercase focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted">
                                    <option value="" disabled>Select Property...</option>
                                    <option v-for="item in items" :key="item.id" :value="item.id">
                                        {{ item.name }} (Current: {{ item.quantity }})
                                    </option>
                                </select>
                                
                                <div v-if="line.item_id" class="mt-1 ml-1 space-y-1">
                                    <p v-if="props.items.find(i => i.id === line.item_id && line.quantity > i.quantity)" class="text-[9px] text-red-500 font-bold uppercase flex items-center gap-1">
                                        <AlertCircle class="w-3 h-3" /> Exceeds available stock!
                                    </p>
                                    <p v-else-if="props.items.find(i => i.id === line.item_id && (i.quantity - line.quantity) <= i.min_stock)" class="text-[9px] text-amber-500 font-bold uppercase flex items-center gap-1">
                                        <AlertTriangle class="w-3 h-3" /> Will drop below minimum stock
                                    </p>
                                </div>
                            </td>
                            <td class="px-4 py-3 border-l border-slate-50 text-center">
                                <input v-model="line.quantity" type="number" step="0.1" min="0.1" class="w-full border border-slate-300 text-center h-9 rounded-sm focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted" />
                            </td>
                            <td class="px-2 py-3 text-center">
                                <button v-if="!recentlySubmitted" @click="removeItemRow(index)" type="button" class="text-slate-300 hover:text-red-500 transition-colors">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!recentlySubmitted" class="p-3 bg-slate-100 border-t border-slate-100 flex justify-center mt-2 rounded-b-lg">
                    <button @click="addItemRow" type="button" class="text-[10px] font-bold text-slate-900 uppercase  flex items-center gap-2 hover:text-purple-600 transition-colors">
                        <Plus class="w-3 h-3" /> Add Item Row
                    </button>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <div v-if="recentlySubmitted" class="flex gap-3">
                    <Button @click="triggerExport" type="button" class="bg-purple-600 hover:bg-purple-700 text-white">
                        <Download class="w-4 h-4 mr-3" /> Download Issuance PDF
                    </Button>
                    <Button @click="resetForm" type="button" class="flex items-center px-8 py-4 bg-white border border-slate-300 text-slate-600 text-[10px] font-bold rounded-sm hover:bg-slate-100 transition-colors uppercase ">
                        <RefreshCw class="w-4 h-4 mr-3" /> New Entry
                    </Button>
                </div>
                <Button v-else type="submit" :disabled="form.processing" class="bg-purple-600 hover:bg-purple-700 text-white">
                    <Save class="w-4 h-4 mr-3 text-white-400" />
                    {{ form.processing ? 'Processing...' : 'Save Outbound Entry' }}
                </Button>
            </div>
        </form>
    </AppLayout>
</template>