/**
 * Skular mentee read-path load test (k6).
 *
 * This intentionally exercises only cheap, authenticated read paths:
 * login → dashboard → scholarship catalog → library. It never calls AI
 * providers, never submits practice forms and never touches production data.
 *
 * Usage (staging only):
 *   BASE_URL=https://staging.example.com \
 *   MENTEE_EMAIL=loadtest@example.com \
 *   MENTEE_PASSWORD=secret \
 *   k6 run tests/Load/skular-smoke.js
 *
 * The ramps below are a starting point, NOT a proof of 10,000 concurrent users.
 * Raise stages only after the staging database and worker capacity are sized.
 */
import http from 'k6/http';
import { check, group, sleep } from 'k6';

export const options = {
  scenarios: {
    mentee_reads: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '1m', target: 5 },
        { duration: '2m', target: 20 },
        { duration: '2m', target: 40 },
        { duration: '1m', target: 0 },
      ],
      gracefulRampDown: '30s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    'http_req_duration{page:dashboard}': ['p(95)<800'],
    'http_req_duration{page:catalog}': ['p(95)<600'],
    'http_req_duration{page:library}': ['p(95)<600'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const EMAIL = __ENV.MENTEE_EMAIL || '';
const PASSWORD = __ENV.MENTEE_PASSWORD || '';

function login() {
  const page = http.get(`${BASE_URL}/login`, { tags: { page: 'login' } });
  const token = page.body.match(/name="_token"\s+value="([^"]+)"/);

  check(page, { 'login page loads': (r) => r.status === 200 });
  check(token, { 'csrf token present': (value) => value !== null });

  if (!token) {
    return false;
  }

  const response = http.post(`${BASE_URL}/login`, {
    _token: token[1],
    email: EMAIL,
    password: PASSWORD,
  }, { tags: { page: 'login' }, redirects: 0 });

  const loggedIn = response.status === 302 && !String(response.headers.Location || '').includes('/login');

  check(loggedIn, { 'login succeeds': (value) => value === true });

  return loggedIn;
}

export default function () {
  if (!login()) {
    return;
  }

  group('mentee reads', () => {
    const dashboard = http.get(`${BASE_URL}/dashboard`, { tags: { page: 'dashboard' } });
    check(dashboard, {
      'dashboard ok': (r) => r.status === 200,
      'dashboard has roadmap': (r) => r.body.includes('Roadmap belajar'),
    });

    const catalog = http.get(`${BASE_URL}/scholarships`, { tags: { page: 'catalog' } });
    check(catalog, { 'catalog ok': (r) => r.status === 200 });

    const library = http.get(`${BASE_URL}/library`, { tags: { page: 'library' } });
    check(library, { 'library ok': (r) => r.status === 200 });
  });

  sleep(2);
}
