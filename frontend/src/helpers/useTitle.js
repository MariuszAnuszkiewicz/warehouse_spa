import { onMounted } from 'vue';

export function useTitle(title) {
  onMounted(() => {
     document.title = title;
  });
}

