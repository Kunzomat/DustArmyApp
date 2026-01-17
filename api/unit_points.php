<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/unit_rating.php';

// DB-Verbindung
$host = "localhost";
$db   = "dust1947";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(array("error" => "DB connection failed: " . $conn->connect_error));
    exit;
}

$conn->set_charset("utf8mb4");

// 1) Einheiten laden (Basisdaten)
$sqlUnits = "
    SELECT 
        u.id,
        u.name,
        u.type,
        u.level,
        u.points,
        u.health,
		u.models,
        u.speed,
        u.march_speed,
        u.faction_id,
        f.name AS faction_name
    FROM units u
    LEFT JOIN factions f ON u.faction_id = f.id
    ORDER BY u.id ASC
";

$resultUnits = $conn->query($sqlUnits);
if (!$resultUnits) {
    http_response_code(500);
    echo json_encode(array("error" => "Query units failed: " . $conn->error));
    exit;
}

$units = array();
while ($row = $resultUnits->fetch_assoc()) {
    $row['special_rules'] = array();
    $row['weapons']       = array();
    $units[] = $row;
}

if (empty($units)) {
    echo json_encode(array());
    exit;
}

// Hilfsindex nach ID
$unitsById = array();
foreach ($units as $idx => $u) {
    $unitsById[$u['id']] = $idx;
}

// 2) Einheiten-Sonderregeln
$sqlUnitRules = "
    SELECT
        ur.unit_id,
        r.id,
        r.name,
        r.full_text,
		r.bonus_factor
    FROM unit_rules ur
    LEFT JOIN rules r ON r.id = ur.unit_rule_id
";

$resultUR = $conn->query($sqlUnitRules);
if ($resultUR) {
    $specialByUnit = array();
    while ($r = $resultUR->fetch_assoc()) {
        $unitId = (int)$r['unit_id'];
        if (!isset($specialByUnit[$unitId])) {
            $specialByUnit[$unitId] = array();
        }
        $specialByUnit[$unitId][] = array(
            "id"   => $r["id"],
            "name" => $r["name"],
            "text" => $r["full_text"],
			"bonus_factor" => $r["bonus_factor"]
        );
    }

    foreach ($specialByUnit as $unitId => $rulesList) {
        if (isset($unitsById[$unitId])) {
            $units[$unitsById[$unitId]]["special_rules"] = $rulesList;
        }
    }
}

// 3) Waffen je Einheit
$sqlWeapons = "
    SELECT 
        uw.unit_id,
        uw.id AS unit_weapon_id,
        w.id  AS weapon_id,
        w.name,
        w.range,
        w.disposable,
        uw.number,
        uw.firing_arc
    FROM unit_weapons uw
    LEFT JOIN weapons w ON uw.weapon_id = w.id
";

$resultWeapons = $conn->query($sqlWeapons);
if (!$resultWeapons) {
    http_response_code(500);
    echo json_encode(array("error" => "Query unit_weapons failed: " . $conn->error));
    exit;
}

$weaponsByUnit = array();
while ($w = $resultWeapons->fetch_assoc()) {
    $unitId = (int)$w["unit_id"];
    if (!isset($weaponsByUnit[$unitId])) {
        $weaponsByUnit[$unitId] = array();
    }
    $weaponsByUnit[$unitId][] = $w;
}

// 4) Waffen-Statlines
$sqlStats = "
    SELECT
        ws.weapon_id,
        ws.target_type,
        ws.target_level,
        ws.dice,
        ws.damage
    FROM weapon_stats ws
";

$resultStats = $conn->query($sqlStats);
if (!$resultStats) {
    http_response_code(500);
    echo json_encode(array("error" => "Query weapon_stats failed: " . $conn->error));
    exit;
}

$statsByWeapon = array();
while ($s = $resultStats->fetch_assoc()) {
    $weaponId = (int)$s["weapon_id"];
    if (!isset($statsByWeapon[$weaponId])) {
        $statsByWeapon[$weaponId] = array();
    }
    $statsByWeapon[$weaponId][] = array(
        "type"   => $s["target_type"],
        "level"  => $s["target_level"],
        "dice"   => $s["dice"],
        "damage" => $s["damage"]
    );
}

// 5) Waffen-Regeln
$sqlWeaponRules = "
    SELECT
        wr.weapon_id,
        r.id,
        r.name,
        r.full_text,
		r.bonus_factor
    FROM weapon_rules wr
    LEFT JOIN rules r ON r.id = wr.rule_id
";

$resultWR = $conn->query($sqlWeaponRules);
if (!$resultWR) {
    http_response_code(500);
    echo json_encode(array("error" => "Query weapon_rules failed: " . $conn->error));
    exit;
}

$rulesByWeapon = array();
while ($r = $resultWR->fetch_assoc()) {
    $weaponId = (int)$r["weapon_id"];
    if (!isset($rulesByWeapon[$weaponId])) {
        $rulesByWeapon[$weaponId] = array();
    }
    $rulesByWeapon[$weaponId][] = array(
        "id"   => $r["id"],
        "name" => $r["name"],
        "text" => $r["full_text"],
		"bonus_factor" => $r["bonus_factor"]
    );
}

// 6) Daten aufbereiten + theoretische Punkte berechnen
$result = array();

foreach ($units as $unit) {
    $unitId = (int)$unit['id'];

    // Waffenstruktur für das Rating vorbereiten
    $unitWeapons = array();
    if (!empty($weaponsByUnit[$unitId])) {
        foreach ($weaponsByUnit[$unitId] as $w) {
            $weaponId = (int)$w["weapon_id"];

            $weapon = array(
                "id"         => $weaponId,
                "name"       => $w["name"],
                "range"      => $w["range"],
                "disposable" => $w["disposable"],
                "number"     => $w["number"],
                "arc"        => $w["firing_arc"],
                "stats"      => isset($statsByWeapon[$weaponId]) ? $statsByWeapon[$weaponId] : array(),
                "rules"      => isset($rulesByWeapon[$weaponId]) ? $rulesByWeapon[$weaponId] : array()
            );

            $unitWeapons[] = $weapon;
        }
    }

    // Minimiertes Objekt für Rating
    $unitForRating = array(
        "health"        => $unit["health"],
		"models"		=> $unit["models"],
        "speed"         => $unit["speed"],
        "march_speed"   => $unit["march_speed"],
        "level"         => $unit["level"],
        "special_rules" => $unit["special_rules"],
        "weapons"       => $unitWeapons
    );

    // Berechnung
    $components = compute_theoretical_points_components($unitForRating);

    // Minimale Ausgabe mit Analyse
    $result[] = array(
        "id"                 => $unit["id"],
        "name"               => $unit["name"],
        "points"             => $unit["points"],
        "theoretical_points" => $components["theoreticalPoints"],
        "details"            => $components
    );
}

// 7) Ausgabe
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit;
