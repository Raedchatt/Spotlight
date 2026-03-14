<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Ticket, Trophy, User as UserIcon, Phone, Mail, User } from 'lucide-vue-next';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type User as UserType } from '@/types';

type Props = {
    user: UserType & { telephone?: string; interests?: string[]; about?: string };
    stats: {
        total_events: number;
        best_category: string;
    };
    status?: string;
};

const props = defineProps<Props>();

const profileForm = useForm({
    username: props.user.username,
    email: props.user.email,
    telephone: props.user.telephone || '',
    about: props.user.about || '',
});

const interestsForm = useForm({
    interests: props.user.interests || [],
});

const availableInterests = [
    { label: 'Music', slug: 'music', icon: '🎵' },
    { label: 'Sports', slug: 'sports', icon: '⚽' },
    { label: 'Art', slug: 'art', icon: '🎨' },
    { label: 'Tech', slug: 'tech', icon: '💻' },
    { label: 'Theater', slug: 'theater', icon: '🎭' },
    { label: 'Food', slug: 'food', icon: '🍕' },
    { label: 'Cinema', slug: 'cinema', icon: '🎬' },
    { label: 'Gaming', slug: 'gaming', icon: '🎮' },
];

const toggleInterest = (slug: string) => {
    const index = interestsForm.interests.indexOf(slug);
    if (index > -1) {
        interestsForm.interests.splice(index, 1);
    } else {
        interestsForm.interests.push(slug);
    }
};

const updateProfile = () => {
    profileForm.patch('/settings/participant', {
        preserveScroll: true,
    });
};

const updateInterests = () => {
    interestsForm.patch('/settings/participant', {
        preserveScroll: true,
    });
};

