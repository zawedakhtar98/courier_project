<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getAllCountries } from '@/services/admin/CountryService';
import { useToast } from 'vue-toastification';

const route = useRoute()
const toast = useToast()

// Step 1 validation errors
const errors = ref({})

// Active Stepper state
const currentStep = ref(1)

// Document upload states
const kycFile = ref(null)
const invoiceFile = ref(null)

// Shipment Type (Import / Export)
const shipmentType = ref('export')

// Step 1: Sender Details
const senderForm = ref({
  sender_name: '',
  sender_company_name: '',
  sender_address_line_1: '',
  sender_address_line_2: '',
  sender_address_line_3: '',
  sender_mobile: '',
  sender_email: '',
  sender_city: '',
  sender_state: '',
  sender_pincode: '',
  sender_kyc_type: '',
  sender_kyc_number: '',
  sender_kyc_front: '',
  sender_kyc_back: '',
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
  const selectedCountry = countryList.value.find(country =>
    String(country.id) === String(newDest) || country.name?.toUpperCase() === String(newDest).toUpperCase()
  )
  const countryName = selectedCountry?.name?.toUpperCase() || String(newDest).toUpperCase()
  consigneeForm.value.isoCode = selectedCountry?.code || countryIsoMapping[countryName] || consigneeForm.value.isoCode
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

const showAlert = (text, type = 'danger') => {
  if (type === 'danger' || type === 'error') {
    toast.error(text)
  } else {
    toast.success(text)
  }
}

const isStep1Valid = computed(() => {
  const f = senderForm.value
  const hasName = Boolean(f.sender_name && f.sender_name.trim())
  const hasCompany = Boolean(f.sender_company_name && f.sender_company_name.trim())
  const hasAddr1 = Boolean(f.sender_address_line_1 && f.sender_address_line_1.trim())
  const hasAddr2 = Boolean(f.sender_address_line_2 && f.sender_address_line_2.trim())
  const hasEmail = Boolean(f.sender_email && f.sender_email.trim() && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.sender_email.trim()))
  const hasMobile = Boolean(f.sender_mobile && f.sender_mobile.trim() && f.sender_mobile.replace(/\D/g, '').length >= 10)
  const hasKycType = Boolean(f.sender_kyc_type && f.sender_kyc_type.trim())
  const kycTypeVal = (f.sender_kyc_type || '').trim().toLowerCase()
  const kycNumVal = (f.sender_kyc_number || '').trim().toUpperCase().replace(/\s/g, '')
  let hasKycNo = Boolean(kycNumVal)
  if (hasKycNo) {
    if (kycTypeVal === 'aadhaar') hasKycNo = /^\d{12}$/.test(kycNumVal)
    else if (kycTypeVal === 'pan') hasKycNo = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(kycNumVal)
    else if (kycTypeVal === 'passport') hasKycNo = /^[A-Z][0-9]{7}$/.test(kycNumVal)
    else if (kycTypeVal === 'driving license') hasKycNo = /^[A-Z]{2}[0-9]{2}[0-9]{4}[0-9]{5,7}$/.test(kycNumVal.replace(/-/g, ''))
    else if (kycTypeVal === 'voter id') hasKycNo = /^[A-Z]{3}[0-9]{7}$/.test(kycNumVal)
    else if (kycTypeVal === 'gst') hasKycNo = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(kycNumVal)
  }
  const hasFront = Boolean(kycFile.value || f.sender_kyc_front)
  const hasBack = Boolean(invoiceFile.value || f.sender_kyc_back)

  return (
    hasName &&
    hasCompany &&
    hasAddr1 &&
    hasAddr2 &&
    hasEmail &&
    hasMobile &&
    hasKycType &&
    hasKycNo &&
    hasFront &&
    hasBack
  )
})


const kycRules = {
  'aadhaar': { placeholder: 'Enter 12-digit Aadhaar number', hint: 'Must be exactly 12 digits (e.g. 123456789012)', maxLength: 12 },
  'pan': { placeholder: 'e.g. ABCDE1234F', hint: 'Format: 5 letters + 4 digits + 1 letter (e.g. ABCDE1234F)', maxLength: 10 },
  'passport': { placeholder: 'e.g. A1234567', hint: 'Format: 1 letter + 7 digits (e.g. A1234567)', maxLength: 8 },
  'driving license': { placeholder: 'e.g. DL0120110012345', hint: 'Format: State code + Year + 5–7 digits (e.g. DL0120110012345)', maxLength: 15 },
  'voter id': { placeholder: 'e.g. ABC1234567', hint: 'Format: 3 letters + 7 digits (e.g. ABC1234567)', maxLength: 10 },
  'gst': { placeholder: 'e.g. 22AAAAA0000A1Z5', hint: 'Must be 15-character GST format (e.g. 22AAAAA0000A1Z5)', maxLength: 15 },
}

const kycPlaceholder = computed(() => {
  const rule = kycRules[senderForm.value.sender_kyc_type?.toLowerCase()]
  return rule ? rule.placeholder : 'Enter KYC number'
})

const kycFormatHint = computed(() => {
  const rule = kycRules[senderForm.value.sender_kyc_type?.toLowerCase()]
  return rule ? rule.hint : ''
})

const kycMaxLength = computed(() => {
  const rule = kycRules[senderForm.value.sender_kyc_type?.toLowerCase()]
  return rule ? rule.maxLength : 50
})

const clearError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

const validateSenderForm = () => {
  const errs = {}

  if (!senderForm.value.sender_name || !senderForm.value.sender_name.trim()) {
    errs.sender_name = 'Sender name is required'
  }

  if (!senderForm.value.sender_company_name || !senderForm.value.sender_company_name.trim()) {
    errs.sender_company_name = 'Company name is required'
  }

  if (!senderForm.value.sender_address_line_1 || !senderForm.value.sender_address_line_1.trim()) {
    errs.sender_address_line_1 = 'Address line 1 is required'
  }

  if (!senderForm.value.sender_address_line_2 || !senderForm.value.sender_address_line_2.trim()) {
    errs.sender_address_line_2 = 'Address line 2 is required'
  }

  const email = senderForm.value.sender_email ? senderForm.value.sender_email.trim() : ''
  if (!email) {
    errs.sender_email = 'Email address is required'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errs.sender_email = 'Please enter a valid email address'
  }

  const mobile = senderForm.value.sender_mobile ? senderForm.value.sender_mobile.trim() : ''
  if (!mobile) {
    errs.sender_mobile = 'Mobile number is required'
  } else if (!/^[0-9+\-\s]{10,15}$/.test(mobile) || mobile.replace(/\D/g, '').length < 10) {
    errs.sender_mobile = 'Please enter a valid mobile number (at least 10 digits)'
  }

  if (!senderForm.value.sender_kyc_type || !senderForm.value.sender_kyc_type.trim()) {
    errs.sender_kyc_type = 'Please select a KYC type'
  }

  const kycType = (senderForm.value.sender_kyc_type || '').trim().toLowerCase()
  const kycNum = (senderForm.value.sender_kyc_number || '').trim().toUpperCase().replace(/\s/g, '')

  if (!kycNum) {
    errs.sender_kyc_number = 'KYC number is required'
  } else if (kycType === 'aadhaar') {
    // Aadhaar: exactly 12 digits
    if (!/^\d{12}$/.test(kycNum)) {
      errs.sender_kyc_number = 'Aadhaar number must be exactly 12 digits'
    }
  } else if (kycType === 'pan') {
    // PAN: 5 letters + 4 digits + 1 letter (e.g. ABCDE1234F)
    if (!/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(kycNum)) {
      errs.sender_kyc_number = 'PAN must be in format ABCDE1234F (5 letters, 4 digits, 1 letter)'
    }
  } else if (kycType === 'passport') {
    // Passport: 1 letter + 7 digits (e.g. A1234567)
    if (!/^[A-Z][0-9]{7}$/.test(kycNum)) {
      errs.sender_kyc_number = 'Passport number must be 1 letter followed by 7 digits (e.g. A1234567)'
    }
  } else if (kycType === 'driving license') {
    // Driving License: 2 letters + 2 digits (state + year) + 4–11 alphanumeric (e.g. DL0120110012345)
    if (!/^[A-Z]{2}[0-9]{2}[0-9]{4}[0-9]{5,7}$/.test(kycNum.replace(/-/g, ''))) {
      errs.sender_kyc_number = 'Driving License must follow format: DL012011XXXXXXX'
    }
  } else if (kycType === 'voter id') {
    // Voter ID: 3 letters + 7 digits (e.g. ABC1234567)
    if (!/^[A-Z]{3}[0-9]{7}$/.test(kycNum)) {
      errs.sender_kyc_number = 'Voter ID must be 3 letters followed by 7 digits (e.g. ABC1234567)'
    }
  } else if (kycType === 'gst') {
    // GST: 15-char alphanumeric (e.g. 22AAAAA0000A1Z5)
    if (!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(kycNum)) {
      errs.sender_kyc_number = 'GST number must be in format: 22AAAAA0000A1Z5 (15 characters)'
    }
  }

  if (!kycFile.value && !senderForm.value.sender_kyc_front) {
    errs.sender_kyc_front = 'Please upload front side of KYC document'
  }

  if (!invoiceFile.value && !senderForm.value.sender_kyc_back) {
    errs.sender_kyc_back = 'Please upload back side of KYC document'
  }

  errors.value = errs
  return Object.keys(errs).length === 0
}

const nextStep = () => {
  if (currentStep.value === 1) {
    const isValid = validateSenderForm()
    if (!isValid) {
      toast.error('Please fill in all required sender fields and upload KYC documents.')
      return
    }
  }
  if (currentStep.value < 4) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 1) currentStep.value--
}

