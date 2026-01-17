import React, { useEffect, useMemo, useState } from "react";
import { apiRequest } from "./apiClient";
import {
  AppBar,
  Toolbar,
  Typography,
  Box,
  Paper,
  List,
  ListItemButton,
  ListItemText,
  Divider,
  Chip,
  Button,
  Stack,
  CircularProgress,
  Alert,
} from "@mui/material";

const API_URL = "http://kunzomat.de/dust1947/backend/army_api.php";

function ColumnPaper({ title, children, sx }) {
  return (
    <Paper
      elevation={2}
      sx={{
        height: "calc(100vh - 64px - 16px)",
        display: "flex",
        flexDirection: "column",
        overflow: "hidden",
        ...sx,
      }}
    >
      <Box sx={{ px: 2, py: 1.5 }}>
        <Typography variant="h6" fontWeight={700}>
          {title}
        </Typography>
      </Box>
      <Divider />
      <Box sx={{ p: 1.5, overflow: "auto", flex: 1 }}>{children}</Box>
    </Paper>
  );
}

async function apiCall(action, { method = "GET", params = {}, body } = {}) {
  return apiRequest(action, params, method === "GET" ? "GET" : "POST");
}

function sumPoints(units) {
  return (units || []).reduce(
    (acc, u) => acc + Number(u.points || 0) * Number(u.quantity || 1),
    0
  );
}

function groupByPlatoon(units) {
  const groups = new Map();
  for (const u of units || []) {
    const key =
      u.platoon_id === null || u.platoon_id === undefined
        ? "FREE"
        : String(u.platoon_id);
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push(u);
  }
  return groups;
}

