<template>
  <Navbar />
  <div class="container-md bg-light mt-5 p-5">
    <div class="justify-content-center">
      <div class="card-body pb-3">
        <h5><strong class="header-text">List of Products</strong></h5>
      </div>
      <p class="text-secondary" v-if="isLoading">Loading...</p>
      <table class="table table-hover">
        <thead>
        <tr>
          <th class="text-center">id</th>
          <th class="text-center">Name</th>
          <th class="text-center">Location</th>
          <th class="text-center">Quantity</th>
          <th class="text-center">Actions</th>
        </tr>
        </thead>
        <tbody>
        <template v-for="product in products">
          <tr>
            <td class="text-center text-danger" scope="row"><b>{{ product.id }}</b></td>
            <td class="text-center">{{ product.stock.productName }}</td>
            <td class="text-center" v-for="location in product.locations">{{ location.name }}</td>
            <td class="text-center">{{ product.stock.quantityInStock }}</td>
            <td class="text-center">
                <span class="btn-info">
                  <div @click.prevent="showModal($event)">
                    <a :href='`product/${product.id}`' class="btn btn-info">
                      <font-awesome-icon :icon="['fas', 'eye']" />
                    </a>
                  </div>
                </span>
            </td>
          </tr>
        </template>
        </tbody>
      </table>
      <productModal :product="product" :modal="modal" :width="width" :isLoading="isLoading" @update:isOpen="closeModal">
        <template v-slot:header></template>
        <template v-slot:content></template>
        <template v-slot:footer></template>
      </productModal>
    </div>
  </div>
</template>

<script setup>
useTitle('products');

import { ref, onMounted, watch, inject } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/services/apiClient';
import Navbar from '@/components/navbar/Navbar';
import ProductModal from '@/components/products/modals/ProductModal';
import { useTitle } from '@/helpers/useTitle';

const apiDomain = inject('apiDomain');
const isLoading = ref(true);
const link = ref('');
const modal = ref(false);
const products = ref([]);
const product = ref([]);
const width = ref('40');
const router = useRouter();

const showModal = (event) => {
  getLink(event);
  const $id = link.value.split('/')[1];
  fetchProduct($id);
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

const fetchProducts = async () => {
  try {
    await apiClient.get(apiDomain + '/api/products').then(response => {
      products.value = JSON.parse(response.data.products)
    });
  } catch (error) {
    console.warn(error);
  } finally {
    isLoading.value = false;
  }
}

const fetchProduct = async (id) => {
  try {
    await apiClient.get(apiDomain + `/api/product/${id}`).then(response => {
      product.value = JSON.parse(response.data.product);
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
  fetchProducts();
});

</script>
