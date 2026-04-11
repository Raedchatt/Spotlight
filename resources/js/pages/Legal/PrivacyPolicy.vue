<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Shield, Lock, Eye, FileText } from 'lucide-vue-next';

const sections = [
  {
    title: 'Information We Collect',
    icon: Eye,
    content: 'We collect information you provide directly to us when you create an account, create an event, or make a reservation. This includes your name, email address, phone number, and any other information you choose to provide.'
  },
  {
    title: 'How We Use Your Information',
    icon: FileText,
    content: 'We use the information we collect to provide, maintain, and improve our services, to process transactions, to send you technical notices, updates, security alerts, and support messages.'
  },
  {
    title: 'Data Protection',
    icon: Lock,
    content: 'We take reasonable measures to help protect information about you from loss, theft, misuse and unauthorized access, disclosure, alteration and destruction.'
  },
  {
    title: 'Sharing of Information',
    icon: Shield,
    content: 'We may share information about you with organizers of events you reserve, or with third-party vendors and service providers who perform services for us.'
  }
];
</script>

<template>
  <Head title="Privacy Policy" />

  <div class="min-h-screen bg-background text-foreground selection:bg-indigo-500/30">
    <!-- Navbar Spacer -->
    <div class="h-20"></div>

    <section class="py-20 lg:py-32">
      <div class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
          <h1 class="mb-4 text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl">Privacy Policy</h1>
          <p class="text-muted-foreground">Last updated: April 9, 2026</p>
        </div>

        <div class="space-y-12">
          <div v-for="section in sections" :key="section.title" class="group relative rounded-3xl border border-border bg-card/30 p-8 transition-colors hover:border-border/80">
            <div class="mb-6 flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <component :is="section.icon" class="h-6 w-6" />
              </div>
              <h2 class="text-2xl font-bold text-foreground">{{ section.title }}</h2>
            </div>
            <p class="leading-relaxed text-muted-foreground">
              {{ section.content }}
            </p>
          </div>

          <!-- detailed legal text -->
          <div class="max-w-none text-muted-foreground space-y-6">
            <h2 class="text-2xl font-bold text-foreground">Detailed Data Usage</h2>
            <p>
              Spotlight uses industry-standard encryption and security practices to ensure your data stays private. We do not sell your personal data to advertisers. Your data is used exclusively to facilitate event organization and participation.
            </p>
            <h3 class="text-xl font-bold text-foreground">Cookies</h3>
            <p>
              We use cookies to improve your browsing experience, remember your preferences, and analyze our traffic. By using Spotlight, you consent to our use of cookies.
            </p>
            <h3 class="text-xl font-bold text-foreground">Your Rights</h3>
            <p>
              You have the right to access, correct, or delete your personal information at any time. You can manage your data settings in your account profile or by contacting our support team.
            </p>
          </div>
        </div>

        <div class="mt-20 flex justify-center border-t border-border pt-10">
          <p class="text-center text-sm text-muted-foreground">
            If you have any questions about this Privacy Policy, please contact us at <span class="text-indigo-600 dark:text-indigo-400">privacy@spotlight.com</span>
          </p>
        </div>
      </div>
    </section>
  </div>
</template>
