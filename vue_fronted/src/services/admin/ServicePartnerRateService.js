import API from "../ApiService";

const saveServicePartnerRates = async (payload) => {
    try {
        const response = await API.post('admin/service-partner-rates/save', payload);
        return response.data;
    } catch (error) {
        throw error;
    }
}

const getServicePartnerRates = async (servicePartnerId) => {
    try {
        const response = await API.get(`admin/service-partner-rates/get/${servicePartnerId}`);
        return response.data;
    } catch (error) {
        throw error;
    }
}

export {
    saveServicePartnerRates,
    getServicePartnerRates
}
