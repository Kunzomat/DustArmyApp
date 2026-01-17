<?php

/**
 * Berechnet alle Komponenten des theoretischen Punktewertes für eine Einheit.
 *
 * Nutzt:
 *  - health
 *  - models (optional, Standard 1)
 *  - level
 *  - speed
 *  - march_speed
 *  - special_rules
 *  - weapons (mit stats & rules)
 *
 * Zusätzlich kann $unit['system'] gesetzt werden:
 *  - 'dust'  -> Dust-Scale (0.20)
 *  - '40k'   -> 40k-Scale (0.33)
 *  - sonst   -> Default 0.33
 */
function compute_theoretical_points_components(array $unit)
{
    $health = isset($unit['health']) ? (float)$unit['health'] : 1.0;
    $speed  = isset($unit['speed']) ? (float)$unit['speed'] : 0.0;
    $march  = isset($unit['march_speed']) ? (float)$unit['march_speed'] : 0.0;
    $level  = isset($unit['level']) ? (float)$unit['level'] : 1.0;

    // NEU: Anzahl Modelle berücksichtigen
    $models = isset($unit['models']) ? (int)$unit['models'] : 1;
    if ($models < 1) {
        $models = 1;
    }


	// 1) Überlebensfähigkeit mit effektiven Lebenspunkten
    $effectiveHealth = $health * $models;

	$levelFactor = $level * 0.50;

	// aber Level 1 ist jetzt extrem billig:
	if ($level == 1) {
		$levelFactor = 0.20;  // vorher 1.0 → jetzt 40% Rabatt
	}
	$baseSurv = $effectiveHealth * (2.0 * $levelFactor);

    //$baseSurv        = $effectiveHealth * (2.0 + $level * 0.5);
    //$mobilityFactor  = 1.0 + ($speed + $march / 2.0) / 10.0;
    $mobilityFactor  = 1.0 + ($speed + $march / 2.0) / 30.0;
    $survivabilityScore = $baseSurv * $mobilityFactor;

    // 2) Feuerkraft (alle Waffen, komplette Statline)
    $firePower        = 0.0;
    $starDiceValue    = 5.0; // "*" = 5 Würfel
    $hashDamageValue  = 3.0; // "#" = 3 Schaden

    if (!empty($unit['weapons']) && is_array($unit['weapons'])) {
        foreach ($unit['weapons'] as $weapon) {

            // Reichweite & Nahkampf
            $rangeStr = isset($weapon['range']) ? $weapon['range'] : '';
            $isMelee  = ($rangeStr === 'C');

            if ($rangeStr !== '' && $rangeStr !== '*' && !$isMelee) {
                $range = (float)$rangeStr;
            } else {
                $range = 0.0;
            }

            $rangeFactor = 1.0 + $range / 12.0 / 2.0; // 0..+0.5
            if ($isMelee) {
                // Nahkampf – etwas abwerten, da man erst rankommen muss
                $rangeFactor *= 0.30;
            }

            // Anzahl der Waffen
            $number = isset($weapon['number']) ? (float)$weapon['number'] : 1.0;
			
			// Disposable-Faktor: Einweg-Waffen deutlich abwerten
			$disposableFlag   = isset($weapon['disposable']) ? (int)$weapon['disposable'] : 0;
			$disposableFactor = $disposableFlag ? 0.2 : 1.0;   // hier stellst du ein, wie stark abgewertet wird

            // Arc-Faktor: F, R, L = -25 %
            $arcStr = '';
            if (isset($weapon['arc'])) {
                $arcStr = strtoupper(trim($weapon['arc']));
            }
            $restrictedArcs = array('F', 'R', 'L');
            $arcFactor = in_array($arcStr, $restrictedArcs, true) ? 0.75 : 1.0;

            // Statlines nach Typ sammeln (I, V, A, …)
            $typeData = array();
            if (!empty($weapon['stats']) && is_array($weapon['stats'])) {
                foreach ($weapon['stats'] as $stat) {
                    $type = isset($stat['type']) ? $stat['type'] : null;
                    if (!$type) {
                        continue;
                    }

                    // Dice verarbeiten (* = 5)
                    $diceStr = isset($stat['dice']) ? $stat['dice'] : '0';
                    if ($diceStr === '*') {
                        $dice = $starDiceValue;
                    } else {
                        $dice = (float)$diceStr;
                    }

                    // Damage verarbeiten (# = 3)
                    $damageStr = isset($stat['damage']) ? $stat['damage'] : '0';
                    if ($damageStr === '#') {
                        $damage = $hashDamageValue;
                    } else {
                        $damage = (float)$damageStr;
                    }

                    $value = $dice * $damage;

                    if (!isset($typeData[$type])) {
                        $typeData[$type] = array('sum' => 0.0, 'count' => 0);
                    }
                    $typeData[$type]['sum']   += $value;
                    $typeData[$type]['count'] += 1;
                }
            }

            // Durchschnitt pro Typ → gewichten
            $weaponBaseValue = 0.0;
            foreach ($typeData as $type => $data) {
                if ($data['count'] <= 0) {
                    continue;
                }

                $avg = $data['sum'] / $data['count'];

                // leichte Gewichtung nach Zieltyp
                switch ($type) {
                    case 'V': $weight = 1.1;  break; // Fahrzeuge
                    case 'A': $weight = 1.05; break; // Flugzeuge
                    default:  $weight = 1.0;  break; // Infanterie / Sonstiges
                }

                $weaponBaseValue += $avg * $weight;
            }

            // Gesamtwert dieser Waffe
            //$firePower += $number * $weaponBaseValue * $rangeFactor * $arcFactor;
			// Gesamtwert dieser Waffe (mit Disposable-Malus)
			$firePower += $number * $weaponBaseValue * $rangeFactor * $arcFactor * $disposableFactor;
        }
    }

    // NEU: FirePower bei Mehrmodell-Einheiten (mit 1 LP) abschwächen
    $firePowerFactor = 1.0;
    if ($models > 1 && $health == 1.0) {
        $firePowerFactor = ($models + 1.0) / (2.0 * $models);
    }
    $firePowerEffective = $firePower * $firePowerFactor;

    // 3) Sonderregeln (Einheit + Waffen) mit bonus_factor
    $specialCount = 0;
    $specialBonus = 0.0;

    // Einheiten-Sonderregeln
    if (!empty($unit['special_rules']) && is_array($unit['special_rules'])) {
        foreach ($unit['special_rules'] as $rule) {
            $specialCount++;
            $bf = isset($rule['bonus_factor']) ? (float)$rule['bonus_factor'] : 0.5; // Default 0.5
            $specialBonus += $bf;
        }
    }

    // Waffen-Sonderregeln
    if (!empty($unit['weapons']) && is_array($unit['weapons'])) {
        foreach ($unit['weapons'] as $weapon) {
            if (!empty($weapon['rules']) && is_array($weapon['rules'])) {
                foreach ($weapon['rules'] as $rule) {
                    $specialCount++;
                    $bf = isset($rule['bonus_factor']) ? (float)$rule['bonus_factor'] : 0.5; // Default 0.5
                    $specialBonus += $bf;
                }
            }
        }
    }

    // 4) Rohwert
    $rawValue = $survivabilityScore + $firePowerEffective + $specialBonus;

    // 5) Scale nach System
    $system = isset($unit['system']) ? strtolower($unit['system']) : '';

    if ($system === 'dust') {
        $scale = 0.25;   // Startwert für Dust AP
    } elseif ($system === '40k') {
        $scale = 0.33;   // dein bisheriger 40k-Scale
    } else {
        $scale = 0.25;   // Default
    }

    $theoreticalPoints = round($rawValue * $scale, 0);

    return array(
        'baseSurv'           => $baseSurv,
        'effectiveHealth'    => $effectiveHealth,
        'models'             => $models,
        'mobilityFactor'     => $mobilityFactor,
        'survivabilityScore' => $survivabilityScore,
        'firePowerRaw'       => $firePower,
        'firePowerFactor'    => $firePowerFactor,
        'firePower'          => $firePowerEffective,
        'specialRuleCount'   => $specialCount,
        'specialRuleBonus'   => $specialBonus,
        'rawValue'           => $rawValue,
        'scale'              => $scale,
        'theoreticalPoints'  => $theoreticalPoints,
        'system'             => $system,
    );
}

/**
 * Wrapper: gibt nur den finalen theoretischen Punktwert zurück
 */
function compute_theoretical_points(array $unit)
{
    $components = compute_theoretical_points_components($unit);
    return $components['theoreticalPoints'];
}