const jumpToStep = (stepNum) => {
  if (currentStep.value === 1 && stepNum > 1) {
    const isValid = true;//validateSenderForm()
    if (!isValid) {
      toast.error('Please complete Sender Details first before proceeding.')
      return
    }
  }
  if (stepNum >= 1 && stepNum <= 4) {
    currentStep.value = stepNum
  }
}

const handleFileUpload = (type, event) => {
  const target = event.target
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    if (file) {
      const sizeStr = (file.size / 1024).toFixed(1) + ' KB'
      if (type === 'kyc') {
        kycFile.value = { name: file.name, size: sizeStr, file: file }
        senderForm.value.sender_kyc_front = file
        clearError('sender_kyc_front')
      } else {
        invoiceFile.value = { name: file.name, size: sizeStr, file: file }
        senderForm.value.sender_kyc_back = file
        clearError('sender_kyc_back')
      }
    }
  }
}

const removeFile = (type) => {
  if (type === 'kyc') {
    kycFile.value = null
    senderForm.value.sender_kyc_front = null
  } else {
    invoiceFile.value = null
    senderForm.value.sender_kyc_back = null
  }
}



const defaultIndia = { id: 234, name: 'India', code: 'IN' };
const countryList = ref([defaultIndia]);
const origin_country = ref(defaultIndia.id);

