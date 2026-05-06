<script setup lang="ts">
import { ref } from 'vue';
import { 
    X, 
    BookOpen, 
    Zap, 
    Target, 
    DollarSign, 
    Share2, 
    ShieldCheck,
    ChevronRight,
    ExternalLink
} from 'lucide-vue-next';

defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits(['close']);

const activeTab = ref('basics');

const guideTabs = [
    { id: 'basics', label: 'The Basics', icon: BookOpen },
    { id: 'strategy', label: 'Sales Strategy', icon: Target },
    { id: 'payouts', label: 'Earnings & Payouts', icon: DollarSign },
    { id: 'rules', label: 'Rules & Ethics', icon: ShieldCheck },
];

const strategies = [
    {
        title: 'Leverage Social Proof',
        description: 'Share your personal experience with the events. Authentic testimonials convert 3x better than generic links.',
        icon: Share2,
        color: 'bg-blue-500'
    },
    {
        title: 'Target Your Audience',
        description: 'Promote sports events to athletes and music festivals to concert-goers. Relevance is key to high CTR.',
        icon: Target,
        color: 'bg-purple-500'
    },
    {
        title: 'Flash Sales Momentum',
        description: 'Use "Limited Time" or "Almost Sold Out" messaging when events are reaching capacity.',
        icon: Zap,
        color: 'bg-amber-500'
    }
];

