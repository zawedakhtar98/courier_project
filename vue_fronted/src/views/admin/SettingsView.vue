<script setup>
import { ref } from 'vue'

const savedAlert = ref(false)

const settings = ref({
  companyName: 'ExpressIt Courier & Freight Ltd',
  supportEmail: 'ops@expressit.com',
  dispatchPhone: '+1 (800) 555-EXPR',
  hubAddress: '100 Logistics Way, Distribution Center 4, Chicago, IL',
  autoAssignDrivers: true,
  requireSignature: true,
  sendSmsUpdates: true,
  defaultCurrency: 'USD ($)',
})

const handleSave = () => {
  savedAlert.value = true
  setTimeout(() => {
    savedAlert.value = false
  }, 3000)
}
</script>

<template>
  <div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h4 class="fw-bold mb-1 text-dark">System & Hub Settings</h4>
        <p class="text-muted mb-0 small">Configure dispatch operational rules, branding, and customer notifications.</p>
      </div>
      <button class="btn btn-primary btn-sm rounded-3 px-4 py-2 shadow-sm" @click="handleSave">
        <i class="bi bi-check2 me-1"></i> Save Changes
      </button>
    </div>

    <div v-if="savedAlert" class="alert alert-success d-flex align-items-center mb-4 shadow-sm" role="alert">
      <i class="bi bi-check-circle-fill me-2 fs-5"></i>
      <div>Hub preferences and dispatch rules updated successfully.</div>
    </div>

    <div class="row g-4">
      <!-- Left Column: Company Profile -->
      <div class="col-12 col-lg-7">
        <div class="custom-card p-4 mb-4">
          <h5 class="fw-bold text-dark mb-3">
            <i class="bi bi-building me-2 text-primary"></i> Company & Hub Identity
          </h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Company Name</label>
              <input v-model="settings.companyName" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Operations Support Email</label>
              <input v-model="settings.supportEmail" type="email" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Dispatch Hotline</label>
              <input v-model="settings.dispatchPhone" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Base Currency</label>
              <select v-model="settings.defaultCurrency" class="form-select">
                <option>USD ($)</option>
                <option>EUR (€)</option>
                <option>GBP (£)</option>
                <option>CAD ($)</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Main Sorting Hub Address</label>
              <textarea v-model="settings.hubAddress" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>

        <div class="custom-card p-4">
          <h5 class="fw-bold text-dark mb-3">
            <i class="bi bi-shield-lock me-2 text-primary"></i> Dispatch Security & Verification
          </h5>
          <div class="d-flex flex-column gap-3">
            <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
              <div>
                <label class="form-check-label fw-semibold small text-dark d-block">Require Digital Signature on Delivery</label>
                <span class="text-muted small">Prompt courier to collect recipient signature for all high-value parcels.</span>
              </div>
              <input v-model="settings.requireSignature" class="form-check-input ms-3" type="checkbox" role="switch" />
            </div>

            <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0 pt-2 border-top">
              <div>
                <label class="form-check-label fw-semibold small text-dark d-block">AI Auto-Assign Nearest Driver</label>
                <span class="text-muted small">Automatically allocate new orders to available drivers based on GPS proximity.</span>
              </div>
              <input v-model="settings.autoAssignDrivers" class="form-check-input ms-3" type="checkbox" role="switch" />
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Notifications & Integration -->
      <div class="col-12 col-lg-5">
        <div class="custom-card p-4 mb-4">
          <h5 class="fw-bold text-dark mb-3">
            <i class="bi bi-bell me-2 text-primary"></i> Live Notifications
          </h5>
          <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0 mb-3">
            <div>
              <label class="form-check-label fw-semibold small text-dark d-block">Customer SMS Updates</label>
              <span class="text-muted small">Send tracking link when out for delivery.</span>
            </div>
            <input v-model="settings.sendSmsUpdates" class="form-check-input ms-3" type="checkbox" role="switch" />
          </div>

          <div class="p-3 bg-light rounded-3 border">
            <div class="fw-semibold small text-dark mb-1">
              <i class="bi bi-chat-dots text-primary me-1"></i> Sample SMS Preview:
            </div>
            <p class="text-muted mb-0 small" style="font-family: monospace;">
              "ExpressIt: Your parcel EXP-889021 is out for delivery with driver Michael Chen. Track live: https://expr.it/t/889021"
            </p>
          </div>
        </div>

        <div class="custom-card p-4">
          <h5 class="fw-bold text-dark mb-3">
            <i class="bi bi-key me-2 text-primary"></i> Dispatch API Key
          </h5>
          <div class="input-group mb-2">
            <input type="password" value="exp_live_891273981273189237" class="form-control" readonly />
            <button class="btn btn-outline-secondary" type="button">Copy</button>
          </div>
          <span class="text-muted small">Use this API key for integrating with Shopify, WooCommerce, or Custom ERP.</span>
        </div>
      </div>
    </div>
  </div>
</template>
