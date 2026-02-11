<template>
  <Navbar />
  <div class="justify-content-center">
    <div class="mt-4 md col-3 mx-auto">
      <form @submit.prevent="createOrder">
        <div class="md col-3 pt-3 d-inline">
          <label class="typo__label pb-4"><b>Select Products</b></label>
          <multiselect
              id="multiselect"
              v-model="form"
              :options="options"
              :multiple="true"
              :close-on-select="true"
              :clear-on-select="false"
              :preserve-search="true"
              placeholder="Select Products"
              label="name"
              track-by="name"
              :preselect-first="true"
              @update:modelValue="onSelected"
          >
            <template #selection="{ values, search, isOpen }">
              <span class="multiselect__single" v-if="values.length" v-show="!isOpen">{{ values.length }}</span>
            </template>
          </multiselect>
          <pre class="language-json"><code>selected: {{ form }}</code></pre>
        </div>
        <div class="md col-1 pt-3 d-inline">
          <div v-for="field in form" :key="field">
            <p class="pt-2"><b>quantity for: {{ field.name }}</b></p>
            <!-- field type number -->
            <div class="mb-2 col-6">
              <input type="number" v-model="field.quantity" id="" :min="1" :max="field.quantityInStock" />
              <span> quantity in stock: {{ field.quantityInStock }}</span>
            </div>
            <!-- checkbox -->
            <div class="form-check form-switch pt-2">
              <input class="form-check-input" type="checkbox" v-model="field.isPick" role="switch" id="flexSwitchCheckDefault">
              <label class="form-check-label" for="flexSwitchCheckDefault"><b>is pick: <span class="text-danger">{{ field.isPick }}</span></b></label>
            </div>
            <!-- textarea -->
            <div class="row pt-2">
              <label for="note" class="pb-2"><b>Note:</b></label>
              <textarea id="note" class="mx-lg-2" name="note" v-model="field.note" :rows="4" :cols="50" />
            </div>
          </div>
        </div>
        <div class="mt-4 pb-4 col-3 mx-auto text-center">
          <button type="submit" class="btn btn-primary">submit</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
useTitle('create orders');

import { ref, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/services/apiClient';
import Multiselect from 'vue-multiselect';
import Navbar from '@/components/navbar/Navbar';
import { useTitle } from '@/helpers/useTitle';
import { useOrdersQueries } from '@/composables/useOrdersQueries';

const apiDomain = inject('apiDomain');
const form = ref([]);
const options = ref([]);
const products = ref([]);
const router = useRouter();

const { queryCreateOrder } = useOrdersQueries()

const onSelected = (value) => {
  form.value = value.map((item) => ({
    ...item,
  }))
};

const unSelected = () => {
  form.value = [];
};

const fetchEntityData = async () => {
  try {
    await apiClient.get(apiDomain + '/api/stocks').then(response => {
      let entityData = JSON.parse(response.data.stocks).map((item) => ({
        name: item?.productName,
        quantityInStock: item?.quantityInStock,
        quantity: 1,
        isPick: item.product.orders?.isPick
      }));

      if (!entityData) {
        return
      }
      console.log('items options: ', entityData);
      options.value = entityData;
    });
  } catch (error) {
    console.warn(error);
  }
}

const createOrder = async () => {
  await queryCreateOrder(form)
}

onMounted(() => {
  fetchEntityData();
});
</script>
