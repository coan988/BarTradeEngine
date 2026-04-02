console.log("App rendert");

import { useEffect, useState } from "react";

function App() {
  const [drinks, setDrinks] = useState([]);
  const [scenario, setScenario] = useState(null);

  async function fetchState() {
  try {
    console.log("fetchState startet");

    const res = await fetch("http://localhost:8080/src/API/state.php");

    console.log("HTTP Status:", res.status, res.ok);

    const text = await res.text();
    console.log("Rohantwort:", text);

    const data = JSON.parse(text);
    console.log("JSON data:", data);

    setDrinks(data.drinks ?? []);
    setScenario(data.scenario ?? null);
  } catch (err) {
    console.error("fetchState Fehler:", err);
  }
}

  async function runScenario() {
  try {
    console.log("runScenario startet");

    const res = await fetch("http://localhost:8080/src/API/scenario-run.php", {
      method: "POST",
    });

    console.log("runScenario HTTP Status:", res.status, res.ok);

    const text = await res.text();
    console.log("runScenario Rohantwort:", text);

    const data = JSON.parse(text);
    console.log("runScenario JSON:", data);

    if (data.success) {
      setDrinks(data.drinks ?? []);
      setScenario(data.scenario ?? null);
    } else {
      console.error("API Fehler:", data.error);
    }
  } catch (err) {
    console.error("runScenario Fehler:", err);
  }
}

useEffect(() => {
  console.log("useEffect läuft");
  fetchState();
}, []);

  return (
    <div>
      <h1>Drinks</h1>

      <button onClick={runScenario}>Scenario ausführen</button>

      <h2>Aktuelles Scenario</h2>
      <pre>{JSON.stringify(scenario, null, 2)}</pre>

      <h2>Preise</h2>
      <ul>
        {drinks.map((drink) => (
          <li key={drink.id}>
            {drink.name} - {drink.price} € - Orders: {drink.order_count}
          </li>
        ))}
      </ul>
    </div>
  );
}

export default App;