const selectConsigneeCountry = (value) => {
  const country = countryList.value.find(item =>
    String(item.id) === String(value) || item.name?.toUpperCase() === String(value).toUpperCase()
  )
  if (country) {
    consigneeForm.value.destination = country.id
    consigneeForm.value.isoCode = country.code || countryIsoMapping[country.name?.toUpperCase()] || ''
  }
};

const setIndiaIfExport = () => {
  if (shipmentType.value === 'export') {
    const india = countryList.value.find(c =>
      c.name?.toLowerCase() === 'india' || c.code?.toUpperCase() === 'IN' || String(c.id) === '234'
    );
    if (india) {
      origin_country.value = india.id;
    } else {
      countryList.value.unshift(defaultIndia);
      origin_country.value = defaultIndia.id;
    }
  }
};

watch(shipmentType, (newType) => {
  if (newType === 'export') {
    setIndiaIfExport();
  } else {
    destination.value = '';
  }
}, { immediate: true });

const getCountryList = async () => {
  try {
    const response = await getAllCountries();
    const data = response?.data || response;
    if (Array.isArray(data) && data.length > 0) {
      countryList.value = data;
    }
    selectConsigneeCountry(route.query.destination || consigneeForm.value.destination);
    setIndiaIfExport();
  } catch (error) {
    console.error('Failed to fetch countries:', error);
    selectConsigneeCountry(route.query.destination || consigneeForm.value.destination);
    setIndiaIfExport();
  }
};

onMounted(async () => {
  await getCountryList();
})
</script>

