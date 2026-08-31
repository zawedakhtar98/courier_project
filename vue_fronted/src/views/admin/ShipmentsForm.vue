<script setup>
import { ref, computed, watch } from 'vue'

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
    },
    {
        id: 'DRAFT-002',
        awbNo: 'EXP-AWB-20919',
        customer: 'Apex Global Logistics',
        consignee: 'Jane Doe Logistics',
        destination: 'GERMANY',
        service: 'DHL_EXPRESS',
        weight: 2.50,
        pcs: 1,
        date: '17-08-2026',
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
    },
    {
        id: 'SHIP-002',
        awbNo: 'EXP-AWB-10030',
        sender: 'TechGiant Corp',
        consignee: 'Marcus Miller',
        destination: 'UNITED STATES',
        service: 'FEDEX_PRIORITY',
        weight: 0.50,
        pcs: 1,
        date: '16-08-2026',
        status: 'In Transit',
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

// Search utility for AWB Search Bar
const handleAwbSearch = () => {
    if (!searchAwbQuery.value) {
        showAlert('Please enter an AWB number to search', 'danger')
        return
    }

    // Look in shipments
    const foundShipment = shipmentsList.value.find(
        s => s.awbNo.toLowerCase().includes(searchAwbQuery.value.toLowerCase())
    )
    if (foundShipment) {
        showAlert(`Found Shipment: AWB ${foundShipment.awbNo} (${foundShipment.status}) to ${foundShipment.destination}`)
        activeTab.value = 'shipments'
        return
    }

    // Look in drafts
    const foundDraft = draftsList.value.find(
        d => d.awbNo.toLowerCase().includes(searchAwbQuery.value.toLowerCase())
    )
    if (foundDraft) {
        showAlert(`Found Draft: AWB ${foundDraft.awbNo} for Customer ${foundDraft.customer}`)
        activeTab.value = 'drafts'
        return
    }

    showAlert(`AWB Number "${searchAwbQuery.value}" not found.`, 'danger')
}

// Recharge action
const processRecharge = () => {
    if (rechargeAmount.value <= 0) return
    walletBalance.value += rechargeAmount.value
    showRechargeModal.value = false
    showAlert(`Recharged ₹${rechargeAmount.value.toFixed(2)} successfully. New Balance: ₹${walletBalance.value.toFixed(2)}`)
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
    city: 'SINGAPORE', // Mocked user inputs matching reference
    state: 'SINGAPORE',
    telephone: '+6580708618',
    email: 'celine.chan@sofinagroup.com',
    vatTaxId: ''
})

// Watch destination to auto-update ISO Code
const countryIsoMapping = {
    'AUSTRIA': 'AT',
    'SINGAPORE': 'SG',
    'UNITED STATES': 'US',
    'GERMANY': 'DE',
    'CANADA': 'CA',
    'UNITED KINGDOM': 'GB',
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

// Watch service type to auto-fill service codes/vendors
const serviceInfoMapping = {
    'UPS_SAVER': { code: 'S102', vendor: 'UPS' },
    'DHL_EXPRESS': { code: 'S101', vendor: 'DHL' },
    'FEDEX_PRIORITY': { code: 'S103', vendor: 'FEDEX' },
    'ARAMEX_WORLDWIDE': { code: 'S104', vendor: 'ARAMEX' }
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
    { boxNo: 1, actWt: 2, length: 30, width: 30, height: 50, volWt: 9.00, chgWt: 9.00 },
    { boxNo: 2, actWt: 2, length: 40, width: 40, height: 60, volWt: 19.20, chgWt: 19.50 },
    { boxNo: 3, actWt: 2, length: 40, width: 40, height: 60, volWt: 19.20, chgWt: 19.50 }
])

// Calculate specific box row volumetric and chargeable weights
const recalculateBoxRow = (row) => {
    // Volumetric weight: (L * W * H) / 5000
    if (row.length && row.width && row.height) {
        row.volWt = parseFloat(((row.length * row.width * row.height) / 5000).toFixed(2))
    } else {
        row.volWt = 0
    }

    // Chargeable weight: Max(Act Wt, Vol Wt) rounded up to nearest 0.5 kg
    const baseWeight = Math.max(row.actWt || 0, row.volWt)
    row.chgWt = parseFloat((Math.ceil(baseWeight * 2) / 2).toFixed(2))
}