const breadcrumbItems = [
    { title: 'Settings', href: '/settings/participant' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Settings" />

        <SettingsLayout>
            <div class="space-y-8">
                <!-- Top: Appearance Section -->
                <Card class="border-none shadow-sm overflow-hidden">
                    <div class="flex flex-col items-center justify-center p-8 bg-muted/30">
                        <div class="text-center mb-6">
                            <h2 class="text-xl font-bold">Appearance</h2>
                            <p class="text-sm text-muted-foreground">Customize how the application looks for you</p>
                        </div>
                        <AppearanceTabs />
                    </div>
                </Card>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Column 1: Profile & Stats -->
                    <div class="lg:col-span-3 space-y-6">
                        <Card class="overflow-hidden border-none shadow-md bg-gradient-to-br from-blue-500/5 to-blue-500/10">
                            <CardContent class="pt-6">
                                <div class="flex flex-col items-center text-center py-4">
                                    <div class="h-20 w-20 rounded-full bg-blue-500/20 flex items-center justify-center mb-4 border-4 border-background shadow-sm">
                                        <span class="text-2xl font-bold text-blue-600">{{ user.username.substring(0, 2).toUpperCase() }}</span>
                                    </div>
                                    <h3 class="font-bold text-lg">{{ user.username }}</h3>
                                    <Badge variant="secondary" class="mt-2 bg-blue-100 text-blue-700 hover:bg-blue-100 border-none">Participant</Badge>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-none shadow-sm h-32 flex flex-col justify-center">
                            <CardHeader class="pb-2 pt-4">
                                <div class="flex items-center gap-2">
                                    <div class="p-2 bg-blue-500/10 rounded-lg">
                                        <Ticket class="h-4 w-4 text-blue-500" />
                                    </div>
                                    <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Events joined</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent class="pb-4">
                                <div class="text-3xl font-bold text-blue-600">{{ stats.total_events }}</div>
                            </CardContent>
                        </Card>

                        <Card class="border-none shadow-sm h-32 flex flex-col justify-center">
                            <CardHeader class="pb-2 pt-4">
                                <div class="flex items-center gap-2">
                                    <div class="p-2 bg-orange-500/10 rounded-lg">
                                        <Trophy class="h-4 w-4 text-orange-500" />
                                    </div>
                                    <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Most attended category</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent class="pb-4">
                                <div class="text-xl font-bold text-orange-600 truncate">{{ stats.best_category }}</div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Column 2: Personal Information -->
                    <div class="lg:col-span-6">
                        <Card class="border-none shadow-sm">
                            <CardHeader>
                                <CardTitle class="text-lg">Personal Information</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="updateProfile" class="space-y-6">
                                    <div class="grid gap-2">
                                        <Label for="username" class="flex items-center gap-2">
                                            <UserIcon class="h-3.5 w-3.5 text-muted-foreground" />
                                            Username
                                        </Label>
                                        <Input
                                            id="username"
                                            v-model="profileForm.username"
                                            placeholder="Your username"
                                            class="h-11 rounded-xl"
                                        />
                                        <InputError :message="profileForm.errors.username" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="email" class="flex items-center gap-2">
                                            <Mail class="h-3.5 w-3.5 text-muted-foreground" />
                                            Email address
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            v-model="profileForm.email"
                                            placeholder="email@example.com"
                                            class="h-11 rounded-xl"
                                        />
                                        <InputError :message="profileForm.errors.email" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="telephone" class="flex items-center gap-2">
                                            <Phone class="h-3.5 w-3.5 text-muted-foreground" />
                                            Phone Number
                                        </Label>
                                        <Input
                                            id="telephone"
                                            v-model="profileForm.telephone"
                                            placeholder="+216 -- --- ---"
                                            class="h-11 rounded-xl"
                                        />
                                        <InputError :message="profileForm.errors.telephone" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="about" class="flex items-center gap-2">
                                            <UserIcon class="h-3.5 w-3.5 text-muted-foreground" />
                                            About me
                                        </Label>
                                        <textarea
                                            id="about"
                                            v-model="profileForm.about"
                                            placeholder="Tell us about yourself..."
                                            class="flex min-h-[120px] w-full rounded-xl border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        ></textarea>
                                        <InputError :message="profileForm.errors.about" />
                                    </div>

                                    <Button
                                        type="submit"
                                        class="w-full h-11 bg-black hover:bg-zinc-800 text-white font-medium rounded-xl transition-all"
                                        :disabled="profileForm.processing"
                                    >
                                        {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                                    </Button>

                                    <p v-if="profileForm.recentlySuccessful" class="text-sm text-center text-emerald-600 font-medium">
                                        Profile updated successfully.
                                    </p>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Column 3: Interests & Preferences -->
                    <div class="lg:col-span-3">
                        <Card class="border-none shadow-sm">
                            <CardHeader>
                                <CardTitle class="text-lg">Interests & Preferences</CardTitle>
                                <p class="text-xs text-muted-foreground mt-1">Select the event types you enjoy</p>
                            </CardHeader>
                            <CardContent>
                                <div class="flex flex-wrap gap-2 mb-8">
                                    <button
                                        v-for="interest in availableInterests"
                                        :key="interest.slug"
                                        type="button"
                                        @click="toggleInterest(interest.slug)"
                                        :class="[
                                            'px-3 py-2 rounded-full text-xs font-semibold transition-all border outline-none',
                                            interestsForm.interests.includes(interest.slug)
                                                ? 'bg-zinc-900 border-zinc-900 text-white shadow-sm'
                                                : 'bg-transparent border-zinc-200 text-zinc-600 hover:border-zinc-400'
                                        ]"
                                    >
                                        <span class="mr-1.5">{{ interest.icon }}</span>
                                        {{ interest.label }}
                                    </button>
                                </div>

                                <Button
                                    @click="updateInterests"
                                    variant="outline"
                                    class="w-full h-11 border-zinc-200 hover:bg-zinc-50 rounded-xl font-medium transition-all"
                                    :disabled="interestsForm.processing"
                                >
                                    {{ interestsForm.processing ? 'Saving...' : 'Save Interests' }}
                                </Button>

                                <p v-if="interestsForm.recentlySuccessful" class="text-sm text-center text-emerald-600 font-medium mt-4">
                                    Interests updated.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<style scoped>
/* Subtle entrance animation */
.grid > div {
    animation: slideUp 0.4s ease-out forwards;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.grid > div:nth-child(2) { animation-delay: 0.1s; }
.grid > div:nth-child(3) { animation-delay: 0.2s; }
</style>
