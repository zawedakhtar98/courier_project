import API from "../ApiService";

const shipmentRateCalculator = async (payload) => {
    try {
        const response = await API.post('shipment/calculator', payload);
        return response.data;
    } catch (error) {
        throw error;
    }
}

export {
    shipmentRateCalculator
}