const addBoxRow = () => {
    const newNo = boxRows.value.length > 0 ? Math.max(...boxRows.value.map(b => b.boxNo)) + 1 : 1
    const newRow = {
        boxNo: newNo,
        actWt: 2,
        length: 30,
        width: 30,
        height: 50,
        volWt: 9.00,
        chgWt: 9.00
    }
    recalculateBoxRow(newRow)
    boxRows.value.push(newRow)
    showAlert(`Added Box No. ${newNo}`)
}

const deleteBoxRow = (index) => {
    if (boxRows.value.length <= 1) {
        showAlert('At least one box is required.', 'danger')
        return
    }
    const row = boxRows.value[index]
    if (row) {
        const removedNo = row.boxNo
        boxRows.value.splice(index, 1)
        showAlert(`Deleted Box No. ${removedNo}`, 'danger')
    }
}

// Watchers on box values to recalculate
const handleBoxFieldChange = (row) => {
    recalculateBoxRow(row)
}

// Computed Totals for Step 3
const totalPcs = computed(() => boxRows.value.length)
const totalActualWeight = computed(() => {
    const sum = boxRows.value.reduce((acc, row) => acc + (row.actWt || 0), 0)
    return parseFloat(sum.toFixed(2))
})
const totalVolWeight = computed(() => {
    const sum = boxRows.value.reduce((acc, row) => acc + (row.volWt || 0), 0)
    return parseFloat(sum.toFixed(2))
})
const totalChargeableWeight = computed(() => {
    const sum = boxRows.value.reduce((acc, row) => acc + (row.chgWt || 0), 0)
    return parseFloat(sum.toFixed(2))
})


