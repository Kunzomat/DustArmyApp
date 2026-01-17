<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . "/auth.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = "database-5019385374.webspace-host.com";
$db   = "dbs15166077";
$user = "dbu358620";
$pass = "!Don10Draco!";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
	$conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => "DB Verbindung fehlgeschlagen", "details" => $e->getMessage()]);
    exit;
}

function jsonBody(): array {
    $raw = file_get_contents("php://input");
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function ok($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function fail(string $msg, int $code = 400, $extra = null): void {
    http_response_code($code);
    $out = ["error" => $msg];
    if ($extra !== null) $out["details"] = $extra;
    echo json_encode($out);
    exit;
}

function requireInt($value, string $name): int {
    if ($value === null || $value === '' || !is_numeric($value)) {
        fail("Missing/invalid parameter: $name", 400);
    }
    return (int)$value;
}

function requireString($value, string $name): string {
    $s = trim((string)$value);
    if ($s === '') fail("Missing/invalid parameter: $name", 400);
    return $s;
}

$action = $_GET['action'] ?? '';

/**
 * =========================================================
 * Armies
 * =========================================================
 * GET  ?action=armies.list
 * GET  ?action=armies.get&id=1
 * POST ?action=armies.create  {name,faction_id,points_limit}
 * PUT  ?action=armies.update&id=1 {name?,faction_id?,points_limit?}
 * DELETE ?action=armies.delete&id=1
 */

if ($action === 'armies.list') {
    $sql = "SELECT a.id, a.name, a.faction_id, f.name AS faction_name, a.points_limit
            FROM armies a
            JOIN factions f ON f.id = a.faction_id
            ORDER BY a.id DESC";
    $res = $conn->query($sql);
    ok(["armies" => $res->fetch_all(MYSQLI_ASSOC)]);
}

if ($action === 'armies.get') {
    $id = requireInt($_GET['id'] ?? null, 'id');

    // Army basic
    $stmt = $conn->prepare("SELECT a.id, a.name, a.faction_id, f.name AS faction_name, a.points_limit
                            FROM armies a
                            JOIN factions f ON f.id = a.faction_id
                            WHERE a.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $army = $stmt->get_result()->fetch_assoc();
    if (!$army) fail("Army not found", 404);

    // Platoons in army
    $stmt = $conn->prepare("
        SELECT ap.id AS army_platoon_id, p.id AS platoon_id, p.name, p.rule_id, p.faction_id
        FROM army_platoons ap
        JOIN platoons p ON p.id = ap.platoon_id
        WHERE ap.army_id = ?
        ORDER BY ap.id ASC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $platoons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Units in army (with slot info if inside platoon_unit_id)
    $stmt = $conn->prepare("
        SELECT
            au.id AS army_unit_id,
            au.army_id,
            au.platoon_id,
            au.platoon_unit_id,
            pu.slot AS platoon_slot,
            au.unit_id,
            u.name AS unit_name,
            u.points AS unit_points,
            au.quantity
        FROM army_units au
        JOIN units u ON u.id = au.unit_id
        LEFT JOIN platoon_units pu ON pu.id = au.platoon_unit_id
        WHERE au.army_id = ?
        ORDER BY au.id ASC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $units = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // current points sum (simple: unit_points * quantity)
    $sum = 0;
    foreach ($units as $row) {
        $sum += ((int)$row['unit_points']) * ((int)$row['quantity']);
    }

    ok([
        "army" => $army,
        "platoons" => $platoons,
        "units" => $units,
        "points_used" => $sum,
        "points_remaining" => ((int)$army["points_limit"]) - $sum
    ]);
}

if ($action === 'armies.create') {
    $body = jsonBody();
    $name = requireString($body['name'] ?? null, 'name');
    $factionId = requireInt($body['faction_id'] ?? null, 'faction_id');
    $limit = isset($body['points_limit']) ? (int)$body['points_limit'] : 100;

    $stmt = $conn->prepare("INSERT INTO armies (name, faction_id, points_limit) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $name, $factionId, $limit);
    $stmt->execute();

    ok(["id" => $conn->insert_id], 201);
}

if ($action === 'armies.update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') fail("Use PUT", 405);
    $id = requireInt($_GET['id'] ?? null, 'id');
    $body = jsonBody();

    $fields = [];
    $params = [];
    $types  = "";

    if (isset($body['name'])) {
        $fields[] = "name = ?";
        $params[] = requireString($body['name'], 'name');
        $types .= "s";
    }
    if (isset($body['faction_id'])) {
        $fields[] = "faction_id = ?";
        $params[] = (int)$body['faction_id'];
        $types .= "i";
    }
    if (isset($body['points_limit'])) {
        $fields[] = "points_limit = ?";
        $params[] = (int)$body['points_limit'];
        $types .= "i";
    }

    if (!$fields) fail("Nothing to update", 400);

    $sql = "UPDATE armies SET " . implode(", ", $fields) . " WHERE id = ?";
    $types .= "i";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    ok(["ok" => true]);
}

if ($action === 'armies.delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') fail("Use DELETE", 405);
    $id = requireInt($_GET['id'] ?? null, 'id');

    $stmt = $conn->prepare("DELETE FROM armies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    ok(["ok" => true]);
}

/**
 * =========================================================
 * Platoons
 * =========================================================
 * GET  ?action=platoons.list&faction_id=3
 * POST ?action=army.platoons.add  {army_id, platoon_id}
 * DELETE ?action=army.platoons.remove&id=ARMY_PLATOON_ID
 */

if ($action === 'platoons.list') {
    $factionId = requireInt($_GET['faction_id'] ?? null, 'faction_id');

    $stmt = $conn->prepare("SELECT id, name, rule_id, faction_id FROM platoons WHERE faction_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $factionId);
    $stmt->execute();
    ok(["platoons" => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

if ($action === 'army.platoons.add') {
    $body = jsonBody();
    $armyId = requireInt($body['army_id'] ?? null, 'army_id');
    $platoonId = requireInt($body['platoon_id'] ?? null, 'platoon_id');

    $stmt = $conn->prepare("INSERT INTO army_platoons (army_id, platoon_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $armyId, $platoonId);
    $stmt->execute();

    ok(["id" => $conn->insert_id], 201);
}

if ($action === 'army.platoons.remove') {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') fail("Use DELETE", 405);
    $id = requireInt($_GET['id'] ?? null, 'id'); // army_platoons.id

    $stmt = $conn->prepare("DELETE FROM army_platoons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    ok(["ok" => true]);
}

/**
 * =========================================================
 * Army Units
 * =========================================================
 * POST   ?action=army.units.add
 *        {
 *          army_id,
 *          unit_id,
 *          quantity,
 *          platoon_id (optional: null or omitted for "free unit"),
 *          platoon_unit_id (optional)
 *        }
 *
 * PUT    ?action=army.units.update&id=ARMY_UNIT_ID  {quantity}
 * DELETE ?action=army.units.delete&id=ARMY_UNIT_ID
 */

if ($action === 'army.units.add') {
    $body = jsonBody();
    $armyId = requireInt($body['army_id'] ?? null, 'army_id');
    $unitId = requireInt($body['unit_id'] ?? null, 'unit_id');
    $qty = isset($body['quantity']) ? max(1, (int)$body['quantity']) : 1;

    // platoon_id optional (for free units)
    $platoonId = $body['platoon_id'] ?? null; // may be null
    $platoonUnitId = $body['platoon_unit_id'] ?? null; // may be null

    // If your DB still requires platoon_id NOT NULL, you MUST send a valid platoon_id.
    // We validate if provided:
    if ($platoonId !== null && $platoonId !== '') $platoonId = (int)$platoonId;
    else $platoonId = null;

    if ($platoonUnitId !== null && $platoonUnitId !== '') $platoonUnitId = (int)$platoonUnitId;
    else $platoonUnitId = null;

    // Insert
    if ($platoonId === null) {
        // Free unit: requires army_units.platoon_id to allow NULL and FK to be removed/adjusted.
        $stmt = $conn->prepare("INSERT INTO army_units (army_id, platoon_unit_id, unit_id, quantity, platoon_id)
                                VALUES (?, ?, ?, ?, NULL)");
        // platoon_unit_id can be NULL
        $stmt->bind_param("iiii", $armyId, $platoonUnitId, $unitId, $qty);
    } else {
        $stmt = $conn->prepare("INSERT INTO army_units (army_id, platoon_unit_id, unit_id, quantity, platoon_id)
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiii", $armyId, $platoonUnitId, $unitId, $qty, $platoonId);
    }

    $stmt->execute();
    ok(["id" => $conn->insert_id], 201);
}

if ($action === 'army.units.update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') fail("Use PUT", 405);
    $id = requireInt($_GET['id'] ?? null, 'id');
    $body = jsonBody();
    $qty = isset($body['quantity']) ? max(1, (int)$body['quantity']) : null;
    if ($qty === null) fail("Missing quantity", 400);

    $stmt = $conn->prepare("UPDATE army_units SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $qty, $id);
    $stmt->execute();

    ok(["ok" => true]);
}

if ($action === 'army.units.delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') fail("Use DELETE", 405);
    $id = requireInt($_GET['id'] ?? null, 'id');

    $stmt = $conn->prepare("DELETE FROM army_units WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    ok(["ok" => true]);
}

/**
 * =========================================================
 * Platoon Unit Templates
 * (Welche Units sind in einem Platoon-Slot erlaubt?)
 * =========================================================
 * GET ?action=platoon.units.list&platoon_id=2
 */
if ($action === 'platoon.units.list') {
    $platoonId = requireInt($_GET['platoon_id'] ?? null, 'platoon_id');

    $stmt = $conn->prepare("
        SELECT pu.id, pu.platoon_id, pu.slot, pu.unit_id, u.name AS unit_name, u.points AS unit_points
        FROM platoon_units pu
        JOIN units u ON u.id = pu.unit_id
        WHERE pu.platoon_id = ?
        ORDER BY pu.id ASC
    ");
    $stmt->bind_param("i", $platoonId);
    $stmt->execute();
    ok(["platoon_units" => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

fail("Unknown action", 404, ["action" => $action]);