<template>
  <div class="container-fluid p-0 page-add-shipment">

    <!-- ORDER BOOKING -->
    <div class="custom-card p-4 bg-white">
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
            <div @click="jumpToStep(2)" class="step-item d-flex align-items-center gap-3"
              :class="{ active: currentStep === 2, completed: currentStep > 2, 'cursor-pointer': isStep1Valid || currentStep > 1, 'cursor-not-allowed opacity-50': currentStep === 1 && !isStep1Valid }">
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
                <i class="bi bi-building me-2"></i> Sender Details
              </h5>
            </div>


            <!-- Shipment Type: Import / Export -->
            <div class="d-flex align-items-center gap-4 mb-3">
              <label class="form-label small fw-bold mb-0">Shipment Type <span class="text-danger">*</span></label>
              <div class="form-check form-check-inline mb-0">
                <input v-model="shipmentType" class="form-check-input" type="radio" name="shipmentType"
                  id="shipmentTypeExport" value="export" />
                <label class="form-check-label fw-semibold" for="shipmentTypeExport">Export</label>
              </div>
              <div class="form-check form-check-inline mb-0">
                <input v-model="shipmentType" class="form-check-input" type="radio" name="shipmentType"
                  id="shipmentTypeImport" value="import" />
                <label class="form-check-label fw-semibold" for="shipmentTypeImport">Import</label>
              </div>
            </div>
            <div class="row g-3">
              <!-- Sender Name -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">Sender Name <span class="text-danger">*</span></label>
                <input v-model="senderForm.sender_name" @input="clearError('sender_name')" type="text"
                  class="form-control" :class="{ 'is-invalid': errors.sender_name }" placeholder="Enter sender name" />
                <div v-if="errors.sender_name" class="invalid-feedback">{{ errors.sender_name }}</div>
              </div>

              <!-- Company Name -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">Company Name <span class="text-danger">*</span></label>
                <input v-model="senderForm.sender_company_name" @input="clearError('sender_company_name')" type="text"
                  class="form-control" :class="{ 'is-invalid': errors.sender_company_name }"
                  placeholder="Enter company name" />
                <div v-if="errors.sender_company_name" class="invalid-feedback">{{ errors.sender_company_name }}</div>
              </div>

              <!-- Address Line 1 -->
              <div class="col-12">
                <label class="form-label small fw-bold">Address Line 1 <span class="text-danger">*</span></label>
                <input v-model="senderForm.sender_address_line_1" @input="clearError('sender_address_line_1')"
                  type="text" class="form-control" :class="{ 'is-invalid': errors.sender_address_line_1 }"
                  placeholder="Enter address line 1" />
                <div v-if="errors.sender_address_line_1" class="invalid-feedback">{{ errors.sender_address_line_1 }}
                </div>
              </div>

              <!-- Address Line 2 -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">Address Line 2 <span class="text-danger">*</span></label>
                <input v-model="senderForm.sender_address_line_2" @input="clearError('sender_address_line_2')"
                  type="text" class="form-control" :class="{ 'is-invalid': errors.sender_address_line_2 }"
                  placeholder="Enter address line 2" />
                <div v-if="errors.sender_address_line_2" class="invalid-feedback">{{ errors.sender_address_line_2 }}
                </div>
              </div>

              <!-- Address Line 3 -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">Address Line 3</label>
                <input v-model="senderForm.sender_address_line_3" type="text" class="form-control"
                  placeholder="Enter address line 3 (optional)" />
              </div>

              <!-- Pincode -->
              <div class="col-md-3">
                <label class="form-label small fw-bold">Pincode </label>
                <input v-model="senderForm.sender_pincode" type="text" class="form-control" placeholder="Pincode" />
              </div>

              <!-- City -->
              <div class="col-md-3">
                <label class="form-label small fw-bold">City </label>
                <input v-model="senderForm.sender_city" type="text" class="form-control" placeholder="City" />
              </div>

              <!-- State -->
              <div class="col-md-3">
                <label class="form-label small fw-bold">State </label>
                <input v-model="senderForm.sender_state" type="text" class="form-control" placeholder="State" />
              </div>

              <!-- Country -->
              <div class="col-md-3">
                <label class="form-label small fw-bold">Country <span class="text-danger">*</span></label>
                <v-select class="form-control" :options="countryList" v-model="origin_country"
                  :reduce="country => country.id" label="name" placeholder="Origin Country"
                  :disabled="shipmentType === 'export'" />
              </div>

              <!-- Mobile No -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">Mobile No <span class="text-danger">*</span></label>
                <input v-model="senderForm.sender_mobile" @input="clearError('sender_mobile')" type="text"
                  class="form-control" :class="{ 'is-invalid': errors.sender_mobile }" placeholder="e.g. 9876543210" />
                <div v-if="errors.sender_mobile" class="invalid-feedback">{{ errors.sender_mobile }}</div>
              </div>

              <!-- E-mail Id -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">E-mail Id <span class="text-danger">*</span></label>
                <input v-model="senderForm.sender_email" @input="clearError('sender_email')" type="email"
                  class="form-control" :class="{ 'is-invalid': errors.sender_email }"
                  placeholder="e.g. sender@example.com" />
                <div v-if="errors.sender_email" class="invalid-feedback">{{ errors.sender_email }}</div>
              </div>

              <!-- KYC Type -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">KYC Type <span class="text-danger">*</span></label>
                <select v-model="senderForm.sender_kyc_type"
                  @change="clearError('sender_kyc_type'); clearError('sender_kyc_number')" class="form-select"
                  :class="{ 'is-invalid': errors.sender_kyc_type }">
                  <option value="" disabled>Select KYC Type</option>
                  <option value="aadhaar">Aadhaar (12 digits)</option>
                  <option value="pan">PAN Card (e.g. ABCDE1234F)</option>
                  <option value="passport">Passport (e.g. A1234567)</option>
                  <option value="driving license">Driving License</option>
                  <option value="voter id">Voter ID (e.g. ABC1234567)</option>
                  <option value="gst">GST Number</option>
                </select>
                <div v-if="errors.sender_kyc_type" class="invalid-feedback">{{ errors.sender_kyc_type }}</div>
              </div>

              <!-- KYC No -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">KYC No. <span class="text-danger">*</span></label>
                <input v-model="senderForm.sender_kyc_number" @input="clearError('sender_kyc_number')" type="text"
                  class="form-control" :class="{ 'is-invalid': errors.sender_kyc_number }" :placeholder="kycPlaceholder"
                  :maxlength="kycMaxLength" />
                <div v-if="!errors.sender_kyc_number && senderForm.sender_kyc_type" class="form-text text-muted small">
                  <i class="bi bi-info-circle me-1"></i>{{ kycFormatHint }}
                </div>
                <div v-if="errors.sender_kyc_number" class="invalid-feedback">{{ errors.sender_kyc_number }}</div>
              </div>
            </div>

            <!-- KYC Document Upload Row -->
            <div class="row g-4 mb-2 mt-1">
              <!-- KYC Front Document Upload -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">Upload KYC Front Side Document <span
                    class="text-danger">*</span></label>
                <div
                  class="upload-box d-flex flex-column align-items-center justify-content-center p-4 border border-2 border-dashed rounded-3 bg-light text-center position-relative"
                  :class="{ 'border-danger bg-danger-subtle': errors.sender_kyc_front }">
                  <input type="file" @change="handleFileUpload('kyc', $event)"
                    class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer"
                    accept=".pdf,.png,.jpg,.jpeg" />

                  <div v-if="!kycFile" class="d-flex flex-wrap align-items-center justify-content-center">
                    <div class="upload-icon-circle bg-white shadow-sm me-2">
                      <i class="bi bi-cloud-upload"
                        :class="errors.sender_kyc_front ? 'text-danger' : 'text-primary'"></i>
                    </div>
                    <span class="fw-bold text-dark drop-text">Drag and Drop Front side of KYC Document</span>
                    <span class="text-muted small drop-text">Supports PDF, PNG, JPG</span>
                  </div>
                  <div v-else class="uploaded-state d-flex flex-column align-items-center">
                    <i class="bi bi-file-earmark-check text-success fs-2 mb-1"></i>
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
                <div v-if="errors.sender_kyc_front" class="text-danger small mt-1 fw-semibold">
                  <i class="bi bi-exclamation-circle me-1"></i>{{ errors.sender_kyc_front }}
                </div>
              </div>

              <!-- KYC Back Document Upload -->
              <div class="col-md-6">
                <label class="form-label small fw-bold">Upload KYC Back Side Document <span
                    class="text-danger">*</span></label>
                <div
                  class="upload-box d-flex flex-column align-items-center justify-content-center p-4 border border-2 border-dashed rounded-3 bg-light text-center position-relative"
                  :class="{ 'border-danger bg-danger-subtle': errors.sender_kyc_back }">
                  <input type="file" @change="handleFileUpload('invoice', $event)"
                    class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer"
                    accept=".pdf,.png,.jpg,.jpeg" />

                  <div v-if="!invoiceFile" class="d-flex flex-wrap align-items-center justify-content-center">
                    <div class="upload-icon-circle bg-white shadow-sm me-2">
                      <i class="bi bi-cloud-upload"
                        :class="errors.sender_kyc_back ? 'text-danger' : 'text-primary'"></i>
                    </div>
                    <span class="fw-bold text-dark drop-text">Drag and Drop Back side of KYC Document</span>
                    <span class="text-muted small drop-text">Supports PDF, PNG, JPG</span>
                  </div>
                  <div v-else class="uploaded-state d-flex flex-column align-items-center">
                    <i class="bi bi-file-earmark-check text-success fs-2 mb-1"></i>
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
                <div v-if="errors.sender_kyc_back" class="text-danger small mt-1 fw-semibold">
                  <i class="bi bi-exclamation-circle me-1"></i>{{ errors.sender_kyc_back }}
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 2: CONSIGNEE DETAILS -->
          <div v-if="currentStep === 2" class="step-content-section animate-fade">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h5 class="fw-bold mb-0 text-dark-accent">
                <i class="bi bi-building me-2"></i> Consignee Details
              </h5>
            </div>

            <div class="row g-3 mb-4">

              <div class="col-md-4">
                <label class="form-label small fw-bold">Destination <span class="text-danger">*</span></label>
                <v-select class="form-control" :options="countryList" v-model="consigneeForm.destination"
                  :reduce="country => country.id" label="name" placeholder="Destination" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold">Service Partner <span class="text-danger">*</span></label>
                <input v-model="serviceForm.vendor" type="text" class="form-control bg-light" readonly />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Package Type <span class="text-danger">*</span></label>
                <input v-model="serviceForm.vendor" type="text" class="form-control bg-light" readonly />
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Consignee's Name <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.contactPerson" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Company Name <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.companyName" type="text" class="form-control" />
              </div>

              <div class="col-md-12">
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

              <div class="col-md-3">
                <label class="form-label small fw-bold">Pincode <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.pincode" type="text" class="form-control" />
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">City <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.city" type="text" class="form-control" />
              </div>
              <div class="col-md-3">
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
              class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold"
              :class="{ 'opacity-50': currentStep === 1 && !isStep1Valid }"
              :title="currentStep === 1 && !isStep1Valid ? 'Please fill all required sender fields & upload KYC' : ''">
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
  </div>
