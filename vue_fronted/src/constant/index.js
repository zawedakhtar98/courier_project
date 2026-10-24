const RATE_TYPES = [
    'Flat', 'Per_Kg'
]

const DOCUMENT_TYPE = [
    'NONDOC',
    'DOC',
]

const CURRENCIES = [
    { label: '₹ (INR)', value: '₹', value: '' },
    { label: '$ (USD)', value: '$' },
]

const shipmentCalculation_basis = [
    { value: 'per_shipment', label: 'Per Shipment', description: 'One fixed amount for the whole shipment. Ex: Handling ₹100, Documentation ₹50' },
    { value: 'per_box', label: 'Per Box', description: 'Fixed amount × number of boxes. Ex: ₹30 per box' },
    { value: 'chargeable_weight', label: 'Chargeable Weight', description: 'Rate × the higher of actual and volumetric weight. Ex: Remote area ₹15/kg' },
    { value: 'actual_weight', label: 'Actual Weight', description: 'Rate × real weight on the scale. Ex: Special handling per kg' },
    { value: 'freight', label: 'Freight', description: '% of base freight only. Ex: CFX 4.75% of 3,771.96' },
    { value: 'shipment_total', label: 'Shipment Total', description: '% of freight plus all charges with a smaller apply_order. Ex: Fuel 43.25%' },
    { value: 'shipment_value', label: 'Shipment Value', description: '% of the declared goods value. Ex: Insurance 1%' },
    { value: 'sub_total', label: 'Sub Total', description: '% of everything before tax (the final shipment total). Ex: GST 18%' }
]

/**
 * calculation_method:
 * 1. if rate_type is Flat => Calculate on per_shipment
 * 
 * calculation_basis:
 * 1. if calculation_method is percentage => Calculate on shipment_value
 * 2. if calculation_method is running_total => Calculate on running_total
 * 3. if calculation_method is sub_total => Calculate on sub_total
 * 4. if calculation_method is rate_type => Calculate on rate_type
 * 5. if calculation_method is freight => Calculate on freight
 * 6. if calculation_method is weight => Calculate on weight
 * 7. if calculation_method is per_box => Calculate on per_box
 * 8. if calculation_method is shipment_value => Calculate on shipment_value
 * 9. if calculation_method is sub_total => Calculate on sub_total
 *  
 * 
 * 1. per_shipment	    |   flat    | One fixed amount for the whole shipment. | Handling ₹100, Documentation ₹50
2. per_box	            |   flat    | Fixed amount × number of boxes.          | ₹30 per box
3. chargeable_weight	|   per_kg  | Rate × the higher of actual and volumetric weight. | Remote area ₹15/kg
4. actual_weight	    |   per_kg  | Rate × real weight on the scale. | Special handling per kg
5. freight	            |  percentage | % of base freight only. | CFX 4.75% of 3,771.96
6. running_total	    |  percentage | % of freight plus all charges with a smaller apply_order. | Fuel 43.25%
7. shipment_value	    |  percentage | % of the declared goods value. | Insurance 1%
8. sub_total	        |  percentage | % of everything before tax (the final running total). | GST 18%

 * 
 * **/

export { RATE_TYPES, DOCUMENT_TYPE, CURRENCIES, shipmentCalculation_basis }