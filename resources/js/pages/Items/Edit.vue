<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import { Save, Loader2, Info, ArrowLeft } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { watch } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';


const toast = useToast();

const props = defineProps({
    item: Object,
    mainCategories: Array,
    subCategories: Array,
    units: Array
});

const labelClass = "block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5";
const inputClass = "w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors";
const errorClass = "text-red-600 text-[11px] mt-1 font-semibold";

const form = useForm({
    product_code: props.item.product_code,
    serial_no: props.item.serial_no || '0',
    name: props.item.name,
    min_stock: props.item.min_stock,
    category_id: props.item.category_item?.category_id || '', 
    subcategory_id: props.item.category_item?.subcategory_id || '',
    unit_id: props.item.unit_id || '', 
    description: props.item.description || ''
});

const fetchProductCode = async (catId, subCatId) => {
    try {
        const response = await axios.get(route('web.items.generate-code'), {
            params: { 
                category_id: catId,
                subcategory_id: subCatId || null 
            }
        });
        form.product_code = response.data.next_code;
    } catch (error) {
        console.error("Code Gen Error:", error);
    }
};

// Watch primary category to reset sub-category and clear product code
watch(() => form.category_id, (newVal, oldVal) => {
    // Only clear if the value actually changes (prevents clearing on initial edit load)
    if (oldVal !== undefined && oldVal !== newVal) {
        form.subcategory_id = '';
        form.product_code = '';
    }
});

watch([() => form.category_id, () => form.subcategory_id], ([newCat, newSub], [oldCat, oldSub]) => {
    // Only fetch if a category is selected and it's not the initial mounting state
    if (newCat && (newCat !== oldCat || newSub !== oldSub)) {
        fetchProductCode(newCat, newSub);
    }
});

const submit = () => {
    form.put(route('web.items.update', props.item.id), {
        preserveScroll: true,
        onSuccess: () => toast.success("Item updated successfully!"),
        onError: () => toast.error("Please check the form for errors.")
    });
};
</script>

<template>
    <Head :title="`Edit ${item.name}`" />

    <AppLayout :breadcrumbs="[{ title: 'Inventory Items', href: route('web.items.index') }, { title: item.product_code, href: '#' }]">    
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <Card class="p-6 bg-white border border-slate-200 shadow-sm rounded-lg">
                    <div class="flex items-center gap-3 mb-4">
                        <Link :href="route('web.items.index')" class="p-1.5 hover:bg-slate-100 rounded-full transition-colors">
                            <ArrowLeft class="w-4 h-4 text-slate-600" />
                        </Link>
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Edit Item</h1>
                    </div>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                        Updating the <span class="text-purple-700 font-semibold">Classification</span> will automatically regenerate the Product Code to maintain organizational standards.
                    </p>
                </Card>
                
                <Card class="p-4 bg-slate-50 border border-slate-200 shadow-sm rounded-lg">
                    <div class="flex gap-3">
                        <Info class="w-5 h-5 text-slate-400 shrink-0" />
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Modifying the product code might affect historical audit logs. Please proceed with caution.
                        </p>
                    </div>
                </Card>
            </div>

            <div class="lg:col-span-2">
                <Card class="p-0 bg-white border border-slate-200 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Update Specifications</span>
                    </div>
                    
                    <form @submit.prevent="submit" class="p-6 space-y-5">
                        <div>
                            <label :class="labelClass">Item Name *</label>
                            <input v-model="form.name" type="text" :class="inputClass" required />
                            <div v-if="form.errors.name" :class="errorClass">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label :class="labelClass">Serial Number</label>
                            <input v-model="form.serial_no" type="text" :class="inputClass" />
                            <div v-if="form.errors.serial_no" :class="errorClass">{{ form.errors.serial_no }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label :class="labelClass">Main Category *</label>
                                <select v-model="form.category_id" :class="[inputClass, 'bg-white']" required>
                                    <option value="">Select Classification</option>
                                    <option v-for="cat in mainCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Sub-Category</label>
                                <select 
                                    v-model="form.subcategory_id" 
                                    :disabled="!form.category_id"
                                    :class="[inputClass, 'bg-white disabled:bg-slate-50']" 
                                >
                                    <option value="">None</option>
                                    <option v-for="sub in subCategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                </select>
                                <div v-if="form.errors.subcategory_id" :class="errorClass">{{ form.errors.subcategory_id }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label :class="labelClass">Product Code *</label>
                                <input 
                                    v-model="form.product_code" 
                                    type="text" 
                                    :class="[inputClass, 'bg-slate-50']" 
                                    readonly
                                    required 
                                />
                                <div v-if="form.errors.product_code" :class="errorClass">{{ form.errors.product_code }}</div>
                            </div>
                            <div>
                                <label :class="labelClass">Unit of Measure</label>
                                <select v-model="form.unit_id" :class="[inputClass, 'bg-white']">
                                    <option value="">Select Unit</option>
                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                </select>
                                <div v-if="form.errors.unit_id" :class="errorClass">{{ form.errors.unit_id }}</div>
                            </div>
                        </div>

                        <div>
                            <label :class="labelClass">Min. Stock Level</label>
                            <input v-model="form.min_stock" type="number" :class="inputClass" min="0" />
                            <div v-if="form.errors.min_stock" :class="errorClass">{{ form.errors.min_stock }}</div>
                        </div>

                        <div>
                            <label :class="labelClass">Additional Description</label>
                            <textarea 
                                v-model="form.description" 
                                :class="[inputClass, 'min-h-[100px]']"
                            ></textarea>
                            <div v-if="form.errors.description" :class="errorClass">{{ form.errors.description }}</div>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                            <Link :href="route('web.items.index')" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors px-4">
                                Cancel
                            </Link>
                            <Button 
                                type="submit"
                                variant="default" 
                                :disabled="form.processing" 
                                class="flex items-center gap-2"
                            >
                                <component :is="form.processing ? Loader2 : Save" class="w-4 h-4" :class="{'animate-spin': form.processing}" />
                                {{ form.processing ? 'Updating...' : 'Update Item' }}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>