<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { Calendar, DollarSign, User as UserIcon, Trophy, ChevronRight, CreditCard, Check } from 'lucide-vue-next';
import axios from 'axios';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type AppPageProps, type BreadcrumbItem } from '@/types';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    stats?: {
        type: 'organizer' | 'participant' | 'revendeur';
        events_count?: number;
        revenue?: number;
        total_events?: number;
        best_category?: string;
    };
    bestEvents?: Array<{
        id: number;
        titre: string;
        categorie: string;
        categorie_autre?: string;
        reservations_count: number;
        medias: Array<{ url: string }>;
    }>;
    rib?: string;
};

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage<AppPageProps>();
const user = page.props.auth.user as any;

const connectingStripe = ref(false);

const connectStripe = async () => {
    connectingStripe.value = true;
    try {
        const response = await axios.post('/web-api/stripe/connect');
        if (response.data.url) {
            window.location.href = response.data.url;
        }
    } catch (error: any) {
        console.error('Stripe Connect error:', error);
        toast.error(error.response?.data?.error || 'Failed to initialize Stripe connection.');
    } finally {
        connectingStripe.value = false;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="space-y-8">
                <!-- Appearance Section (Centered at Top) -->
                <div class="flex flex-col items-center justify-center p-8 bg-muted/50 rounded-xl border border-dashed">
                    <div class="text-center mb-6">
                        <h2 class="text-lg font-semibold">Appearance</h2>
                        <p class="text-sm text-muted-foreground">Customize how the application looks for you</p>
                    </div>
                    <AppearanceTabs />
                </div>

                <Form
                    v-bind="ProfileController.update.form()"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        <!-- Left Column: User Info & Stats -->
                        <div class="lg:col-span-3 space-y-6">
                            <Card class="overflow-hidden border-none shadow-md bg-gradient-to-br from-primary/5 to-primary/10">
                                <CardHeader class="pb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 bg-primary/10 rounded-lg">
                                            <UserIcon class="h-4 w-4 text-primary" />
                                        </div>
                                        <CardTitle class="text-sm font-medium">Profile Card</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div class="flex flex-col items-center text-center py-4">
                                        <div class="h-20 w-20 rounded-full bg-primary/20 flex items-center justify-center mb-4 border-4 border-background shadow-sm">
                                            <span class="text-2xl font-bold text-primary">{{ user.username.substring(0, 2).toUpperCase() }}</span>
                                        </div>
                                        <h3 class="font-bold text-lg">{{ user.username }}</h3>
                                        <div v-if="(user.role?.value || user.role) === 'revendeur'" class="mt-1">
                                            <Badge class="bg-blue-600 hover:bg-blue-700 text-[10px] py-0 px-2 h-5 flex items-center gap-1">
                                                <Check class="w-2.5 h-2.5" />
                                                Verified Reseller
                                            </Badge>
                                        </div>
                                        <p v-else class="text-xs text-muted-foreground capitalize">{{ user.role }}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            <template v-if="stats">
                                <!-- Organizer Stats -->
                                <template v-if="stats.type === 'organizer'">
                                    <Card class="border-none shadow-sm">
                                        <CardHeader class="pb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="p-2 bg-blue-500/10 rounded-lg">
                                                    <Calendar class="h-4 w-4 text-blue-500" />
                                                </div>
                                                <CardTitle class="text-sm font-medium">Events</CardTitle>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-2xl font-bold">{{ stats.events_count ?? 0 }}</div>
                                            <p class="text-xs text-muted-foreground mt-1">Total events created</p>
                                        </CardContent>
                                    </Card>

                                    <Card class="border-none shadow-sm">
                                        <CardHeader class="pb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="p-2 bg-emerald-500/10 rounded-lg">
                                                    <DollarSign class="h-4 w-4 text-emerald-500" />
                                                </div>
                                                <CardTitle class="text-sm font-medium">Revenue</CardTitle>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-2xl font-bold">{{ stats.revenue?.toLocaleString('fr-FR', { style: 'currency', currency: 'TND' }) ?? '0,00 TND' }}</div>
                                            <p class="text-xs text-muted-foreground mt-1">Total ticket sales</p>
                                        </CardContent>
                                    </Card>
                                </template>

                                <!-- Participant Stats -->
                                <template v-else-if="stats.type === 'participant'">
                                    <Card class="border-none shadow-sm">
                                        <CardHeader class="pb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="p-2 bg-blue-500/10 rounded-lg">
                                                    <Trophy class="h-4 w-4 text-blue-500" />
                                                </div>
                                                <CardTitle class="text-sm font-medium">Participation</CardTitle>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-2xl font-bold">{{ stats.total_events ?? 0 }}</div>
                                            <p class="text-xs text-muted-foreground mt-1">Events joined</p>
                                        </CardContent>
                                    </Card>

                                    <Card class="border-none shadow-sm">
                                        <CardHeader class="pb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="p-2 bg-orange-500/10 rounded-lg">
                                                    <Trophy class="h-4 w-4 text-orange-500" />
                                                </div>
                                                <CardTitle class="text-sm font-medium">Interests</CardTitle>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-xl font-bold truncate">{{ stats.best_category ?? 'None' }}</div>
                                            <p class="text-xs text-muted-foreground mt-1">Most attended category</p>
                                        </CardContent>
                                    </Card>
                                </template>

                                <!-- Revendeur Stats -->
                                <template v-else-if="stats.type === 'revendeur'">
                                    <Card class="border-none shadow-sm">
                                        <CardHeader class="pb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="p-2 bg-blue-500/10 rounded-lg">
                                                    <Calendar class="h-4 w-4 text-blue-500" />
                                                </div>
                                                <CardTitle class="text-sm font-medium">Promoted Events</CardTitle>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-2xl font-bold">{{ stats.events_count ?? 0 }}</div>
                                            <p class="text-xs text-muted-foreground mt-1">Events you are promoting</p>
                                        </CardContent>
                                    </Card>

                                    <Card class="border-none shadow-sm">
                                        <CardHeader class="pb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="p-2 bg-emerald-500/10 rounded-lg">
                                                    <DollarSign class="h-4 w-4 text-emerald-500" />
                                                </div>
                                                <CardTitle class="text-sm font-medium">Earnings</CardTitle>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-2xl font-bold">{{ stats.revenue?.toLocaleString('fr-FR', { style: 'currency', currency: 'TND' }) ?? '0,00 TND' }}</div>
                                            <p class="text-xs text-muted-foreground mt-1">Total commissions earned</p>
                                        </CardContent>
                                    </Card>
                                </template>
                            </template>
                        </div>

                        <!-- Middle Column: Main Form Fields -->
                        <div class="lg:col-span-6 space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Personal Information</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-6">
                                    <div class="grid gap-2">
                                        <Label for="username">Username</Label>
                                        <Input
                                            id="username"
                                            class="mt-1 block w-full"
                                            name="username"
                                            :default-value="user.username"
                                            required
                                            autocomplete="username"
                                            placeholder="Username"
                                        />
                                        <InputError class="mt-2" :message="errors.username" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="email">Email address</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            class="mt-1 block w-full"
                                            name="email"
                                            :default-value="user.email"
                                            required
                                            autocomplete="email"
                                            placeholder="Email address"
                                        />
                                        <InputError class="mt-2" :message="errors.email" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="telephone">Phone Number</Label>
                                        <Input
                                            id="telephone"
                                            class="mt-1 block w-full"
                                            name="telephone"
                                            :default-value="user.telephone"
                                            placeholder="Phone number"
                                        />
                                        <InputError class="mt-2" :message="errors.telephone" />
                                    </div>

                                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                                        <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                            Your email address is unverified.
                                            <Link
                                                :href="send()"
                                                as="button"
                                                class="font-medium underline hover:no-underline"
                                            >
                                                Resend verification email.
                                            </Link>
                                        </p>

                                        <div
                                            v-if="status === 'verification-link-sent'"
                                            class="mt-2 text-sm font-medium text-green-600"
                                        >
                                            A new verification link has been sent.
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Bank Information Section (Organizers & Resellers) -->
                            <Card v-if="(user.role?.value || user.role) === 'organisateur' || (user.role?.value || user.role) === 'revendeur'" class="mt-6">
                                <CardHeader>
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 bg-blue-500/10 rounded-lg">
                                            <CreditCard class="h-4 w-4 text-blue-500" />
                                        </div>
                                        <CardTitle>Payouts & Bank Information</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div v-if="page.props.auth.user?.organisateur?.stripe_account_id" class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-emerald-100 dark:bg-emerald-800/50 rounded-full">
                                                <Check class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-emerald-900 dark:text-emerald-300">Stripe Account Connected</h4>
                                                <p class="text-xs text-emerald-700 dark:text-emerald-400 font-mono mt-1">
                                                    {{ user.organisateur?.stripe_account_id || user.revendeur?.stripe_account_id }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="space-y-4">
                                        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl">
                                            <h4 class="font-bold text-indigo-900 dark:text-indigo-300">Connect with Stripe</h4>
                                            <p class="text-sm text-indigo-700 dark:text-indigo-400 mt-1 mb-3">
                                                Spotlight uses Stripe to send your funds instantly and securely. You must connect your account to receive your payouts.
                                            </p>
                                            <Button @click.prevent="connectStripe" :disabled="connectingStripe" class="bg-indigo-600 hover:bg-indigo-700 text-white w-full sm:w-auto">
                                                {{ connectingStripe ? 'Connecting...' : 'Connect Stripe Account' }}
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <div class="flex items-center gap-4 mt-6">
                                <Button
                                    type="submit"
                                    :disabled="processing"
                                    class="w-full sm:w-auto"
                                >
                                    {{ processing ? 'Saving...' : 'Save Changes' }}
                                </Button>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p
                                        v-show="recentlySuccessful"
                                        class="text-sm text-emerald-600 font-medium"
                                    >
                                        Changes saved successfully.
                                    </p>
                                </Transition>
                            </div>
                        </div>


                        <!-- Right Column: Biography -->
                        <div class="lg:col-span-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Biography</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="grid gap-2">
                                        <Label for="about">About me</Label>
                                        <Textarea
                                            id="about"
                                            name="about"
                                            class="min-h-[200px]"
                                            :default-value="user.about"
                                            placeholder="Tell us about yourself..."
                                        />
                                        <InputError class="mt-2" :message="errors.about" />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </Form>

                <!-- Best Performing Events (Organizers Only) -->
                <div v-if="stats?.type === 'organizer' && bestEvents && bestEvents.length > 0" class="space-y-6 pt-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <Trophy class="h-5 w-5 text-primary" />
                            </div>
                            <h3 class="text-xl font-bold">Best Performing Events</h3>
                        </div>
                        <Link :href="`/organizer/${user.id}`" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 transition-colors">
                            View all my events <ChevronRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                        <Link 
                            v-for="event in bestEvents" 
                            :key="event.id" 
                            :href="`/events/${event.id}`"
                            class="group bg-card rounded-xl border border-border overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col"
                        >
                            <!-- Card Image -->
                            <div class="relative h-32 bg-muted overflow-hidden">
                                <img 
                                    v-if="event.medias?.[0]" 
                                    :src="event.medias[0].url" 
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                                />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <Calendar class="h-8 w-8 text-muted-foreground/30" />
                                </div>
                                <div class="absolute top-2 left-2">
                                    <span class="bg-background/90 backdrop-blur-sm text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider border border-border">
                                        {{ event.categorie === 'autre' && event.categorie_autre ? event.categorie_autre : event.categorie }}
                                    </span>
                                </div>
                            </div>

                            <!-- Card Content -->
                            <div class="p-3 flex-1 flex flex-col justify-between">
                                <div>
                                    <h4 class="font-bold text-sm line-clamp-1 group-hover:text-primary transition-colors">
                                        {{ event.titre }}
                                    </h4>
                                    <div class="mt-1 flex items-center gap-1.5 grayscale opacity-70">
                                        <Trophy class="h-3 w-3 text-primary" />
                                        <span class="text-[10px] font-medium uppercase tracking-tight">{{ event.reservations_count }} Reservations</span>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <div class="w-full py-1.5 rounded-lg bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wider text-center group-hover:bg-primary group-hover:text-primary-foreground transition-all duration-300">
                                        View Details
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
