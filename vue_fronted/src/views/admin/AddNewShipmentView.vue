<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getAllCountries } from '@/services/admin/CountryService';
import { DOCUMENT_TYPE, CURRENCIES } from '@/constant'
import { useToast } from 'vue-toastification';
import { getAllServicePartners } from '@/services/admin/servicePartner';
import { HsnSearch } from '@/services/hsnSearchService';
import { bookShipment } from '@/services/admin/shipmentService';


const route = useRoute()
const router = useRouter()
const toast = useToast()
const isBooking = ref(false)

// Step 1 validation errors
const errors = ref({})
const servicePartnerList = ref([]);

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
  origin_country_name: '',
  origin_country_id: '',
  sender_pincode: '',
  sender_kyc_type: '',
  sender_kyc_number: '',
  sender_kyc_front: '',
  sender_kyc_back: '',
})

// Step 2: Consignee Details
const consigneeForm = ref({
  consignee_name: '',
  consignee_company_name: '',
  consignee_address_line_1: '',
  consignee_address_line_2: '',
  consignee_address_line_3: '',
  consignee_mobile: '',
  consignee_email: '',
  consignee_city: '',
  consignee_state: '',
  destination_country_name: '',
  destination_country_id: '',
  consignee_pincode: '',
  service_partner: '',
  packageType: ''
})


const createEmptyBoxRow = (boxNo = 1) => ({
  boxNo,
  actWt: '',
  length: '',
  width: '',
  height: '',
  volWt: 0,
  chgWt: 0
})

const boxRows = ref([createEmptyBoxRow(1)])

const recalculateBoxRow = (row) => {
  const l = parseFloat(row.length) || 0
  const w = parseFloat(row.width) || 0
  const h = parseFloat(row.height) || 0
  const act = parseFloat(row.actWt) || 0

  if (l && w && h) {
    row.volWt = parseFloat(((l * w * h) / 5000).toFixed(2))
  } else {
    row.volWt = 0
  }
  const baseWeight = Math.max(act, row.volWt)
  row.chgWt = baseWeight > 0 ? parseFloat((Math.ceil(baseWeight * 2) / 2).toFixed(2)) : 0
}

// Step 4: Custom Invoice Details Model
const createEmptyInvoiceRow = (boxNo = 1) => ({
  boxNo,
  description: '',
  hsnCode: '',
  unit: 'PCS',
  qty: 1,
  rate: '',
  amount: 0
})

const invoiceRows = ref([
  createEmptyInvoiceRow(1)
])

// Auto-sync invoice rows with package box details
function syncInvoiceRowsWithBoxes() {
  if (!boxRows.value || boxRows.value.length === 0) return

  const currentBoxNos = boxRows.value.map(b => Number(b.boxNo))
  const validBoxSet = new Set(currentBoxNos)

  // 1. Remove rows for deleted boxes if they are blank/empty
  invoiceRows.value = invoiceRows.value.filter(r => {
    if (validBoxSet.has(Number(r.boxNo))) return true
    // Keep row if user has entered data
    const hasData = Boolean((r.description && r.description.trim()) || (parseFloat(r.rate) > 0))
    return hasData
  })

  // 2. Reassign any retained rows from deleted boxes to an existing box
  const fallbackBoxNo = currentBoxNos[0] || 1
  invoiceRows.value.forEach(r => {
    if (!validBoxSet.has(Number(r.boxNo))) {
      r.boxNo = fallbackBoxNo
    }
  })

  // 3. For each package box, ensure at least one invoice item exists
  boxRows.value.forEach(box => {
    const bNo = Number(box.boxNo)
    const exists = invoiceRows.value.some(r => Number(r.boxNo) === bNo)
    if (!exists) {
      invoiceRows.value.push(createEmptyInvoiceRow(bNo))
    }
  })

  // 4. Ensure at least one invoice row exists overall
  if (invoiceRows.value.length === 0) {
    invoiceRows.value.push(createEmptyInvoiceRow(fallbackBoxNo))
  }

  // 5. Keep items sorted by boxNo
  invoiceRows.value.sort((a, b) => Number(a.boxNo) - Number(b.boxNo))

  // 6. Clear box coverage error if previously set
  if (errors.value.invoice_boxes) {
    clearError('invoice_boxes')
  }
}

// Auto-detect whenever package box numbers change
watch(() => boxRows.value.map(b => b.boxNo), () => {
  syncInvoiceRowsWithBoxes()
}, { deep: true })

const addBoxRow = () => {
  const newNo = boxRows.value.length > 0 ? Math.max(...boxRows.value.map(b => b.boxNo)) + 1 : 1
  boxRows.value.push(createEmptyBoxRow(newNo))
  syncInvoiceRowsWithBoxes()
}

const deleteBoxRow = (index) => {
  if (boxRows.value.length <= 1) {
    showAlert('At least one box is required.', 'danger')
    return
  }
  const row = boxRows.value[index]
  if (row) {
    boxRows.value.splice(index, 1)
    syncInvoiceRowsWithBoxes()
  }
}

const handleBoxFieldChange = (row) => {
  recalculateBoxRow(row)
}

const totalPcs = computed(() => boxRows.value.length)
const totalActualWeight = computed(() => {
  return parseFloat(boxRows.value.reduce((acc, row) => acc + (parseFloat(row.actWt) || 0), 0).toFixed(2))
})
const totalVolWeight = computed(() => {
  return parseFloat(boxRows.value.reduce((acc, row) => acc + (parseFloat(row.volWt) || 0), 0).toFixed(2))
})
const totalChargeableWeight = computed(() => {
  return parseFloat(boxRows.value.reduce((acc, row) => acc + (parseFloat(row.chgWt) || 0), 0).toFixed(2))
})

const invoiceDltForm = ref({
  invoiceType: 'CSB 4',
  invoiceNo: '',
  invoiceDate: new Date().toISOString().split('T')[0],
  Incoterms: 'DDU',
  invoiceNote: 'Gift',
  currency: 'INR',
  invoiceTotalVal: 0
})

const addInvoiceRow = () => {
  closeHsnDropdown()
  const coveredBoxes = new Set(invoiceRows.value.map(r => Number(r.boxNo)))
  const nextMissingBox = boxRows.value.find(b => !coveredBoxes.has(Number(b.boxNo)))
  const defaultBox = nextMissingBox
    ? nextMissingBox.boxNo
    : (invoiceRows.value[invoiceRows.value.length - 1]?.boxNo || boxRows.value[0]?.boxNo || 1)
  invoiceRows.value.push(createEmptyInvoiceRow(defaultBox))
  clearError('invoice_boxes')
  clearError('invoice_items')
}

