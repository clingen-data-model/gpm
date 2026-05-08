import { ref } from "vue";
import { api } from "@/http";

export default function useOpenAlex() {
  const loading = ref(false);

  async function lookupIdentifier(raw) {
    loading.value = true;

    try {
      const { data } = await api.post('/api/publications/lookup', {
        query: raw,
      });

      return data;
    } catch (error) {
      const message =
        error?.response?.data?.errors?.query?.[0] ||
        error?.response?.data?.message ||
        'Lookup failed.';

      throw new Error(message);
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    lookupIdentifier,
  };
}