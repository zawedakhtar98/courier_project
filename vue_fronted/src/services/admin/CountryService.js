import API from "../ApiService";

const addCountry = async (countryData) => {
    try {
        const response = await API.post('/admin/country/add', countryData);
        return response.data;
    } catch (error) {
        throw error;
    }
}

const getAllCountries = async () => {
    try {
        const response = await API.get('admin/country/get-list');
        return response.data;
    } catch (error) {
        throw error;
    }
}

const updateCountry = async (countryId, countryData) => {
    try {
        const response = await API.put(`admin/country/update/${countryId}`, countryData);
        return response.data;
    } catch (error) {
        throw error;
    }
}

const updateCoutryStatus = async (countryId, countryData) => {
    try {
        const response = await API.put(`admin/country/update-status/${countryId}`, countryData);
        return response.data;
    } catch (error) {
        throw error;
    }
}

const deleteCountry = async (countryId) => {
    try {
        const response = await API.delete(`admin/country/delete/${countryId}`);
        return response.data;
    } catch (error) {
        throw error;
    }
}

export {
    addCountry,
    getAllCountries,
    updateCountry,
    updateCoutryStatus,
    deleteCountry
}
