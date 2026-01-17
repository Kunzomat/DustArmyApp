import React from "react";
import { Box, Typography } from "@mui/material";

//const API_BASE = process.env.REACT_APP_API_BASE_URL || "http://localhost"; // CRA-kompatibel
const API_BASE = "http://localhost";

function CardFront({ details = {}, bgColor }) {
  const factionInitial = details.faction
    ? details.faction.charAt(0).toUpperCase()
    : "S";

  return (
    <Box
      sx={{
        width: "50%",
        height: "100%",
        background: bgColor, // leichtes Papier-Beige
        position: "relative",
        overflow: "hidden",
        border: "8px solid #FFFFFF",
        borderRadius: "12px",
        boxSizing: "border-box",
        display: "flex",
        flexDirection: "column",
      }}
    >
      {/* ============================== */}
      {/*           BILDBEREICH          */}
      {/* ============================== */}
      <Box
        sx={{
          flex: 1,
          position: "relative",
          borderBottom: "2px solid #000",
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          overflow: "hidden",
        }}
      >
        {details.image_url ? (
          <img
            src={API_BASE+details.image_url}
            alt={details.name}
            style={{
              width: "90%",
              height: "90%",
              objectFit: "cover",
            }}
          />
        ) : (
          <Typography variant="h6">Kein Bild vorhanden</Typography>
        )}

        {/* ============================== */}
        {/*           AP-BOX               */}
        {/* ============================== */}
        <Box
          sx={{
            position: "absolute",
            top: 12,
            left: 12,
            background: "#ffffff",
            color: "#000",
            border: "3px solid #000",
            borderRadius: "12px", // 🟢 abgerundetes Rechteck
            px: 1.5,
            py: 0.75,
            fontWeight: 900,
            lineHeight: 1,
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 5, // 🟢 im Vordergrund
            boxShadow: "0 2px 8px rgba(0,0,0,0.25)",
          }}
          title="Army Points"
        >
          <Typography sx={{ fontSize: 14, fontWeight: 900, letterSpacing: 1 }}>
            AP
          </Typography>
          <Typography sx={{ fontSize: 20, fontWeight: 900 }}>
            {details.points}
          </Typography>
        </Box>

        {/* ============================== */}
        {/*       FRAKTIONS-LOGO           */}
        {/* ============================== */}
        <Box
          sx={{
            position: "absolute",
            right: 16,
            bottom: 30,
            width: 70,
            height: 70,
            borderRadius: "5%",
            border: "4px solid #000",
            backgroundColor: "#fff",
            boxShadow: "0 4px 10px rgba(0,0,0,0.25)",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 10, // 🟢 im Vordergrund
          }}
          title={details.faction || "Faction"}
        >
		{details.faction_symbol_url ? (
          <img
            src={API_BASE+details.faction_symbol_url}
            alt={details.faction}
            style={{
              width: "100%",
              height: "100%",
              objectFit: "cover",
            }}
          />
        ) : (
          <Typography sx={{ fontWeight: 900, fontSize: 36, lineHeight: 1 }}>
            {factionInitial}
          </Typography>
        )}
        </Box>

        {/* ============================== */}
        {/*        NAMENS-BOX              */}
        {/* ============================== */}
        <Box
          sx={{
            position: "absolute",
            bottom: 12,
            left: "50%",
            transform: "translateX(-50%)",
            width: "80%",
            backgroundColor: "#fff",
            border: "3px solid #000",
            borderRadius: "8px",
            p: 1,
            display: "flex",
            flexDirection: "column",
            justifyContent: "center",
            alignItems: "center",
            zIndex: 7, // 🟢 ganz vorne
            boxShadow: "0 2px 10px rgba(0,0,0,0.25)",
          }}
        >
          <Typography
            sx={{
              fontWeight: 900,
              fontSize: 20,
              lineHeight: 1.1,
              textAlign: "center",
            }}
          >
            {details.name}
          </Typography>

          {!!details.notes && (
            <Typography
              sx={{
                fontWeight: 600,
                fontSize: 14,
                opacity: 0.9,
                textAlign: "center",
              }}
            >
              {details.notes}
            </Typography>
          )}
        </Box>
      </Box>

      {/* ============================== */}
      {/*          REGEL-BEREICH         */}
      {/* ============================== */}
      <Box
        sx={{
          flex: 1,
          p: 1.25,
          overflowY: "auto",
          fontFamily: "inherit",
		  background: "#d4dbe2",
        }}
      >
        {details.special_rules?.length > 0 &&
          details.special_rules.map((r) => (
            <Typography key={r.id} variant="body2" sx={{ mb: 0.75 }}>
              <strong style={{ fontWeight: 800 }}>{r.name} {r.note && <> ({r.note})</>} :</strong> {r.desc}
            </Typography>
          ))}
		
		{/* Waffen-Regeln (Duplikate gefiltert) */}
		{(() => {
		  const rendered = [];
		  const seen = new Set();

		  details.weapons?.forEach((w) => {
			if (!w.rules) return;

			w.rules.forEach((rule) => {
			  const key =
				rule.id || `${w.id || ""}-${rule.name || ""}-${rule.desc || ""}`;

			  if (seen.has(key)) return; // schon gerendert → überspringen
			  seen.add(key);

			  rendered.push(
				<Typography
				  key={rule.id || `${w.id}-${rule.name}`}
				  variant="body2"
				  sx={{ mb: 0.75 }}
				>
				  {/* Überschrift: Waffenname + Regelname */}
				  <strong style={{ fontWeight: 800 }}>
					{rule.name}:
				  </strong>
				  {/* Optional: Beschreibung, falls vorhanden */}
				  {rule.desc && <> {rule.desc}</>}
				</Typography>
			  );
			});
		  });

		  return rendered;
		})()}
      </Box>
    </Box>
  );
}

export default CardFront;
