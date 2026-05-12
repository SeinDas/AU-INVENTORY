<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { ArrowLeft, Plus, Trash2, Truck, Download, Save, RefreshCw } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { Button } from '@/components/ui/button';

const props = defineProps({ items: Array });
const page = usePage();
const recentlySubmitted = ref(false);
const submittedDate = ref('');
const toast = useToast();

const form = useForm({
    supplier_name: '',
    received_by: page.props.auth.user.name, 
    date_received: new Date().toISOString().substr(0, 10),
    line_items: [{ item_id: '', quantity: 1, unit_cost: 0 }]
});

const generatedRefNo = computed(() => form.date_received);

const addItemRow = () => form.line_items.push({ item_id: '', quantity: 1, unit_cost: 0 });
const removeItemRow = (index) => form.line_items.length > 1 && form.line_items.splice(index, 1);

// Export Logic
const triggerExport = () => {
    const url = route('web.transactions.export-daily-in', { date: submittedDate.value });
    window.open(url, '_blank');
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.line_items = [{ item_id: '', quantity: 1, unit_cost: 0 }];
    recentlySubmitted.value = false;
};

const submit = () => {
    form.post(route('web.transactions.store_bulk_in'), {
        onBefore: () => { submittedDate.value = form.date_received; },
        onSuccess: () => { 
            recentlySubmitted.value = true; 
            toast.success("Stock in record submitted successfully!"); 
        },
    });
};
</script>

<template>
    <Head title="STOCK IN" />
    <AppLayout :breadcrumbs="[{ title: 'Transactions', href: route('web.transactions.index') }, { title: 'Stock In', href: '#' }]">
        <div class="flex items-center gap-4 border-b border-slate-300 pb-6">
            <Link :href="route('web.transactions.index')" class="p-2 bg-white border border-slate-300 rounded-sm hover:bg-slate-100 text-slate-400 transition-colors">
                <ArrowLeft class="w-4 h-4" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight uppercase">STOCK IN</h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase  mt-1">
                    Reference Date: <span class="text-purple-600">{{ generatedRefNo }}</span>
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6 mt-6">
            <div class="p-2 bg-white border border-slate-300 rounded-xl">
                <div class="px-3 py-2 border-b border-slate-100 bg-slate-100 flex items-center gap-2 mb-4 rounded-t-lg">
                    <Truck class="w-3.5 h-3.5 text-slate-400" />
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase ">Inbound Log Details</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-3">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ">Supplier Name</label>
                        <input v-model="form.supplier_name" type="text" class="w-full border border-slate-300 rounded-sm text-sm h-10 px-3 font-semibold focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ">Date Received</label>
                        <input v-model="form.date_received" type="date" class="w-full border border-slate-300 rounded-sm text-sm h-10 px-3 font-semibold focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ">Received By</label>
                        <input v-model="form.received_by" type="text" class="w-full border border-slate-300 rounded-sm text-sm h-10 px-3 uppercase font-semibold bg-slate-100" readonly />
                    </div>
                </div>
            </div>

            <div class="p-2 bg-white border border-slate-300 rounded-xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300 text-[10px] font-bold uppercase  text-slate-500">
                            <th class="px-6 py-3">Property Description</th>
                            <th class="px-6 py-3 w-32 border-l border-slate-300 text-center">Qty</th>
                            <th class="px-6 py-3 w-40 border-l border-slate-300 text-center">Unit Cost</th>
                            <th class="px-4 py-3 w-12 text-center text-slate-300">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <tr v-for="(line, index) in form.line_items" :key="index">
                            <td class="px-4 py-3">
                                <select v-model="line.item_id" class="w-full border border-slate-300 rounded-sm text-sm h-9 uppercase focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted">
                                    <option value="" disabled>Select Item...</option>
                                    <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 border-l border-slate-50">
                                <input v-model="line.quantity" type="number" step="0.1" class="w-full border border-slate-300 text-center h-9 rounded-sm focus:ring-slate-900 focus:border-slate-900" required :disabled="recentlySubmitted" />
                            </td>
                            <td class="px-4 py-3 border-l border-slate-50">
                                <input v-model="line.unit_cost" type="number" step="0.01" class="w-full border border-slate-300 text-center h-9 rounded-sm focus:ring-slate-900 focus:border-slate-900" :disabled="recentlySubmitted" />
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
                    <button @click="addItemRow" type="button" class="text-[10px] font-bold text-slate-900 uppercase  flex items-center gap-2 hover:text-indigo-600 transition-colors">
                        <Plus class="w-3 h-3" /> Add Item Row
                    </button>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <div v-if="recentlySubmitted" class="flex gap-3">
                    <Button @click="triggerExport" type="button" class="bg-purple-600 hover:bg-purple-700 text-white">
                        <Download class="w-4 h-4 mr-3" /> Download PDF
                    </Button>
                    <Button @click="resetForm" type="button" class="flex items-center px-8 py-4 bg-white border border-slate-300 text-slate-600 text-[10px] font-bold rounded-sm hover:bg-slate-100 transition-colors uppercase ">
                        <RefreshCw class="w-4 h-4 mr-3" /> New Entry
                    </Button>
                </div>
                <Button v-else type="submit" :disabled="form.processing" class="bg-purple-600 hover:bg-purple-700 text-white">
                    <Save class="w-4 h-4 mr-3 text-white-400" />
                    {{ form.processing ? 'Saving...' : 'Save Stock Entry' }}
                </Button>
            </div>
        </form>
    </AppLayout>
</template>