</template>

<style>
/* Custom styling for vue-select to match form-control */
.page-add-shipment .v-select,
.page-add-shipment .v-select.form-control,
.v-select.form-control {
  padding: 0 !important;
  border: none !important;
  background: transparent !important;
  box-shadow: none !important;
  min-height: auto !important;
}

.page-add-shipment .v-select.form-control:focus,
.v-select.form-control:focus {
  border: none !important;
  box-shadow: none !important;
}

.page-add-shipment .v-select .vs__dropdown-toggle,
.v-select.form-control .vs__dropdown-toggle {
  border-radius: 8px !important;
  border: 1px solid #dcdcdc !important;
  padding: 0.35rem 0.75rem !important;
  font-size: 0.9rem !important;
  font-weight: 500 !important;
  min-height: 41px !important;
  height: 41px !important;
  background-color: #ffffff !important;
  display: flex !important;
  align-items: center !important;
  transition: all 0.2s ease-in-out !important;
}

.page-add-shipment .v-select.vs--open .vs__dropdown-toggle,
.page-add-shipment .v-select.vs--focused .vs__dropdown-toggle,
.v-select.form-control.vs--open .vs__dropdown-toggle,
.v-select.form-control.vs--focused .vs__dropdown-toggle {
  border-color: #1b357d !important;
  box-shadow: 0 0 0 3px rgba(27, 53, 125, 0.12) !important;
  background-color: #ffffff !important;
}

