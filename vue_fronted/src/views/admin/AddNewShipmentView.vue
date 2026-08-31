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
  contactPerson: 'Test',
  address1: 'Test one',
  address2: 'Test one',
  address3: '',
  pincode: '700001',
  city: 'Kolkata',
  state: 'WB',
  telephone: '8947582597',
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
  zipcode: '069928',
  city: 'SINGAPORE',
  state: 'SINGAPORE',
  telephone: '+6580708618',
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
  { boxNo: 1, description: 'TESI HAMMER', hsnCode: '82071900', htsCode: '82071900', unit: 'PCS', qty: 1, rate: 22, amount: 22 }
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
    htsCode: '82071900',
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
  if (route.query.goodsType) {
    serviceForm.value.goodsType = String(route.query.goodsType)
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

  showAlert('Preloaded booking options from Rate Calculator.')
})

const nextStep = () => { if (currentStep.value < 5) currentStep.value++ }
const prevStep = () => { if (currentStep.value > 1) currentStep.value-- }
const jumpToStep = (stepNum) => { if (stepNum <= currentStep.value + 1) currentStep.value = stepNum }
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
  <div class="container-fluid p-0">
    <!-- Top Alert System -->
    <transition name="fade">
      <div v-if="alertMessage"
        class="alert position-fixed top-0 start-50 translate-middle-x mt-5 z-3 shadow-lg rounded-3 border-0 px-4 py-3"
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

    <!-- Content Outlet -->
    <div v-if="activeTab === 'booking'" class="custom-card p-4 bg-white">
      <div class="row g-4">
        <!-- Stepper Left Panel -->
        <div class="col-12 col-md-4 col-xl-3 border-end">
          <div class="stepper-vertical d-flex flex-column gap-4 py-2">
            <div @click="jumpToStep(1)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 1, completed: currentStep > 1 }">
              <div class="step-circle">
                <i v-if="currentStep > 1" class="bi bi-check-lg text-white"></i><i v-else class="bi bi-person"></i>
              </div>
              <div class="step-label d-flex flex-column"><span class="step-title">Sender Details</span></div>
            </div>

            <div @click="jumpToStep(2)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 2, completed: currentStep > 2 }">
              <div class="step-circle">
                <i v-if="currentStep > 2" class="bi bi-check-lg text-white"></i><i v-else class="bi bi-people"></i>
              </div>
              <div class="step-label d-flex flex-column"><span class="step-title">Consignee Details</span></div>
            </div>

            <div @click="jumpToStep(3)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 3, completed: currentStep > 3 }">
              <div class="step-circle">
                <i v-if="currentStep > 3" class="bi bi-check-lg text-white"></i><i v-else class="bi bi-box-seam"></i>
              </div>
              <div class="step-label d-flex flex-column"><span class="step-title">Service Details</span></div>
            </div>

            <div @click="jumpToStep(4)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 4, completed: currentStep > 4 }">
              <div class="step-circle">
                <i v-if="currentStep > 4" class="bi bi-check-lg text-white"></i><i v-else
                  class="bi bi-file-earmark-text"></i>
              </div>
              <div class="step-label d-flex flex-column"><span class="step-title">Invoice Details</span></div>
            </div>

            <div @click="jumpToStep(5)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
              :class="{ active: currentStep === 5 }">
              <div class="step-circle"><i class="bi bi-shield-check"></i></div>
              <div class="step-label d-flex flex-column"><span class="step-title">KYC Details</span></div>
            </div>
          </div>
        </div>

        <!-- Form Right Panel -->
        <div class="col-12 col-md-8 col-xl-9">
          <!-- Step 1: Shipper Details -->
          <div v-if="currentStep === 1" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent"><i class="bi bi-building me-2 text-danger"></i> Shipper Details
              </h5>
              <a href="#" @click.prevent="saveDetailsDraft"
                class="save-link text-success fw-bold text-decoration-none">Save Details</a>
            </div>

            <div class="row g-3">
              <div class="col-md-12"><label class="form-label small fw-bold">Customer Account <span
                    class="text-danger">*</span></label><input v-model="shipperForm.customerAccount" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Company Name <span
                    class="text-danger">*</span></label><input v-model="shipperForm.companyName" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Contact Person <span
                    class="text-danger">*</span></label><input v-model="shipperForm.contactPerson" type="text"
                  class="form-control" /></div>
              <div class="col-12"><label class="form-label small fw-bold">Address Line 1 <span
                    class="text-danger">*</span></label><input v-model="shipperForm.address1" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Address Line 2 <span
                    class="text-danger">*</span></label><input v-model="shipperForm.address2" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Address Line 3</label><input
                  v-model="shipperForm.address3" type="text" class="form-control" /></div>
              <div class="col-md-4"><label class="form-label small fw-bold">Pincode <span
                    class="text-danger">*</span></label><input v-model="shipperForm.pincode" type="text"
                  class="form-control" /></div>
              <div class="col-md-4"><label class="form-label small fw-bold">City <span
                    class="text-danger">*</span></label><input v-model="shipperForm.city" type="text"
                  class="form-control" /></div>
              <div class="col-md-4"><label class="form-label small fw-bold">State <span
                    class="text-danger">*</span></label><input v-model="shipperForm.state" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Telephone <span
                    class="text-danger">*</span></label><input v-model="shipperForm.telephone" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">E-mail Id</label><input
                  v-model="shipperForm.email" type="email" class="form-control" /></div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">KYC Type <span class="text-danger">*</span></label>
                <select v-model="shipperForm.kycType" class="form-select">
                  <option>Aadhaar Number</option>
                  <option>PAN Card</option>
                  <option>GSTIN</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label small fw-bold">KYC No. <span
                    class="text-danger">*</span></label><input v-model="shipperForm.kycNo" type="text"
                  class="form-control" /></div>
            </div>
          </div>

          <!-- Step 2: Consignee Details -->
          <div v-if="currentStep === 2" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent"><i class="bi bi-building me-2 text-danger"></i> Consignee
                Details</h5>
              <a href="#" @click.prevent="saveDetailsDraft"
                class="save-link text-success fw-bold text-decoration-none">Save Details</a>
            </div>

            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label small fw-bold">Destination <span class="text-danger">*</span></label>
                <select v-model="consigneeForm.destination" class="form-select">
                  <option value="AUSTRIA">AUSTRIA</option>
                  <option value="SINGAPORE">SINGAPORE</option>
                  <option value="UNITED STATES">UNITED STATES</option>
                  <option value="GERMANY">GERMANY</option>
                  <option value="ALBANIA">ALBANIA</option>
                  <option value="INDIA">INDIA</option>
                </select>
              </div>
              <div class="col-md-4"><label class="form-label small fw-bold">ISO Code <span
                    class="text-danger">*</span></label><input v-model="consigneeForm.isoCode" type="text"
                  class="form-control bg-light" readonly /></div>
              <div class="col-md-12"><label class="form-label small fw-bold">Consignee Type <span
                    class="text-danger">*</span></label><select v-model="consigneeForm.consigneeType"
                  class="form-select">
                  <option>Business</option>
                  <option>Individual</option>
                </select></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Company Name <span
                    class="text-danger">*</span></label><input v-model="consigneeForm.companyName" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Contact Person <span
                    class="text-danger">*</span></label><input v-model="consigneeForm.contactPerson" type="text"
                  class="form-control" /></div>
              <div class="col-12"><label class="form-label small fw-bold">Address Line 1 <span
                    class="text-danger">*</span></label><input v-model="consigneeForm.address1" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Address Line 2</label><input
                  v-model="consigneeForm.address2" type="text" class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Address Line 3</label><input
                  v-model="consigneeForm.address3" type="text" class="form-control" /></div>
              <div class="col-md-4"><label class="form-label small fw-bold">Zipcode <span
                    class="text-danger">*</span></label><input v-model="consigneeForm.zipcode" type="text"
                  class="form-control" /></div>
              <div class="col-md-4"><label class="form-label small fw-bold">City <span
                    class="text-danger">*</span></label><input v-model="consigneeForm.city" type="text"
                  class="form-control" /></div>
              <div class="col-md-4"><label class="form-label small fw-bold">State</label><input
                  v-model="consigneeForm.state" type="text" class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Telephone <span
                    class="text-danger">*</span></label><input v-model="consigneeForm.telephone" type="text"
                  class="form-control" /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">E-mail Id</label><input
                  v-model="consigneeForm.email" type="email" class="form-control" /></div>
              <div class="col-12"><label class="form-label small fw-bold">VAT/TAX Id</label><input
                  v-model="consigneeForm.vatTaxId" type="text" class="form-control" /></div>
            </div>
          </div>

          <!-- Step 3: Service Details -->
          <div v-if="currentStep === 3" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent"><i class="bi bi-gear-fill me-2 text-danger"></i> Services
                Details</h5>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Service <span class="text-danger">*</span></label>
                <select v-model="serviceForm.service" class="form-select">
                  <option value="UPS_SAVER">UPS_SAVER</option>
                  <option value="DHL_EXPRESS">DHL_EXPRESS</option>
                  <option value="FEDEX_PRIORITY">FEDEX_PRIORITY</option>
                  <option value="FEDEX_IP">FEDEX_IP</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label small fw-bold">Service Code <span
                    class="text-danger">*</span></label><input v-model="serviceForm.serviceCode" type="text"
                  class="form-control bg-light" readonly /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Vendor <span
                    class="text-danger">*</span></label><input v-model="serviceForm.vendor" type="text"
                  class="form-control bg-light" readonly /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Vendor Code <span
                    class="text-danger">*</span></label><input v-model="serviceForm.vendorCode" type="text"
                  class="form-control bg-light" readonly /></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Goods Type <span
                    class="text-danger">*</span></label><select v-model="serviceForm.goodsType" class="form-select">
                  <option>NDox</option>
                  <option>Dox</option>
                </select></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Package Type <span
                    class="text-danger">*</span></label><select v-model="serviceForm.packageType" class="form-select">
                  <option>PACKAGE</option>
                  <option>BOX</option>
                </select></div>

              <div class="col-md-3"><label class="form-label small fw-bold">Total Pcs</label><input :value="totalPcs"
                  type="text" class="form-control bg-light" readonly /></div>
              <div class="col-md-3"><label class="form-label small fw-bold">Actual Weight</label><input
                  :value="totalActualWeight" type="text" class="form-control bg-light" readonly /></div>
              <div class="col-md-3"><label class="form-label small fw-bold">Vol Weight</label><input
                  :value="totalVolWeight" type="text" class="form-control bg-light" readonly /></div>
              <div class="col-md-3"><label class="form-label small fw-bold">Chg. Weight</label><input
                  :value="totalChargeableWeight" type="text" class="form-control bg-light" readonly /></div>
            </div>

            <!-- Box details table -->
            <div class="border-top pt-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-dark-accent"><i class="bi bi-box me-1 text-danger"></i> Box Details</h6>
                <button type="button" @click="addBoxRow" class="btn btn-sm btn-outline-primary rounded-3"><i
                    class="bi bi-plus-lg me-1"></i>Add Box</button>
              </div>
              <div class="table-responsive">
                <table class="table custom-table table-bordered align-middle">
                  <thead class="table-light text-secondary">
                    <tr>
                      <th>BOX NO.</th>
                      <th>ACT. WT</th>
                      <th>LENGTH</th>
                      <th>WIDTH</th>
                      <th>HEIGHT</th>
                      <th>VOL. WT</th>
                      <th>CHG. WT</th>
                      <th>ACTION</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(box, index) in boxRows" :key="box.boxNo">
                      <td class="text-center fw-bold text-muted">{{ box.boxNo }}</td>
                      <td><input v-model.number="box.actWt" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" step="0.1" /></td>
                      <td><input v-model.number="box.length" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" /></td>
                      <td><input v-model.number="box.width" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" /></td>
                      <td><input v-model.number="box.height" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" /></td>
                      <td class="text-center fw-semibold text-dark">{{ box.volWt }}</td>
                      <td class="text-center fw-semibold text-primary">{{ box.chgWt }}</td>
                      <td class="text-center">
                        <button type="button" @click="deleteBoxRow(index)"
                          class="btn btn-sm btn-link text-danger p-0 border-0 bg-transparent"><i
                            class="bi bi-trash fs-5"></i></button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Step 4: Invoice Details -->
          <div v-if="currentStep === 4" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent"><i class="bi bi-file-earmark-text-fill me-2 text-danger"></i>
                Custom Invoice Details</h5>
            </div>

            <div class="table-responsive mb-4">
              <table class="table custom-table table-bordered align-middle">
                <thead class="table-light text-secondary">
                  <tr>
                    <th>BOX</th>
                    <th>DESCRIPTION *</th>
                    <th>HSN CODE</th>
                    <th>HTS CODE</th>
                    <th>UNIT</th>
                    <th>QTY</th>
                    <th>RATE</th>
                    <th>AMOUNT</th>
                    <th>ACTION</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(invItem, idx) in invoiceRows" :key="idx">
                    <td>
                      <select v-model.number="invItem.boxNo" class="form-select form-select-sm">
                        <option v-for="b in boxRows" :key="b.boxNo" :value="b.boxNo">{{ b.boxNo }}</option>
                      </select>
                    </td>
                    <td><input v-model="invItem.description" type="text" class="form-control form-control-sm" /></td>
                    <td><input v-model="invItem.hsnCode" type="text" class="form-control form-control-sm" /></td>
                    <td><input v-model="invItem.htsCode" type="text" class="form-control form-control-sm" /></td>
                    <td><input v-model="invItem.unit" type="text" class="form-control form-control-sm text-center" />
                    </td>
                    <td><input v-model.number="invItem.qty" @input="handleInvoiceItemChange(invItem)" type="number"
                        class="form-control form-control-sm text-center" /></td>
                    <td><input v-model.number="invItem.rate" @input="handleInvoiceItemChange(invItem)" type="number"
                        class="form-control form-control-sm text-center" /></td>
                    <td class="text-center fw-semibold text-dark">{{ invItem.amount }}</td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-2">
                        <button type="button" @click="addInvoiceRow"
                          class="btn btn-sm btn-link text-success p-0 border-0 bg-transparent"><i
                            class="bi bi-plus-circle fs-5"></i></button>
                        <button type="button" @click="deleteInvoiceRow(idx)"
                          class="btn btn-sm btn-link text-danger p-0 border-0 bg-transparent"><i
                            class="bi bi-trash fs-5"></i></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6"><label class="form-label small fw-bold">Description *</label><input
                  v-model="concatenatedDescriptions" type="text" class="form-control bg-light" readonly /></div>
              <div class="col-md-2"><label class="form-label small fw-bold">Value</label><input
                  :value="invoiceTotalValue" type="text" class="form-control bg-light" readonly /></div>
              <div class="col-md-2"><label class="form-label small fw-bold">Currency</label><select
                  v-model="invoiceCurrency" class="form-select">
                  <option>INR</option>
                  <option>USD</option>
                </select></div>
              <div class="col-md-2"><label class="form-label small fw-bold">Total IGST</label><input
                  v-model.number="totalIgst" type="number" class="form-control" /></div>
            </div>

            <!-- Customs clearance -->
            <div class="border-top pt-3">
              <h6 class="fw-bold mb-3 text-dark-accent"><i class="bi bi-shield-lock-fill me-1 text-danger"></i> Customs
                Clearance Detail</h6>
              <div class="row g-3">
                <div class="col-md-4"><label class="form-label small fw-bold">CSB Type *</label><select
                    v-model="customsClearance.csbType" class="form-select">
                    <option>CSB 4</option>
                    <option>CSB 5</option>
                  </select></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Invoice No *</label><input
                    v-model="customsClearance.invoiceNo" type="text" class="form-control" /></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Invoice Date *</label><input
                    v-model="customsClearance.invoiceDate" type="date" class="form-control" /></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Term of Trade *</label><select
                    v-model="customsClearance.termOfTrade" class="form-select">
                    <option>DAP</option>
                    <option>FOB</option>
                  </select></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Reason For Export *</label><select
                    v-model="customsClearance.reasonForExport" class="form-select">
                    <option>Bonafide Gift</option>
                    <option>Commercial</option>
                  </select></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Bond/UT *</label><input
                    v-model="customsClearance.bondUt" type="text" class="form-control" /></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Reference No.</label><input
                    v-model="customsClearance.referenceNo" type="text" class="form-control" /></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Duty Tax</label><select
                    v-model="customsClearance.dutyTax" class="form-select">
                    <option>DDU</option>
                    <option>DDP</option>
                  </select></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Signature Required</label><select
                    v-model="customsClearance.signatureRequired" class="form-select">
                    <option>No</option>
                    <option>Yes</option>
                  </select></div>
              </div>
            </div>
          </div>

          <!-- Step 5: KYC Details -->
          <div v-if="currentStep === 5" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent"><i class="bi bi-shield-check me-2 text-danger"></i> KYC Details
              </h5>
            </div>

            <div class="row g-4 mb-4">
              <div class="col-md-6">
                <div
                  class="upload-box d-flex flex-column align-items-center justify-content-center p-4 border border-2 border-dashed rounded-3 bg-light text-center position-relative">
                  <input type="file" @change="handleFileUpload('kyc', $event)"
                    class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer" />
                  <div v-if="!kycFile" class="d-flex flex-column align-items-center">
                    <div class="upload-icon-circle bg-white shadow-sm mb-3"><i
                        class="bi bi-cloud-upload text-primary fs-3"></i></div>
                    <span class="fw-bold text-dark mb-1">Drag and Drop OR Upload KYC</span>
                  </div>
                  <div v-else class="uploaded-state d-flex flex-column align-items-center">
                    <i class="bi bi-file-earmark-check text-success fs-2 mb-2"></i>
                    <span class="fw-semibold text-dark text-truncate" style="max-width: 200px;">{{ kycFile.name
                      }}</span>
                    <button type="button" @click.stop="removeFile('kyc')"
                      class="btn btn-sm btn-outline-danger mt-2 rounded-pill">Remove</button>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div
                  class="upload-box d-flex flex-column align-items-center justify-content-center p-4 border border-2 border-dashed rounded-3 bg-light text-center position-relative">
                  <input type="file" @change="handleFileUpload('invoice', $event)"
                    class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer" />
                  <div v-if="!invoiceFile" class="d-flex flex-column align-items-center">
                    <div class="upload-icon-circle bg-white shadow-sm mb-3"><i
                        class="bi bi-cloud-upload text-primary fs-3"></i></div>
                    <span class="fw-bold text-dark mb-1">Drag and Drop OR Upload Invoice</span>
                  </div>
                  <div v-else class="uploaded-state d-flex flex-column align-items-center">
                    <i class="bi bi-file-earmark-check text-success fs-2 mb-2"></i>
                    <span class="fw-semibold text-dark text-truncate" style="max-width: 200px;">{{ invoiceFile.name
                      }}</span>
                    <button type="button" @click.stop="removeFile('invoice')"
                      class="btn btn-sm btn-outline-danger mt-2 rounded-pill">Remove</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- 
            <div class="custom-card p-3 mb-4 last-mile-banner">
              <div class="form-check form-switch ps-0 d-flex align-items-center gap-3">
                <input v-model="generateLabelToggle" class="form-check-input ms-0" type="checkbox" role="switch"
                  id="lastMileSwitch" style="width: 2.8em; height: 1.4em; cursor: pointer;" />
                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="lastMileSwitch">
                  Click here to generate your last-mile delivery label.
                </label>
              </div>
            </div> -->

            <!-- <div class="d-flex justify-content-start gap-3 flex-wrap">
              <button @click="triggerCreateLabel"
                class="btn btn-create-label d-flex align-items-center gap-2 px-4 py-2"><i
                  class="bi bi-file-earmark-check text-white"></i>Create Label</button>
              <button @click="triggerDraftBooking"
                class="btn btn-draft-booking d-flex align-items-center gap-2 px-4 py-2"><i
                  class="bi bi-envelope text-white"></i>Draft Booking</button>
              <button @click="triggerCancel" class="btn btn-cancel-booking d-flex align-items-center gap-2 px-4 py-2"><i
                  class="bi bi-x-circle text-white"></i>Cancel</button>
            </div> -->
          </div>

          <!-- Footer Buttons -->
          <div class="d-flex justify-content-between align-items-center border-top pt-4 mt-4">
            <button v-if="currentStep > 1" @click="prevStep" type="button"
              class="btn btn-outline-secondary px-4 py-2 rounded-3">&larr; Previous</button>
            <div v-else></div>
            <button v-if="currentStep < 5" @click="nextStep" type="button"
              class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold">Next &rarr;</button>
            <button v-if="currentStep >= 5" type="button" class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold">Book
              Now &rarr;</button>
          </div>
        </div>
      </div>
    </div>

    <!-- History Lists -->
    <div v-if="activeTab === 'drafts'" class="custom-card p-4 bg-white animate-fade">
      <h5 class="fw-bold mb-3 text-dark-accent">Draft Bookings History</h5>
      <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>AWB</th>
              <th>Date</th>
              <th>Customer</th>
              <th>Consignee</th>
              <th>Service</th>
              <th>Weight</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="draft in draftsList" :key="draft.id">
              <td>
                <div class="fw-bold">{{ draft.awbNo }}</div>
              </td>
              <td>{{ draft.date }}</td>
              <td>{{ draft.customer }}</td>
              <td>{{ draft.consignee }} ({{ draft.destination }})</td>
              <td>{{ draft.service }}</td>
              <td>{{ draft.weight }} kg</td>
              <td class="text-end"><button @click="loadDraft(draft)"
                  class="btn btn-sm btn-primary-gradient rounded-3 px-3 py-1">Load</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="activeTab === 'shipments'" class="custom-card p-4 bg-white animate-fade">
      <h5 class="fw-bold mb-3 text-dark-accent">Shipments & AWB List</h5>
      <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>AWB</th>
              <th>Date</th>
              <th>Sender</th>
              <th>Consignee</th>
              <th>Service</th>
              <th>Weight</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="shipment in shipmentsList" :key="shipment.id">
              <td>
                <div class="fw-bold text-primary">{{ shipment.awbNo }}</div>
              </td>
              <td>{{ shipment.date }}</td>
              <td>{{ shipment.sender }}</td>
              <td>{{ shipment.consignee }} ({{ shipment.destination }})</td>
              <td>{{ shipment.service }}</td>
              <td>{{ shipment.weight }} kg</td>
              <td><span class="badge-status"
                  :class="shipment.status === 'Label Generated' ? 'badge-delivered' : 'badge-in-transit'">{{
                    shipment.status }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Wallet recharge modal -->
    <div v-if="showRechargeModal" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.6); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
          <div class="modal-header bg-danger text-white p-3 px-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 me-2"></i> Recharge Wallet</h5>
            <button type="button" class="btn-close btn-close-white" @click="showRechargeModal = false"></button>
          </div>
          <form @submit.prevent="processRecharge">
            <div class="modal-body p-4">
              <label class="form-label small fw-bold">Enter Amount (₹)</label>
              <input v-model.number="rechargeAmount" type="number"
                class="form-control fw-bold fs-5 text-center text-dark" min="10" max="10000" required />
            </div>
            <div class="modal-footer bg-light p-3 px-4 justify-content-between">
              <button type="button" class="btn btn-outline-secondary rounded-3"
                @click="showRechargeModal = false">Cancel</button>
              <button type="submit" class="btn btn-recharge px-4 py-2">Recharge</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.text-dark-accent {
  color: #2b2b2b;
}

