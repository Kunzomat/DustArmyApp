const API_BASE = "http://localhost/dust1947-backend/api.php";

export async function getUnits() {
  const res = await fetch(API_BASE);
  if (!res.ok) throw new Error("Fehler beim Laden der Einheiten");
  return res.json();
}

export async function getUnit(id) {
  const res = await fetch(`${API_BASE}?unit_id=${id}`);
  if (!res.ok) throw new Error("Fehler beim Laden der Einheit");
  return res.json();
}
