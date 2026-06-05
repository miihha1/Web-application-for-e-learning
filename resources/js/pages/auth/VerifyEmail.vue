<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Overenie e-mailu"
        description="Overte svoju e-mailovú adresu kliknutím na odkaz, ktorý sme vám práve poslali."
    >
        <Head title="Overenie e-mailu" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            Nový overovací odkaz bol odoslaný na e-mailovú adresu, ktorú ste
            zadali pri registrácii.
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <Spinner v-if="processing" />
                Znovu odoslať overovací e-mail
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                Odhlásiť sa
            </TextLink>
        </Form>
    </AuthLayout>
</template>
