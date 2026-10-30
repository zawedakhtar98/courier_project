<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

// ----------------------------------------------------
// Global Mock State & Data
// ----------------------------------------------------
const activeTab = ref('booking')
const searchAwbQuery = ref('')
const walletBalance = ref(408.61)
const showRechargeModal = ref(false)
const rechargeAmount = ref(500)

// Active Stepper state
const currentStep = ref(1)

// Document upload states
const kycFile = ref(null)
const invoiceFile = ref(null)
const generateLabelToggle = ref(true)

// History List State
const draftsList = ref([
  {
    id: 'DRAFT-001',
    awbNo: 'EXP-AWB-20918',
    customer: 'Sam Web Solutio',
    consignee: 'SOFINA ASIA PRIVATE LIMITED',
    destination: 'AUSTRIA',
    service: 'UPS_SAVER',
    weight: 48.00,
    pcs: 3,
    date: '18-08-2026',
    status: 'Draft',
  }
])

const shipmentsList = ref([
  {
    id: 'SHIP-001',
    awbNo: 'EXP-AWB-10029',
    sender: 'Sam Web Solutio',
    consignee: 'Celine Chan Singapore',
    destination: 'SINGAPORE',
    service: 'UPS_SAVER',
    weight: 12.50,
    pcs: 2,
    date: '15-08-2026',
    status: 'Label Generated',
    labelGenerated: true,
  }
])

// Alert message helper
const alertMessage = ref(null)
const showAlert = (text, type = 'success') => {
  alertMessage.value = { type, text }
  setTimeout(() => {
    alertMessage.value = null
  }, 4000)
}

// Search utility
const handleAwbSearch = () => {
  if (!searchAwbQuery.value) {
    showAlert('Please enter an AWB number', 'danger')
    return
  }
  const foundShipment = shipmentsList.value.find(s => s.awbNo.toLowerCase().includes(searchAwbQuery.value.toLowerCase()))
  if (foundShipment) {
    showAlert(`Found: AWB ${foundShipment.awbNo} (${foundShipment.status})`)
    activeTab.value = 'shipments'
    return
  }
  showAlert(`AWB "${searchAwbQuery.value}" not found.`, 'danger')
}

const processRecharge = () => {
  walletBalance.value += rechargeAmount.value
  showRechargeModal.value = false
  showAlert(`Recharged ₹${rechargeAmount.value}. Balance: ₹${walletBalance.value}`)
}

// ----------------------------------------------------
// Form Form-State Setup
// ----------------------------------------------------

// Step 1: Shipper Details
const shipperForm = ref({
  customerAccount: 'OVS1704-EXPRESS IT\'S',
  companyName: 'Sam Web Solutio',
  shipper_name: 'Test',
  address1: 'Test one',
  address2: 'Test one',
  address3: '',
  pincode: '700001',
  city: 'Kolkata',
  state: 'WB',
  mobile_no: '8947582597',
  email: '',
  kycType: 'Aadhaar Number',
  kycNo: '224855584525'
})

// Step 2: Consignee Details
const consigneeForm = ref({
  destination: 'AUSTRIA',
  isoCode: 'AT',
  consigneeType: 'Business',
  companyName: 'SOFINA ASIA PRIVATE LIMITED',
  contactPerson: 'CELINE CHAN',
  address1: '108',
  address2: 'AMOY',
  address3: 'STREET',
  pincode: '069928',
  city: 'SINGAPORE',
  state: 'SINGAPORE',
  mobile_no: '+6580708618',
  email: 'celine.chan@sofinagroup.com',
  vatTaxId: ''
})

const countryIsoMapping = {
  'AUSTRIA': 'AT',
  'SINGAPORE': 'SG',
  'UNITED STATES': 'US',
  'GERMANY': 'DE',
  'ALBANIA': 'AL',
  'INDIA': 'IN'
}

watch(() => consigneeForm.value.destination, (newDest) => {
  if (countryIsoMapping[newDest]) {
    consigneeForm.value.isoCode = countryIsoMapping[newDest]
  }
})

// Step 3: Service Details
const serviceForm = ref({
  service: 'UPS_SAVER',
  serviceCode: 'S102',
  vendor: 'UPS',
  vendorCode: 'UPS',
  goodsType: 'NDox',
  packageType: 'PACKAGE'
})

const serviceInfoMapping = {
  'UPS_SAVER': { code: 'S102', vendor: 'UPS' },
  'DHL_EXPRESS': { code: 'S101', vendor: 'DHL' },
  'FEDEX_PRIORITY': { code: 'S103', vendor: 'FEDEX' },
  'FEDEX_IP': { code: 'S105', vendor: 'FEDEX' }
}

