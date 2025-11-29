<template>
  <Navbar />
  <div class="container-md bg-light mt-5 p-5">
    <div class="justify-content-center">
      <div class="card-body pb-3">
        <h5><strong class="header-text">List of Orders</strong></h5>
      </div>
      <p class="text-secondary" v-if="isLoading">Loading...</p>
      <table class="table table-hover">
        <thead>
        <tr>
          <th class="text-center">id</th>
          <th class="text-center">Product Id</th>
          <th class="text-center">Product Name</th>
          <th class="text-center">Is Pick</th>
          <th class="text-center">Create</th>
          <th class="text-center">Actions</th>
        </tr>
        </thead>
        <tbody>
        <template v-for="order in orders">
          <tr v-for="product in order.products" :key="order.id">
            <td class="text-center text-danger" scope="row"><b>{{ order.id }}</b></td>
            <td class="text-center" scope="row">{{ product.id }}</td>
            <td class="text-center">{{ product.stock.productName }}</td>
            <td class="text-center">{{ order.isPick }}</td>
            <td class="text-center">{{ formatDate(order.createdAt) }}</td>
            <td class="text-center">
              <span class="btn-info">
                <div @click.prevent="showModal($event)">
                  <a :href='`order/${order.id}`' class="btn btn-info">
                    <font-awesome-icon :icon="['fas', 'eye']" />
                  </a>
                </div>
              </span>
            </td>
          </tr>
        </template>
        </tbody>
      </table>
      <OrderModal :order="order" :modal="modal" :isLoading="isLoading" :width="width" @update:isOpen="closeModal">
        <template v-slot:header></template>
        <template v-slot:content></template>
        <template v-slot:footer></template>
      </OrderModal>
    </div>
  </div>
</template>

<script setup>
useTitle('orders');

import { ref, onMounted, watch, inject } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/services/apiClient';
import authService from '@/services/authService';
import Navbar from '@/components/navbar/Navbar';
import OrderModal from '@/components/orders/modals/OrderModal';
import formatDate from '@/helpers/formatDate';
import { useTitle } from '@/helpers/useTitle';

const apiDomain = inject('apiDomain');
const isLoading = ref(true);
const link = ref('');
const modal = ref(false);
const orders = ref([]);
const order = ref([]);
const width = ref('65');
const router = useRouter();

const showModal = (event) => {
  getLink(event);
  const $id = link.value.split('/')[1];
  fetchOrder($id);
  modal.value = true;
  isLoading.value = true;
}

const closeModal = (value) => {
  modal.value = value;
};

const getLink = (event) => {
  const href = event.target.closest('a').getAttribute('href');
  link.value = href;
}

const fetchOrders = async () => {
  try {
    await apiClient.get(apiDomain + '/api/orders').then(response => {
      orders.value = JSON.parse(response.data.orders)
    });
  } catch (error) {
    console.warn(error);
  } finally {
    isLoading.value = false;
  }
}

const fetchOrder = async (id) => {
  try {
    await apiClient.get(apiDomain + `/api/order/${id}`).then(response => {
      order.value = JSON.parse(response.data.order)
    });
  } catch (error) {
    console.warn(error);
  } finally {
    isLoading.value = false;
  }
}

watch(modal, (newValue, oldValue) => {
  console.log(`Modal Ref changed from ${oldValue} to ${newValue}`);
});

onMounted(() => {
  fetchOrders();
});
</script>
