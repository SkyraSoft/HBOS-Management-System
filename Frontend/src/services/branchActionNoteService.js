import api from '../api';

export const branchActionNoteService = {
  /**
   * List action notes for current user branch scope
   */
  async list(params = {}) {
    const response = await api.get('/action-notes', { params });
    return response.data?.data || response.data || [];
  },

  /**
   * Create an action note (Owner only)
   */
  async create(data) {
    const response = await api.post('/action-notes', data);
    return response.data;
  },

  /**
   * Update status of an action note (open -> in_progress -> resolved)
   */
  async updateStatus(id, status, resolutionNotes = '') {
    const response = await api.patch(`/action-notes/${id}/status`, {
      status,
      resolution_notes: resolutionNotes,
    });
    return response.data;
  },

  /**
   * Get pending count of action notes
   */
  async getPendingCount() {
    const response = await api.get('/action-notes/pending-count');
    return response.data?.count || 0;
  },
};

export default branchActionNoteService;