export default function App() {
  const isDesktop = useMemo(
    () => window.matchMedia("(min-width: 900px)").matches,
    []
  );
  const [activePane, setActivePane] = useState("armies");

  const [armies, setArmies] = useState([]);
  const [selectedArmyId, setSelectedArmyId] = useState(null);

  const [armyDetail, setArmyDetail] = useState(null);
  const [selectedUnit, setSelectedUnit] = useState(null);

  const [loadingArmies, setLoadingArmies] = useState(false);
  const [loadingArmy, setLoadingArmy] = useState(false);
  const [error, setError] = useState("");

  const pointsUsed = useMemo(() => {
    if (!armyDetail) return 0;
    return sumPoints(armyDetail.units || armyDetail.army_units || []);
  }, [armyDetail]);

  const pointsLimit = useMemo(() => {
    if (!armyDetail) return 0;
    return Number(
      (armyDetail.army || armyDetail).points_limit || 0
    );
  }, [armyDetail]);

  const factionName = useMemo(() => {
    if (!armyDetail) return "";
    return (armyDetail.army || armyDetail).faction_name || "";
  }, [armyDetail]);

  const armyName = useMemo(() => {
    if (!armyDetail) return "";
    return (armyDetail.army || armyDetail).name || "";
  }, [armyDetail]);

  async function loadArmies() {
    setError("");
    setLoadingArmies(true);
    try {
      const data = await apiCall("armies.list");
      setArmies(data.armies || []);
      if (!selectedArmyId && data.armies?.length) {
        setSelectedArmyId(data.armies[0].id);
      }
    } catch (e) {
      setError(String(e.message || e));
    } finally {
      setLoadingArmies(false);
    }
  }

  async function loadArmy(id) {
    if (!id) return;
    setError("");
    setLoadingArmy(true);
    try {
      const data = await apiCall("armies.get", { params: { id } });
      setArmyDetail(data);
      setSelectedUnit(null);
    } catch (e) {
      setError(String(e.message || e));
    } finally {
      setLoadingArmy(false);
    }
  }

  async function handleCreateArmy() {
    const name = prompt("Name der neuen Armee:");
    if (!name) return;

    const factionId = prompt("Faction-ID:");
    if (!factionId) return;

    const pointsLimit = prompt("Punktelimit:", "100");

    try {
      await apiRequest("armies.create", {
        name,
        faction_id: Number(factionId),
        points_limit: Number(pointsLimit || 100),
      }, "POST");

      await loadArmies();
    } catch (e) {
      setError(String(e.message || e));
    }
  }

  async function handleDeleteArmy(id) {
    if (!window.confirm("Diese Armee wirklich löschen?")) return;

    try {
      await apiRequest("armies.delete", { id }, "POST");
      setSelectedArmyId(null);
      setArmyDetail(null);
      await loadArmies();
    } catch (e) {
      setError(String(e.message || e));
    }
  }

  async function handleEditArmy() {
    const newName = prompt("Neuer Name:", armyName);
    if (!newName) return;

    const newLimit = prompt("Neues Punktelimit:", pointsLimit);

    try {
      await apiRequest(
        "armies.update",
        {
          id: selectedArmyId,
          name: newName,
          points_limit: Number(newLimit),
        },
        "POST"
      );
      await loadArmy(selectedArmyId);
      await loadArmies();
    } catch (e) {
      setError(String(e.message || e));
    }
  }

  useEffect(() => {
    loadArmies();
  }, []);

  useEffect(() => {
    if (selectedArmyId) loadArmy(selectedArmyId);
  }, [selectedArmyId]);

  const rosterUnits = useMemo(() => {
    if (!armyDetail) return [];
    return (armyDetail.units || []).map((u) => ({
      ...u,
      name: u.unit_name,
      points: u.unit_points,
    }));
  }, [armyDetail]);

  const grouped = useMemo(() => groupByPlatoon(rosterUnits), [rosterUnits]);

  return (
    <Box sx={{ height: "100vh", display: "flex", flexDirection: "column" }}>
      <AppBar position="static">
        <Toolbar>
          <Typography variant="h6" fontWeight={800} sx={{ flex: 1 }}>
            Dust 1947 – Army Builder
          </Typography>
          {armyDetail && (
            <Chip
              label={`${pointsUsed}/${pointsLimit} Punkte`}
              color={pointsUsed > pointsLimit ? "error" : "default"}
            />
          )}
        </Toolbar>
      </AppBar>

      <Box sx={{ p: 1 }}>
        {error && <Alert severity="error">{error}</Alert>}

        <Box
          sx={{
            display: "grid",
            gridTemplateColumns: isDesktop ? "1fr 1.5fr 1fr" : "1fr",
            gap: 1,
          }}
        >
          <ColumnPaper title="Armeen">
            <Stack direction="row" spacing={1} sx={{ mb: 1 }}>
              <Button size="small" onClick={loadArmies}>
                Aktualisieren
              </Button>
              <Button size="small" variant="contained" onClick={handleCreateArmy}>
                Neue Armee
              </Button>
              {selectedArmyId && (
                <Button
                  size="small"
                  color="error"
                  variant="outlined"
                  onClick={() => handleDeleteArmy(selectedArmyId)}
                >
                  Löschen
                </Button>
              )}
            </Stack>

            {loadingArmies ? (
              <CircularProgress />
            ) : (
              <List dense>
                {armies.map((a) => (
                  <ListItemButton
                    key={a.id}
                    selected={String(a.id) === String(selectedArmyId)}
                    onClick={() => setSelectedArmyId(a.id)}
                  >
                    <ListItemText
                      primary={a.name}
                      secondary={`${a.points_limit} Punkte • ${a.faction_name}`}
                    />
                  </ListItemButton>
                ))}
              </List>
            )}
          </ColumnPaper>

          <ColumnPaper title={`Armee: ${armyName}`}>
            <Stack direction="row" spacing={1} sx={{ mb: 1 }}>
              <Button size="small" onClick={handleEditArmy}>
                Bearbeiten
              </Button>
            </Stack>

            {loadingArmy ? (
              <CircularProgress />
            ) : (
              [...grouped.entries()].map(([key, units]) => (
                <Box key={key} sx={{ mb: 1 }}>
                  <Typography fontWeight={700}>
                    {key === "FREE" ? "Freie Einheiten" : `Platoon ${key}`}
                  </Typography>
                  <List dense>
                    {units.map((u) => (
                      <ListItemText
                        key={u.id}
                        primary={u.name}
                        secondary={`${u.points} Punkte`}
                      />
                    ))}
                  </List>
                </Box>
              ))
            )}
          </ColumnPaper>

          <ColumnPaper title="Details">
            <Typography color="text.secondary">
              Einheit auswählen…
            </Typography>
          </ColumnPaper>
        </Box>
      </Box>
    </Box>
  );
}
