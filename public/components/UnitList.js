import React, { useEffect, useState } from "react";
import { List, ListItemButton, ListItemText, Paper, Typography, CircularProgress } from "@mui/material";

function UnitList({ onSelect }) {
  const [units, setUnits] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("http://kunzomat.de/backend/api.php")
      .then(res => res.json())
      .then(data => {
        setUnits(data);
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setLoading(false);
      });
  }, []);

  if (loading) return <CircularProgress sx={{ m: 5 }} />;

  return (
    <Paper sx={{ p: 2 }}>
      <Typography variant="h6" gutterBottom>
        Einheiten
      </Typography>
      <List>
        {units.map(unit => (
          <ListItemButton key={unit.id} onClick={() => onSelect(unit.id)}>
            <ListItemText primary={unit.name} secondary={unit.faction} />
          </ListItemButton>
        ))}
      </List>
    </Paper>
  );
}

export default UnitList;