watch(() => serviceForm.value.service, (newService) => {
  if (serviceInfoMapping[newService]) {
    serviceForm.value.serviceCode = serviceInfoMapping[newService].code
    serviceForm.value.vendor = serviceInfoMapping[newService].vendor
    serviceForm.value.vendorCode = serviceInfoMapping[newService].vendor
  }
})

// Box Details List
const boxRows = ref([
  { boxNo: 1, actWt: 2, length: 30, width: 30, height: 50, volWt: 9.00, chgWt: 9.00 }
])

const recalculateBoxRow = (row) => {
  if (row.length && row.width && row.height) {
    row.volWt = parseFloat(((row.length * row.width * row.height) / 5000).toFixed(2))
  } else {
    row.volWt = 0
  }
  const baseWeight = Math.max(row.actWt || 0, row.volWt)
  row.chgWt = parseFloat((Math.ceil(baseWeight * 2) / 2).toFixed(2))
}

const addBoxRow = () => {
  const newNo = boxRows.value.length > 0 ? Math.max(...boxRows.value.map(b => b.boxNo)) + 1 : 1
  const newRow = { boxNo: newNo, actWt: 2, length: 30, width: 30, height: 50, volWt: 9.00, chgWt: 9.00 }
  recalculateBoxRow(newRow)
  boxRows.value.push(newRow)
}

const deleteBoxRow = (index) => {
  if (boxRows.value.length <= 1) {
    showAlert('At least one box is required.', 'danger')
    return
  }
  const row = boxRows.value[index]
  if (row) {
    boxRows.value.splice(index, 1)
  }
}

const handleBoxFieldChange = (row) => {
  recalculateBoxRow(row)
}

const totalPcs = computed(() => boxRows.value.length)
const totalActualWeight = computed(() => {
  return parseFloat(boxRows.value.reduce((acc, row) => acc + (row.actWt || 0), 0).toFixed(2))
})
const totalVolWeight = computed(() => {
  return parseFloat(boxRows.value.reduce((acc, row) => acc + (row.volWt || 0), 0).toFixed(2))
})
const totalChargeableWeight = computed(() => {
  return parseFloat(boxRows.value.reduce((acc, row) => acc + (row.chgWt || 0), 0).toFixed(2))
})

// Step 4: Custom Invoice Details
const invoiceRows = ref([
  { boxNo: 1, description: 'TESI HAMMER', hsnCode: '82071900', unit: 'PCS', qty: 1, rate: 22, amount: 22 }
])

const customsClearance = ref({
  csbType: 'CSB 4',
  invoiceNo: '230875',
  invoiceDate: '2026-08-09',
  termOfTrade: 'DAP',
  reasonForExport: 'Bonafide Gift',
  bondUt: 'NA',
  referenceNo: 'AB!233',
  dutyTax: 'DDU',
  signatureRequired: 'No'
})

const invoiceCurrency = ref('INR')
const totalIgst = ref(22)

const addInvoiceRow = () => {
  invoiceRows.value.push({
    boxNo: boxRows.value[0]?.boxNo || 1,
    description: 'TESI HAMMER',
    hsnCode: '82071900',
    unit: 'PCS',
    qty: 1,
    rate: 22,
    amount: 22
  })
}

const deleteInvoiceRow = (index) => {
  if (invoiceRows.value.length <= 1) {
    showAlert('At least one invoice item is required.', 'danger')
    return
  }
  invoiceRows.value.splice(index, 1)
}

const handleInvoiceItemChange = (row) => {
  row.amount = parseFloat(((row.qty || 0) * (row.rate || 0)).toFixed(2))
}

const invoiceTotalValue = computed(() => {
  return parseFloat(invoiceRows.value.reduce((acc, r) => acc + (r.amount || 0), 0).toFixed(2))
})

const concatenatedDescriptions = computed(() => {
  return invoiceRows.value.map(r => r.description).filter(Boolean).join('; ')
})