const deleteInvoiceRow = (index) => {
  closeHsnDropdown()
  if (invoiceRows.value.length <= 1) {
    showAlert('At least one invoice item is required.', 'danger')
    return
  }
  invoiceRows.value.splice(index, 1)
  if (Object.keys(errors.value).some(k => k.startsWith('inv_') || k === 'invoice_boxes')) {
    validateInvoiceForm()
  }
}

const handleInvoiceItemChange = (row) => {
  const qty = parseFloat(row.qty) || 0
  const rate = parseFloat(row.rate) || 0
  row.amount = parseFloat((qty * rate).toFixed(2))
}

// --- HSN Code & Description Autocomplete Search ---
const activeHsnIndex = ref(null)
const activeHsnField = ref('description') // 'description' | 'hsnCode'
const isDropdownOpen = ref(false)
const isHsnLoading = ref(false)
const hsnSuggestions = ref([])
const highlightedSuggestionIndex = ref(-1)
let hsnDebounceTimer = null
let hsnAbortController = null

const closeHsnDropdown = () => {
  isDropdownOpen.value = false
  activeHsnIndex.value = null
  highlightedSuggestionIndex.value = -1
}

const onHsnSearchInput = (value, rowIndex, field) => {
  if (field === 'description') {
    clearError(`inv_desc_${rowIndex}`)
  }
  activeHsnIndex.value = rowIndex
  activeHsnField.value = field
  highlightedSuggestionIndex.value = -1

  const query = (value || '').trim()
  if (query.length < 2) {
    hsnSuggestions.value = []
    isDropdownOpen.value = false
    isHsnLoading.value = false
    return
  }

  isDropdownOpen.value = true
  isHsnLoading.value = true

  if (hsnDebounceTimer) clearTimeout(hsnDebounceTimer)
  hsnDebounceTimer = setTimeout(async () => {
    if (hsnAbortController) {
      hsnAbortController.abort()
    }
    hsnAbortController = new AbortController()

    try {
      const response = await HsnSearch(query, { signal: hsnAbortController.signal })
      if (response && response.status === 'success') {
        hsnSuggestions.value = Array.isArray(response.data) ? response.data : []
      }
    } catch (err) {
      if (err.name !== 'CanceledError' && err.code !== 'ERR_CANCELED') {
        console.error('HSN search failed:', err)
      }
    } finally {
      isHsnLoading.value = false
    }
  }, 300)
}

const onHsnSearchFocus = (value, rowIndex, field) => {
  const query = (value || '').trim()
  if (query.length >= 2 && activeHsnIndex.value === rowIndex && activeHsnField.value === field && hsnSuggestions.value.length > 0) {
    isDropdownOpen.value = true
  }
}

const selectHsnItem = (item, rowIndex) => {
  if (!item || !invoiceRows.value[rowIndex]) return
  invoiceRows.value[rowIndex].description = item.description
  invoiceRows.value[rowIndex].hsnCode = item.hsn_code
  clearError(`inv_desc_${rowIndex}`)
  closeHsnDropdown()
}

const navigateHsnSuggestion = (direction) => {
  if (!isDropdownOpen.value || hsnSuggestions.value.length === 0) return
  const count = hsnSuggestions.value.length
  let next = highlightedSuggestionIndex.value + direction
  if (next < 0) next = count - 1
  if (next >= count) next = 0
  highlightedSuggestionIndex.value = next
}

const selectHighlightedHsn = (rowIndex) => {
  if (isDropdownOpen.value && highlightedSuggestionIndex.value >= 0 && hsnSuggestions.value[highlightedSuggestionIndex.value]) {
    selectHsnItem(hsnSuggestions.value[highlightedSuggestionIndex.value], rowIndex)
  }
}

const handleDocumentClick = (e) => {
  if (!e.target.closest('.hsn-search-cell')) {
    closeHsnDropdown()
  }
}

const invoiceTotalValue = computed(() => {
  return parseFloat(invoiceRows.value.reduce((acc, r) => acc + (parseFloat(r.amount) || 0), 0).toFixed(2))
})

watch(invoiceTotalValue, (newVal) => {
  invoiceDltForm.value.invoiceTotalVal = newVal
}, { immediate: true })

const concatenatedDescriptions = computed(() => {
  return invoiceRows.value.map(r => r.description).filter(Boolean).join('; ')
})

