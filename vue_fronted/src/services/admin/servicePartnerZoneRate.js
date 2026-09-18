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


export {
    addServicePartnerZoneRate,
    getServicePartnerZoneRate
};