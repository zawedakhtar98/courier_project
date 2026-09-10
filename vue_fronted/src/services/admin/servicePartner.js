import API from "../ApiService";

const getAllServicePartners = async (perPage, page) => {
    try {
        const resp = await API.get('admin/service-partner/getAll-partners',
            {
                params: {
                    perPage: perPage,
                    page: page
                }
            });
        return resp.data;
    }
    catch (error) {
        return error;
    }
}

const addNewServicePartner = async (formdata) => {
    try {
        const resp = await API.post('admin/service-partner/add-new', formdata);
        return resp.data
    }
    catch (err) {
        return err;
    }
}



export {
    getAllServicePartners,
    addNewServicePartner
};