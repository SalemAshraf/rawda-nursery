<script setup>
import { computed } from "vue";
const props = defineProps({ values: Array, label: String });
const max = computed(() => Math.max(1, ...props.values));
const points = computed(() =>
    props.values
        .map(
            (v, i) =>
                `${16 + (props.values.length > 1 ? (i / (props.values.length - 1)) * 568 : 284)},${144 - (v / max.value) * 120}`,
        )
        .join(" "),
);
</script>
<template>
    <svg
        class="w-full h-40"
        viewBox="0 0 600 160"
        role="img"
        :aria-label="label"
    >
        <path
            d="M16 24H584M16 84H584M16 144H584"
            stroke="#edf0eb"
            fill="none"
        />
        <polyline
            :points="points"
            fill="none"
            stroke="#5c9b79"
            stroke-width="2.5"
            stroke-linejoin="round"
        />
        <circle
            v-if="values.length === 1"
            cx="300"
            :cy="144 - (values[0] / max) * 120"
            r="4"
            fill="#5c9b79"
        />
        <text
            x="16"
            y="15"
            direction="ltr"
            text-anchor="start"
            fill="#8b998e"
            font-size="10"
        >
            {{ max }}
        </text>
    </svg>
</template>
