import { useEffect, useState } from "react";

function App() {
  const [drinks, setDrinks] = useState([]);
  const [scenario, setScenario] = useState(null);
  const [quantities, setQuantities] = useState({});
  const [message, setMessage] = useState("");

  async function fetchState() {
    try {
      setMessage("");

      const res = await fetch("http://localhost:8080/src/API/state.php");
      const data = await res.json();

      setDrinks(data.drinks ?? []);
      setScenario(data.scenario ?? null);

      const initialQuantities = {};
      (data.drinks ?? []).forEach((drink) => {
        initialQuantities[drink.id] = quantities[drink.id] ?? "";
      });
      setQuantities(initialQuantities);
    } catch (err) {
      console.error("fetchState Fehler:", err);
      setMessage("Fehler beim Laden des States.");
    }
  }

  async function runScenario() {
    try {
      setMessage("");

      const res = await fetch("http://localhost:8080/src/API/scenario-run.php", {
        method: "POST",
      });

      const data = await res.json();

      if (!data.success) {
        throw new Error(data.error || "Scenario konnte nicht gestartet werden.");
      }

      setDrinks(data.drinks ?? []);
      setScenario(data.scenario ?? null);
      setMessage("Scenario wurde gestartet.");
    } catch (err) {
      console.error("runScenario Fehler:", err);
      setMessage(err.message);
    }
  }

  async function runOrder() {
    try {
      setMessage("");

      const orders = drinks
        .map((drink) => ({
          drink_id: drink.id,
          quantity: parseInt(quantities[drink.id], 10),
        }))
        .filter((item) => Number.isInteger(item.quantity) && item.quantity > 0);

      if (orders.length === 0) {
        setMessage("Bitte mindestens eine gültige Quantity größer als 0 eingeben.");
        return;
      }

      for (const order of orders) {
        const res = await fetch("http://localhost:8080/src/API/order-run.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(order),
        });

        const data = await res.json();

        if (!data.success) {
          throw new Error(data.error || `Order für Drink ${order.drink_id} fehlgeschlagen.`);
        }
      }

      setMessage("Order erfolgreich gestartet.");
      fetchState();
    } catch (err) {
      console.error("runOrder Fehler:", err);
      setMessage(err.message);
    }
  }

  function handleQuantityChange(drinkId, value) {
    if (value === "" || /^\d+$/.test(value)) {
      setQuantities((prev) => ({
        ...prev,
        [drinkId]: value,
      }));
    }
  }

  useEffect(() => {
    fetchState();
  }, []);

  return (
    <div style={{ padding: "20px", fontFamily: "Arial, sans-serif" }}>
      <h1>Getränke</h1>

      <div style={{ marginBottom: "16px" }}>
        <button onClick={fetchState} style={{ marginRight: "8px" }}>
          Preise aktualisieren
        </button>
      </div>

      <table
        style={{
          borderCollapse: "collapse",
          width: "100%",
          marginBottom: "16px",
        }}
      >
        <thead>
          <tr>
            <th style={thStyle}>ID</th>
            <th style={thStyle}>Name</th>
            <th style={thStyle}>Preis</th>
            <th style={thStyle}>Quantity</th>
          </tr>
        </thead>
        <tbody>
          {drinks.map((drink) => (
            <tr key={drink.id}>
              <td style={tdStyle}>{drink.id}</td>
              <td style={tdStyle}>{drink.name}</td>
              <td style={tdStyle}>{drink.price} €</td>
              <td style={tdStyle}>
                <input
                  type="text"
                  value={quantities[drink.id] ?? ""}
                  onChange={(e) => handleQuantityChange(drink.id, e.target.value)}
                  placeholder="Int eingeben"
                  style={{ width: "100px" }}
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <div style={{ marginBottom: "16px" }}>
        <button onClick={runOrder} style={{ marginRight: "8px" }}>
          Order starten
        </button>
        <button onClick={runScenario}>Scenario starten</button>
      </div>

      <h2>Letztes Scenario</h2>
      <div
        style={{
          border: "1px solid #ccc",
          padding: "12px",
          minHeight: "80px",
          backgroundColor: "#f9f9f9",
          whiteSpace: "pre-wrap",
        }}
      >
        {scenario ? JSON.stringify(scenario, null, 2) : "Kein Scenario vorhanden."}
      </div>

      {message && (
        <div style={{ marginTop: "16px", color: "darkred" }}>
          {message}
        </div>
      )}
    </div>
  );
}

const thStyle = {
  border: "1px solid #ccc",
  padding: "8px",
  textAlign: "left",
  backgroundColor: "#eee",
};

const tdStyle = {
  border: "1px solid #ccc",
  padding: "8px",
};

export default App;