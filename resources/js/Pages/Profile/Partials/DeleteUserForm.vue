<script setup>
import { useI18n } from "@/lib/i18n";
const { t } = useI18n();
import DangerButton from "@/Components/DangerButton.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: "",
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route("profile.destroy"), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ t("Delete Account") }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{
                    t(
                        "Deleting your login ends your access. Nursery records remain with the nursery. The last owner account cannot be deleted.",
                    )
                }}
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">{{
            t("Delete Account")
        }}</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ t("Are you sure you want to delete your account?") }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{
                        t(
                            "Enter your password to delete your login permanently. This does not delete the nursery or its child, billing and care records.",
                        )
                    }}
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        :value="t('Password')"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        :placeholder="t('Password')"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">{{
                        t("Cancel")
                    }}</SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                        >{{ t("Delete Account") }}</DangerButton
                    >
                </div>
            </div>
        </Modal>
    </section>
</template>