.form-control,
.form-select {
  border-radius: 8px;
  border: 1px solid #dcdcdc;
  padding: 0.55rem 0.75rem;
  font-size: 0.9rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
}

.header-panel {
  border: 1px solid #f1f5f9;
  border-radius: 12px;
  background: #ffffff;
}

.header-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #8a8a8a !important;
}

.search-group {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  border-radius: 8px;
  overflow: hidden;
}

.btn-search-custom {
  background-color: #e02229;
  color: #ffffff;
  border: none;
  padding-left: 1.25rem;
  padding-right: 1.25rem;
}

.btn-recharge {
  background-color: #e02229;
  color: #ffffff;
  border: none;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  padding: 0.5rem 1rem;
}

.balance-badge {
  background-color: #a3181d;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.98rem;
  border-radius: 8px;
  height: 38px;
  min-width: 100px;
}

.icon-btn {
  background-color: #f7f9fa;
  border: 1px solid #e9ecef;
}

.profile-avatar-circle {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background-color: #6f42c1;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.88rem;
}

.nav-custom-pills {
  background-color: #ffffff;
  border: 1px solid #f0f0f0;
}

.nav-link-pill {
  border: none;
  background: transparent;
  color: #9b7a82;
  font-weight: 700;
  font-size: 0.88rem;
  padding: 0.5rem 1.25rem;
  border-radius: 6px;
}

