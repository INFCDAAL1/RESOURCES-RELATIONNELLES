<script setup lang="ts">
export type TextThemeVariant = 'primary' | 'secondary' | 'tertiary';
const props = defineProps({
  variant: { type: TextThemeVariant, default: 'primary' },
})

const textThemeClasses = {
  primary: 'text-black dark:text-white',
  secondary: 'text-gray-700 dark:text-gray-400',
  tertiary: 'text-gray-500 dark:text-gray-600'
}
const textThemeClass = computed(() => {
  return textThemeClasses[props.variant]
})
</script>

<template>
   <div :class="textThemeClass">
    <slot></slot>
   </div>
</template>
