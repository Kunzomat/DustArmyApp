<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . '/unit_rating.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

//$host = "localhost";
//$db   = "dust1947";
//$user = "root";
//$pass = "";

$host = "database-5019385374.webspace-host.com";
$db   = "dbs15166077";
$user = "dbu358620";
$pass = "!Don10Draco!";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Datenbankverbindung fehlgeschlagen: " . $conn->connect_error]);
    exit;
}

// Prüfen, ob eine einzelne Einheit abgefragt wird
if (isset($_GET['unit_id'])) {
    $unit_id = intval($_GET['unit_id']);

    // 1️⃣ Einheit + Fraktion laden
    $sql = "
		SELECT 
			u.id AS unit_id,
			u.name AS unit_name,
			u.notes,
			u.type,
			u.level,
			u.speed,
			u.march_speed,
			u.points,
			u.image_url,
			u.health,
			u.faction_id AS faction_id,
			f.name AS faction_name,
			f.symbol_url AS faction_symbol_url,
			ur.id AS special_rule_id,
			ur.name AS special_rule_name,
			uur.note AS special_rule_note,
			ur.short_text AS special_rule_desc,
			ur.full_text AS special_rule_text,
			uw.id AS unit_weapon_id,
			uw.number AS weapon_number,
			uw.firing_arc AS weapon_firing_arc,
			w.id AS weapon_id,
			w.name AS weapon_name,
			w.range AS weapon_range,
			w.disposable as weapon_disposable,
			wr.id AS weapon_rule_id,
			wr.name AS weapon_rule_name,
			wr.short_text AS weapon_rule_desc,
			wr.full_text AS weapon_rule_text,
			ws.id AS weapon_stat_id,
			ws.target_type AS weapon_target_type,
			ws.target_level AS weapon_target_level,
			ws.dice AS weapon_dice,
			ws.damage AS weapon_damage
		FROM units u
		LEFT JOIN factions f ON u.faction_id = f.id
		LEFT JOIN unit_rules uur ON u.id = uur.unit_id
		LEFT JOIN rules ur ON uur.unit_rule_id = ur.id
		LEFT JOIN unit_weapons uw ON u.id = uw.unit_id
		LEFT JOIN weapons w ON uw.weapon_id = w.id
		LEFT JOIN weapon_stats ws ON w.id = ws.weapon_id
		LEFT JOIN weapon_rules wwr ON w.id = wwr.weapon_id
		LEFT JOIN rules wr ON wwr.rule_id = wr.id
		WHERE u.id = ?
		ORDER BY u.id, w.id, wr.id
		";

		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s", $unit_id); // "s" für String
		$stmt->execute();
		$result = $stmt->get_result();

		$units = [];

		while ($row = $result->fetch_assoc()) {
			$unitId = $row['unit_id'];
			$weaponId = $row['weapon_id'];
			$unitWeaponId = $row['unit_weapon_id'];

			if (!isset($units[$unitId])) {
				$units[$unitId] = [
					'id' => $unitId,
					'name' => $row['unit_name'],
					'type' => $row['type'],
					'level' => $row['level'],
					'notes' => $row['notes'],
					'speed' => $row['speed'],
					'march_speed' => $row['march_speed'],
					'faction' => $row['faction_name'],
					'faction_symbol_url' => $row['faction_symbol_url'],
					'points' => $row['points'],
					'image_url' => $row['image_url'],
					'health' => $row['health'],
					'faction_id' => $row['faction_id'],
					'special_rules' => [],
					'weapons' => []
				];
			}

			if (!empty($row['special_rule_id'])) {
				$units[$unitId]['special_rules'][$row['special_rule_id']] = [
					'id' => $row['special_rule_id'],
					'name' => $row['special_rule_name'],
					'note' => $row['special_rule_note'],
					'desc' => $row['special_rule_desc'],
					'text' => $row['special_rule_text']
				];
			}

			if (!empty($weaponId)) {
				if (!isset($units[$unitId]['weapons'][$unitWeaponId])) {
					$units[$unitId]['weapons'][$unitWeaponId] = [
						'id' => $weaponId,
						'name' => $row['weapon_name'],
						'range' => $row['weapon_range'],
						'disposable' => $row['weapon_disposable'],
						'number' => $row['weapon_number'],
						'arc' => $row['weapon_firing_arc'],
						'rules' => []
					];
				}
				if (!empty($row['weapon_rule_id'])) {
					 $units[$unitId]['weapons'][$unitWeaponId]['rules'][$row['weapon_rule_id']] = [
						'id' => $row['weapon_rule_id'],
						'name' => $row['weapon_rule_name'],
						'desc' => $row['weapon_rule_desc'],
						'text' => $row['weapon_rule_text']
					];
				}
				if (!empty($row['weapon_stat_id'])) {
					$units[$unitId]['weapons'][$unitWeaponId]['stats'][$row['weapon_stat_id']] = [
						'type' => $row['weapon_target_type'],
						'level' => $row['weapon_target_level'],
						'dice' => $row['weapon_dice'],
						'damage' => $row['weapon_damage']
					];
				}
			}
		}

		foreach ($units as &$unit) {
			$unit['theoretical_points'] = compute_theoretical_points($unit);
			$unit['special_rules'] = array_values($unit['special_rules']);
			$unit['weapons'] = array_values($unit['weapons']);
			foreach ($unit['weapons'] as &$weapon) {
				$weapon['rules'] = array_values($weapon['rules']);
			}
		}

		//echo json_encode(array_values($units), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		echo json_encode(reset($units), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Standard: Alle Einheiten
$sql = "SELECT id, name, type, points, image_url FROM units";
$result = $conn->query($sql);

$units = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $units[] = $row;
    }
}

echo json_encode($units, JSON_PRETTY_PRINT);
$conn->close();
