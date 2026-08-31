<script>
export function defaultSlabData() {
    return {
        service: 'DHL EXPRESS',
        packageType: 'NONDOC',
        zoneCount: 14,
        rows: [
            { weight: '0.500', zones: ['1,214', '1,206', '1,366', '1,289', '1,609', '1,788', '1,460', '3,309', '1,607', '2,471', '3,343', '1,586', '2,752', '1,642'] },
            { weight: '1.000', zones: ['1,461', '1,441', '1,657', '1,442', '1,983', '2,132', '1,684', '4,221', '1,822', '3,215', '3,854', '1,779', '3,416', '1,694'] },
            { weight: '1.500', zones: ['1,707', '1,705', '1,946', '1,594', '2,349', '2,467', '1,910', '5,123', '1,990', '3,572', '4,362', '1,959', '3,944', '2,006'] },
            { weight: '2.000', zones: ['1,953', '1,962', '2,235', '1,745', '2,713', '2,804', '2,135', '6,027', '2,227', '3,931', '4,867', '2,136', '4,472', '2,306'] },
            { weight: '2.500', zones: ['2,242', '2,228', '2,386', '1895', '2,813', '3,143', '2,362', '6,934', '2,473', '4,292', '5,375', '2,388', '5,000', '2,619'] },
            { weight: '3.000', zones: ['2,433', '2,436', '2,651', '2,137', '3,049', '3,413', '2,667', '7,882', '2,732', '4,663', '5,590', '2,634', '5,489', '2,863'] },
            { weight: '3.500', zones: ['2,623', '2,644', '2,915', '2,381', '3,285', '3,684', '2,971', '8,830', '2,991', '5,036', '5,804', '2,880', '5,979', '3,109'] }
        ]
    }
}
</script>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    service: { type: String, default: 'DHL EXPRESS' },
    packageType: { type: String, default: 'NONDOC' },
    zoneCount: { type: Number, default: 14 },
    defaultOpen: { type: Boolean, default: true },
    rows: { type: Array, default: () => defaultSlabData().rows }
})

const isOpen = ref(props.defaultOpen)

const toggleCollapse = () => {
    isOpen.value = !isOpen.value
}
</script>

<template>
    <div class="container-fluid py-4">
        <div class="rate-wrapper">
            <!-- DHL ACCORDION HEADER -->
            <button type="button" class="dhl-header" :class="{ collapsed: !isOpen }" :aria-expanded="isOpen"
                @click="toggleCollapse">
                <span class="dhl-title">{{ service }}</span>
                <span class="nondoc">{{ packageType }}</span>
                <span class="dhl-arrow">▼</span>
            </button>

            <!-- COLLAPSIBLE CONTENT -->
            <div v-show="isOpen" class="collapse-content">
                <div class="rate-table-wrapper">
                    <table class="table rate-table">
                        <thead>
                            <tr>
                                <th>To Weight (kg)</th>
                                <th v-for="zone in zoneCount" :key="zone">
                                    Zone {{ zone }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in rows" :key="index">
                                <td class="weight">{{ row.weight }}</td>
                                <td v-for="(val, zIdx) in row.zones" :key="zIdx">
                                    {{ val }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rate-wrapper {
    border: 1px solid #d5dce5;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
}

/* DHL accordion header */
.dhl-header {
    width: 100%;
    border: 0;
    background: #f8f9fa;
    padding: 14px 20px;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dhl-title {
    font-size: 18px;
    font-weight: 700;
    color: #1d1d1d;
}

.nondoc {
    background: #e9eef4;
    color: #526170;
    border-radius: 15px;
    padding: 4px 12px;
    font-size: 13px;
    font-weight: 500;
}

.dhl-arrow {
    margin-left: auto;
    font-size: 14px;
    transition: transform 0.25s ease;
}

/* Rotate arrow when collapsed */
.dhl-header.collapsed .dhl-arrow {
    transform: rotate(-90deg);
}

.rate-table-wrapper {
    overflow-x: auto;
}

.rate-table {
    min-width: 1400px;
    margin-bottom: 0;
}

.rate-table th,
.rate-table td {
    padding: 12px 18px;
    text-align: center;
    white-space: nowrap;
    border-bottom: 1px solid #e3e7ec;
}

.rate-table thead th {
    background: #fff;
    color: #34495e;
    font-weight: 500;
}

.rate-table .weight {
    color: #0056d6;
    font-weight: 600;
}
</style>