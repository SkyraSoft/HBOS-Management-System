<template>
  <div class="empty-layout">
    <router-view />
  </div>
</template>

<style scoped>
.empty-layout {
  width: 100%;
  min-height: 100vh;
  /* No specific backgrounds or constraints, allow the child view to control the layout */
}
</style>
