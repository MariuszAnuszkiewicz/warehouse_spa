<template>
  <baseModal v-if="modal" :width="width">
    <template v-slot:top>
      <button @click="closeWindow()" type="button" class="close bg-danger border border-danger float-end">
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
          <div v-if="order.note || order.note === ''">
            <div class="col-6 bg-warning-subtle mx-auto p-2 mb-2">
              <span><h6>Note: </h6>{{ order.note }}</span>
            </div>
            <div class="d-flex justify-content-center">
              <div class="col-2 text-center bg-white">
                <span><p class="text-secondary text-small">Edit Note</p><b class="font-weight-bold">off/on</b></span>
                <label class="switch m-2">
                  <input
                      type="checkbox"
                      :checked="editNote"
                      @change="toggleNote"
                  />
                 <span class="slider"></span>
                </label>
              </div>
            </div>
            <div v-if="editNote" class="d-flex justify-content-center bg-light p-2">
              <form id="updateNote" @submit.prevent="updateOrderNote(order.id)">
                <div class="row align-items-center">
                  <label class="col-auto col-form-label"><b>Note:</b></label>
                  <textarea class="mx-lg-2" name="note" v-model="order.note" :rows="3" :cols="50" />
                </div>
                <div class="d-flex justify-content-center">
                  <div class="mt-3 mb-3">
                    <button
                        type="submit"
                        form="updateNote"
                        class="btn btn-primary">
                      Update Note
                    </button>
                  </div>
                </div>
              </form>
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
              <th class="col-md-2 text-center bg-info-subtle">Actions</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="(product, idx) in order.products" :key="product.id">
              <tr>
                <td class="text-center align-middle" scope="row">{{ product.id }}</td>
                <td class="text-center align-middle">{{ product.stock.productName }}</td>
                <td class="text-center align-middle">{{ product.stock.ean13 }}</td>
                <td class="text-center align-middle">{{ order.quantityInOrder }}</td>
                <td class="text-center align-middle">
                  {{ product?.locations.at(0)?.name }}
                </td>
                <td class="text-center align-middle">{{ order.isPick }}</td>
                <td class="text-center align-middle">{{ formatDate(order.createdAt) }}</td>
                <td>
                  <div class="btn-group">
                    <div @click.prevent="deleteProductFromTheOrder(product.id)">
                      <form method="POST" :action="`order/del/prod/${product.id}`">
                        <input type="hidden" name="_method" value="DELETE" />
                        <input type="hidden" name="order_id" :value="order.id" />

                        <button type="submit" class="btn btn-danger mx-1 my-1">
                          <font-awesome-icon :icon="['fas', 'trash-arrow-up']" />
                        </button>
                      </form>
                    </div>
                    <label class="switch m-2">
                      <input
                          type="checkbox"
                          :checked="isProductSelected(product.id)"
                          @click.stop="toggleSwitch(product, order)"
                      />
                      <span class="slider"></span>
                    </label>
                  </div>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>
      </div>
      <template v-for="(product, index) in selectedProduct">
        <div v-if="product">
          <form id="updateForm" @submit.prevent="updateOrder">
            <div class="mt-2 d-flex align-items-center gap-3 w-100 bg-light p-2 py-1">
              <label class="fw-bold mb-0 flex-shrink-0"><p class="my-1 text-bold">Product Name</p></label>
              <select
                  v-model="selectedProduct[index].productName"
              >
                <option
                    v-for="stock in stocks"
                    :key="stock.id"
                    :value="stock?.productName">
                  {{ stock?.productName }}
                </option>
              </select>
              <label class="fw-bold mb-0 flex-shrink-0"><p class="my-1 text-bold">Quantity</p></label>
              <input class="form-control form-control-sm w-auto"
                     type="number"
                     min="1"
                     :max="order.products[index].stock.quantityInStock"
                     v-model="selectedQuantityInOrder[index].quantityInOrder"
              />
              <label class="fw-bold mb-0 flex-shrink-0"><p class="my-1 text-bold">Location</p></label>
              <select
                  v-model="selectedLocation[index].locationName"
              >
                <option
                    v-for="location in locations"
                    :key="location.id"
                    :value="location?.name">
                  {{ location?.name }}
                </option>
              </select>
              <label class="fw-bold mb-0 flex-shrink-0"><p class="my-1 text-bold">Is Pick</p></label>
              <select
                  v-model="selectedIsPick[index].isPick"
              >
                <option
                    v-for="isPick in [true, false]"
                    :value="isPick"
                >
                  {{ isPick }}
                </option>
              </select>
            </div>
          </form>
        </div>
      </template>
      <div v-if="enabledEdit" class="d-flex align-items-center gap-3 w-100 bg-light pb-2 px-2">
        <button
            type="submit"
            form="updateForm"
            class="btn btn-primary">
          Update
        </button>
      </div>
    </template>
  </baseModal>