// Load Router query params if present
onMounted(() => {
  if (route.query.destination) {
    consigneeForm.value.destination_country_name = route.query.destination
    selectConsigneeCountry(route.query.destination)
  }
  if (route.query.service_partner) {
    selectServicePartner(route.query.service_partner)
  }
  if (route.query.packageType || route.query.package_type) {
    selectedPaTypeckage(route.query.packageType || route.query.package_type)
  }
  if (route.query.pincode) {
    consigneeForm.value.consignee_pincode = String(route.query.pincode)
  }

  // Pre-load boxes if sent in JSON format
  if (route.query.boxes) {
    try {
      const parsedBoxes = JSON.parse(String(route.query.boxes))
      if (Array.isArray(parsedBoxes) && parsedBoxes.length > 0) {
        boxRows.value = parsedBoxes.map((b, index) => {
          const row = {
            boxNo: index + 1,
            actWt: b.actWt !== undefined && b.actWt !== null && b.actWt !== '' ? parseFloat(b.actWt) : '',
            length: b.length !== undefined && b.length !== null && b.length !== '' ? parseFloat(b.length) : '',
            width: b.width !== undefined && b.width !== null && b.width !== '' ? parseFloat(b.width) : '',
            height: b.height !== undefined && b.height !== null && b.height !== '' ? parseFloat(b.height) : '',
            volWt: 0,
            chgWt: 0
          }
          recalculateBoxRow(row)
          return row
        })
      } else {
        boxRows.value = [createEmptyBoxRow(1)]
      }
    } catch (e) {
      console.error('Failed to parse query boxes', e)
      boxRows.value = [createEmptyBoxRow(1)]
    }
  } else {
    boxRows.value = [createEmptyBoxRow(1)]
  }

  // Auto-sync invoice item rows for all package boxes
  syncInvoiceRowsWithBoxes()

  if (route.query.city) {
    consigneeForm.value.consignee_city = String(route.query.city)
  }
  if (route.query.state) {
    consigneeForm.value.consignee_state = String(route.query.state)
  }

  // Pre-load cloned shipment data if triggered from Shipment Action 'Order Cloning'
  const clonedStr = sessionStorage.getItem('cloned_shipment_data')
  if (clonedStr) {
    try {
      const cloned = JSON.parse(clonedStr)
      sessionStorage.removeItem('cloned_shipment_data')
      if (cloned.sender_name) senderForm.value.sender_name = cloned.sender_name
      if (cloned.sender_company_name) senderForm.value.sender_company_name = cloned.sender_company_name
      if (cloned.sender_address_line_1) senderForm.value.sender_address_line_1 = cloned.sender_address_line_1
      if (cloned.sender_address_line_2) senderForm.value.sender_address_line_2 = cloned.sender_address_line_2
      if (cloned.sender_city) senderForm.value.sender_city = cloned.sender_city
      if (cloned.sender_state) senderForm.value.sender_state = cloned.sender_state
      if (cloned.sender_pincode) senderForm.value.sender_pincode = cloned.sender_pincode
      if (cloned.sender_mobile) senderForm.value.sender_mobile = cloned.sender_mobile
      if (cloned.sender_email) senderForm.value.sender_email = cloned.sender_email
      if (cloned.sender_kyc_type) senderForm.value.sender_kyc_type = cloned.sender_kyc_type
      if (cloned.sender_kyc_number) senderForm.value.sender_kyc_number = cloned.sender_kyc_number

      if (cloned.consignee_name) consigneeForm.value.consignee_name = cloned.consignee_name
      if (cloned.consignee_company_name) consigneeForm.value.consignee_company_name = cloned.consignee_company_name
      if (cloned.consignee_address_line_1) consigneeForm.value.consignee_address_line_1 = cloned.consignee_address_line_1
      if (cloned.consignee_address_line_2) consigneeForm.value.consignee_address_line_2 = cloned.consignee_address_line_2
      if (cloned.consignee_city) consigneeForm.value.consignee_city = cloned.consignee_city
      if (cloned.consignee_state) consigneeForm.value.consignee_state = cloned.consignee_state
      if (cloned.consignee_pincode) consigneeForm.value.consignee_pincode = cloned.consignee_pincode
      if (cloned.consignee_mobile_no) consigneeForm.value.consignee_mobile_no = cloned.consignee_mobile_no
      if (cloned.consignee_email) consigneeForm.value.consignee_email = cloned.consignee_email

      toast.success('Cloned shipment data successfully loaded!')
    } catch (e) {
      console.error('Failed to parse cloned shipment data', e)
    }
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
  const hasOrigin = Boolean(f.origin_country_name)
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
    hasBack &&
    hasOrigin
  )
})

const packageTypeOptions = computed(() => [
  { label: 'Non-Document (NONDOC)', value: 'NONDOC' },
  { label: 'Document (DOC)', value: 'DOC' }
])

const isStep2Valid = computed(() => {
  const f = consigneeForm.value
  const hasDest = Boolean(f.destination_country_name)
  const hasPartner = Boolean(f.service_partner)
  const hasPkgType = Boolean(f.packageType)
  const hasName = Boolean(f.consignee_name && f.consignee_name.trim())
  const hasCompany = Boolean(f.consignee_company_name && f.consignee_company_name.trim())
  const hasAddr1 = Boolean(f.consignee_address_line_1 && f.consignee_address_line_1.trim())
  const hasMobile = Boolean(f.consignee_mobile && String(f.consignee_mobile).trim() && String(f.consignee_mobile).replace(/\D/g, '').length >= 10)
  const hasEmail = Boolean(f.consignee_email && String(f.consignee_email).trim() && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(f.consignee_email).trim()))

  return (
    hasDest &&
    hasPartner &&
    hasPkgType &&
    hasName &&
    hasCompany &&
    hasAddr1 &&
    hasMobile &&
    hasEmail
  )
})

