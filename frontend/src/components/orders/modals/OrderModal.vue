<template>
  <baseModal v-if="modal" :width="width">
    <template v-slot:top>
      <button @click="closeModal" type="button" class="close bg-danger border border-danger float-end">
        <span><font-awesome-icon :icon="['fas', 'x']" :class="['text-white']" /></span>
      </button>
    </template>
    <template v-slot:header>
      <div class="modal-header mt-4">
        <h5><strong class="header-text">Order /</strong></h5>
        <span class="text-danger mt-1 p-2 d-block">
           <h6>Id: {{ order.id }}</h6>
        </span>
      </div>
    </template>
    <template v-slot:content>
      <div class="modal-body mt-4">
        <div class="modal-body mt-4">
          <p class="text-secondary" v-if="isLoading">Loading...</p>
          <div v-if="order.note !== ''">
            <div class="col-6 bg-warning-subtle mx-auto p-2 mb-2">
              <span><h6>Note: </h6>{{ order.note }}</span>
            </div>
          </div>
          <table class="table table-striped">
            <thead>
            <tr>
              <th class="text-center">Product Id</th>
              <th class="text-center">Product Name</th>
              <th class="text-center">Code Ean</th>
              <th class="text-center">Quantity</th>
              <th class="text-center">Location</th>
              <th class="text-center">Is Pick</th>
              <th class="text-center">Create</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="product in order.products" :key="product.id">
              <tr>
                <td class="text-center" scope="row">{{ product.id }}</td>
                <td class="text-center">{{ product.stock.productName }}</td>
                <td class="text-center">{{ product.stock.ean13 }}</td>
                <td class="text-center">{{ order.quantityInOrder }}</td>
                <td class="text-center" v-if="product?.locations.map(item => item)?.name !== ''">
                  {{ product?.locations.map(item => item)?.name ?? 'empty location' }}
                </td>
                <td class="text-center">{{ order.isPick }}</td>
                <td class="text-center">{{ formatDate(order.createdAt) }}</td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </baseModal>
</template>

<script setup>
import { defineModel, defineEmits } from 'vue';
import BaseModal from '@/components/modals/BaseModal';
import formatDate from '@/helpers/formatDate';

defineModel('modal');
defineModel('order');
defineModel('width');
defineModel('isLoading');

const emit = defineEmits(['update:isOpen']);

const closeModal = () => {
  emit('update:isOpen', false);
};
</script>