</template>

<script setup>
import { defineModel, defineEmits, inject, ref } from 'vue';
import BaseModal from '@/components/modals/BaseModal';
import formatDate from '@/helpers/formatDate';
import apiClient from '@/services/apiClient';
import { useOrdersQueries } from '@/composables/useOrdersQueries';

defineModel('isLoading');
defineModel('locations');
defineModel('modal');
defineModel('order');
defineModel('stocks');
defineModel('width');

let { order, stocks, locations } = defineProps({
  order: Object,
  stocks: Object,
  locations: Object
});

const emit = defineEmits(['update:isOpen', 'update:order']);
const apiDomain = inject('apiDomain');

const enabledEdit = ref(false);
const editNote = ref(false);
const selectedLocation = ref([]);
const selectedProduct = ref([]);
const selectedOrder = ref([]);
const selectedIsPick = ref([]);
const selectedQuantityInOrder = ref([]);
const selectedItems = ref([]);

let dataForm = ref({});

const { queryUpdateOrder, queryUpdateNoteField } = useOrdersQueries()

const emitCloseModal = () => {
  emit('update:isOpen', false);
  enabledEdit.value = false;
}

const closeWindow = () => {
  clearSelectData();
  emitCloseModal();
}

const clearSelectData = () => {
  selectedIsPick.value = [];
  selectedLocation.value = [];
  selectedOrder.value = [];
  selectedProduct.value = [];
  selectedQuantityInOrder.value = [];
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

const updateOrder = async () => {
  dataForm.value = {
    product: selectedProduct.value,
    location: selectedLocation.value,
    order: [{
      orderId: selectedOrder.value.at(0)?.orderId,
      isPick: selectedIsPick.value.at(0)?.isPick,
      quantityInOrder: selectedQuantityInOrder.value.at(0)?.quantityInOrder,
    }]
  }

  const response = await queryUpdateOrder(dataForm)

  if (response.data) {
    clearSelectData();
    enabledEdit.value = false;
  }
}

const updateOrderNote = async (orderId) => {
  dataForm.value = {
    order: [{
      orderId: orderId,
      note: order.note
    }]
  }

  const response = await queryUpdateNoteField(dataForm)

  if (response.data) {
    editNote.value = false;
  }
}

const toggleSwitch = (product, order) => {

  const index = selectedProduct.value.findIndex(p => p.oldProductId === product.id);

  if (index === -1) {
    selectedOrder.value.push({ orderId: order.id });

    selectedProduct.value.push({
      oldProductId: product.id,
      productName: product.stock.productName
    });

    selectedLocation.value.push({
      oldProductId: product.id,
      locationName: product.locations?.at(0)?.name || ''
    });

    selectedIsPick.value.push({ isPick: order.isPick });

    selectedQuantityInOrder.value.push({
      quantityInOrder: order.quantityInOrder
    });
  } else {
    selectedOrder.value.splice(index, 1);
    selectedProduct.value.splice(index, 1);
    selectedLocation.value.splice(index, 1);
    selectedIsPick.value.splice(index, 1);
    selectedQuantityInOrder.value.splice(index, 1);
  }
  editMode();
};

const isProductSelected = (productId) => {
  return selectedProduct.value.some(p => p.oldProductId === productId);
};

const toggleNote = (event) => {
  editNote.value = event.target.checked;
}

const editMode = () => {
  enabledEdit.value = selectedProduct.value.length > 0;
};

</script>

<style scoped>
.switch {
  position: relative;
  display: inline-block;
  width: 52px;
  height: 28px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background-color: #ccc;
  transition: 0.3s;
  border-radius: 34px;
}

.slider::before {
  position: absolute;
  content: "";
  height: 22px;
  width: 22px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #42b883;
}

input:checked + .slider::before {
  transform: translateX(24px);
}
</style>