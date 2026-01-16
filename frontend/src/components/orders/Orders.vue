<template>
  <Navbar />
  <div class="container-md bg-light mt-5 p-5">
    <div class="justify-content-center">
      <div class="card-body pb-3">
        <h5><strong class="header-text">List of Orders</strong></h5>
      </div>
      <p class="text-secondary" v-if="isLoading">Loading...</p>

      <div class="d-flex justify-content-center">
        <button
            @click="removeSelected"
            class="btn btn-danger m-2"
        > Delete ({{ selectedIds.length }})
          <font-awesome-icon :icon="['fas', 'trash-arrow-up']" />
        </button>
      </div>
      <table class="table table-hover">
        <thead>
          <tr>
            <th class="text-center">id</th>
            <th class="text-center">Product Id</th>
            <th class="text-center">Product Name</th>
            <th class="text-center">Is Pick</th>
            <th class="text-center">Create</th>
            <th class="text-center bg-info-subtle">Actions</th>
          </tr>
        </thead>
        <tbody>
        <template v-for="order in orders" :key="order.id">
          <tr v-for="product in order.products" :key="product.id">
            <td class="text-center align-middle text-danger" scope="row"><b>{{ order.id }}</b></td>
            <td class="text-center align-middle" scope="row">{{ product.id }}</td>
            <td class="text-center align-middle">{{ product.stock.productName }}</td>
            <td class="text-center align-middle">{{ order.isPick }}</td>
            <td class="text-center align-middle">{{ formatDate(order.createdAt) }}</td>
            <td class="text-center align-middle">
              <div class="btn-group">
                <div @click.prevent="showModal($event)">
                  <a :href='`order/${order.id}`' class="btn btn-info mx-2">
                    <font-awesome-icon :icon="['fas', 'eye']" />
                  </a>
                </div>
                <div class="my-2 mx-1">
                  <input
                     type="checkbox"
                     v-model="selectedIds"
                     :value="order.id"
                  />
                </div>
              </div>
            </td>
          </tr>
        </template>
        </tbody>
      </table>
      <OrderModal
          :order="order"
          :stocks="stocks"
          :locations="locations"
          :modal="modal"
          :isLoading="isLoading"
          :width="width"
          @update:isOpen="closeModal"
          @update:order="refreshOrder"
      >
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
const locations = ref([]);
const orders = ref([]);
const order = ref([]);
const selectedIds = ref([]);
const stocks = ref([]);
const width = ref('65');
const router = useRouter();

const showModal = (event) => {
  getLink(event);
  const $id = link.value.split('/')[1];
  fetchOrder(+$id);
  modal.value = true;
  isLoading.value = true;
}

const closeModal = (value) => {
  modal.value = value;
  fetchOrders();
};

const refreshOrder = (newOrder) => {
  order.value = newOrder
}

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

const fetchStocks = async () => {
  try {
    await apiClient.get(apiDomain + '/api/stocks').then(response => {
      stocks.value = JSON.parse(response.data.stocks)
      console.log('Stocks: ', stocks.value);
    });
  } catch (error) {
    console.warn(error);
  } finally {
    isLoading.value = false;
  }
}

const fetchLocations = async () => {
  try {
    await apiClient.get(apiDomain + '/api/locations').then(response => {
      locations.value = JSON.parse(response.data.locations)
      console.log('Locations: ', locations.value);
    });
  } catch (error) {
    console.warn(error);
  } finally {
    isLoading.value = false;
  }
}

const getSelectedIds = (id) => {
  return selectedIds.value.find(ids => ids === id);
}

const removeSelected = async () => {
  await apiClient.delete(`${apiDomain}/api/order/del`, {
    data: { order_ids: selectedIds.value }
  }).then(() => {
     orders.value = orders.value.filter(o => o.id !== getSelectedIds(o.id))
     selectedIds.value.length = 0;
  });
};

watch(modal, (newValue, oldValue) => {
  console.log(`Modal Ref changed from ${oldValue} to ${newValue}`);
});

onMounted(() => {
  fetchOrders();
  fetchStocks();
  fetchLocations();
});
</script>
