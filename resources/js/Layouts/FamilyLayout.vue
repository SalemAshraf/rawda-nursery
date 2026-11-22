<script setup>
import { Link, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useFamilyCopy } from '@/lib/family';
defineProps({ signedIn: {type:Boolean,default:true} });
const {l,locale,toggleLocale}=useFamilyCopy();
</script>
<template><div class="family-shell"><header class="family-top"><Link href="/family" class="brand"><ApplicationLogo/></Link><div class="flex items-center gap-2"><button class="utility-button" @click="toggleLocale">{{locale==='ar'?'English':'العربية'}}</button><button v-if="signedIn" class="utility-button" @click="router.post('/family/logout')">{{l('خروج','Log out')}}</button></div></header><main class="family-main"><slot/></main><footer class="family-footer">{{l('تواصل مع الحضانة مباشرة في الحالات العاجلة. الرسائل ليست خدمة طوارئ.','Contact the nursery directly for urgent matters. Messages are not an emergency service.')}}</footer></div></template>
