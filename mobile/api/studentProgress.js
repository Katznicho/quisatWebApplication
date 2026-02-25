import axios from 'axios';

// API client for the existing student progress endpoint used by the parent app.

export function createStudentProgressApi({ baseUrl, token }) {
  const client = axios.create({
    baseURL: baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });

  async function getProgress(studentId) {
    const res = await client.get(`/students/${studentId}/progress`);
    return res.data?.data ?? {};
  }

  return {
    getProgress,
  };
}

