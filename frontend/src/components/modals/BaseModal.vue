<template>
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container" :style="{ width: width + '%' }" ref="target">
        <slot name="top"></slot>
        <div class="modal-header mt-4">
          <slot name="header"></slot>
        </div>
        <div class="modal-body mt-4">
          <slot name="content"></slot>
        </div>
        <div class="modal-footer">
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineModel } from 'vue';
defineModel('width');
</script>

<style>
.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
}
.modal-container {
  hight: auto;
  margin: 150px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
}
</style>