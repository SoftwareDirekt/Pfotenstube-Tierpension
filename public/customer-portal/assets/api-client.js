const CustomerPortalApi = (() => {
  const TOKEN_KEY = "customer_portal_token";
  const API_BASE_KEY = "customer_portal_api_base";
  const defaultBase = `${window.location.origin}/api`;

  function getApiBase() {
    return localStorage.getItem(API_BASE_KEY) || defaultBase;
  }

  function setApiBase(base) {
    localStorage.setItem(API_BASE_KEY, base.replace(/\/+$/, ""));
  }

  function getToken() {
    return localStorage.getItem(TOKEN_KEY);
  }

  function setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
  }

  function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
  }

  async function request(path, { method = "GET", body, auth = false } = {}) {
    const headers = { "Content-Type": "application/json" };
    if (auth && getToken()) headers.Authorization = `Bearer ${getToken()}`;

    const response = await fetch(`${getApiBase()}${path}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.message || "Anfrage fehlgeschlagen.");
    }
    return data;
  }

  return {
    getApiBase,
    setApiBase,
    getToken,
    setToken,
    clearToken,
    request,
  };
})();
