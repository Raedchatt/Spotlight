<script setup lang="ts">

import { Head } from '@inertiajs/vue3';
import { 
  Languages, 
  Gavel, 
  ShoppingCart, 
  CreditCard, 
  Truck, 
  RefreshCcw, 
  ShieldAlert, 
  Database, 
  Scale 
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
type Language = 'en' | 'fr' | 'ar';

const currentLang = ref<Language>('en');

const content = {
  en: {
    title: 'Terms of Service',
    description: 'Please read these conditions carefully before making a purchase on Spotlight.',
    lastUpdated: 'Last updated: April 9, 2026',
    sections: [
      {
        title: 'General Terms',
        icon: Gavel,
        text: 'These terms govern the use of the online ticketing service provided by Spotlight. By using this service, you agree to be bound by these conditions.'
      },
      {
        title: 'Orders & Availability',
        icon: ShoppingCart,
        text: 'All orders are subject to availability. It is your responsibility to verify event details (date, time, location) before purchasing. The Seller is not responsible for schedule changes or organizer cancellations.'
      },
      {
        title: 'Pricing & Fees',
        icon: CreditCard,
        text: 'Prices include all taxes. Cash payments are valid for 4 hours only; otherwise, the order is automatically cancelled.'
      },
      {
        title: 'Delivery',
        icon: Truck,
        text: 'Tickets are delivered electronically via email or your user account. The Seller is not liable for loss or theft of tickets once delivered.'
      },
      {
        title: 'Cancellations & Refunds',
        icon: RefreshCcw,
        text: 'Tickets are non-refundable unless the event is cancelled by the organizer. You may cancel your reservation up to 72 hours before the event, but no refund is issued for buyer cancellations. Organizers trigger automatic Stripe refunds upon event cancellation.'
      },
      {
        title: 'Responsibility',
        icon: ShieldAlert,
        text: 'The Seller declines liability for any loss, damage, or injury arising from event attendance. Attendees must take necessary safety measures.'
      },
      {
        title: 'Data Protection',
        icon: Database,
        text: 'We protect your personal data according to current legislation. Please refer to our Privacy Policy for more details.'
      },
      {
        title: 'Jurisdiction',
        icon: Scale,
        text: 'These terms are governed by Tunisian law. Any disputes will be submitted to the exclusive jurisdiction of the courts of Tunis.'
      }
    ],
    footer: 'For any legal inquiries, please contact our customer service.'
  },
  fr: {
    title: 'Conditions de Vente',
    description: 'Veuillez lire attentivement ces conditions avant d\'effectuer un achat sur Spotlight.',
    lastUpdated: 'Dernière mise à jour : 9 avril 2026',
    sections: [
      {
        title: 'Généralités',
        icon: Gavel,
        text: 'Les présentes conditions régissent l\'utilisation de la billetterie en ligne fournie par Spotlight. En utilisant ce service, vous acceptez d\'être lié par ces conditions.'
      },
      {
        title: 'Commandes & Disponibilité',
        icon: ShoppingCart,
        text: 'Toutes les commandes sont soumises à disponibilité. Il est de votre responsabilité de vérifier les détails de l\'événement (date, lieu) avant l\'achat. Le Vendeur décline toute responsabilité en cas de changement d\'horaire ou d\'annulation par l\'organisateur.'
      },
      {
        title: 'Prix et Paiement',
        icon: CreditCard,
        text: 'Les prix incluent les taxes. Le paiement en espèces reste valide 4h seulement avant annulation automatique.'
      },
      {
        title: 'Livraison des Billets',
        icon: Truck,
        text: 'Les billets sont livrés électroniquement via email ou votre compte utilisateur. Le Vendeur décline toute responsabilité en cas de perte ou vol des billets après livraison.'
      },
      {
        title: 'Annulations & Remboursements',
        icon: RefreshCcw,
        text: 'Les billets ne sont ni échangeables ni remboursables, sauf annulation par l\'organisateur. Vous pouvez annuler jusqu\'à 72h avant l\'événement, mais aucun remboursement n\'est effectué pour l\'acheteur. En cas d\'annulation par l\'organisateur, un remboursement Stripe est déclenché automatiquement.'
      },
      {
        title: 'Responsabilité',
        icon: ShieldAlert,
        text: 'Le Vendeur décline toute responsabilité en cas de perte ou blessure lors de l\'événement. Il est de votre responsabilité d\'assurer votre propre sécurité.'
      },
      {
        title: 'Données Personnelles',
        icon: Database,
        text: 'Nous protégeons vos données conformément à la législation. Consultez notre politique de confidentialité pour plus d\'informations.'
      },
      {
        title: 'Droit & Juridiction',
        icon: Scale,
        text: 'Les présentes conditions sont régies par les lois Tunisiennes. Tout litige sera soumis à la juridiction exclusive des tribunaux de Tunis.'
      }
    ],
    footer: 'Pour toute question, veuillez contacter notre service clientèle.'
  },
  ar: {
    title: 'شروط البيع',
    description: 'يرجى قراءة هذه الشروط بعناية قبل إجراء عملية الشراء على سبوتلايت.',
    lastUpdated: 'آخر تحديث: ٩ أبريل ٢٠٢٦',
    sections: [
      {
        title: 'عام',
        icon: Gavel,
        text: 'تحكم هذه الشروط استخدام خدمة التذاكر عبر الإنترنت التي تقدمها سبوتلايت. باستخدامك لهذه الخدمة، فإنك توافق على الالتزام بهذه الشروط.'
      },
      {
        title: 'الطلبات والتوافر',
        icon: ShoppingCart,
        text: 'تخضع جميع الطلبات لمدى التوافر. تقع على عاتقك مسؤولية التحقق من تفاصيل الفعالية قبل الشراء. البائع غير مسؤول عن تغيير المواعيد أو الإلغاء.'
      },
      {
        title: 'الأسعار والدفع',
        icon: CreditCard,
        text: 'الأسعار تشمل الضرائب. الدفع نقداً صالح لمدة 4 ساعات فقط قبل الإلغاء التلقائي.'
      },
      {
        title: 'تسليم التذاكر',
        icon: Truck,
        text: 'يتم تسليم التذاكر إلكترونياً عبر البريد الإلكتروني أو حسابك. البائع غير مسؤول عن فقدان أو سرقة التذاكر بعد تسليمها.'
      },
      {
        title: 'الإلغاء والاسترداد',
        icon: RefreshCcw,
        text: 'التذاكر غير قابلة للتبديل أو الاسترداد إلا في حال إلغاء الفعالية من قبل المنظم. يمكنك الإلغاء حتى 72 ساعة قبل الفعالية، ولكن لا يتم استرداد المبلغ للمشتري. يتم تفعيل استرداد الأموال تلقائياً عبر Stripe في حال إلغاء المنظم للفعالية.'
      },
      {
        title: 'المسؤولية',
        icon: ShieldAlert,
        text: 'يخلي البائع مسؤوليته عن أي خسارة أو إصابة ناتجة عن حضور الفعالية. تقع على عاتقك مسؤولية اتخاذ جميع التدابير اللازمة لسلامتك.'
      },
      {
        title: 'حماية البيانات',
        icon: Database,
        text: 'نحن نحمي بياناتك الشخصية وفقاً للتشريعات المعمول بها. يرجى مراجعة سياسة الخصوصية لمزيد من المعلومات.'
      },
      {
        title: 'القانون والاختصاص',
        icon: Scale,
        text: 'تخضع هذه الشروط للقانون التونسي. تخضع جميع النزاعات للاختصاص الحصري لمحاكم تونس العاصمة.'
      }
    ],
    footer: 'لأية استفسارات، يرجى الاتصال بخدمة العملاء لدينا.'
  }
};

const t = computed(() => content[currentLang.value]);
const isRTL = computed(() => currentLang.value === 'ar');
</script>

<template>
  <Head :title="t.title" />

  <div 
    class="min-h-screen bg-background text-foreground selection:bg-indigo-500/30 transition-all duration-300"
    :dir="isRTL ? 'rtl' : 'ltr'"
  >
    <!-- Navbar Spacer -->
    <div class="h-20"></div>

    <section class="py-20 lg:py-32">
      <div class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        
        <!-- Language Switcher -->
        <div class="mb-12 flex justify-center gap-2" dir="ltr">
          <button 
            v-for="lang in (['en', 'fr', 'ar'] as Language[])" 
            :key="lang"
            @click="currentLang = lang"
            class="flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-all"
            :class="currentLang === lang 
              ? 'bg-indigo-600 text-white border-indigo-500 shadow-lg shadow-indigo-500/20' 
              : 'bg-card text-muted-foreground border-border hover:bg-accent'"
          >
            <Languages v-if="currentLang === lang" class="h-4 w-4" />
            {{ lang.toUpperCase() }}
          </button>
        </div>

        <div class="mb-16 text-center">
          <h1 class="mb-4 text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl transition-all duration-500">
            {{ t.title }}
          </h1>
          <p class="text-muted-foreground">{{ t.description }}</p>
          <p class="mt-2 text-xs text-muted-foreground/60">{{ t.lastUpdated }}</p>
        </div>

        <div class="grid gap-6">
          <div v-for="section in t.sections" :key="section.title" class="group relative rounded-3xl border border-border bg-card/20 p-8 transition-all hover:bg-card/40">
            <div class="mb-4 flex items-center gap-4">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <component :is="section.icon" class="h-5 w-5" />
              </div>
              <h2 class="text-xl font-bold text-foreground">{{ section.title }}</h2>
            </div>
            <p class="text-muted-foreground leading-relaxed">
              {{ section.text }}
            </p>
          </div>
        </div>

        <div class="mt-20 flex flex-col items-center gap-6 border-t border-border pt-10">
          <p class="text-center text-sm text-muted-foreground">
            {{ t.footer }}
          </p>
          <div class="flex gap-4">
             <div class="h-1 w-12 rounded-full bg-indigo-500/20"></div>
             <div class="h-1 w-12 rounded-full bg-indigo-500/40"></div>
             <div class="h-1 w-12 rounded-full bg-indigo-500/20"></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Smooth transition for language switching effects */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>
