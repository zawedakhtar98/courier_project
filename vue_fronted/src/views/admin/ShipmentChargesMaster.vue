<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from "vue-toastification";
const toast = useToast();
const isEditing = ref(false);
const showPartnerModal = ref(false);
const filterStatus = ref('All')
const openAddModal = () => {
  isEditing.value = false;
  showPartnerModal.value = true;
}
</script>

<template>
  <div class="container-fluid p-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary-subtle text-primary px-2 py-1 rounded-pill small fw-semibold">Settings</span>
          <i class="bi bi-chevron-right text-muted small"></i>
          <span class="text-secondary small fw-medium">Shipment Charges Master</span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add New</span>
        </button>
      </div>
    </div>

    <!-- Filters & Table Section -->
    <div class="custom-card p-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <!-- Search -->
        <div class="input-group" style="max-width: 380px;">
          <!--<span class="input-group-text bg-light border-end-0">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input v-model="searchQuery" type="text" class="form-control bg-light border-start-0"
            placeholder="Search..." /> -->
        </div>

        <!-- Status Filter Pills -->
        <div class="d-flex align-items-end gap-2">
          <span class="text-muted small fw-semibold me-1 d-none d-sm-inline">Status:</span>
          <button v-for="status in ['All', 'Active', 'Inactive']" :key="status" type="button"
            class="btn btn-sm rounded-pill px-3"
            :class="filterStatus === status ? 'btn-primary' : 'btn-light text-secondary'"
            @click="filterStatus = status">
            {{ status }}
          </button>
        </div>
      </div>

      <!-- Partner Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="border-0 rounded-start">Name</th>
              <th class="border-0">Code</th>
              <th class="border-0">Calculation Method</th>
              <th class="border-0">Calculation Based On</th>
              <th class="border-0">Description</th>
              <th class="border-0">Status</th>
              <th class="border-0 text-end rounded-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- <template v-if="isLoading">
              <tr v-for="i in 5" :key="'skel-' + i">
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-8 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-6 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-4 rounded-pill bg-secondary bg-opacity-25"></span></div>
                </td>
                <td class="text-end">
                  <div class="placeholder-glow"><span
                      class="placeholder col-3 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
              </tr>
            </template> -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Add / Edit Partner -->
    <div v-if="showPartnerModal" class="modal-backdrop fade show"></div>
    <div v-if="showPartnerModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow-md">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold text-dark">
              <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
              {{ isEditing ? 'Edit' : 'Add New' }}
            </h5>
            <button type="button" class="btn-close" @click="showPartnerModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" placeholder="Enter name" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Code<span class="text-danger">*</span></label>
                <input type="text" class="form-control" placeholder="Enter code" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Calculation Method<span class="text-danger">*</span></label>
                <select name="" id="" class="form-select">
                  <option value="">Select Method</option>
                  <option value="flat">Flat</option>
                  <option value="per_kg">Per KG</option>
                  <option value="fixed">Fixed</option>
                  <option value="percentage">Percentage</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Calculation Based On<span
                    class="text-danger">*</span></label>
                <select name="" id="" class="form-select">
                  <option value="">Select Method</option>
                  <option value="weight">Weight</option>
                  <option value="freight">Freight</option>
                  <option value="shipment_value">Shipment Value</option>
                  <option value="subtotal">Subtotal</option>
                </select>
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Description<span class="text-danger">*</span></label>
                <textarea class="form-control" placeholder="Enter description"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showPartnerModal = false">
              Cancel
            </button>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm" @click="savePartner">
              <i class="bi bi-check2 me-1"></i>
              {{ isEditing ? 'Update' : 'Create' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Country Mapping -->
    <div v-if="showCountryMappingModal && selectedPartner" class="modal-backdrop fade show"></div>
    <div v-if="showCountryMappingModal && selectedPartner" class="modal fade show d-block" tabindex="-1" role="dialog"
      aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom px-4 py-3">
            <div>
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-globe me-2 text-primary"></i> Map Countries to {{ selectedservicePartnersList.name }}
              </h5>
              <span class="text-muted small">Partner Code: <strong class="text-primary">{{
                selectedservicePartnersList.code
                  }}</strong>
                &bull; Currently Mapped: {{ selectedservicePartnersList.countries.length }} countries</span>
            </div>
            <button type="button" class="btn-close" @click="showCountryMappingModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="mb-3">
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input v-model="countrySearch" type="text" class="form-control bg-light border-start-0"
                  placeholder="Filter available countries..." />
              </div>
            </div>

            <div class="p-3 bg-light rounded-3 border" style="max-height: 320px; overflow-y: auto;">
              <div class="row g-2">
                <div v-for="country in filteredAvailableCountries" :key="country" class="col-6 col-md-4">
                  <div
                    class="d-flex align-items-center gap-2 p-2 rounded-3 border bg-white cursor-pointer transition-all"
                    :class="{ 'border-primary bg-primary-subtle text-primary fw-semibold': selectedservicePartnersList.countries.includes(country) }"
                    @click="toggleCountryForSelectedPartner(country)">
                    <input type="checkbox" class="form-check-input mt-0"
                      :checked="selectedservicePartnersList.countries.includes(country)" />
                    <span class="small text-truncate">{{ country }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
            <span class="small text-muted">{{ selectedservicePartnersList.countries.length }} countries selected</span>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm"
              @click="showCountryMappingModal = false">
              Done
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.transition-all {
  transition: all 0.2s ease;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
