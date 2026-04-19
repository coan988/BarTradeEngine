import { useEffect, useState } from "react";
import "./App.css";
import Pricelist from "./pricelist";

function App() {
  const [drinks, setDrinks] = useState([]);
  const [scenario, setScenario] = useState(null);
  const [quantities, setQuantities] = useState({});
  const [message, setMessage] = useState("");
  const [currentPage, setCurrentPage] = useState("home");

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

  if (currentPage === "pricelist") {
    return <Pricelist onBack={() => setCurrentPage("home")} />;
  }

  return (
    <div className="app-container">
      <h1>Getränke</h1>

      <div className="button-row">
        <button onClick={fetchState} className="button with-margin">
          Preise aktualisieren
        </button>
        <button
          onClick={() => setCurrentPage("pricelist")}
          className="button"
        >
          Preisliste öffnen
        </button>
      </div>

      <table className="drinks-table">
        <thead>
          <tr>
            <th className="table-head-cell">ID</th>
            <th className="table-head-cell">Name</th>
            <th className="table-head-cell">Preis</th>
            <th className="table-head-cell">Quantity</th>
          </tr>
        </thead>
        <tbody>
          {drinks.map((drink) => (
            <tr key={drink.id}>
              <td className="table-cell">{drink.id}</td>
              <td className="table-cell">{drink.name}</td>
              <td className="table-cell">{drink.price} €</td>
              <td className="table-cell">
                <input
                  type="text"
                  value={quantities[drink.id] ?? ""}
                  onChange={(e) => handleQuantityChange(drink.id, e.target.value)}
                  placeholder="Int eingeben"
                  className="quantity-input"
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <div className="button-row">
        <button onClick={runOrder} className="button with-margin">
          Order starten
        </button>
        <button onClick={runScenario} className="button">
          Scenario starten
        </button>
      </div>

      <h2>Letztes Scenario</h2>
      <div className="scenario-box">
        {scenario ? JSON.stringify(scenario, null, 2) : "Kein Scenario vorhanden."}
      </div>

      {message && <div className="message-box">{message}</div>}
    </div>
  );
}

export default App;