.page-add-shipment .v-select.vs--disabled .vs__dropdown-toggle,
.v-select.form-control.vs--disabled .vs__dropdown-toggle {
  background-color: #e9ecef !important;
  border-color: #dcdcdc !important;
  cursor: not-allowed !important;
  opacity: 1 !important;
}

.page-add-shipment .v-select.vs--disabled .vs__selected,
.v-select.form-control.vs--disabled .vs__selected {
  color: #495057 !important;
  font-weight: 600 !important;
  background: transparent !important;
}

.page-add-shipment .v-select.vs--disabled .vs__search,
.v-select.form-control.vs--disabled .vs__search {
  cursor: not-allowed !important;
  background: transparent !important;
  display: none !important;
}

.page-add-shipment .v-select.vs--disabled .vs__open-indicator,
.v-select.form-control.vs--disabled .vs__open-indicator,
.page-add-shipment .v-select.vs--disabled .vs__clear,
.v-select.form-control.vs--disabled .vs__clear {
  cursor: not-allowed !important;
  opacity: 0.4 !important;
}

.page-add-shipment .v-select .vs__selected-options,
.v-select.form-control .vs__selected-options {
  padding: 0 !important;
  display: flex !important;
  align-items: center !important;
  flex-wrap: nowrap !important;
  white-space: nowrap !important;
  overflow: hidden !important;
}

