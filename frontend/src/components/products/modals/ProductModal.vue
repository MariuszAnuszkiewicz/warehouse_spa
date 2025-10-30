<template>
  <BaseModal v-if="modal" :width="width">
    <template v-slot:top>
      <button @click="closeModal" type="button" class="close bg-danger border border-danger float-end">
        <span><font-awesome-icon :icon="['fas', 'x']" :class="['text-white']" /></span>
      </button>
    </template>
    <template v-slot:header>
      <div class="modal-header mt-4">
        <h5><strong class="header-text">Product</strong></h5>
      </div>
    </template>
    <template v-slot:content>
      <div class="modal-body mt-4">
        <p class="text-secondary" v-if="isLoading">Loading...</p>
        <div class="modal-body mt-4">
          <table class="table table-striped">
            <thead>
            <tr>
              <th class="text-center">id</th>
              <th class="text-center">Name</th>
              <th class="text-center">Ean</th>
              <th class="text-center">Location</th>
              <th class="text-center">Quantity</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="prod in product" :key="prod.id">
              <tr v-for="location in prod.locations" :key="location.id">
                <td class="text-center" scope="row">{{ prod.id }}</td>
                <td class="text-center">{{ prod.stock.productName }}</td>
                <td class="text-center">{{ prod.stock.ean13 }}</td>
                <td class="text-center">{{ location.name }}</td>
                <td class="text-center">{{ prod.stock.quantityInStock }}</td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { defineModel, defineEmits } from 'vue';
import BaseModal from '@/components/modals/BaseModal';

defineModel('modal');
defineModel('product');
defineModel('width');
defineModel('isLoading');

const emit = defineEmits(['update:isOpen']);

const closeModal = () => {
  emit('update:isOpen', false);
};
</script>

