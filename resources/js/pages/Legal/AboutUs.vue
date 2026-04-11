<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Mail, Globe, Users, Target, ShieldCheck } from 'lucide-vue-next';

const team = [
  { name: 'Hamlaoui Mahran', role: 'CEO & Founder', image: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=400&fit=crop' },
  { name: 'Chattaoui Raed', role: 'Head of Product', image: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop' },
  { name: 'Abdeljalil Mohamed', role: 'Technical Lead', image: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop' },
  { name: 'Ourir Med Raouf', role: 'Technical Lead', image: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop' },
];

const values = [
  { 
    title: 'Innovation', 
    description: 'Pushing the boundaries of event management and discovery technology.',
    icon: Globe
  },
  { 
    title: 'Community', 
    description: 'Connecting people through shared experiences that matter.',
    icon: Users
  },
  { 
    title: 'Reliability', 
    description: 'Providing a secure and trusted platform for organizers and participants.',
    icon: ShieldCheck
  }
];
</script>

<template>
  <Head title="About Us" />

  <div class="min-h-screen bg-background text-foreground selection:bg-indigo-500/30">
    <!-- Navbar Spacer -->
    <div class="h-20"></div>

    <!-- Hero Section -->
    <section class="relative overflow-hidden py-24 lg:py-32">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(79,70,229,0.1),transparent_50%)]"></div>
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative z-10 text-center">
          <h1 class="mb-6 bg-gradient-to-r from-indigo-600 via-indigo-500 to-indigo-400 bg-clip-text text-5xl font-extrabold tracking-tight text-transparent dark:from-white dark:via-indigo-200 dark:to-indigo-400 sm:text-7xl">
            Redefining Events
          </h1>
          <p class="mx-auto max-w-2xl text-lg leading-relaxed text-muted-foreground sm:text-xl">
            Spotlight is the world's leading platform for discovering and managing extraordinary events. We believe in the power of shared experiences to change lives.
          </p>
        </div>
      </div>
    </section>

    <!-- Mission Section -->
    <section class="py-20">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
          <div>
            <span class="mb-4 inline-block rounded-full bg-indigo-500/10 px-4 py-1.5 text-sm font-semibold text-indigo-600 dark:text-indigo-400">Our Mission</span>
            <h2 class="mb-6 text-4xl font-bold tracking-tight text-foreground">Connecting the world through experiences</h2>
            <p class="mb-8 text-lg text-muted-foreground">
              Our mission is to empower anyone to create, share, find and attend events that fuel their passions and enrich their lives. From music festivals and marathons to conferences and community rallies, we provide the tools to bring people together.
            </p>
            <div class="flex flex-wrap gap-4">
              <Link href="/discovery" class="rounded-full bg-indigo-600 px-8 py-3 font-semibold text-white transition-all hover:bg-indigo-700 hover:shadow-[0_0_20px_rgba(79,70,229,0.4)]">
                Explore Events
              </Link>
            </div>
          </div>
          <div class="relative">
            <div class="aspect-square overflow-hidden rounded-3xl border border-border bg-card/50 p-2 backdrop-blur-3xl shadow-2xl">
              <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&fit=crop" alt="Event crowd" class="h-full w-full rounded-2xl object-cover grayscale transition-all duration-700 hover:scale-105 hover:grayscale-0" />
            </div>
            <!-- Decorative circle -->
            <div class="absolute -bottom-6 -right-6 h-32 w-32 rounded-full bg-indigo-600/10 dark:bg-indigo-600/20 blur-3xl"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- Values Section -->
    <section class="bg-muted/40 py-24 backdrop-blur-sm">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
          <h2 class="text-3xl font-bold text-foreground sm:text-4xl">Driven by Values</h2>
        </div>
        <div class="grid gap-8 md:grid-cols-3">
          <div v-for="value in values" :key="value.title" class="group rounded-3xl border border-border bg-card/50 p-8 transition-all hover:border-indigo-500/50 hover:bg-card">
            <div class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 transition-colors group-hover:bg-indigo-500 group-hover:text-white">
              <component :is="value.icon" class="h-6 w-6" />
            </div>
            <h3 class="mb-4 text-xl font-bold text-foreground">{{ value.title }}</h3>
            <p class="text-muted-foreground">{{ value.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Team Section -->
    <section class="py-24">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
          <h2 class="text-3xl font-bold text-foreground sm:text-4xl">The Team behind the Magic</h2>
        </div>
        <div class="grid gap-12 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="person in team" :key="person.name" class="group">
            <div class="mb-6 overflow-hidden rounded-3xl border border-border bg-muted transition-all group-hover:border-indigo-500/30">
              <img :src="person.image" :alt="person.name" class="aspect-[4/5] w-full object-cover grayscale transition-all duration-500 group-hover:scale-105 group-hover:grayscale-0" />
            </div>
            <h3 class="text-xl font-bold text-foreground">{{ person.name }}</h3>
            <p class="text-indigo-600 dark:text-indigo-400">{{ person.role }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="pb-32 pt-12">
      <div class="container mx-auto px-4">
        <div class="relative overflow-hidden rounded-[3rem] bg-indigo-600 px-8 py-20 text-center text-white shadow-2xl lg:py-24">
          <div class="relative z-10">
            <h2 class="mb-6 text-4xl font-extrabold sm:text-5xl">Join our community</h2>
            <p class="mx-auto mb-10 max-w-xl text-indigo-100/80">
              Whether you're an organizer or a participant, there's a place for you at Spotlight. Let's make every moment count.
            </p>
            <Link href="/register" class="inline-block rounded-full bg-white px-10 py-4 font-bold text-indigo-600 transition-transform hover:scale-105 active:scale-95 shadow-xl">
              Get Started for Free
            </Link>
          </div>
          <!-- Decorative gradients -->
          <div class="absolute left-0 top-0 h-64 w-64 bg-white/10 blur-3xl"></div>
          <div class="absolute bottom-0 right-0 h-96 w-96 bg-indigo-900/30 blur-3xl"></div>
        </div>
      </div>
    </section>
  </div>
</template>
