<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import { Save, Loader2, Info } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { watch } from 'vue'; 
import axios from 'axios';

const toast = useToast();
const props = defineProps({
    categories: Array,
    units: Array
});

const form = useForm({
    product_code: '',
    name: '',
    quantity: 0,
    min_stock: 0,
    category_id: '',
    unit_id: '', 
    description: ''
});

watch(() => form.category_id, async (newId) => {
    if (newId) {
        try {
            const response = await axios.get(route('web.items.generate-code'), {
                params: { category_id: newId }
            });
            form.product_code = response.data.next_code;
        } catch (error) {
            console.error("Product Code Generation Error:", error);
            toast.error("Failed to generate product code.");
        }
    } else {
        form.product_code = ''; 
    }
});

const submit = () => {
    form.post(route('web.items.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Item registered successfully!");
        },
        onError: () => {
            toast.error("Please check the form for errors.");
        }
    });
};
</script>

<template>
    <Head title="Add New Item" />

    <AppLayout :breadcrumbs="[{ title: 'Inventory Items', href: route('web.items.index') }, { title: 'Add New Item', href: '#' }]">    
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <Card class="p-6 bg-white border border-slate-200 shadow-sm rounded-lg">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Add New Item</h1>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                        Please ensure the <span class="text-purple-700 font-semibold">Product Code</span> matches the physical tag on the asset for audit consistency.
                    </p>
                </Card>
                
                <Card class="p-4 bg-slate-50 border border-slate-200 shadow-sm rounded-lg">
                    <div class="flex gap-3">
                        <Info class="w-5 h-5 text-slate-400 shrink-0" />
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Fields marked with an asterisk (*) are required for the official registry.
                        </p>
                    </div>
                </Card>
            </div>

            <div class="lg:col-span-2">
                <Card class="p-0 bg-white border border-slate-200 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Item Specifications</span>
                    </div>
                    
                    <form @submit.prevent="submit" class="p-6 space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Item Name *</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors" 
                                required 
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Category *</label>
                                <select v-model="form.category_id" class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors bg-white" required>
                                    <option value="">Select Classification</option>
                                    <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <div v-if="form.errors.category_id" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.category_id }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Product Code *</label>
                                <input 
                                    v-model="form.product_code" 
                                    type="text" 
                                    placeholder="Auto-generated"
                                    class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors placeholder:text-slate-300 bg-slate-50" 
                                    required 
                                />
                                <div v-if="form.errors.product_code" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.product_code }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Unit of Measure</label>
                                <select v-model="form.unit_id" class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors bg-white">
                                    <option value="">Select Unit</option>
                                    <option v-for="unit in props.units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Initial Quantity</label>
                                <input 
                                    v-model="form.quantity" 
                                    type="number" 
                                    class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors" 
                                    min="0"
                                />
                                <div v-if="form.errors.quantity" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.quantity }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Min. Stock Level</label>
                                <input 
                                    v-model="form.min_stock" 
                                    type="number" 
                                    placeholder="Alert at..."
                                    class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors" 
                                    min="0"
                                />
                                <div v-if="form.errors.min_stock" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.min_stock }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Additional Description</label>
                            <textarea 
                                v-model="form.description" 
                                class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors min-h-[100px]" 
                                placeholder="Enter asset details or serial numbers..."
                            ></textarea>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                            <Link :href="route('web.items.index')" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors px-4">
                                Cancel
                            </Link>
                            <button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="bg-purple-900 hover:bg-slate-900 text-white px-6 py-2.5 text-sm font-bold rounded-sm shadow-sm transition-colors flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                <component :is="form.processing ? Loader2 : Save" class="w-4 h-4" :class="{'animate-spin': form.processing}" />
                                {{ form.processing ? 'Processing...' : 'Register Item' }}
                            </button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>