.nav-link-pill:hover {
  color: #e02229;
  background-color: #faf3f5;
}

.nav-link-pill.active {
  background-color: #e02229;
  color: #ffffff;
}

.stepper-vertical {
  position: relative;
}

.stepper-vertical::before {
  content: '';
  position: absolute;
  top: 10px;
  left: 18px;
  bottom: 10px;
  width: 2px;
  background-color: #e9ecef;
}

.step-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: #e9ecef;
  color: #adb5bd;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  font-weight: 600;
}

.step-item.active .step-circle {
  background-color: #e02229;
  color: #ffffff;
}

.step-item.completed .step-circle {
  background-color: #6c757d;
  color: #ffffff;
}

.step-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #6c757d;
}

.step-item.active .step-title {
  color: #e02229;
}

.save-link:hover {
  text-decoration: underline !important;
}

.btn-next-custom {
  background-color: #e02229;
  color: #ffffff;
  border: none;
}

.upload-box {
  min-height: 140px;
  border-color: #cfd8dc !important;
}

.last-mile-banner {
  background-color: #e8f5e9;
  border: 1px solid #c8e6c9;
}

.btn-create-label {
  background-color: #2ecb71;
  color: #ffffff;
  border: none;
  border-radius: 8px;
}

.btn-draft-booking {
  background-color: #8ddca4;
  color: #ffffff;
  border: none;
  border-radius: 8px;
}

.btn-cancel-booking {
  background-color: #ff8b94;
  color: #ffffff;
  border: none;
  border-radius: 8px;
}

.animate-fade {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}
</style>
