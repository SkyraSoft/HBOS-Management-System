import api from '../api';

export const cashRegisterService = {
  /**
   * Retrieve current active register session for branch.
   */
  async getCurrentSession(params = {}) {
    const cleanParams = {};
    if (params.branch_id && params.branch_id !== 'null' && params.branch_id !== 'undefined') {
      cleanParams.branch_id = params.branch_id;
    }
    if (params.financial_account_id && params.financial_account_id !== 'null' && params.financial_account_id !== 'undefined') {
      cleanParams.financial_account_id = params.financial_account_id;
    }
    const response = await api.get('/cash-register-sessions/current', { params: cleanParams });
    return response.data;
  },

  /**
   * Open a register session with opening float.
   */
  async openRegister(payload) {
    const response = await api.post('/cash-register-sessions/open', payload);
    return response.data;
  },
  async openSession(payload) {
    return this.openRegister(payload);
  },

  /**
   * Close a register session with counted cash and optional discrepancy reason.
   */
  async closeRegister(payload) {
    const response = await api.post('/cash-register-sessions/close', payload);
    return response.data;
  },
  async closeSession(payload) {
    return this.closeRegister(payload);
  },

  /**
   * Get daily summary for closed sessions.
   */
  async getDailySummary(params = {}) {
    const response = await api.get('/cash-register-sessions/daily-summary', { params });
    return response.data;
  },

  /**
   * List sessions history.
   */
  async getSessions(params = {}) {
    const response = await api.get('/cash-register-sessions', { params });
    return response.data;
  },

  /**
   * Get single session details.
   */
  async getSession(id) {
    const response = await api.get(`/cash-register-sessions/${id}`);
    return response.data;
  },

  /**
   * Retrieve Net Profit / Profitability statement.
   */
  async getProfitabilityReport(params = {}) {
    const response = await api.get('/reports/profitability', { params });
    return response.data;
  },
};

export default cashRegisterService;
