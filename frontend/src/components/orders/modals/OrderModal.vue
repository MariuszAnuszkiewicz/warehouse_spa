<template>
  <baseModal v-if="modal" :width="width">
    <template v-slot:top>
      <button @click="emitCloseModal" type="button" class="close bg-danger border border-danger float-end">
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
          <div v-if="order.note">
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
              <th class="text-center bg-info-subtle">Actions</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="product in order.products" :key="product.id">
              <tr>
                <td class="text-center align-middle" scope="row">{{ product.id }}</td>
                <td class="text-center align-middle">{{ product.stock.productName }}</td>
                <td class="text-center align-middle">{{ product.stock.ean13 }}</td>
                <td class="text-center align-middle">{{ order.quantityInOrder }}</td>
                <td class="text-center align-middle" v-if="product?.locations.map(item => item)?.name !== ''">
                  {{ product?.locations.map(item => item)?.name ?? 'empty location' }}
                </td>
                <td class="text-center align-middle">{{ order.isPick }}</td>
                <td class="text-center align-middle">{{ formatDate(order.createdAt) }}</td>
                <td>
                  <div @click.prevent="deleteProductFromTheOrder(product.id)">
                    <form method="POST" :action="`order/del/prod/${product.id}`">
                      <input type="hidden" name="_method" value="DELETE" />
                      <input type="hidden" name="order_id" :value="order.id" />

                      <button type="submit" class="btn btn-danger mx-1">
                        <font-awesome-icon :icon="['fas', 'trash-arrow-up']" />
                      </button>
                    </form>
                  </div>
                </td>
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
import { defineModel, defineEmits, inject } from 'vue';
import BaseModal from '@/components/modals/BaseModal';
import formatDate from '@/helpers/formatDate';
import apiClient from '@/services/apiClient';

defineModel('modal');
defineModel('order');
defineModel('width');
defineModel('isLoading');

let { order } = defineProps({
  order: Object
});

const emit = defineEmits(['update:isOpen', 'update:order']);
const apiDomain = inject('apiDomain');

const emitCloseModal = () => {
  emit('update:isOpen', false);
}

const emitUpdateOrder = (newOrder) => {
  emit('update:order', newOrder)
}

const deleteProductFromTheOrder = async (id) => {
  try {
    await apiClient.delete(`${apiDomain}/api/order/del/product/${id}`, {
      data: { order_id: order.id }
    }).then(() => {

      const newOrder = {
        ...order,
        products: order.products.filter(p => p.id !== id)
      }

      emitUpdateOrder(newOrder)
    });

  } catch (error) {
    console.warn(error);
  }
}

</script>