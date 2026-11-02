import api from '../api';

export const stockTransferService = {
  /**
   * List transfers with optional filtering (status, from_branch_id, to_branch_id)
   */
  async list(params = {}) {
    const response = await api.get('/transfers', { params });
    return response.data?.data || response.data || [];
  },

  /**
   * Create a new draft transfer
   */
  async create(data) {
    const response = await api.post('/transfers', data);
    return response.data;
  },

  /**
   * Get single transfer details with items
   */
  async get(id) {
    const response = await api.get(`/transfers/${id}`);
    return response.data?.data || response.data;
  },

  /**
   * Dispatch transfer (source decrement & in-transit transition)
   */
  async dispatch(id) {
    const response = await api.post(`/transfers/${id}/dispatch`);
    return response.data;
  },

  /**
   * Receive transfer (destination increment & completion)
   */
  async receive(id) {
    const response = await api.post(`/transfers/${id}/receive`);
    return response.data;
  },

  /**
   * Cancel transfer (draft cancellation or dispatched restoration)
   */
  async cancel(id, reason = '') {
    const response = await api.post(`/transfers/${id}/cancel`, { reason });
    return response.data;
  },

  /**
   * Get delivery challan printable payload
   */
  async getChallan(id) {
    const response = await api.get(`/transfers/${id}/challan`);
    return response.data?.data || response.data;
  },
};

export default stockTransferService;
