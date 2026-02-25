import axios from 'axios';

// Simple API client for the student character program endpoints.
// Assumes you already have:
// - a base API URL, e.g. https://your-domain.com/api/v1
// - an auth token for the logged-in staff user

export function createStudentCharacterApi({ baseUrl, token }) {
  const client = axios.create({
    baseURL: baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });

  async function listReports(studentId) {
    const res = await client.get(`/students/${studentId}/character-reports`);
    const { data } = res.data || {};
    return data?.reports ?? [];
  }

  async function saveReport({ studentId, recordDate, status, headline, notes, termId, traits }) {
    const payload = {
      ...(recordDate ? { record_date: recordDate } : {}),
      ...(status ? { status } : {}),
      ...(headline ? { headline } : {}),
      ...(notes ? { notes } : {}),
      ...(termId ? { term_id: termId } : {}),
      traits: traits ?? [],
    };

    const res = await client.post(`/students/${studentId}/character-reports`, payload);
    const { data } = res.data || {};
    return data?.report;
  }

  return {
    listReports,
    saveReport,
  };
}