// Step 4: Custom Invoice Details
const invoiceRows = ref([
    { boxNo: 1, description: 'TESI HAMMER', hsnCode: '82071900', htsCode: '82071900', unit: 'PCS', qty: 1, rate: 22, amount: 22 },
    { boxNo: 1, description: 'TESI HAMMER', hsnCode: '82071900', htsCode: '82071900', unit: 'PCS', qty: 1, rate: 22, amount: 22 },
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
    showAlert('Added new custom invoice item')
}

const deleteInvoiceRow = (index) => {
    if (invoiceRows.value.length <= 1) {
        showAlert('At least one invoice item is required.', 'danger')
        return
    }
    invoiceRows.value.splice(index, 1)
    showAlert('Removed invoice item', 'danger')
}

const handleInvoiceItemChange = (row) => {
    row.amount = parseFloat(((row.qty || 0) * (row.rate || 0)).toFixed(2))
}

const invoiceTotalValue = computed(() => {
    const sum = invoiceRows.value.reduce((acc, r) => acc + (r.amount || 0), 0)
    return parseFloat(sum.toFixed(2))
})

// Auto-derived full description field
const concatenatedDescriptions = computed({
    get: () => {
        return invoiceRows.value.map(r => r.description).filter(Boolean).join('; ')
    },
    set: (val) => {
        // Allows user override if required, but default binds to computed list
    }
})

// ----------------------------------------------------
// Navigation / Form Actions
// ----------------------------------------------------
const nextStep = () => {
    if (currentStep.value < 5) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

const jumpToStep = (stepNum) => {
    // Let user jump to steps if they want, but typically step-by-step
    if (stepNum <= currentStep.value || stepNum === currentStep.value + 1) {
        currentStep.value = stepNum
    }
}

const saveDetailsDraft = () => {
    showAlert(`Details saved for Step ${currentStep.value}!`)
}

// Drag & Drop Mock handlers
const handleFileUpload = (type, event) => {
    const target = event.target
    if (target.files && target.files.length > 0) {
        const file = target.files[0]
        if (file) {
            const sizeStr = (file.size / 1024).toFixed(1) + ' KB'
            if (type === 'kyc') {
                kycFile.value = { name: file.name, size: sizeStr }
                showAlert('KYC Document uploaded successfully')
            } else {
                invoiceFile.value = { name: file.name, size: sizeStr }
                showAlert('Custom Invoice uploaded successfully')
            }
        }
    }
}

const removeFile = (type) => {
    if (type === 'kyc') {
        kycFile.value = null
    } else {
        invoiceFile.value = null
    }
    showAlert('Document removed', 'danger')
}

// Step 5 Actions
const triggerCreateLabel = () => {
    // Check wallet balance
    const bookingFee = 200.00
    if (walletBalance.value < bookingFee) {
        showAlert(`Insufficient credit balance. Cost of booking is ₹${bookingFee}. Recharge to continue.`, 'danger')
        return
    }

    // Deduct fee
    walletBalance.value = parseFloat((walletBalance.value - bookingFee).toFixed(2))

    const newAwb = 'EXP-AWB-' + Math.floor(10000 + Math.random() * 90000)

    const record = {
        id: 'SHIP-' + Math.floor(100 + Math.random() * 900),
        awbNo: newAwb,
        sender: shipperForm.value.companyName || shipperForm.value.contactPerson,
        consignee: consigneeForm.value.companyName || consigneeForm.value.contactPerson,
        destination: consigneeForm.value.destination,
        service: serviceForm.value.service,
        weight: totalChargeableWeight.value,
        pcs: totalPcs.value,
        date: new Date().toLocaleDateString('en-GB').replace(/\//g, '-'),
        status: generateLabelToggle.value ? 'Label Generated' : 'Pending Dispatch',
        labelGenerated: generateLabelToggle.value
    }

    shipmentsList.value.unshift(record)
    showAlert(`Label Created successfully! AWB: ${newAwb}. Fee: ₹${bookingFee} deducted from wallet.`)

    // Reset form and jump back
    resetFormState()
    activeTab.value = 'shipments'
}

const triggerDraftBooking = () => {
    const newAwb = 'EXP-AWB-' + Math.floor(10000 + Math.random() * 90000)

    const record = {
        id: 'DRAFT-' + Math.floor(100 + Math.random() * 900),
        awbNo: newAwb,
        customer: shipperForm.value.companyName || shipperForm.value.contactPerson,
        consignee: consigneeForm.value.companyName || consigneeForm.value.contactPerson,
        destination: consigneeForm.value.destination,
        service: serviceForm.value.service,
        weight: totalChargeableWeight.value,
        pcs: totalPcs.value,
        date: new Date().toLocaleDateString('en-GB').replace(/\//g, '-'),
        status: 'Draft'
    }

    draftsList.value.unshift(record)
    showAlert(`Draft Saved successfully! AWB: ${newAwb}`)

    // Reset form and jump
    resetFormState()
    activeTab.value = 'drafts'
}

const triggerCancel = () => {
    resetFormState()
    showAlert('Booking process cancelled and reset', 'danger')
}

const resetFormState = () => {
    currentStep.value = 1
    kycFile.value = null
    invoiceFile.value = null
    // Keep box items default
    boxRows.value = [
        { boxNo: 1, actWt: 2, length: 30, width: 30, height: 50, volWt: 9.00, chgWt: 9.00 },
        { boxNo: 2, actWt: 2, length: 40, width: 40, height: 60, volWt: 19.20, chgWt: 19.50 },
        { boxNo: 3, actWt: 2, length: 40, width: 40, height: 60, volWt: 19.20, chgWt: 19.50 }
    ]
    invoiceRows.value = [
        { boxNo: 1, description: 'TESI HAMMER', hsnCode: '82071900', htsCode: '82071900', unit: 'PCS', qty: 1, rate: 22, amount: 22 },
        { boxNo: 1, description: 'TESI HAMMER', hsnCode: '82071900', htsCode: '82071900', unit: 'PCS', qty: 1, rate: 22, amount: 22 },
        { boxNo: 1, description: 'TESI HAMMER', hsnCode: '82071900', htsCode: '82071900', unit: 'PCS', qty: 1, rate: 22, amount: 22 }
    ]
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
                class="alert position-fixed top-0 start-50 translate-middle-x mt-3 z-3 shadow-lg rounded-3 border-0 px-4 py-3"
                :class="alertMessage.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
                style="width: 90%; max-width: 480px;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi"
                        :class="alertMessage.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
                    <div class="fw-semibold text-break flex-fill">{{ alertMessage.text }}</div>
                    <button type="button" class="btn-close btn-close-white ms-auto"
                        @click="alertMessage = null"></button>
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
                                <span class="step-title">Service Details</span>
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

                        <!-- Step 5 Indicator -->
                        <div @click="jumpToStep(5)" class="step-item d-flex align-items-center gap-3 cursor-pointer"
                            :class="{ active: currentStep === 5 }">
                            <div class="step-circle">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="step-label d-flex flex-column">
                                <span class="step-title">KYC Details</span>
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
                            <a href="#" @click.prevent="saveDetailsDraft"
                                class="save-link text-success fw-bold text-decoration-none">Save Details</a>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Customer Account <span
                                        class="text-danger">*</span></label>
                                <input v-model="shipperForm.customerAccount" type="text" class="form-control" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Company Name <span
                                        class="text-danger">*</span></label>
                                <input v-model="shipperForm.companyName" type="text" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Contact Person <span
                                        class="text-danger">*</span></label>
                                <input v-model="shipperForm.contactPerson" type="text" class="form-control" />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Address Line 1 <span
                                        class="text-danger">*</span></label>
                                <input v-model="shipperForm.address1" type="text" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Address Line 2 <span
                                        class="text-danger">*</span></label>
                                <input v-model="shipperForm.address2" type="text" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Address Line 3</label>
                                <input v-model="shipperForm.address3" type="text" class="form-control" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Pincode <span
                                        class="text-danger">*</span></label>
                                <input v-model="shipperForm.pincode" type="text" class="form-control" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">City <span class="text-danger">*</span></label>
                                <input v-model="shipperForm.city" type="text" class="form-control" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">State <span class="text-danger">*</span></label>
                                <input v-model="shipperForm.state" type="text" class="form-control" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Telephone <span
                                        class="text-danger">*</span></label>
                                <input v-model="shipperForm.telephone" type="text" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">E-mail Id</label>
                                <input v-model="shipperForm.email" type="email" class="form-control" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">KYC Type <span
                                        class="text-danger">*</span></label>
                                <select v-model="shipperForm.kycType" class="form-select">
                                    <option>Aadhaar Number</option>
                                    <option>PAN Card</option>
                                    <option>GSTIN</option>
                                    <option>Passport</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">KYC No. <span
                                        class="text-danger">*</span></label>
                                <input v-model="shipperForm.kycNo" type="text" class="form-control" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: CONSIGNEE DETAILS -->
                    <div v-if="currentStep === 2" class="step-content-section animate-fade">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h5 class="fw-bold mb-0 text-dark-accent">
                                <i class="bi bi-building me-2 text-danger"></i> Consignee Details
                            </h5>
                            <a href="#" @click.prevent="saveDetailsDraft"
                                class="save-link text-success fw-bold text-decoration-none">Save Details</a>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Destination <span
                                        class="text-danger">*</span></label>
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
                                <label class="form-label small fw-bold">ISO Code <span
                                        class="text-danger">*</span></label>
                                <input v-model="consigneeForm.isoCode" type="text" class="form-control bg-light"
                                    readonly />
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Consignee Type <span
                                        class="text-danger">*</span></label>
                                <select v-model="consigneeForm.consigneeType" class="form-select">
                                    <option>Business</option>
                                    <option>Individual</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Company Name <span
                                        class="text-danger">*</span></label>
                                <input v-model="consigneeForm.companyName" type="text" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Contact Person <span
                                        class="text-danger">*</span></label>
                                <input v-model="consigneeForm.contactPerson" type="text" class="form-control" />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Address Line 1 <span
                                        class="text-danger">*</span></label>
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
                                <label class="form-label small fw-bold">Zipcode <span
                                        class="text-danger">*</span></label>
                                <input v-model="consigneeForm.zipcode" type="text" class="form-control" />
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
                                <label class="form-label small fw-bold">Telephone <span
                                        class="text-danger">*</span></label>
                                <input v-model="consigneeForm.telephone" type="text" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">E-mail Id</label>
                                <input v-model="consigneeForm.email" type="email" class="form-control" />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">VAT/TAX Id</label>
                                <input v-model="consigneeForm.vatTaxId" type="text" class="form-control"
                                    placeholder="Optional" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: SERVICE DETAILS -->
                    <div v-if="currentStep === 3" class="step-content-section animate-fade">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h5 class="fw-bold mb-0 text-dark-accent">
                                <i class="bi bi-gear-fill me-2 text-danger"></i> Services Details
                            </h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Service <span
                                        class="text-danger">*</span></label>
                                <select v-model="serviceForm.service" class="form-select">
                                    <option value="UPS_SAVER">UPS_SAVER</option>
                                    <option value="DHL_EXPRESS">DHL_EXPRESS</option>
                                    <option value="FEDEX_PRIORITY">FEDEX_PRIORITY</option>
                                    <option value="ARAMEX_WORLDWIDE">ARAMEX_WORLDWIDE</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Service Code <span
                                        class="text-danger">*</span></label>
                                <input v-model="serviceForm.serviceCode" type="text" class="form-control bg-light"
                                    readonly />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Vendor <span
                                        class="text-danger">*</span></label>
                                <input v-model="serviceForm.vendor" type="text" class="form-control bg-light"
                                    readonly />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Vendor Code <span
                                        class="text-danger">*</span></label>
                                <input v-model="serviceForm.vendorCode" type="text" class="form-control bg-light"
                                    readonly />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Goods Type <span
                                        class="text-danger">*</span></label>
                                <select v-model="serviceForm.goodsType" class="form-select">
                                    <option>NDox</option>
                                    <option>Dox</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Package Type <span
                                        class="text-danger">*</span></label>
                                <select v-model="serviceForm.packageType" class="form-select">
                                    <option>PACKAGE</option>
                                    <option>BOX</option>
                                    <option>ENVELOPE</option>
                                </select>
                            </div>

                            <!-- Readonly summary outputs -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Total Pcs <span
                                        class="text-danger">*</span></label>
                                <input :value="totalPcs" type="number" class="form-control bg-light fw-bold" readonly />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Actual Weight (kg) <span
                                        class="text-danger">*</span></label>
                                <input :value="totalActualWeight.toFixed(2)" type="text"
                                    class="form-control bg-light fw-bold" readonly />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Vol Weight <span
                                        class="text-danger">*</span></label>
                                <input :value="totalVolWeight.toFixed(2)" type="text"
                                    class="form-control bg-light fw-bold" readonly />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Chargeable Weight (kg) <span
                                        class="text-danger">*</span></label>
                                <input :value="totalChargeableWeight.toFixed(2)" type="text"
                                    class="form-control bg-light fw-bold" readonly />
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
                                                <input v-model.number="box.actWt" @input="handleBoxFieldChange(box)"
                                                    type="number" class="form-control form-control-sm text-center"
                                                    min="0" step="0.1" />
                                            </td>
                                            <td>
                                                <input v-model.number="box.length" @input="handleBoxFieldChange(box)"
                                                    type="number" class="form-control form-control-sm text-center"
                                                    min="0" />
                                            </td>
                                            <td>
                                                <input v-model.number="box.width" @input="handleBoxFieldChange(box)"
                                                    type="number" class="form-control form-control-sm text-center"
                                                    min="0" />
                                            </td>
                                            <td>
                                                <input v-model.number="box.height" @input="handleBoxFieldChange(box)"
                                                    type="number" class="form-control form-control-sm text-center"
                                                    min="0" />
                                            </td>
                                            <td class="text-center fw-semibold text-dark">{{ box.volWt.toFixed(2) }}
                                            </td>
                                            <td class="text-center fw-semibold text-primary">{{ box.chgWt.toFixed(2) }}
                                            </td>
                                            <td class="text-center">
                                                <button type="button" @click="deleteBoxRow(index)"
                                                    class="btn btn-sm btn-link text-danger p-0 border-0 bg-transparent"
                                                    title="Delete Row">
                                                    <i class="bi bi-trash fs-5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: INVOICE DETAILS -->
                    <div v-if="currentStep === 4" class="step-content-section animate-fade">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h5 class="fw-bold mb-0 text-dark-accent">
                                <i class="bi bi-file-earmark-text-fill me-2 text-danger"></i> Custom Invoice Details
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
                                        <th>HTS CODE</th>
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
                                            <select v-model.number="invItem.boxNo"
                                                class="form-select form-select-sm text-center">
                                                <option v-for="b in boxRows" :key="b.boxNo" :value="b.boxNo">{{ b.boxNo
                                                    }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input v-model="invItem.description" type="text"
                                                class="form-control form-control-sm" />
                                        </td>
                                        <td>
                                            <input v-model="invItem.hsnCode" type="text"
                                                class="form-control form-control-sm" />
                                        </td>
                                        <td>
                                            <input v-model="invItem.htsCode" type="text"
                                                class="form-control form-control-sm" />
                                        </td>
                                        <td>
                                            <input v-model="invItem.unit" type="text"
                                                class="form-control form-control-sm text-center" />
                                        </td>
                                        <td>
                                            <input v-model.number="invItem.qty"
                                                @input="handleInvoiceItemChange(invItem)" type="number"
                                                class="form-control form-control-sm text-center" min="1" />
                                        </td>
                                        <td>
                                            <input v-model.number="invItem.rate"
                                                @input="handleInvoiceItemChange(invItem)" type="number"
                                                class="form-control form-control-sm text-center" min="0" />
                                        </td>
                                        <td class="text-center fw-semibold text-dark">{{ invItem.amount.toFixed(2) }}
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="button" @click="addInvoiceRow"
                                                    class="btn btn-sm btn-link text-success p-0 border-0 bg-transparent"
                                                    title="Add Row">
                                                    <i class="bi bi-plus-circle fs-5"></i>
                                                </button>
                                                <button type="button" @click="deleteInvoiceRow(idx)"
                                                    class="btn btn-sm btn-link text-danger p-0 border-0 bg-transparent"
                                                    title="Delete Row">
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
                                <label class="form-label small fw-bold">Description <span
                                        class="text-danger">*</span></label>
                                <input v-model="concatenatedDescriptions" type="text" class="form-control bg-light"
                                    readonly />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Value <span class="text-danger">*</span></label>
                                <input :value="invoiceTotalValue.toFixed(2)" type="text"
                                    class="form-control bg-light fw-bold" readonly />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Currency <span
                                        class="text-danger">*</span></label>
                                <select v-model="invoiceCurrency" class="form-select">
                                    <option value="INR">INR</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Total IGST <span
                                        class="text-danger">*</span></label>
                                <input v-model.number="totalIgst" type="number" class="form-control" />
                            </div>
                        </div>

                        <!-- Customs Clearance Section -->
                        <div class="border-top pt-3">
                            <h6 class="fw-bold mb-3 text-dark-accent">
                                <i class="bi bi-shield-lock-fill me-1 text-danger"></i> Customs Clearance Detail
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">CSB Type <span
                                            class="text-danger">*</span></label>
                                    <select v-model="customsClearance.csbType" class="form-select">
                                        <option>CSB 4</option>
                                        <option>CSB 5</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Invoice No <span
                                            class="text-danger">*</span></label>
                                    <input v-model="customsClearance.invoiceNo" type="text" class="form-control" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Invoice Date <span
                                            class="text-danger">*</span></label>
                                    <input v-model="customsClearance.invoiceDate" type="date" class="form-control" />
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Term of Trade <span
                                            class="text-danger">*</span></label>
                                    <select v-model="customsClearance.termOfTrade" class="form-select">
                                        <option>DAP</option>
                                        <option>FOB</option>
                                        <option>CIF</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Reason For Export <span
                                            class="text-danger">*</span></label>
                                    <select v-model="customsClearance.reasonForExport" class="form-select">
                                        <option>Bonafide Gift</option>
                                        <option>Commercial Sample</option>
                                        <option>Merchandise</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Bond/UT <span
                                            class="text-danger">*</span></label>
                                    <input v-model="customsClearance.bondUt" type="text" class="form-control" />
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Reference No. <span
                                            class="text-danger">*</span></label>
                                    <input v-model="customsClearance.referenceNo" type="text" class="form-control" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Duty Tax <span
                                            class="text-danger">*</span></label>
                                    <select v-model="customsClearance.dutyTax" class="form-select">
                                        <option>DDU</option>
                                        <option>DDP</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Signature Required.</label>
                                    <select v-model="customsClearance.signatureRequired" class="form-select">
                                        <option>No</option>
                                        <option>Yes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: KYC DETAILS -->
                    <div v-if="currentStep === 5" class="step-content-section animate-fade">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h5 class="fw-bold mb-0 text-dark-accent">
                                <i class="bi bi-shield-check me-2 text-danger"></i> KYC Details
                            </h5>
                        </div>

                        <!-- Upload Zones Grid -->
                        <div class="row g-4 mb-4">
                            <!-- KYC Document Upload -->
                            <div class="col-md-6">
                                <div
                                    class="upload-box d-flex flex-column align-items-center justify-content-center p-4 border border-2 border-dashed rounded-3 bg-light text-center position-relative">
                                    <input type="file" @change="handleFileUpload('kyc', $event)"
                                        class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer"
                                        accept=".pdf,.png,.jpg,.jpeg" />

                                    <div v-if="!kycFile" class="d-flex flex-column align-items-center">
                                        <div class="upload-icon-circle bg-white shadow-sm mb-3">
                                            <i class="bi bi-cloud-upload text-primary fs-3"></i>
                                        </div>
                                        <span class="fw-bold text-dark mb-1">Drag and Drop OR Upload KYC</span>
                                        <span class="text-muted small">Supports PDF, PNG, JPG</span>
                                    </div>
                                    <div v-else class="uploaded-state d-flex flex-column align-items-center">
                                        <i class="bi bi-file-earmark-check text-success fs-2 mb-2"></i>
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

                            <!-- Invoice Document Upload -->
                            <div class="col-md-6">
                                <div
                                    class="upload-box d-flex flex-column align-items-center justify-content-center p-4 border border-2 border-dashed rounded-3 bg-light text-center position-relative">
                                    <input type="file" @change="handleFileUpload('invoice', $event)"
                                        class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer"
                                        accept=".pdf,.png,.jpg,.jpeg" />

                                    <div v-if="!invoiceFile" class="d-flex flex-column align-items-center">
                                        <div class="upload-icon-circle bg-white shadow-sm mb-3">
                                            <i class="bi bi-cloud-upload text-primary fs-3"></i>
                                        </div>
                                        <span class="fw-bold text-dark mb-1">Drag and Drop OR Upload Invoice</span>
                                        <span class="text-muted small">Supports PDF, PNG, JPG</span>
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

                        <!-- Last Mile Toggle Switch -->
                        <!-- <div class="custom-card p-3 mb-4 last-mile-banner">
              <div class="form-check form-switch ps-0 d-flex align-items-center gap-3">
                <input v-model="generateLabelToggle" class="form-check-input ms-0" type="checkbox" role="switch"
                  id="lastMileSwitch" style="width: 2.8em; height: 1.4em; cursor: pointer;" />
                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="lastMileSwitch">
                  Click here to generate your last-mile delivery label.
                </label>
              </div>
            </div> -->

                        <!-- Booking Actions Panel -->
                        <!-- <div class="d-flex justify-content-start gap-3 flex-wrap">
              <button @click="triggerCreateLabel"
                class="btn btn-create-label d-flex align-items-center gap-2 px-4 py-2 shadow-sm fw-bold">
                <i class="bi bi-file-earmark-check text-white"></i>
                Create Label
              </button>
              <button @click="triggerDraftBooking"
                class="btn btn-draft-booking d-flex align-items-center gap-2 px-4 py-2 shadow-sm fw-bold">
                <i class="bi bi-envelope text-white"></i>
                Draft Booking
              </button>
              <button @click="triggerCancel"
                class="btn btn-cancel-booking d-flex align-items-center gap-2 px-4 py-2 shadow-sm fw-bold">
                <i class="bi bi-x-circle text-white"></i>
                Cancel
              </button>
            </div> -->
                    </div>

                    <!-- Bottom Navigation Stepper Buttons -->
                    <div class="d-flex justify-content-between align-items-center border-top pt-4 mt-4">
                        <button v-if="currentStep > 1" @click="prevStep" type="button"
                            class="btn btn-outline-secondary px-4 py-2 rounded-3">
                            &larr; Previous
                        </button>
                        <div v-else></div> <!-- spacer -->

                        <button v-if="currentStep < 5" @click="nextStep" type="button"
                            class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold">
                            Next &rarr;
                        </button>
                        <button v-if="currentStep >= 5" @click="nextStep" type="button"
                            class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold">
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
                                <button @click="loadDraft(draft)"
                                    class="btn btn-sm btn-primary-gradient rounded-3 px-3 py-1">
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
                                <span
                                    class="badge bg-primary-subtle text-primary border border-primary border-opacity-10">{{
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
                                <button class="btn btn-sm btn-outline-secondary rounded-3 px-2 py-1"
                                    title="Print Waybill">
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
                        <button type="button" class="btn-close btn-close-white"
                            @click="showRechargeModal = false"></button>
                    </div>
                    <form @submit.prevent="processRecharge">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Enter Amount (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">₹</span>
                                    <input v-model.number="rechargeAmount" type="number"
                                        class="form-control fw-bold fs-5 text-center" min="10" max="10000" required />
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
                            <button type="button" class="btn btn-outline-secondary rounded-3"
                                @click="showRechargeModal = false">
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

<style scoped>
/* ----------------------------------------------------
   General Design Accents & Custom Colors
   ---------------------------------------------------- */
.text-dark-accent {
    color: #2b2b2b;
}

/* Form inputs & selects styled round */
.form-control,
.form-select {
    border-radius: 8px;
    border: 1px solid #dcdcdc;
    padding: 0.55rem 0.75rem;
    font-size: 0.9rem;
    transition: all 0.2s ease-in-out;
}

.form-control:focus,
.form-select:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
}

.form-control-sm,
.form-select-sm {
    padding: 0.35rem 0.5rem;
    font-size: 0.82rem;
}

/* Header Panel Customizations */
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

/* AWB Search input custom button */
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
    font-size: 0.9rem;
    transition: opacity 0.15s ease;
}

.btn-search-custom:hover {
    opacity: 0.9;
    color: #ffffff;
}

/* Wallet Recharge & Credit Indicators */
.btn-recharge {
    background-color: #e02229;
    color: #ffffff;
    border: none;
    font-size: 0.9rem;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    transition: opacity 0.15s ease;
}

.btn-recharge:hover {
    opacity: 0.9;
    color: #ffffff;
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

.icon-btn:hover {
    background-color: #e9ecef;
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
    border: 2px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

/* Pills Tab Custom Styling */
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
    transition: all 0.2s ease;
}

.nav-link-pill:hover {
    color: #e02229;
    background-color: #faf3f5;
}

.nav-link-pill.active {
    background-color: #e02229;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(224, 34, 41, 0.25);
}

/* Stepper Vertical Layout */
.stepper-vertical {
    position: relative;
}

.step-item {
    position: relative;
    transition: all 0.2s ease;
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
    transition: all 0.25s ease;
    border: 2px solid transparent;
    z-index: 2;
}

.step-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #6c757d;
    transition: color 0.25s ease;
}

/* Connecting Line between step circles */
.stepper-vertical::before {
    content: '';
    position: absolute;
    top: 10px;
    left: 18px;
    bottom: 10px;
    width: 2px;
    background-color: #e9ecef;
    z-index: 1;
}

/* Stepper Active State */
.step-item.active .step-circle {
    background-color: #e02229;
    color: #ffffff;
    box-shadow: 0 0 0 4px rgba(224, 34, 41, 0.15);
}

.step-item.active .step-title {
    color: #e02229;
}

/* Stepper Completed State */
.step-item.completed .step-circle {
    background-color: #6c757d;
    color: #ffffff;
}

.step-item.completed .step-title {
    color: #6c757d;
}

/* Save Details top-right link */
.save-link:hover {
    text-decoration: underline !important;
    opacity: 0.85;
}

/* Next / Prev Nav Buttons */
.btn-next-custom {
    background-color: #e02229;
    color: #ffffff;
    border: none;
    transition: opacity 0.15s ease;
}

.btn-next-custom:hover {
    opacity: 0.9;
    color: #ffffff;
}

/* Step 5 File Upload Box */
.upload-box {
    min-height: 180px;
    border-color: #cfd8dc !important;
    transition: all 0.2s ease-in-out;
    overflow: hidden;
}

.upload-box:hover {
    background-color: #eceff1 !important;
    border-color: #e02229 !important;
}

.upload-icon-circle {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* KYC Details Bottom Panel */
.last-mile-banner {
    background-color: #e8f5e9;
    border: 1px solid #c8e6c9;
    border-radius: 10px;
}

.btn-create-label {
    background-color: #2ecb71;
    color: #ffffff;
    border: none;
    border-radius: 8px;
}

.btn-create-label:hover {
    background-color: #27ae60;
    color: #ffffff;
}

.btn-draft-booking {
    background-color: #8ddca4;
    color: #ffffff;
    border: none;
    border-radius: 8px;
}

.btn-draft-booking:hover {
    background-color: #79c991;
    color: #ffffff;
}

.btn-cancel-booking {
    background-color: #ff8b94;
    color: #ffffff;
    border: none;
    border-radius: 8px;
}

.btn-cancel-booking:hover {
    background-color: #ff7680;
    color: #ffffff;
}

/* Animations */
.animate-fade {
    animation: fadeIn 0.28s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
