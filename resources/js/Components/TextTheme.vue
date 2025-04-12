<script setup lang="ts">
import { computed } from 'vue';

export type TextThemeVariant = 'primary' | 'secondary' | 'tertiary';

const props = defineProps<{
  variant?: TextThemeVariant; // Utilisation du type TextThemeVariant
}>();

const textThemeClasses: Record<TextThemeVariant, string> = {
  primary: 'text-black dark:text-white',
  secondary: 'text-gray-700 dark:text-gray-400',
  tertiary: 'text-gray-500 dark:text-gray-600'
};

// Utilisation d'une valeur par défaut pour éviter les erreurs de type
const textThemeClass = computed(() => {
  return textThemeClasses[props.variant || 'primary' as TextThemeVariant]; // Cast pour éviter l'erreur de type
});
</script>

<template>
   <div :class="textThemeClass">
    <slot></slot>
   </div>
</template>