// Load Router query params if present
onMounted(() => {
  if (route.query.destination) {
    consigneeForm.value.destination = String(route.query.destination).toUpperCase()
    const foundIso = countryIsoMapping[consigneeForm.value.destination]
    if (foundIso) {
      consigneeForm.value.isoCode = foundIso
    }
  }
  if (route.query.service) {
    serviceForm.value.service = String(route.query.service)
  }
  if (route.query.goodsType || route.query.packageType) {
    const rawType = String(route.query.goodsType || route.query.packageType).toUpperCase()
    serviceForm.value.goodsType = rawType.includes('NON') ? 'NDox' : (rawType.includes('DOX') ? 'Dox' : rawType)
  }
  if (route.query.pincode || route.query.pincode) {
    consigneeForm.value.pincode = String(route.query.pincode || route.query.pincode)
  }

  // Pre-load boxes if sent in JSON format
  if (route.query.boxes) {
    try {
      const parsedBoxes = JSON.parse(String(route.query.boxes))
      if (Array.isArray(parsedBoxes) && parsedBoxes.length > 0) {
        boxRows.value = parsedBoxes.map((b, index) => {
          const row = {
            boxNo: index + 1,
            actWt: parseFloat(b.actWt) || 1,
            length: parseFloat(b.length) || 10,
            width: parseFloat(b.width) || 10,
            height: parseFloat(b.height) || 10,
            volWt: 0,
            chgWt: 0
          }
          recalculateBoxRow(row)
          return row
        })
      }
    } catch (e) {
      console.error('Failed to parse query boxes', e)
    }
  }

  if (route.query.city) {
    consigneeForm.value.city = String(route.query.city)
  }
  if (route.query.state) {
    consigneeForm.value.state = String(route.query.state)
  }

  // showAlert('Preloaded booking options from Rate Calculator.')
})

const nextStep = () => { if (currentStep.value < 4) currentStep.value++ }
const prevStep = () => { if (currentStep.value > 1) currentStep.value-- }
const jumpToStep = (stepNum) => { if (stepNum >= 1 && stepNum <= 4 && stepNum <= currentStep.value + 1) currentStep.value = stepNum }
const saveDetailsDraft = () => { showAlert(`Details saved locally for Step ${currentStep.value}`) }

const handleFileUpload = (type, event) => {
  const target = event.target
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    if (file) {
      const sizeStr = (file.size / 1024).toFixed(1) + ' KB'
      if (type === 'kyc') {
        kycFile.value = { name: file.name, size: sizeStr }
      } else {
        invoiceFile.value = { name: file.name, size: sizeStr }
      }
    }
  }
}

const removeFile = (type) => {
  if (type === 'kyc') kycFile.value = null; else invoiceFile.value = null
}

const triggerCreateLabel = () => {
  const bookingFee = 200.00
  if (walletBalance.value < bookingFee) {
    showAlert('Insufficient credit balance.', 'danger')
    return
  }
  walletBalance.value = parseFloat((walletBalance.value - bookingFee).toFixed(2))
  const newAwb = 'EXP-AWB-' + Math.floor(10000 + Math.random() * 90000)

  shipmentsList.value.unshift({
    id: 'SHIP-' + Math.floor(100 + Math.random() * 900),
    awbNo: newAwb,
    sender: shipperForm.value.companyName,
    consignee: consigneeForm.value.companyName,
    destination: consigneeForm.value.destination,
    service: serviceForm.value.service,
    weight: totalChargeableWeight.value,
    pcs: totalPcs.value,
    date: '18-08-2026',
    status: 'Label Generated',
    labelGenerated: true
  })
  showAlert(`Label Created successfully! AWB: ${newAwb}`)
  resetFormState()
  activeTab.value = 'shipments'
}

const triggerDraftBooking = () => {
  const newAwb = 'EXP-AWB-' + Math.floor(10000 + Math.random() * 90000)
  draftsList.value.unshift({
    id: 'DRAFT-' + Math.floor(100 + Math.random() * 900),
    awbNo: newAwb,
    customer: shipperForm.value.companyName,
    consignee: consigneeForm.value.companyName,
    destination: consigneeForm.value.destination,
    service: serviceForm.value.service,
    weight: totalChargeableWeight.value,
    pcs: totalPcs.value,
    date: '18-08-2026',
    status: 'Draft'
  })
  showAlert(`Draft Saved successfully! AWB: ${newAwb}`)
  resetFormState()
  activeTab.value = 'drafts'
}

const triggerCancel = () => {
  resetFormState()
  showAlert('Booking process cancelled', 'danger')
}

const resetFormState = () => {
  currentStep.value = 1
  kycFile.value = null
  invoiceFile.value = null
}

const loadDraft = (draft) => {
  shipperForm.value.companyName = draft.customer
  consigneeForm.value.destination = draft.destination
  serviceForm.value.service = draft.service
  activeTab.value = 'booking'
  currentStep.value = 1
  showAlert(`Loaded draft ${draft.awbNo} into booking form.`)
}
</script>

