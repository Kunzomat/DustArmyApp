import React from "react";
import { Box } from "@mui/material";

import iconInfanterie from "../assets/icons/infanterie.png";
import iconVehicle from "../assets/icons/vehicle_tank.png";
import iconAircraft from "../assets/icons/aircraft_jet.png";

function CardBack({
  weapons = [],
  bgColor,
  unitName,
  specialRules = [],
  type,
  level,
  speed,
  health,
  marchSpeed,
  points,
}) {

  const TABLE_WIDTH = 860;
  const AVAILABLE_AFTER_ROTATION = 657;
  const SCALE = (AVAILABLE_AFTER_ROTATION - 0) / TABLE_WIDTH;
  const MAX_ROWS = 8;
  // Map für Typen
  const TYPE_ICONS = {
    I: iconInfanterie,
	H: iconInfanterie,
	S: iconInfanterie,
    V: iconVehicle,
    A: iconAircraft,
  };
  
  const iconUrl = TYPE_ICONS[type] || null;

  const getCellValue = (w, type, level) => {
    if (!w.stats) return "";
    const stats = Object.values(w.stats);
    const match = stats.find(
      (s) => s.type === type && Number(s.level) === Number(level)
    );
    return match ? `${match.dice}/${match.damage}` : "";
  };

  const columns =
    "180px 60px repeat(4, 40px) repeat(7, 40px) repeat(3, 40px) 60px";

  // Spaltentrenner nach bestimmten Spalten
  const dividerAfter = [0, 1, 5, 12, 15];

  // Hintergrundfarbe für echte Waffenzeilen
  const getCellBg = (rowIndex, colIndex) => {
    const isEvenRow = rowIndex % 2 === 1;
    const baseColor = isEvenRow ? "#e6e6e6" : "#ffffff";
    const isEvenCol = colIndex % 2 === 1;
    if (isEvenCol) {
      return isEvenRow ? "#dcdcdc" : "#f0f0f0";
    }
    return baseColor;
  };

  // Waffen auffüllen auf 6 Zeilen
  const paddedWeapons = [
    ...weapons,
    ...Array.from({ length: Math.max(0, MAX_ROWS - weapons.length) }, () => ({})),
  ];

  return (
    <Box
      sx={{
        width: "50%",
        height: "100%",
        background: bgColor,
        position: "relative",
        overflow: "visible",
        border: "8px solid #FFFFFF",       // ⚪ separater Rand
        //padding: "50px",
        boxSizing: "border-box",
        borderRadius: "12px",              // optional: Rundung
      }}
    >
      <Box
        sx={{
          position: "absolute",
          top: "50%",
          left: "50%",
          transform: `translate(-50%, -50%) rotate(90deg) scale(${SCALE})`,
          transformOrigin: "center",
          high: "100%",
          width: `${TABLE_WIDTH}px`,
          /*width: `${TABLE_WIDTH}px`,*/
          background: bgColor,
        }}
      >
	  
	  
	  {/* 🆕 ======================================================
            EINHEITEN-KOPF (neuer Bereich über der Tabelle)
        ====================================================== */}
        <Box
          sx={{
            //background: "#b0b2b5",     // grauer Balken wie Vorlage
			display: "flex",
			justifyContent: "space-between",
			alignItems: "flex-start",   // 🟢 Inhalt bleibt oben
			height: "200px",
			boxSizing: "border-box",    // 🟢 Padding wird in die Höhe eingerechnet
			gap: 2,
			padding: "10px 16px 8px 16px",
			mb: 1,                      // etwas Abstand zur Tabelle
			overflow: "hidden",         // 🟢 optional: verhindert Überlaufen bei zu viel Inhalt
          }}
        >
          {/* 🆕 linker Teil: Titel */}
          <Box sx={{ fontFamily: "inherit", flexBasis: "40%", maxWidth: "40%", height: "100%",}}>
			  <Box
				sx={{
				  fontSize: 32,
				  fontWeight: 900,
				  lineHeight: 1.1,
				  whiteSpace: "normal",   // darf umbrechen, falls nötig
				  wordBreak: "break-word", // falls Name lang ist
					display: "flex",
					flexDirection: "column",
					justifyContent: "flex-start",
				}}
			  >
				 {unitName || "Unit Name fehlt"}
			  </Box>
			</Box>
          {/* 🆕 rechter Teil: Werte + Fähigkeitenliste */}
          <Box sx={{ flexBasis: "60%", maxWidth: "60%", textAlign: "left"}}>
            {/* obere “move/attack”-Zeile */}
			<Box
				  sx={{
					display: "flex",
				   flexDirection: "row",       // 🟢 alles in einer Zeile
				   justifyContent: "center",   // 🟢 Inhalt mittig in der Box
				   alignItems: "center",       // 🟢 vertikal mittig
				   flexWrap: "nowrap",         // 🟢 kein Zeilenumbruch möglich
				   width: "50%",
				   ml: "auto",                 // 🟢 nach rechts schieben
				   gap: 1,
				   mb: 1,
				   background: "#d4dbe2",
				   pl: 1,
				   pr: 1,
				   borderRadius: "12px",
				   whiteSpace: "nowrap",       // 🟢 kein Umbruch im Text
				   flexShrink: 0,              // 🟢 nicht zusammenschieben
				  }}
				>
				  <Box sx={{ fontSize: 30, fontWeight: 700 }}>
					➜ {speed} ⇛ {marchSpeed}
				  </Box>

				  <Box
					sx={{
				     fontSize: 30,
				     fontWeight: 700,
				     display: "inline-flex",     // 🟢 inline-flex verhindert Zeilenumbruch
				     alignItems: "center",
				     gap: 0.5,
				     whiteSpace: "nowrap",       // 🟢 kein Textumbruch
				     flexWrap: "nowrap",         // 🟢 kein Umbrechen der Kinder
				     flexShrink: 0,              // 🟢 nicht verkleinern
				     minWidth: "max-content",    // 🟢 Breite richtet sich nach Inhalt
				     lineHeight: 1,              // 🟢 kompakter, kein Zeilenhöhen-Umbruch
					}}
				  >
					{iconUrl && (
					  <img
						src={iconUrl}
						alt={`${type} icon`}
				       style={{
				         width: "1em",
				         height: "1em",
				         display: "inline-block",  // 🟢 Icon bleibt inline
				         verticalAlign: "middle",
				         flex: "0 0 auto",
				       }}
					  />
					)}
				   <Box component="span" sx={{ display: "inline-block" }}>{level}</Box>  {/* 🟢 Text bleibt inline */}
				  </Box>
				</Box>

			{/* Fähigkeitenliste (Einheit + Waffen) */}
			<Box
  sx={{
    fontSize: 20,
    fontWeight: "bold",
    lineHeight: 1.3,
    display: "flex",
    flexWrap: "wrap",
    gap: "6px",
  }}
>
  {/* Sonderregeln */}
  {specialRules?.length > 0 &&
    specialRules.map((rule) => (
      <span key={`sr-${rule.id || rule.name}`}>
        • {rule.name} {rule.note && <> ({rule.note})</>}
      </span>
    ))}

  {/* Waffen-Regeln */}
  {weapons?.length > 0 &&
    weapons.map((w) =>
      w.rules
        ? w.rules.map((r) => (
            <span key={`wr-${w.id || w.name}-${r.id || r.name}`}>
              • {w.name}: {r.name}
            </span>
          ))
        : null
    )}
</Box>
			
          </Box>
        </Box>
        {/* 🆕 ======================================================
            ENDE DES NEUEN BEREICHS
        ====================================================== */}
        {/* Tabellenkopf – Zeile 1: Gruppenüberschriften */}
        <Box
          sx={{
            display: "grid",
            gridTemplateColumns: columns,
            background: "#d4dbe2", // dunkler als Zeile 2
            borderBottom: "1px solid #b9c1c9",
            fontWeight: "bold",
            fontSize: 16,
            textAlign: "center",
            alignItems: "center",
            height: "32px", // gleiche Höhe wie Zeile 2
          }}
        >
          {/* "Type Name" über der Namensspalte */}
          <Box
            sx={{
              gridColumn: "1 / span 1",
              textAlign: "left",
              pl: 1,
              borderRight: "2px solid #222", // dividerAfter[0]
            }}
          >
            Type Name
          </Box>
          {/* Range hat keine Gruppe */}
          <Box
            sx={{
              gridColumn: "2 / span 1",
              borderRight: "2px solid #222", // dividerAfter[1]
            }}
          >&nbsp;
		  </Box>
          {/* Gruppenlabels für die Typ-Spalten */}
          <Box
            sx={{
              gridColumn: "3 / span 4",
              textAlign: "center",
              borderRight: "2px solid #222", // endet bei Spalte 5
            }}
          >
            Infantry
          </Box>
          <Box
            sx={{
              gridColumn: "7 / span 7",
              textAlign: "center",
              borderRight: "2px solid #222", // endet bei Spalte 12
            }}
          >
            Vehicles
          </Box>
          <Box
            sx={{
              gridColumn: "14 / span 3",
              textAlign: "center",
              borderRight: "2px solid #222", // endet bei Spalte 15
            }}
          >
            Aircraft
          </Box>

          {/* Arc hat keine Gruppe */}
          <Box sx={{ gridColumn: "17 / span 1" }} />
        </Box>

        {/* Tabellenkopf – Zeile 2: Spaltenlabels */}
        <Box
          sx={{
            display: "grid",
            gridTemplateColumns: columns,
            background: "#e6eaef",
            borderBottom: "2px solid #c9cfd8",
            fontWeight: "bold",
            fontSize: 16,
            textAlign: "center",
            // p: 0.5,
            alignItems: "center",
            height: "32px",
          }}
        >
          <Box sx={{ borderRight: "2px solid #222" }}>Weapon</Box>
          <Box sx={{ borderRight: "2px solid #222" }}>Range</Box>
          <Box>1</Box>
          <Box>2</Box>
          <Box>3</Box>
          <Box sx={{ borderRight: "2px solid #222" }}>4</Box>
          <Box>1</Box>
          <Box>2</Box>
          <Box>3</Box>
          <Box>4</Box>
          <Box>5</Box>
          <Box>6</Box>
          <Box sx={{ borderRight: "2px solid #222" }}>7</Box>
          <Box>1</Box>
          <Box>2</Box>
          <Box sx={{ borderRight: "2px solid #222" }}>3</Box>
          <Box>Arc</Box>
        </Box>

        {/* Tabellenzeilen */}
        <Box>
          {paddedWeapons.map((w, rowIndex) => {
            const isRealWeapon = Boolean(w.id);
			const hasDisposable = isRealWeapon && (w.disposable || 0) > 0;
			const arcMap = { L: "Left", R: "Right", F: "Front", T: "Turret",};
            const cells = isRealWeapon
              ? [
                  `${w.number || 1}x ${w.name || ""}`,
				  w.range === 0 ? "C" : (w.range || "-"),
                  ...[1, 2, 3, 4].map((l) => getCellValue(w, "I", l)),
                  ...[1, 2, 3, 4, 5, 6, 7].map((l) => getCellValue(w, "V", l)),
                  ...[1, 2, 3].map((l) => getCellValue(w, "A", l)),
				  arcMap[w.arc] || "-",
                ]
              : Array(17).fill("");

            return (
              <Box
                key={rowIndex}
                sx={{
					display: "grid",
					gridTemplateColumns: columns,
					borderBottom:
						rowIndex === MAX_ROWS - 1
							? "2px solid #000"
							: "1px solid #ddd",
					fontSize: 16,
					textAlign: "center",
					alignItems: "center",
					height: hasDisposable ? "72px" : "36px",
                }}
              >
                {cells.map((value, colIndex) => {
                  const borderRight = dividerAfter.includes(colIndex)
                    ? "2px solid #222"
                    : "1px solid #ccc";
					
				  const bgColor = isRealWeapon
					? getCellBg(rowIndex, colIndex)
					: getCellBg(rowIndex, colIndex);

                  return (
					<Box
                      key={`${rowIndex}-col-${colIndex}`}
                      sx={{
                        backgroundColor: bgColor,
                        fontWeight: colIndex === 0 ? "bold" : "normal",
                        textAlign: colIndex === 0 ? "left" : "center",
                        pl: colIndex === 0 ? 1 : 0,
                        borderRight,
						display: "flex",
						alignItems: colIndex === 0 ? "center" : "center",
                        justifyContent:
                          colIndex === 0 ? "flex-start" : "center",
						height: hasDisposable ? "72px" : "36px",
                        overflow: "hidden",
                      }}
                    >
                      {isRealWeapon && colIndex === 0 ? (
                        <Box
                          sx={{
                            display: "flex",
                            flexDirection: "column",   // Name oben, Boxen darunter
                            alignItems: "flex-start",
                            gap: 0.5,
                            width: "100%",
                            lineHeight: 1.2,
							//height: "100%",
							alignSelf: "center",
                          }}
                        >
							<Box
								component="span"
								sx={{
									whiteSpace: "nowrap",
									fontSize: 18,          // 🟢 etwas größer und lesbarer
									fontWeight: 700,       // 🟢 betont, damit der Name hervortritt
									lineHeight: 1.2,
								}}
							>
                            {`${w.number || 1}x ${w.name || ""}`}
                          </Box>
                          {hasDisposable && (
                            <Box
                              sx={{
                                display: "flex",
                                alignItems: "center",
                                gap: 0.5,
								rowGap: 0.5,
								flexWrap: "wrap",                               // 🟢 darf umbrechen
								mt: 0.25,
                              }}
                            >
                              {Array.from({
                                length: (w.number || 1) * (w.disposable || 0), // ✅ number * disposable
                              }).map((_, i) => (
                                <Box
                                  key={`disp-${rowIndex}-${i}`}
                                  sx={{
                                    width: 16,                                  // 🟢 wieder größer/lesbar
									height: 16,
									borderRadius: "4px",
                                    border: "2px solid #222",
                                    background: "#fff",
                                    flex: "0 0 auto",
                                  }}
                                />
                              ))}
                            </Box>
                          )}
                        </Box>
                      ) : (
                        value || ""
                      )}
                    </Box>
                  );
                })}
              </Box>
            );
          })}
        </Box>
		{/* ===== Untere Leiste: Health links • Kästchen Mitte • Hero/Super Human rechts ===== */}
		<Box
		  sx={{
			display: "flex",
			alignItems: "center",
			justifyContent: "space-between",
			background: "#d4dbe2",
			borderRadius: "12px",
			p: 1,
			mt: 1,
		  }}
		>
		  {/* Links: Health-Zahl */}
		  <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
			<Box sx={{ fontSize: 34, fontWeight: 900, lineHeight: 1 }}>
			  🖤
			</Box>
			<Box sx={{ fontSize: 34, fontWeight: 900, lineHeight: 1 }}>
			  {health}
			</Box>
		  </Box>

		  {/* Mitte: ankreuzbare Boxen – nur zeigen, wenn health > 1 */}
		  <Box
			sx={{
			  display: health > 1 ? "flex" : "none",
			  alignItems: "center",
			  gap: 0.5,
			  flexWrap: "nowrap",
			}}
		  >
			{Array.from({ length: Math.max(0, health) }).map((_, i) => (
			  <Box
				key={`hp-${i}`}
				sx={{
				  width: 24,
				  height: 24,
				  borderRadius: "6px",
				  border: "2px solid #222",
				  background: "#fff",
				}}
			  />
			))}
		  </Box>

		  {/* Rechts: Hero / Super Human (nur für H / S) */}
		  <Box
			sx={{
			  minWidth: 120,
			  textAlign: "right",
			  fontSize: 22,
			  fontWeight: 800,
			}}
		  >
			{type === "H" ? "Hero" : type === "S" ? "Super Human" : ""}
		  </Box>
		</Box>
      </Box>
    </Box>
  );
}

export default CardBack;