.page-add-shipment .v-select .vs__selected,
.v-select.form-control .vs__selected {
  margin: 0 !important;
  padding: 0 !important;
  font-size: 0.9rem !important;
  color: #2b2b2b !important;
  border: none !important;
  background: transparent !important;
  line-height: 1.5 !important;
  white-space: nowrap !important;
  text-overflow: ellipsis !important;
  overflow: hidden !important;
}

.page-add-shipment .v-select .vs__search,
.page-add-shipment .v-select .vs__search:focus,
.v-select.form-control .vs__search,
.v-select.form-control .vs__search:focus {
  margin: 0 !important;
  padding: 0 !important;
  font-size: 0.9rem !important;
  line-height: 1.5 !important;
  border: none !important;
  background: transparent !important;
  box-shadow: none !important;
  color: #2b2b2b !important;
}

.page-add-shipment .v-select .vs__search::placeholder,
.v-select.form-control .vs__search::placeholder {
  color: #8c8c8c !important;
  font-size: 0.9rem !important;
}

.page-add-shipment .v-select .vs__actions,
.v-select.form-control .vs__actions {
  padding: 0 0 0 0.35rem !important;
  display: flex !important;
  align-items: center !important;
}

.page-add-shipment .v-select .vs__clear,
.v-select.form-control .vs__clear {
  margin-right: 4px !important;
  fill: #8c8c8c !important;
}

.page-add-shipment .v-select .vs__open-indicator,
.v-select.form-control .vs__open-indicator {
  fill: #6c757d !important;
  transform: scale(0.85) !important;
  transition: transform 0.15s ease !important;
}

.page-add-shipment .v-select.vs--open .vs__open-indicator,
.v-select.form-control.vs--open .vs__open-indicator {
  transform: rotate(180deg) scale(0.85) !important;
}

.page-add-shipment .v-select .vs__dropdown-menu,
.v-select.form-control .vs__dropdown-menu {
  border-radius: 8px !important;
  border: 1px solid #dcdcdc !important;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08) !important;
  font-size: 0.9rem !important;
  padding: 4px 0 !important;
  top: calc(100% + 2px) !important;
  z-index: 1050 !important;
}

.page-add-shipment .v-select .vs__dropdown-option,
.v-select.form-control .vs__dropdown-option {
  padding: 7px 12px !important;
  color: #2b2b2b !important;
  font-size: 0.88rem !important;
}

.page-add-shipment .v-select .vs__dropdown-option--highlight,
.v-select.form-control .vs__dropdown-option--highlight {
  background-color: #1b357d !important;
  color: #ffffff !important;
}

.page-add-shipment .v-select .vs__dropdown-option--selected,
.v-select.form-control .vs__dropdown-option--selected {
  font-weight: 600 !important;
}
</style>
