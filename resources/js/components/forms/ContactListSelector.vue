<script setup>
import { computed, ref } from 'vue';
import SearchSelect from '@/components/forms/SearchSelect.vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  members: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
  label: { type: String, default: 'Liaisons' },
  allowManual: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
const selectorKey = ref(0);
const manual = ref(false);
const name = ref('');
const email = ref('');
const error = ref('');
const sameContact = (a, b) => (a.member_id && Number(a.member_id) === Number(b.member_id))
  || (a.email && b.email && a.email.trim().toLowerCase() === b.email.trim().toLowerCase());
const available = computed(() => props.members.filter(member => !props.modelValue.some(contact => sameContact(contact, member))));
const search = text => available.value.filter(member => `${member.name} ${member.email} ${member.role || ''}`.toLowerCase().includes((text || '').toLowerCase()));
function add(contact) {
  if (props.disabled || !contact) return;
  if (props.modelValue.some(existing => sameContact(existing, contact))) {
    error.value = 'This contact is already selected.';
    return;
  }
  error.value = '';
  emit('update:modelValue', [...props.modelValue, contact]);
}
function select(selections) {
  const member = selections.at(-1);
  if (!props.disabled && member) add({ member_id: member.member_id, person_id: member.person_id, name: member.name, email: member.email, role: member.role });
  selectorKey.value++; // Keep the search control empty; selected contacts live in the table.
}
function addManual() {
  if (props.disabled || !props.allowManual) return;
  if (!name.value.trim() || !/^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/.test(email.value.trim())) {
    error.value = 'Enter a name and valid email address.';
    return;
  }
  add({ member_id: null, person_id: null, name: name.value.trim(), email: email.value.trim(), role: 'Manual contact' });
  if (!error.value) { name.value = ''; email.value = ''; manual.value = false; }
}
function remove(index) {
  if (!props.disabled) emit('update:modelValue', props.modelValue.filter((_, i) => i !== index));
}
</script>

<template>
  <div class="space-y-2">
    <p class="font-medium">
      {{ label }}
    </p>
    <template v-if="!disabled">
      <SearchSelect
        :key="selectorKey" :model-value="[]" :options="available" :search-function="search"
        multiple show-options-on-focus :disabled="disabled" :placeholder="`Search ${label.toLowerCase()} by name or email`"
        @update:model-value="select"
      >
        <template #option="{ option }">
          <div class="font-medium">
            {{ option.name }}<span v-if="option.role"> — {{ option.role }}</span>
          </div>
          <div class="text-xs text-gray-600">
            {{ option.email }}
          </div>
        </template>
      </SearchSelect>
      <button v-if="allowManual" type="button" class="btn btn-xs" @click="manual = !manual">
        {{ manual ? 'Cancel manual contact' : 'Add manual contact' }}
      </button>
      <div v-if="allowManual && manual" class="flex flex-wrap items-end gap-2">
        <label class="flex-1">Name<input v-model="name" class="border rounded p-2 w-full" aria-label="Manual contact name"></label>
        <label class="flex-1">Email<input v-model="email" type="email" class="border rounded p-2 w-full" aria-label="Manual contact email"></label>
        <button type="button" class="btn btn-xs" @click="addManual">
          Add contact
        </button>
      </div>
      <p v-if="error" role="alert" class="text-red-700">
        {{ error }}
      </p>
    </template>
    <p v-if="!allowManual && modelValue.some(contact => !contact.member_id)" class="text-sm text-amber-800">
      Previously entered manual contacts are preserved. Replace them with GPM member selections before submitting.
    </p>
    <div v-if="modelValue.length" class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead>
          <tr class="border-b">
            <th class="p-2">
              Name
            </th><th class="p-2">
              Role
            </th><th class="p-2">
              Email
            </th><th v-if="!disabled" class="p-2">
              Action
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(contact, index) in modelValue" :key="contact.member_id || `${contact.email}-${index}`" class="border-b">
            <td class="p-2">
              {{ contact.name }}
            </td><td class="p-2">
              {{ contact.role || (contact.member_id ? 'Member' : 'Manual contact') }}
            </td><td class="p-2">
              {{ contact.email }}
            </td>
            <td v-if="!disabled" class="p-2">
              <button type="button" class="btn btn-xs" :aria-label="`Remove ${contact.name}`" @click="remove(index)">
                Remove
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="text-sm text-gray-500">
      No contacts selected.
    </p>
  </div>
</template>
