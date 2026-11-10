import API from './apiService.js';

/**
 * Search HSN Code and Description
 * @param {string} searchTerm - Query string (HSN code digits or description text)
 * @param {object} [options] - Optional axios configuration (e.g., signal for AbortController)
 * @returns {Promise<{ status: string, data: Array }>}
 */
const HsnSearch = async (searchTerm, options = {}) => {
    const query = typeof searchTerm === 'string' ? searchTerm.trim() : '';

    // Guard clause: Do not call API for less than 2 characters
    if (query.length < 2) {
        return { status: 'success', data: [] };
    }

    try {
        const response = await API.get('hsn/search', {
            params: { q: query },
            ...options
        });
        return response.data;
    } catch (error) {
        // Handle cancelled requests cleanly (e.g., when typing fast with AbortController)
        if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
            return { status: 'canceled', data: [] };
        }
        console.error('Failed to search HSN code:', error);
        throw error;
    }
};

const searchHsn = HsnSearch;

export {
    HsnSearch,
    searchHsn
};

export default {
    HsnSearch,
    searchHsn
};