const close = () => {
    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 md:p-6 overflow-hidden">
        <!-- Backdrop -->
        <div 
            class="absolute inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity duration-300"
            @click="close"
        ></div>

        <!-- Modal Container -->
        <div 
            class="relative w-full max-w-4xl bg-white dark:bg-neutral-950 rounded-[2.5rem] shadow-2xl border border-white/20 dark:border-white/10 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-300"
        >
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-100 dark:border-neutral-800 flex items-center justify-between bg-gradient-to-r from-indigo-50/50 to-transparent dark:from-indigo-900/10">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                        <BookOpen class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white leading-tight">Affiliate Success Guide</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Master the art of event promotion</p>
                    </div>
                </div>
                <button 
                    @click="close"
                    class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-800 text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all"
                >
                    <X class="w-6 h-6" />
                </button>
            </div>

            <div class="flex-1 flex flex-col md:flex-row overflow-hidden">
                <!-- Sidebar Navigation -->
                <div class="w-full md:w-64 bg-slate-50/50 dark:bg-neutral-900/50 border-r border-gray-100 dark:border-neutral-800 p-4 space-y-2 overflow-y-auto">
                    <button 
                        v-for="tab in guideTabs" 
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'w-full flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-sm transition-all duration-200',
                            activeTab === tab.id 
                                ? 'bg-white dark:bg-neutral-800 text-indigo-600 dark:text-indigo-400 shadow-sm ring-1 ring-black/[0.05] dark:ring-white/[0.05]' 
                                : 'text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-neutral-800/50'
                        ]"
                    >
                        <component :is="tab.icon" class="w-4 h-4" />
                        {{ tab.label }}
                        <ChevronRight v-if="activeTab === tab.id" class="w-4 h-4 ml-auto" />
                    </button>

                    <div class="mt-8 p-4 rounded-2xl bg-indigo-600 text-white space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wider opacity-80">Support</p>
                        <p class="text-xs leading-relaxed">Need custom assets or have questions?</p>
                        <button class="w-full py-2 bg-white/20 hover:bg-white/30 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-2">
                            Contact Manager <ExternalLink class="w-3 h-3" />
                        </button>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                    <!-- Tab: Basics -->
                    <div v-if="activeTab === 'basics'" class="space-y-8 animate-in slide-in-from-right-4 duration-300">
                        <div class="space-y-4">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Getting Started</h3>
                            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                                Welcome to the Spotlight Affiliate Program. Your journey to earning high commissions starts with understanding how our tracking works. Every link you share from your dashboard contains a unique referral ID.
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-6 rounded-3xl bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-500/10">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center mb-4">
                                    <ShieldCheck class="w-5 h-5" />
                                </div>
                                <h4 class="font-bold text-emerald-900 dark:text-emerald-400 mb-1">30-Day Cookie</h4>
                                <p class="text-xs text-emerald-800/70 dark:text-emerald-400/70 leading-relaxed">Even if they don't buy immediately, you get credit if they return within 30 days.</p>
                            </div>
                            <div class="p-6 rounded-3xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-500/10">
                                <div class="w-10 h-10 rounded-xl bg-blue-500 text-white flex items-center justify-center mb-4">
                                    <TrendingUp class="w-5 h-5" />
                                </div>
                                <h4 class="font-bold text-blue-900 dark:text-blue-400 mb-1">Last-Click Wins</h4>
                                <p class="text-xs text-blue-800/70 dark:text-blue-400/70 leading-relaxed">The last affiliate link clicked before purchase gets the commission.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Strategy -->
                    <div v-if="activeTab === 'strategy'" class="space-y-8 animate-in slide-in-from-right-4 duration-300">
                        <div class="space-y-4">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Winning Strategies</h3>
                            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                                Don't just spam links. The most successful affiliates use these proven techniques to build trust and drive conversions.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div v-for="strat in strategies" :key="strat.title" class="flex gap-4 p-5 rounded-3xl hover:bg-slate-50 dark:hover:bg-neutral-900 transition-colors border border-transparent hover:border-gray-100 dark:hover:border-neutral-800">
                                <div :class="['w-12 h-12 rounded-2xl shrink-0 flex items-center justify-center text-white', strat.color]">
                                    <component :is="strat.icon" class="w-6 h-6" />
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-900 dark:text-white">{{ strat.title }}</h4>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ strat.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Payouts -->
                    <div v-if="activeTab === 'payouts'" class="space-y-8 animate-in slide-in-from-right-4 duration-300">
                        <div class="space-y-4">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Earnings & Payouts</h3>
                            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                                Transparency is our priority. Here is how and when you get paid for your hard work.
                            </p>
                        </div>

                        <div class="relative p-8 rounded-[2rem] bg-slate-900 text-white overflow-hidden">
                            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-600/20 blur-[100px] -mr-32 -mt-32"></div>
                            <div class="relative z-10 space-y-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-indigo-400 text-xs font-bold uppercase tracking-widest mb-1">Standard Commission</p>
                                        <p class="text-3xl font-black">5%</p>
                                    </div>
                                    <DollarSign class="w-12 h-12 text-indigo-400/30" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-500/10">
                            <Clock class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                            <p class="text-xs text-amber-800 dark:text-amber-400 leading-relaxed font-medium">
                                Commissions are held for 24 hours after the event completion to account for potential refunds or disputes before being marked as "Approved".
                            </p>
                        </div>
                    </div>

                    <!-- Tab: Rules -->
                    <div v-if="activeTab === 'rules'" class="space-y-8 animate-in slide-in-from-right-4 duration-300">
                        <div class="space-y-4">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Program Rules</h3>
                            <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-sm">
                                To maintain a high-quality ecosystem, we enforce strict guidelines. Violation of these rules may lead to immediate account suspension.
                            </p>
                        </div>

                        <ul class="space-y-3">
                            <li v-for="rule in [
                                'No keyword bidding on brand terms (Spotlight, etc.)',
                                'No spamming on social media comments or forums',
                                'Transparency is mandatory: disclose your affiliate status',
                                'No cookie-stuffing or misleading redirects',
                                'Use only approved creative assets provided by organizers'
                            ]" :key="rule" class="flex items-center gap-3 p-3 rounded-xl bg-red-50/50 dark:bg-red-900/10 text-red-700 dark:text-red-400 text-sm font-medium">
                                <X class="w-4 h-4" />
                                {{ rule }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-6 border-t border-gray-100 dark:border-neutral-800 bg-slate-50/50 dark:bg-neutral-900/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium text-center sm:text-left">
                    By participating, you agree to our <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:underline">Affiliate Terms of Service</a>.
                </p>
                <button 
                    @click="close"
                    class="px-8 py-2.5 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-black text-sm font-bold hover:opacity-90 transition-opacity"
                >
                    Got it, thanks!
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1f2937;
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes zoom-in {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.animate-in {
    animation: fade-in 0.3s ease-out;
}
.zoom-in {
    animation: zoom-in 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