<template>
  <div class="container-fluid p-0 page-add-shipment">

    <!-- Top Alert System -->
    <transition name="fade">
      <div v-if="alertMessage"
        class="alert position-fixed top-0 start-50 translate-middle-x mt-3 z-3 shadow-lg rounded-3 border-0 px-4 py-3"
        :class="alertMessage.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
        style="width: 90%; max-width: 480px;">
        <div class="d-flex align-items-center gap-2">
          <i class="bi"
            :class="alertMessage.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
          <div class="fw-semibold text-break flex-fill">{{ alertMessage.text }}</div>
          <button type="button" class="btn-close btn-close-white ms-auto" @click="alertMessage = null"></button>
        </div>
      </div>
    </transition>

    <!-- Content Outlet based on Active Tab -->

    <!-- TAB 1: ORDER BOOKING -->
    <div v-if="activeTab === 'booking'" class="custom-card p-4 bg-white">
      <div class="row g-4">

        <!-- Stepper Left Panel -->
        <div class="col-12 col-md-4 col-xl-3 border-end">
          <div class="stepper-vertical d-flex flex-column gap-4 py-2">

            <!-- Step 1 Indicator -->
            <div @click="jumpToStep(1)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 1, completed: currentStep > 1 }">
              <div class="step-circle">
                <i v-if="currentStep > 1" class="bi bi-check-lg text-white"></i>
                <i v-else class="bi bi-person"></i>
              </div>
              <div class="step-label d-flex flex-column">
                <span class="step-title">Sender Details</span>
              </div>
            </div>

            <!-- Step 2 Indicator -->
            <div @click="jumpToStep(2)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 2, completed: currentStep > 2 }">
              <div class="step-circle">
                <i v-if="currentStep > 2" class="bi bi-check-lg text-white"></i>
                <i v-else class="bi bi-people"></i>
              </div>
              <div class="step-label d-flex flex-column">
                <span class="step-title">Consignee Details</span>
              </div>
            </div>

            <!-- Step 3 Indicator -->
            <div @click="jumpToStep(3)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 3, completed: currentStep > 3 }">
              <div class="step-circle">
                <i v-if="currentStep > 3" class="bi bi-check-lg text-white"></i>
                <i v-else class="bi bi-box-seam"></i>
              </div>
              <div class="step-label d-flex flex-column">
                <span class="step-title">Package Details</span>
              </div>
            </div>

            <!-- Step 4 Indicator -->
            <div @click="jumpToStep(4)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 4, completed: currentStep > 4 }">
              <div class="step-circle">
                <i v-if="currentStep > 4" class="bi bi-check-lg text-white"></i>
                <i v-else class="bi bi-file-earmark-text"></i>
              </div>
              <div class="step-label d-flex flex-column">
                <span class="step-title">Invoice Details</span>
              </div>
            </div>

          </div>
        </div>

        <!-- Form Right Panel -->
        <div class="col-12 col-md-8 col-xl-9">

          <!-- STEP 1: SENDER DETAILS -->
          <div v-if="currentStep === 1" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent">
                <i class="bi bi-building me-2 text-danger"></i> Shipper Details
              </h5>
            </div>

            <div class="row g-3">
              <!-- <div class="col-md-12">
                <label class="form-label small fw-bold">Customer Account <span class="text-danger">*</span></label>
                <input v-model="shipperForm.customerAccount" type="text" class="form-control" />
              </div> -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">Shipper Name <span class="text-danger">*</span></label>
                <input v-model="shipperForm.shipper_name" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Company Name <span class="text-danger">*</span></label>
                <input v-model="shipperForm.companyName" type="text" class="form-control" />
              </div>
              <div class="col-12">
                <label class="form-label small fw-bold">Address Line 1 <span class="text-danger">*</span></label>
                <input v-model="shipperForm.address1" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Address Line 2 <span class="text-danger">*</span></label>
                <input v-model="shipperForm.address2" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Address Line 3</label>
                <input v-model="shipperForm.address3" type="text" class="form-control" />
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold">Pincode <span class="text-danger">*</span></label>
                <input v-model="shipperForm.pincode" type="text" class="form-control" />
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">City <span class="text-danger">*</span></label>
                <input v-model="shipperForm.city" type="text" class="form-control" />
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">State <span class="text-danger">*</span></label>
                <input v-model="shipperForm.state" type="text" class="form-control" />
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Country <span class="text-danger">*</span></label>
                <input v-model="shipperForm.state" type="text" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold">Mobile No<span class="text-danger">*</span></label>
                <input v-model="shipperForm.mobile_no" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">E-mail Id</label>
                <input v-model="shipperForm.email" type="email" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold">KYC Type <span class="text-danger">*</span></label>
                <select v-model="shipperForm.kycType" class="form-select">
                  <option value="Aadhaar Number">Aadhaar Number</option>
                  <option value="PAN Card">PAN Card</option>
                  <option value="GST No">GST No</option>
                  <option value="Passport">Passport</option>
                  <option value="Voter ID">Voter ID</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">KYC No. <span class="text-danger">*</span></label>
                <input v-model="shipperForm.kycNo" type="text" class="form-control" />
              </div>
            </div>
            <div class="row g-4 mb-2 mt-1">
              <!-- KYC Document Upload -->
              <div class="col-md-6">
                <!-- <label for="" class="small fw-bold">Upload KYC Front Side Document</label> -->
                <div
                  class="upload-box d-flex flex-column align-items-center justify-content-center p-4 border border-2 border-dashed rounded-3 bg-light text-center position-relative">
                  <input type="file" @change="handleFileUpload('kyc', $event)"
                    class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer"
                    accept=".pdf,.png,.jpg,.jpeg" />

                  <div v-if="!kycFile" class="d-flex flex-wrap align-items-center justify-content-center">
                    <div class="upload-icon-circle bg-white shadow-sm me-2">
                      <i class="bi bi-cloud-upload text-primary"></i>
                    </div>
                    <span class="fw-bold text-dark drop-text">Drag and Drop Front side of KYC Document</span>
                    <span class="text-muted small drop-text">Supports PDF, PNG, JPG</span>
                  </div>
                  <div v-else class="uploaded-state d-flex flex-column align-items-center">
                    <i class="bi bi-file-earmark-check text-success"></i>
                    <span class="fw-semibold text-dark text-truncate" style="max-width: 220px;">{{
                      kycFile.name
                    }}</span>
                    <span class="text-muted small mb-2">{{ kycFile.size }}</span>
                    <button type="button" @click.stop="removeFile('kyc')"
                      class="btn btn-sm btn-outline-danger px-3 py-1 rounded-pill">
                      Remove File
                    </button>
                  </div>
                </div>
              </div>

              <!-- KYC back Document Upload -->
              <div class="col-md-6">
                <!-- <label for="" class="small fw-bold">Upload KYC Back Side Document</label> -->
                <div
                  class="upload-box d-flex flex-column align-items-center justify-content-center p-4 border border-2 border-dashed rounded-3 bg-light text-center position-relative">
                  <input type="file" @change="handleFileUpload('invoice', $event)"
                    class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer"
                    accept=".pdf,.png,.jpg,.jpeg" />

                  <div v-if="!invoiceFile" class="d-flex flex-wrap align-items-center justify-content-center">
                    <div class="upload-icon-circle bg-white shadow-sm me-2">
                      <i class="bi bi-cloud-upload text-primary"></i>
                    </div>
                    <span class="fw-bold text-dark drop-text">Drag and Drop Back side of KYC Document</span>
                    <span class="text-muted small drop-text">Supports PDF, PNG, JPG</span>
                  </div>
                  <div v-else class="uploaded-state d-flex flex-column align-items-center">
                    <i class="bi bi-file-earmark-check text-success fs-2 mb-2"></i>
                    <span class="fw-semibold text-dark text-truncate" style="max-width: 220px;">{{
                      invoiceFile.name
                    }}</span>
                    <span class="text-muted small mb-2">{{ invoiceFile.size }}</span>
                    <button type="button" @click.stop="removeFile('invoice')"
                      class="btn btn-sm btn-outline-danger px-3 py-1 rounded-pill">
                      Remove File
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 2: CONSIGNEE DETAILS -->
          <div v-if="currentStep === 2" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent">
                <i class="bi bi-building me-2 text-danger"></i> Consignee Details
              </h5>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold">Destination <span class="text-danger">*</span></label>
                <select v-model="consigneeForm.destination" class="form-select">
                  <option value="AUSTRIA">AUSTRIA</option>
                  <option value="SINGAPORE">SINGAPORE</option>
                  <option value="UNITED STATES">UNITED STATES</option>
                  <option value="GERMANY">GERMANY</option>
                  <option value="CANADA">CANADA</option>
                  <option value="UNITED KINGDOM">UNITED KINGDOM</option>
                  <option value="INDIA">INDIA</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Consignee's Name <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.contactPerson" type="text" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Company Name <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.companyName" type="text" class="form-control" />
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold">Address Line 1 <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.address1" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Address Line 2</label>
                <input v-model="consigneeForm.address2" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Address Line 3</label>
                <input v-model="consigneeForm.address3" type="text" class="form-control" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold">Pincode <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.pincode" type="text" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">City <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.city" type="text" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">State</label>
                <input v-model="consigneeForm.state" type="text" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold">Mobile No. <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.mobile_no" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">E-mail Id</label>
                <input v-model="consigneeForm.email" type="email" class="form-control" />
              </div>
            </div>
          </div>

          <!-- STEP 3: Package Details -->
          <div v-if="currentStep === 3" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent">
                <i class="bi bi-gear-fill me-2 text-danger"></i> Package Details
              </h5>
            </div>

            <div class="row g-3 mb-4">

              <div class="col-md-6">
                <label class="form-label small fw-bold">Service Partner <span class="text-danger">*</span></label>
                <input v-model="serviceForm.vendor" type="text" class="form-control bg-light" readonly />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Package Type <span class="text-danger">*</span></label>
                <input v-model="serviceForm.vendor" type="text" class="form-control bg-light" readonly />
              </div>
            </div>

            <!-- Dynamic Box Details Table -->
            <div class="border-top pt-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-dark-accent">
                  <i class="bi bi-box me-1 text-danger"></i> Box Details
                </h6>
                <button type="button" @click="addBoxRow"
                  class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 rounded-3">
                  <i class="bi bi-plus-lg"></i>
                  <span>Add Box</span>
                </button>
              </div>

              <div class="table-responsive">
                <table class="table custom-table table-bordered align-middle">
                  <thead class="table-light text-secondary">
                    <tr>
                      <th style="width: 10%;">BOX NO.</th>
                      <th>ACT. WT</th>
                      <th>LENGTH</th>
                      <th>WIDTH</th>
                      <th>HEIGHT</th>
                      <th>VOL. WT</th>
                      <th>CHG. WT (KG)</th>
                      <th style="width: 10%;" class="text-center">ACTION</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(box, index) in boxRows" :key="box.boxNo">
                      <td class="text-center fw-bold text-muted">{{ box.boxNo }}</td>
                      <td>
                        <input v-model.number="box.actWt" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" step="0.1" />
                      </td>
                      <td>
                        <input v-model.number="box.length" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" />
                      </td>
                      <td>
                        <input v-model.number="box.width" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" />
                      </td>
                      <td>
                        <input v-model.number="box.height" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" />
                      </td>
                      <td class="text-center fw-semibold text-dark">{{ box.volWt.toFixed(2) }}
                      </td>
                      <td class="text-center fw-semibold text-primary">{{ box.chgWt.toFixed(2) }}
                      </td>
                      <td class="text-center">
                        <button type="button" @click="deleteBoxRow(index)"
                          class="btn btn-sm btn-link text-danger p-0 border-0 bg-transparent" title="Delete Row">
                          <i class="bi bi-trash fs-5"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="row g-2 mt-3">
                <!-- Readonly summary outputs -->
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Total Box <span class="text-danger">*</span></label>
                  <input :value="totalPcs" type="number" class="form-control bg-light fw-bold" readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Actual Weight (kg) <span class="text-danger">*</span></label>
                  <input :value="totalActualWeight.toFixed(2)" type="text" class="form-control bg-light fw-bold"
                    readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Vol Weight <span class="text-danger">*</span></label>
                  <input :value="totalVolWeight.toFixed(2)" type="text" class="form-control bg-light fw-bold"
                    readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Chargeable Weight (kg) <span
                      class="text-danger">*</span></label>
                  <input :value="totalChargeableWeight.toFixed(2)" type="text" class="form-control bg-light fw-bold"
                    readonly />
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 4: INVOICE DETAILS -->
          <div v-if="currentStep === 4" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent">
                <i class="bi bi-file-earmark-text-fill me-2 text-danger"></i>Invoice Details
              </h5>
            </div>

            <!-- Custom Invoice Item Table -->
            <div class="table-responsive mb-4">
              <table class="table custom-table table-bordered align-middle">
                <thead class="table-light text-secondary">
                  <tr>
                    <th style="width: 10%;">BOX</th>
                    <th>DESCRIPTION <span class="text-danger">*</span></th>
                    <th>HSN CODE</th>
                    <th style="width: 10%;">UNIT</th>
                    <th style="width: 10%;">QTY</th>
                    <th>RATE</th>
                    <th>AMOUNT</th>
                    <th style="width: 12%;" class="text-center">ACTION</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(invItem, idx) in invoiceRows" :key="idx">
                    <td>
                      <!-- Dropdown of available box numbers -->
                      <select v-model.number="invItem.boxNo" class="form-select form-select-sm text-center">
                        <option v-for="b in boxRows" :key="b.boxNo" :value="b.boxNo">{{ b.boxNo
                        }}</option>
                      </select>
                    </td>
                    <td>
                      <input v-model="invItem.description" type="text" class="form-control form-control-sm" />
                    </td>
                    <td>
                      <input v-model="invItem.hsnCode" type="text" class="form-control form-control-sm" />
                    </td>
                    <td>
                      <input v-model="invItem.unit" type="text" class="form-control form-control-sm text-center" />
                    </td>
                    <td>
                      <input v-model.number="invItem.qty" @input="handleInvoiceItemChange(invItem)" type="number"
                        class="form-control form-control-sm text-center" min="1" />
                    </td>
                    <td>
                      <input v-model.number="invItem.rate" @input="handleInvoiceItemChange(invItem)" type="number"
                        class="form-control form-control-sm text-center" min="0" />
                    </td>
                    <td class="text-center fw-semibold text-dark">{{ invItem.amount.toFixed(2) }}
                    </td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-2">
                        <button type="button" @click="addInvoiceRow"
                          class="btn btn-sm btn-link text-success p-0 border-0 bg-transparent" title="Add Row">
                          <i class="bi bi-plus-circle fs-5"></i>
                        </button>
                        <button type="button" @click="deleteInvoiceRow(idx)"
                          class="btn btn-sm btn-link text-danger p-0 border-0 bg-transparent" title="Delete Row">
                          <i class="bi bi-trash fs-5"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Lower Summary Inputs -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Value <span class="text-danger">*</span></label>
                <input :value="invoiceTotalValue.toFixed(2)" type="text" class="form-control bg-light fw-bold"
                  readonly />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Currency <span class="text-danger">*</span></label>
                <select v-model="invoiceCurrency" class="form-select">
                  <option value="INR">INR</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                </select>
              </div>
            </div>

            <!-- Customs Clearance Section -->
            <div class="border-top pt-3">
              <h6 class="fw-bold mb-3 text-dark-accent">
                <i class="bi bi-shield-lock-fill me-1 text-danger"></i> Customs Clearance Detail
              </h6>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Invoice Type <span class="text-danger">*</span></label>
                  <select v-model="customsClearance.csbType" class="form-select">
                    <option value="Commercial">Commercial</option>
                    <option value="CSB 4">CSB 4</option>
                    <option value="CSB 5">CSB 5</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Invoice No <span class="text-danger">*</span></label>
                  <input v-model="customsClearance.invoiceNo" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Invoice Date <span class="text-danger">*</span></label>
                  <input v-model="customsClearance.invoiceDate" type="date" class="form-control" />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold">Term of Trade <span class="text-danger">*</span></label>
                  <select v-model="customsClearance.termOfTrade" class="form-select">
                    <option>DAP</option>
                    <option>FOB</option>
                    <option>CIF</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold">Duty Tax <span class="text-danger">*</span></label>
                  <select v-model="customsClearance.dutyTax" class="form-select">
                    <option>DDU</option>
                    <option>DDP</option>
                  </select>
                </div>
                <!-- <div class="col-md-4">
                  <label class="form-label small fw-bold">Signature Required.</label>
                  <select v-model="customsClearance.signatureRequired" class="form-select">
                    <option>No</option>
                    <option>Yes</option>
                  </select>
                </div> -->
              </div>
            </div>
          </div>

          <!-- Bottom Navigation Stepper Buttons -->
          <div class="d-flex justify-content-between align-items-center border-top pt-4 mt-4">
            <button v-if="currentStep > 1" @click="prevStep" type="button"
              class="btn btn-outline-secondary px-4 py-2 rounded-3">
              &larr; Previous
            </button>
            <div v-else></div> <!-- spacer -->

            <button v-if="currentStep < 4" @click="nextStep" type="button"
              class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold">
              Next &rarr;
            </button>
            <!-- @click="triggerCreateLabel" -->
            <button v-if="currentStep === 4" type="button" class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold">
              <i class="bi bi-calendar-check me-2"></i> Book Now
            </button>
          </div>

        </div>

      </div>
    </div>

    <!-- TAB 2: DRAFT HISTORY -->
    <div v-if="activeTab === 'drafts'" class="custom-card p-4 bg-white animate-fade">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-dark-accent">Draft Bookings History</h5>
        <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-3">{{ draftsList.length
        }}
          Drafts</span>
      </div>

      <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>AWB / ID</th>
              <th>Date</th>
              <th>Customer</th>
              <th>Consignee</th>
              <th>Service</th>
              <th>Weight / Pcs</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="draft in draftsList" :key="draft.id">
              <td>
                <div class="fw-bold text-dark">{{ draft.awbNo }}</div>
                <span class="badge bg-secondary-subtle text-secondary small">{{ draft.id }}</span>
              </td>
              <td>{{ draft.date }}</td>
              <td>
                <span class="fw-semibold">{{ draft.customer }}</span>
              </td>
              <td>
                <div>{{ draft.consignee }}</div>
                <span class="badge bg-light text-secondary border small">{{ draft.destination }}</span>
              </td>
              <td>
                <span class="badge bg-info-subtle text-info border border-info border-opacity-25">{{
                  draft.service
                }}</span>
              </td>
              <td>
                <div class="fw-semibold text-dark">{{ draft.weight }} kg</div>
                <span class="text-muted small">{{ draft.pcs }} pcs</span>
              </td>
              <td class="text-end">
                <button @click="loadDraft(draft)" class="btn btn-sm btn-primary-gradient rounded-3 px-3 py-1">
                  Load & Edit
                </button>
              </td>
            </tr>
            <tr v-if="draftsList.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block text-secondary mb-2"></i>
                No drafts saved. Save drafts inside the booking steps.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 3: SHIPMENT HISTORY -->
    <div v-if="activeTab === 'shipments'" class="custom-card p-4 bg-white animate-fade">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-dark-accent">Shipments & AWB List</h5>
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">{{ shipmentsList.length }}
          Shipments</span>
      </div>

      <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>AWB / ID</th>
              <th>Date</th>
              <th>Sender</th>
              <th>Consignee</th>
              <th>Service</th>
              <th>Weight / Pcs</th>
              <th>Status</th>
              <th class="text-end">Label</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="shipment in shipmentsList" :key="shipment.id">
              <td>
                <div class="fw-bold text-primary">{{ shipment.awbNo }}</div>
                <span class="badge bg-light text-secondary border small">{{ shipment.id }}</span>
              </td>
              <td>{{ shipment.date }}</td>
              <td>
                <span class="fw-semibold text-dark">{{ shipment.sender }}</span>
              </td>
              <td>
                <div>{{ shipment.consignee }}</div>
                <span class="badge bg-light text-secondary border small">{{ shipment.destination
                }}</span>
              </td>
              <td>
                <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-10">{{
                  shipment.service }}</span>
              </td>
              <td>
                <div class="fw-semibold text-dark">{{ shipment.weight }} kg</div>
                <span class="text-muted small">{{ shipment.pcs }} pcs</span>
              </td>
              <td>
                <span class="badge-status"
                  :class="shipment.status === 'Label Generated' ? 'badge-delivered' : 'badge-in-transit'">
                  {{ shipment.status }}
                </span>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary rounded-3 px-2 py-1" title="Print Waybill">
                  <i class="bi bi-printer"></i>
                </button>
              </td>
            </tr>
            <tr v-if="shipmentsList.length === 0">
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-box-seam fs-2 d-block text-secondary mb-2"></i>
                No shipments created yet. Go to step 5 to generate AWB labels.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recharge Wallet Modal -->
    <div v-if="showRechargeModal" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.6); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
          <div class="modal-header bg-danger text-white p-3 px-4">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-wallet2 me-2"></i> Recharge Wallet
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showRechargeModal = false"></button>
          </div>
          <form @submit.prevent="processRecharge">
            <div class="modal-body p-4">
              <div class="mb-3">
                <label class="form-label small fw-bold">Enter Amount (₹)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light fw-bold">₹</span>
                  <input v-model.number="rechargeAmount" type="number" class="form-control fw-bold fs-5 text-center"
                    min="10" max="10000" required />
                </div>
                <div class="text-muted small mt-2 text-center">
                  Recommended:
                  <button type="button" @click="rechargeAmount = 200"
                    class="btn btn-xs btn-outline-secondary py-0 px-2 small rounded-pill m-1">₹200</button>
                  <button type="button" @click="rechargeAmount = 500"
                    class="btn btn-xs btn-outline-secondary py-0 px-2 small rounded-pill m-1">₹500</button>
                  <button type="button" @click="rechargeAmount = 1000"
                    class="btn btn-xs btn-outline-secondary py-0 px-2 small rounded-pill m-1">₹1000</button>
                </div>
              </div>
            </div>
            <div class="modal-footer bg-light p-3 px-4 justify-content-between">
              <button type="button" class="btn btn-outline-secondary rounded-3" @click="showRechargeModal = false">
                Cancel
              </button>
              <button type="submit" class="btn btn-recharge px-4 py-2 fw-semibold">
                Proceed Recharge
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>
