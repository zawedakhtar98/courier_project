import API from "../ApiService";

const addZone = async (payload) => {
    try {
        const response = await API.post('admin/zone/add', payload);
        return response.data;
    } catch (error) {
        throw error;
    }
}

const getZoneList = async () => {
    try {
        const response = await API.get('admin/zone/get-list');
        return response.data;
    } catch (error) {
        throw error;
    }
}

const updateZone = async () => {

}

const deleteZone = async () => {

}

const addZoneCountryMapping = async (payload) => {
    try {
        const response = await API.post('admin/zone/map-countries', payload);
        return response.data;
    } catch (error) {
        throw error;
    }
}

export {
    addZone,
    getZoneList,
    updateZone,
    deleteZone,
    addZoneCountryMapping
}