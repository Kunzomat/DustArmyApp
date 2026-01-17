// src/apiClient.js

const API_BASE = "http://kunzomat.de/dust1947/backend/army_api.php";
const API_KEY = process.env.REACT_APP_API_KEY;

/**
 * action: z.B. "list_armies"
 * params: object (Query bei GET / JSON bei POST)
 * method: "GET" oder "POST"
 */
export async function apiRequest(action, params = {}, method = "GET") {
  let url = `${API_BASE}?action=${encodeURIComponent(action)}`;

  const headers = {
    "X-API-Key": API_KEY || "",
  };

  const options = {
    method,
    headers,
  };

  if (method === "GET") {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v !== undefined && v !== null) qs.append(k, String(v));
    }
    const q = qs.toString();
    if (q) url += `&${q}`;
  } else {
    headers["Content-Type"] = "application/json";
    options.body = JSON.stringify(params);
  }

  const res = await fetch(url, options);
  const text = await res.text(); // kann JSON oder PHP-Fehler/HTML sein

  // Wenn Server kein JSON liefert, gib den Text aus → damit du Fehler sofort siehst
  let data;
  try {
    data = text ? JSON.parse(text) : null;
  } catch (e) {
    throw new Error(`Kein JSON vom Server (HTTP ${res.status}): ${text.slice(0, 500)}`);
  }

  // Wenn HTTP != 200/OK → Fehlermeldung aus data.error oder fallback
  if (!res.ok) {
    throw new Error((data && data.error) ? data.error : `HTTP ${res.status}`);
  }

  return data;
}
