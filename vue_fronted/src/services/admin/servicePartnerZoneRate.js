import API from "../ApiService";

const addServicePartnerZoneRate = async (formdata) => {
    try {
        const resp = await API.post('admin/service-partner/add-zone-rates', formdata);
        return resp.data
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
    }
}

const getServicePartnerZoneRate = async (perPage, page) => {
    try {
        const resp = await API.get('admin/service-partner/get-zone-wise-rate',
            {
                params: {
                    perPage: perPage,
                    page: page
                }
            });
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
    }
}


const bulkUploadServicePartnerZoneRates = async (formData) => {
    try {
        const resp = await API.post('admin/service-partner/bulk-upload-zone-rates', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
        throw err;
    }
}

export {
    addServicePartnerZoneRate,
    getServicePartnerZoneRate,
    bulkUploadServicePartnerZoneRates
};