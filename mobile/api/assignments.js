import axios from 'axios';

// API client for parent/staff assignment flows.
// Supports:
// - list assignments
// - hide assignment for parent (DELETE)
// - list hidden assignments for parent
// - restore hidden assignment for parent
export function createAssignmentsApi({ baseUrl, token }) {
  const client = axios.create({
    baseURL: baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });

  async function listAssignments(params = {}) {
    const res = await client.get('/assignments', { params });
    return res.data?.data ?? { assignments: [], pagination: null };
  }

  async function getAssignment(idOrUuid) {
    const res = await client.get(`/assignments/${idOrUuid}`);
    return res.data?.data?.assignment ?? null;
  }

  async function hideAssignment(idOrUuid) {
    const res = await client.delete(`/assignments/${idOrUuid}`);
    return res.data ?? {};
  }

  async function listHiddenAssignments(params = {}) {
    const res = await client.get('/assignments/hidden-for-parent', { params });
    return res.data?.data ?? { assignments: [], pagination: null };
  }

  async function restoreAssignment(idOrUuid) {
    const res = await client.post(`/assignments/${idOrUuid}/restore-for-parent`);
    return res.data ?? {};
  }

  return {
    listAssignments,
    getAssignment,
    hideAssignment,
    listHiddenAssignments,
    restoreAssignment,
  };
}