const isStep4Valid = computed(() => {
  const c = invoiceDltForm.value
  const hasType = Boolean(c.invoiceType && c.invoiceType.trim())
  const hasNo = Boolean(c.invoiceNo && c.invoiceNo.trim())
  const hasDate = Boolean(c.invoiceDate)
  const hasCurr = Boolean(c.currency)

  if (!hasType || !hasNo || !hasDate || !hasCurr) return false
  if (!invoiceRows.value || invoiceRows.value.length === 0) return false

  const hasInvalidItem = invoiceRows.value.some(r => {
    const hasBox = Boolean(r.boxNo)
    const hasDesc = Boolean(r.description && r.description.trim())
    const hasUnit = Boolean(r.unit && r.unit.trim())
    const hasQty = Number(r.qty) > 0
    const hasRate = Number(r.rate) > 0
    return !hasBox || !hasDesc || !hasUnit || !hasQty || !hasRate
  })
  if (hasInvalidItem) return false

  const itemBoxNos = invoiceRows.value.map(r => Number(r.boxNo)).filter(Boolean)
  const allBoxesCovered = boxRows.value.every(b => itemBoxNos.includes(Number(b.boxNo)))
  if (!allBoxesCovered) return false

  return true
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

  if (shipmentType.value === 'import' && !senderForm.value.origin_country_name) {
    errs.origin_country_name = 'Please select origin country.'
  }

  const senderKeys = [
    'sender_name',
    'sender_company_name',
    'sender_address_line_1',
    'sender_address_line_2',
    'sender_email',
    'sender_mobile',
    'sender_kyc_type',
    'sender_kyc_number',
    'sender_kyc_front',
    'sender_kyc_back',
    'origin_country_name'
  ]
  const newErrors = { ...errors.value }
  senderKeys.forEach(k => {
    if (errs[k]) {
      newErrors[k] = errs[k]
    } else {
      delete newErrors[k]
    }
  })
  errors.value = newErrors
  return Object.keys(errs).length === 0
}

const validateConsigneeForm = () => {
  const errs = {}

  if (!consigneeForm.value.destination_country_name) {
    errs.destination_country_name = 'Destination country is required'
  }

  if (!consigneeForm.value.service_partner) {
    errs.service_partner = 'Service partner is required'
  }

  if (!consigneeForm.value.packageType) {
    errs.packageType = 'Package type is required'
  }

  if (!consigneeForm.value.consignee_name || !consigneeForm.value.consignee_name.trim()) {
    errs.consignee_name = "Consignee's name is required"
  }

  if (!consigneeForm.value.consignee_company_name || !consigneeForm.value.consignee_company_name.trim()) {
    errs.consignee_company_name = 'Company name is required'
  }

  if (!consigneeForm.value.consignee_address_line_1 || !consigneeForm.value.consignee_address_line_1.trim()) {
    errs.consignee_address_line_1 = 'Address line 1 is required'
  }

  const mobile = consigneeForm.value.consignee_mobile ? String(consigneeForm.value.consignee_mobile).trim() : ''
  if (!mobile) {
    errs.consignee_mobile = 'Mobile number is required'
  } else if (!/^[0-9+\-\s]{10,15}$/.test(mobile) || mobile.replace(/\D/g, '').length < 10) {
    errs.consignee_mobile = 'Please enter a valid mobile number (at least 10 digits)'
  }

  const email = consigneeForm.value.consignee_email ? String(consigneeForm.value.consignee_email).trim() : ''
  if (!email) {
    errs.consignee_email = 'Email address is required'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errs.consignee_email = 'Please enter a valid email address'
  }

  const consigneeKeys = [
    'destination_country_name',
    'service_partner',
    'packageType',
    'consignee_name',
    'consignee_company_name',
    'consignee_address_line_1',
    'consignee_mobile',
    'consignee_email'
  ]
  const newErrors = { ...errors.value }
  consigneeKeys.forEach(k => {
    if (errs[k]) {
      newErrors[k] = errs[k]
    } else {
      delete newErrors[k]
    }
  })
  errors.value = newErrors
  return Object.keys(errs).length === 0
}

const validateInvoiceForm = () => {
  const errs = {}

  if (!invoiceDltForm.value.invoiceType || !invoiceDltForm.value.invoiceType.trim()) {
    errs.invoiceType = 'Invoice type is required'
  }

  if (!invoiceDltForm.value.invoiceNo || !invoiceDltForm.value.invoiceNo.trim()) {
    errs.invoiceNo = 'Invoice number is required'
  }

  if (!invoiceDltForm.value.invoiceDate) {
    errs.invoiceDate = 'Invoice date is required'
  }

  if (!invoiceDltForm.value.currency) {
    errs.currency = 'Currency is required'
  }

  if (!invoiceRows.value || invoiceRows.value.length === 0) {
    errs.invoice_items = 'At least one invoice item is required'
  } else {
    invoiceRows.value.forEach((r, idx) => {
      if (!r.boxNo) {
        errs[`inv_box_${idx}`] = 'Please select box'
      }
      if (!r.description || !r.description.trim()) {
        errs[`inv_desc_${idx}`] = 'Description is required'
      }
      if (!r.unit || !r.unit.trim()) {
        errs[`inv_unit_${idx}`] = 'Unit is required'
      }
      if (r.qty === '' || r.qty === null || r.qty === undefined || Number(r.qty) <= 0) {
        errs[`inv_qty_${idx}`] = 'Qty must be > 0'
      }
      if (r.rate === '' || r.rate === null || r.rate === undefined || Number(r.rate) <= 0) {
        errs[`inv_rate_${idx}`] = 'Rate must be > 0'
      }
    })

    // Check per-box coverage: every box in boxRows must have at least one invoice item
    const itemBoxNos = invoiceRows.value.map(r => Number(r.boxNo)).filter(Boolean)
    const missingBoxes = boxRows.value.filter(b => !itemBoxNos.includes(Number(b.boxNo)))
    if (missingBoxes.length > 0) {
      errs.invoice_boxes = `Please add invoice item(s) for Box: ${missingBoxes.map(b => b.boxNo).join(', ')}`
    }
  }

  const newErrors = { ...errors.value }
  Object.keys(newErrors).forEach(k => {
    if (k.startsWith('inv_') || k === 'invoiceType' || k === 'invoiceNo' || k === 'invoiceDate' || k === 'currency' || k === 'invoice_boxes' || k === 'invoice_items') {
      delete newErrors[k]
    }
  })
  Object.assign(newErrors, errs)
  errors.value = newErrors

  return Object.keys(errs).length === 0
}

const handleBookNow = async () => {
  // 1. Validation checks
  if (!isStep1Valid.value) {
    validateSenderForm()
    currentStep.value = 1
    toast.error('Please complete all required Sender Details first.')
    return
  }
  if (!isStep2Valid.value) {
    validateConsigneeForm()
    currentStep.value = 2
    toast.error('Please complete all required Consignee Details first.')
    return
  }
  const isInvoiceValid = validateInvoiceForm()
  if (!isInvoiceValid) {
    toast.error('Please complete all required invoice fields and ensure each box has items.')
    return
  }

  isBooking.value = true

  try {
    const formData = new FormData()

    // Sender details
    formData.append('sender_name', senderForm.value.sender_name || '')
    formData.append('sender_company_name', senderForm.value.sender_company_name || '')
    formData.append('sender_address_line_1', senderForm.value.sender_address_line_1 || '')
    formData.append('sender_address_line_2', senderForm.value.sender_address_line_2 || '')
    formData.append('sender_address_line_3', senderForm.value.sender_address_line_3 || '')
    formData.append('sender_mobile', senderForm.value.sender_mobile || '')
    formData.append('sender_email', senderForm.value.sender_email || '')
    formData.append('sender_city', senderForm.value.sender_city || '')
    formData.append('sender_state', senderForm.value.sender_state || '')
    formData.append('sender_country', senderForm.value.origin_country_name || 'India')
    formData.append('origin', senderForm.value.origin_country_id || 'India')
    formData.append('sender_pincode', senderForm.value.sender_pincode || '')
    formData.append('sender_kyc_type', senderForm.value.sender_kyc_type || '')
    formData.append('sender_kyc_number', senderForm.value.sender_kyc_number || '')

    // KYC Files
    if (senderForm.value.sender_kyc_front instanceof File) {
      formData.append('sender_kyc_front', senderForm.value.sender_kyc_front)
    } else if (kycFile.value?.file instanceof File) {
      formData.append('sender_kyc_front', kycFile.value.file)
    }

    if (senderForm.value.sender_kyc_back instanceof File) {
      formData.append('sender_kyc_back', senderForm.value.sender_kyc_back)
    }

    // Consignee details
    formData.append('consignee_name', consigneeForm.value.consignee_name || '')
    formData.append('consignee_company_name', consigneeForm.value.consignee_company_name || '')
    formData.append('consignee_address_line_1', consigneeForm.value.consignee_address_line_1 || '')
    formData.append('consignee_address_line_2', consigneeForm.value.consignee_address_line_2 || '')
    formData.append('consignee_address_line_3', consigneeForm.value.consignee_address_line_3 || '')
    formData.append('consignee_city', consigneeForm.value.consignee_city || '')
    formData.append('consignee_state', consigneeForm.value.consignee_state || '')
    formData.append('consignee_country', consigneeForm.value.destination_country_name || '')
    formData.append('destination', consigneeForm.value.destination_country_id || '')
    formData.append('consignee_pincode', consigneeForm.value.consignee_pincode || '')
    formData.append('consignee_mobile_no', consigneeForm.value.consignee_mobile || '')
    formData.append('consignee_email', consigneeForm.value.consignee_email || '')

    // Shipment configuration
    formData.append('service_partner_id', consigneeForm.value.service_partner || '')
    formData.append('package_type', consigneeForm.value.packageType || 'NONDOC')
    formData.append('shipment_type', shipmentType.value || 'export')

    // Boxes & Invoicing arrays as JSON
    formData.append('boxes', JSON.stringify(boxRows.value))
    formData.append('invoice_meta', JSON.stringify(invoiceDltForm.value))
    formData.append('invoice_items', JSON.stringify(invoiceRows.value))

    // Optional booking amount if present
    if (invoiceDltForm.value.invoiceTotalVal) {
      formData.append('booking_amount', invoiceDltForm.value.invoiceTotalVal)
    }

    const res = await bookShipment(formData)

    if (res && (res.status === 'success' || res.status === 201)) {
      const bookedData = res.data
      const awb = bookedData?.shipment_no || 'AWB'
      toast.success(`Shipment ${awb} booked successfully!`)
      setTimeout(() => {
        router.push({ name: 'admin-shipments' })
      }, 2000)

      // If user wants to reset or navigate to list
      // router.push('/admin/shipment-list')
    } else {
      toast.error(res?.message || 'Failed to book shipment.')
    }
  } catch (error) {
    const errorMsg = error?.response?.data?.message || error?.message || 'Failed to book shipment.'
    toast.error(errorMsg)
    console.error('Shipment Booking Error:', error)
  } finally {
    isBooking.value = false
  }
}

const nextStep = () => {
  if (currentStep.value === 1) {
    const isValid = validateSenderForm()
    if (!isValid) {
      toast.error('Please fill in all required sender fields and upload KYC documents.')
      return
    }
  } else if (currentStep.value === 2) {
    const isValid = validateConsigneeForm()
    if (!isValid) {
      toast.error('Please fill in all required consignee fields.')
      return
    }
  }
  if (currentStep.value < 4) {
    if (currentStep.value === 3) {
      syncInvoiceRowsWithBoxes()
    }
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 1) currentStep.value--
}

const jumpToStep = (stepNum) => {
  if (stepNum >= 1 && stepNum <= 4) {
    if (stepNum === 4) {
      syncInvoiceRowsWithBoxes()
    }
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
const origin_country_name = ref(defaultIndia.id);

const selectConsigneeCountry = (value) => {
  if (!value) return
  const country = countryList.value.find(item =>
    String(item.id) === String(value)
  )
  if (country) {
    consigneeForm.value.destination_country_name = country.name
    consigneeForm.value.destination_country_id = country.id
  }
}

const selectServicePartner = (partnerId) => {
  if (!partnerId) return
  const partner = servicePartnerList.value.find(item => String(item.id) === String(partnerId))
  if (partner) {
    consigneeForm.value.service_partner = partner.id
  }
}

const selectedPaTypeckage = (val) => {
  if (!val) return
  const upper = String(val).toUpperCase()
  const type = DOCUMENT_TYPE.find(item => item.toUpperCase() === upper)
  if (type) {
    consigneeForm.value.packageType = type
  }
}

const isDestinationDisabled = computed(() => Boolean(route.query.destination))
const isServicePartnerDisabled = computed(() => Boolean(route.query.service_partner))
const isPackageTypeDisabled = computed(() => Boolean(route.query.packageType || route.query.package_type))

const isPrefilledFromQuery = computed(() => {
  return (
    !!route.query.service_partner ||
    !!route.query.destination ||
    !!route.query.package_type ||
    !!route.query.packageType
  )
})



const setIndiaIfExport = () => {
  if (shipmentType.value === 'export') {
    const india = countryList.value.find(c =>
      c.name?.toLowerCase() === 'india' || c.code?.toUpperCase() === 'IN' || String(c.id) === '234'
    );
    if (india) {
      senderForm.value.origin_country_name = india.name;
      senderForm.value.origin_country_id = india.id;
    } else {
      countryList.value.unshift(defaultIndia);
      senderForm.value.origin_country_name = '';
    }
  }
};

watch(shipmentType, (newType) => {
  clearError('origin_country_name');
  if (newType === 'export') {
    setIndiaIfExport();
  } else {
    senderForm.value.origin_country_name = '';
  }
}, { immediate: true });

const getCountryList = async () => {
  try {
    const response = await getAllCountries();
    const data = response?.data || response;
    if (Array.isArray(data) && data.length > 0) {
      countryList.value = data;
    }
    if (route.query.destination) {
      selectConsigneeCountry(route.query.destination);
    }
    setIndiaIfExport();
  } catch (error) {
    if (route.query.destination) {
      selectConsigneeCountry(route.query.destination);
    }
    console.error('Failed to fetch countries:', error);
    setIndiaIfExport();
  }
};

const servicePartner = async () => {
  try {
    const resp = await getAllServicePartners(20, 1);
    if (resp.status == 'success') {
      servicePartnerList.value = resp.data;
    }
  }
  catch (err) {
    console.error('Failed to fetch service partner:', err);
  }
}

onMounted(async () => {
  document.addEventListener('click', handleDocumentClick);
  await getCountryList();
  await servicePartner();
  if (route.query.service_partner) {
    selectServicePartner(route.query.service_partner)
  }
  if (route.query.packageType || route.query.package_type) {
    selectedPaTypeckage(route.query.packageType || route.query.package_type)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
  if (hsnDebounceTimer) clearTimeout(hsnDebounceTimer);
  if (hsnAbortController) hsnAbortController.abort();
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
            <div @click="jumpToStep(3)" class="step-item d-flex align-items-center gap-3" :class="{
              active: currentStep === 3,
              completed: currentStep > 3,
              'cursor-pointer': (isStep1Valid && isStep2Valid) || currentStep > 2,
              'cursor-not-allowed opacity-50': currentStep <= 2 && (!isStep1Valid || !isStep2Valid)
            }">
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
                <v-select class="form-control" :class="{ 'is-invalid': errors.origin_country_name }"
                  :options="countryList" v-model="senderForm.origin_country_name" :reduce="country => country.id"
                  label="name" placeholder="Origin Country" :disabled="shipmentType === 'export'" />
                <div v-if="errors.origin_country_name" class="invalid-feedback">{{ errors.origin_country_name }}</div>
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
                <v-select class="form-control" :class="{ 'is-invalid': errors.destination_country_name }"
                  :options="countryList" v-model="consigneeForm.destination_country_name"
                  :reduce="country => country.id" label="name" :disabled="isDestinationDisabled"
                  placeholder="Select Destination" @input="clearError('destination_country_name')"
                  @option:selected="clearError('destination_country_name')" />
                <div v-if="errors.destination_country_name" class="invalid-feedback d-block">{{
                  errors.destination_country_name }}
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold">Service Partner <span class="text-danger">*</span></label>
                <v-select class="form-control" :class="{ 'is-invalid': errors.service_partner }"
                  :options="servicePartnerList" :reduce="partner => partner.id" label="name"
                  placeholder="Select Service Partner" v-model="consigneeForm.service_partner"
                  :disabled="isServicePartnerDisabled" @input="clearError('service_partner')"
                  @option:selected="clearError('service_partner')"></v-select>
                <div v-if="errors.service_partner" class="invalid-feedback d-block">{{ errors.service_partner }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Package Type <span class="text-danger">*</span></label>
                <v-select class="form-control" :class="{ 'is-invalid': errors.packageType }"
                  :options="packageTypeOptions" :reduce="item => item.value" label="label"
                  placeholder="Select Package Type" v-model="consigneeForm.packageType"
                  :disabled="isPackageTypeDisabled" @input="clearError('packageType')"
                  @option:selected="clearError('packageType')"></v-select>
                <div v-if="errors.packageType" class="invalid-feedback d-block">{{ errors.packageType }}</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Consignee's Name <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.consignee_name" @input="clearError('consignee_name')" type="text"
                  class="form-control" :class="{ 'is-invalid': errors.consignee_name }"
                  placeholder="Enter consignee name" />
                <div v-if="errors.consignee_name" class="invalid-feedback">{{ errors.consignee_name }}</div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Company Name <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.consignee_company_name" @input="clearError('consignee_company_name')"
                  type="text" class="form-control" :class="{ 'is-invalid': errors.consignee_company_name }"
                  placeholder="Enter company name" />
                <div v-if="errors.consignee_company_name" class="invalid-feedback">{{ errors.consignee_company_name }}
                </div>
              </div>

              <div class="col-md-12">
                <label class="form-label small fw-bold">Address Line 1 <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.consignee_address_line_1" @input="clearError('consignee_address_line_1')"
                  type="text" class="form-control" :class="{ 'is-invalid': errors.consignee_address_line_1 }"
                  placeholder="Enter address line 1" />
                <div v-if="errors.consignee_address_line_1" class="invalid-feedback">{{ errors.consignee_address_line_1
                }}</div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Address Line 2</label>
                <input v-model="consigneeForm.consignee_address_line_2" type="text" class="form-control"
                  placeholder="Enter address line 2" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Address Line 3</label>
                <input v-model="consigneeForm.consignee_address_line_3" type="text" class="form-control"
                  placeholder="Enter address line 3" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold">Pincode</label>
                <input v-model="consigneeForm.consignee_pincode" type="text" class="form-control"
                  placeholder="Pincode" />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">City </label>
                <input v-model="consigneeForm.consignee_city" type="text" class="form-control" placeholder="City" />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">State</label>
                <input v-model="consigneeForm.consignee_state" type="text" class="form-control" placeholder="State" />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold">Mobile No. <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.consignee_mobile" @input="clearError('consignee_mobile')" type="text"
                  class="form-control" :class="{ 'is-invalid': errors.consignee_mobile }"
                  placeholder="e.g. 9876543210" />
                <div v-if="errors.consignee_mobile" class="invalid-feedback">{{ errors.consignee_mobile }}</div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">E-mail Id <span class="text-danger">*</span></label>
                <input v-model="consigneeForm.consignee_email" @input="clearError('consignee_email')" type="email"
                  class="form-control" :class="{ 'is-invalid': errors.consignee_email }"
                  placeholder="e.g. consignee@example.com" />
                <div v-if="errors.consignee_email" class="invalid-feedback">{{ errors.consignee_email }}</div>
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
                          class="form-control form-control-sm text-center" min="0" step="0.1" placeholder="0" />
                      </td>
                      <td>
                        <input v-model.number="box.length" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" placeholder="0" />
                      </td>
                      <td>
                        <input v-model.number="box.width" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" placeholder="0" />
                      </td>
                      <td>
                        <input v-model.number="box.height" @input="handleBoxFieldChange(box)" type="number"
                          class="form-control form-control-sm text-center" min="0" placeholder="0" />
                      </td>
                      <td class="text-center fw-semibold text-dark">{{ (box.volWt || 0).toFixed(2) }}
                      </td>
                      <td class="text-center fw-semibold text-primary">{{ (box.chgWt || 0).toFixed(2) }}
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
            <div class="table-responsive mb-4" style="min-height: 260px;">
              <table class="table custom-table table-bordered align-middle">
                <thead class="table-light text-secondary">
                  <tr>
                    <th style="width: 10%;">BOX <span class="text-danger">*</span></th>
                    <th style="width: 28%;">DESCRIPTION <span class="text-danger">*</span></th>
                    <th style="width: 16%;">HSN CODE</th>
                    <th style="width: 12%; text-align: center;">UNIT <span class="text-danger">*</span></th>
                    <th style="width: 8%;">QTY <span class="text-danger">*</span></th>
                    <th>RATE <span class="text-danger">*</span></th>
                    <th>AMOUNT</th>
                    <th style="width: 10%;" class="text-center">ACTION</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(invItem, idx) in invoiceRows" :key="idx">
                    <td>
                      <!-- Dropdown of available box numbers -->
                      <select v-model.number="invItem.boxNo" class="form-select form-select-sm text-center"
                        :class="{ 'is-invalid': errors[`inv_box_${idx}`] }"
                        @change="clearError(`inv_box_${idx}`); clearError('invoice_boxes')">
                        <option v-for="b in boxRows" :key="b.boxNo" :value="b.boxNo">{{ b.boxNo }}</option>
                      </select>
                      <div v-if="errors[`inv_box_${idx}`]" class="invalid-feedback d-block small">{{
                        errors[`inv_box_${idx}`] }}</div>
                    </td>
                    <td class="hsn-search-cell">
                      <div class="position-relative">
                        <input v-model="invItem.description"
                          @input="onHsnSearchInput(invItem.description, idx, 'description')"
                          @focus="onHsnSearchFocus(invItem.description, idx, 'description')"
                          @keydown.down.prevent="navigateHsnSuggestion(1)"
                          @keydown.up.prevent="navigateHsnSuggestion(-1)"
                          @keydown.enter.prevent="selectHighlightedHsn(idx)" @keydown.esc="closeHsnDropdown" type="text"
                          class="form-control form-control-sm pe-4" :class="{ 'is-invalid': errors[`inv_desc_${idx}`] }"
                          placeholder="Type description or HSN..." autocomplete="off" />
                        <span v-if="isHsnLoading && activeHsnIndex === idx && activeHsnField === 'description'"
                          class="position-absolute top-50 end-0 translate-middle-y me-2 text-muted"
                          style="pointer-events: none;">
                          <span class="spinner-border spinner-border-sm"
                            style="width: 0.85rem; height: 0.85rem;"></span>
                        </span>
                      </div>
                      <div v-if="errors[`inv_desc_${idx}`]" class="invalid-feedback d-block small">{{
                        errors[`inv_desc_${idx}`] }}</div>

                      <!-- Autocomplete Dropdown for Description -->
                      <div v-if="isDropdownOpen && activeHsnIndex === idx && activeHsnField === 'description'"
                        class="hsn-autocomplete-menu shadow-lg border rounded-3 p-0">
                        <div v-if="isHsnLoading && hsnSuggestions.length === 0"
                          class="p-3 text-center text-muted small">
                          <span class="spinner-border spinner-border-sm me-2 text-primary"></span> Searching HSN
                          database...
                        </div>
                        <div v-else-if="!isHsnLoading && hsnSuggestions.length === 0"
                          class="p-3 text-center text-muted small">
                          No matching HSN code or description found
                        </div>
                        <ul v-else class="list-unstyled mb-0 hsn-suggestion-list">
                          <li v-for="(item, sIdx) in hsnSuggestions" :key="item.id || sIdx"
                            class="hsn-suggestion-item px-3 py-2 border-bottom d-flex align-items-center justify-content-between"
                            :class="{ 'active-suggestion': highlightedSuggestionIndex === sIdx }"
                            @mousedown.prevent="selectHsnItem(item, idx)">
                            <div class="me-2 text-truncate" style="max-width: 270px;">
                              <div class="fw-semibold text-dark small text-truncate" :title="item.description">{{
                                item.description }}</div>
                              <div class="text-muted" style="font-size: 0.75rem;">HSN: <span
                                  class="badge bg-light text-primary border">{{ item.hsn_code }}</span></div>
                            </div>
                            <span class="badge bg-primary text-white rounded-pill"
                              style="font-size: 0.7rem;">Select</span>
                          </li>
                        </ul>
                      </div>
                    </td>
                    <td class="hsn-search-cell">
                      <div class="position-relative">
                        <input v-model="invItem.hsnCode" @input="onHsnSearchInput(invItem.hsnCode, idx, 'hsnCode')"
                          @focus="onHsnSearchFocus(invItem.hsnCode, idx, 'hsnCode')"
                          @keydown.down.prevent="navigateHsnSuggestion(1)"
                          @keydown.up.prevent="navigateHsnSuggestion(-1)"
                          @keydown.enter.prevent="selectHighlightedHsn(idx)" @keydown.esc="closeHsnDropdown" type="text"
                          class="form-control form-control-sm pe-4" placeholder="HSN code" autocomplete="off" />
                        <span v-if="isHsnLoading && activeHsnIndex === idx && activeHsnField === 'hsnCode'"
                          class="position-absolute top-50 end-0 translate-middle-y me-2 text-muted"
                          style="pointer-events: none;">
                          <span class="spinner-border spinner-border-sm"
                            style="width: 0.85rem; height: 0.85rem;"></span>
                        </span>
                      </div>

                      <!-- Autocomplete Dropdown for HSN Code -->
                      <div v-if="isDropdownOpen && activeHsnIndex === idx && activeHsnField === 'hsnCode'"
                        class="hsn-autocomplete-menu shadow-lg border rounded-3 p-0" style="min-width: 320px;">
                        <div v-if="isHsnLoading && hsnSuggestions.length === 0"
                          class="p-3 text-center text-muted small">
                          <span class="spinner-border spinner-border-sm me-2 text-primary"></span> Searching...
                        </div>
                        <div v-else-if="!isHsnLoading && hsnSuggestions.length === 0"
                          class="p-3 text-center text-muted small">
                          No matching HSN code found
                        </div>
                        <ul v-else class="list-unstyled mb-0 hsn-suggestion-list">
                          <li v-for="(item, sIdx) in hsnSuggestions" :key="item.id || sIdx"
                            class="hsn-suggestion-item px-3 py-2 border-bottom d-flex align-items-center justify-content-between"
                            :class="{ 'active-suggestion': highlightedSuggestionIndex === sIdx }"
                            @mousedown.prevent="selectHsnItem(item, idx)">
                            <div class="me-2 text-truncate" style="max-width: 240px;">
                              <div class="fw-bold text-primary small"><span
                                  class="badge bg-light text-primary border">{{ item.hsn_code }}</span></div>
                              <div class="text-muted small text-truncate" :title="item.description"
                                style="font-size: 0.75rem;">{{ item.description }}</div>
                            </div>
                            <span class="badge bg-primary text-white rounded-pill"
                              style="font-size: 0.7rem;">Select</span>
                          </li>
                        </ul>
                      </div>
                    </td>
                    <td>
                      <select v-model="invItem.unit" @change="clearError(`inv_unit_${idx}`)"
                        class="form-control form-select-sm text-center"
                        :class="{ 'is-invalid': errors[`inv_unit_${idx}`] }" name="invoice_unity_type">
                        <option value="" disabled>Select...</option>
                        <option value="PCS">PCS</option>
                        <option value="Pkt">Pkt</option>
                        <option value="Nos">Nos</option>
                        <option value="Bottle">Bottle</option>
                        <option value="Pair">Pair</option>
                        <option value="Strip">Strip</option>
                        <option value="Dozen">Dozen</option>
                        <option value="Gross">Gross</option>
                        <option value="Sets">Sets</option>
                        <option value="Box">Box</option>
                        <option value="KG">KG</option>
                        <option value="Gram">Gram</option>
                        <option value="Container">Container</option>
                        <option value="Carats">Carats</option>
                      </select>
                      <div v-if="errors[`inv_unit_${idx}`]" class="invalid-feedback d-block small">{{
                        errors[`inv_unit_${idx}`] }}</div>
                    </td>
                    <td>
                      <input v-model.number="invItem.qty"
                        @input="handleInvoiceItemChange(invItem); clearError(`inv_qty_${idx}`)" type="number"
                        class="form-control form-control-sm text-center" min="1"
                        :class="{ 'is-invalid': errors[`inv_qty_${idx}`] }" placeholder="1" />
                      <div v-if="errors[`inv_qty_${idx}`]" class="invalid-feedback d-block small">{{
                        errors[`inv_qty_${idx}`] }}</div>
                    </td>
                    <td>
                      <input v-model.number="invItem.rate"
                        @input="handleInvoiceItemChange(invItem); clearError(`inv_rate_${idx}`)" type="number"
                        class="form-control form-control-sm text-center" min="0" step="0.01"
                        :class="{ 'is-invalid': errors[`inv_rate_${idx}`] }" placeholder="0.00" />
                      <div v-if="errors[`inv_rate_${idx}`]" class="invalid-feedback d-block small">{{
                        errors[`inv_rate_${idx}`] }}</div>
                    </td>
                    <td class="text-center fw-semibold text-dark">{{ (Number(invItem.amount) || 0).toFixed(2) }}
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
              <div v-if="errors.invoice_boxes"
                class="alert alert-danger py-2 px-3 small mt-2 mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ errors.invoice_boxes }}</span>
              </div>
              <div v-if="errors.invoice_items"
                class="alert alert-danger py-2 px-3 small mt-2 mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ errors.invoice_items }}</span>
              </div>
            </div>

            <!-- Lower Summary Inputs -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Invoice Amount <span class="text-danger">*</span></label>
                <input :value="(invoiceDltForm.invoiceTotalVal || 0).toFixed(2)" type="text"
                  class="form-control bg-light fw-bold" readonly />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Currency <span class="text-danger">*</span></label>
                <select v-model="invoiceDltForm.currency" @change="clearError('currency')" class="form-select"
                  :class="{ 'is-invalid': errors.currency }">
                  <option value="" disabled>Select Currency</option>
                  <option v-for="curr in CURRENCIES" :key="curr" :value="curr">
                    {{ curr }}
                  </option>
                </select>
                <div v-if="errors.currency" class="invalid-feedback">{{ errors.currency }}</div>
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
                  <select v-model="invoiceDltForm.invoiceType" @change="clearError('invoiceType')" class="form-select"
                    :class="{ 'is-invalid': errors.invoiceType }">
                    <option value="" disabled>Select Invoice Type</option>
                    <option value="Commercial">Commercial</option>
                    <option value="CSB 4">CSB 4</option>
                    <option value="CSB 5">CSB 5</option>
                  </select>
                  <div v-if="errors.invoiceType" class="invalid-feedback">{{ errors.invoiceType }}</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Invoice No <span class="text-danger">*</span></label>
                  <input v-model="invoiceDltForm.invoiceNo" @input="clearError('invoiceNo')" type="text"
                    class="form-control" :class="{ 'is-invalid': errors.invoiceNo }"
                    placeholder="Enter invoice number" />
                  <div v-if="errors.invoiceNo" class="invalid-feedback">{{ errors.invoiceNo }}</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Invoice Date <span class="text-danger">*</span></label>
                  <input v-model="invoiceDltForm.invoiceDate" @input="clearError('invoiceDate')" type="date"
                    class="form-control" :class="{ 'is-invalid': errors.invoiceDate }" />
                  <div v-if="errors.invoiceDate" class="invalid-feedback">{{ errors.invoiceDate }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold">Incoterms <span class="text-danger">*</span></label>
                  <select v-model="invoiceDltForm.Incoterms" class="form-select">
                    <option value="DDP">DDP</option>
                    <option value="DDU">DDU</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold">Note</label>
                  <select v-model="invoiceDltForm.invoiceNote" class="form-select">
                    <option value="Gift">Gift</option>
                    <option value="Sample">Sample</option>
                    <option value="Commercial">Commercial</option>
                  </select>
                </div>
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
              class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold" :class="{
                'opacity-50': (currentStep === 1 && !isStep1Valid) || (currentStep === 2 && !isStep2Valid)
              }" :title="currentStep === 1 && !isStep1Valid ? 'Please fill all required sender fields & upload KYC' :
                currentStep === 2 && !isStep2Valid ? 'Please fill all required consignee fields' : ''
                ">
              Next &rarr;
            </button>
            <button v-if="currentStep === 4" @click="handleBookNow" :disabled="isBooking" type="button"
              class="btn btn-next-custom px-4 py-2 rounded-3 fw-bold d-inline-flex align-items-center gap-2">
              <span v-if="isBooking" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              <i v-else class="bi bi-calendar-check"></i>
              <span>{{ isBooking ? 'Booking Shipment...' : 'Book Now' }}</span>
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

.page-add-shipment .v-select.is-invalid .vs__dropdown-toggle,
.v-select.form-control.is-invalid .vs__dropdown-toggle {
  border-color: #dc3545 !important;
}

.page-add-shipment .v-select.is-invalid~.invalid-feedback,
.v-select.form-control.is-invalid~.invalid-feedback {
  display: block !important;
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

/* HSN Code & Description Autocomplete Dropdown */
.hsn-search-cell {
  position: relative !important;
}

.hsn-autocomplete-menu {
  position: absolute;
  top: calc(100% + 2px);
  left: 0;
  z-index: 1060;
  min-width: 340px;
  max-width: 440px;
  max-height: 240px;
  overflow-y: auto;
  background-color: #ffffff;
  border: 1px solid #dee2e6;
  border-radius: 8px !important;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
}

.hsn-suggestion-item {
  cursor: pointer;
  transition: background-color 0.15s ease-in-out;
}

.hsn-suggestion-item:hover,
.hsn-suggestion-item.active-suggestion {
  background-color: #f1f4f9;
}
</style>
