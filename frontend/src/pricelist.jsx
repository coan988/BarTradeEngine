import { useEffect, useMemo, useRef, useState } from "react";
import "./pricelist.css";

const FLASH_DURATION_MS = 500;
const POLL_INTERVAL_MS = 1200;

function Pricelist({ onBack }) {
  const [drinks, setDrinks] = useState([]);
  const [message, setMessage] = useState("");
  const [flashStates, setFlashStates] = useState({});

  const previousPricesRef = useRef({});
  const flashTimeoutsRef = useRef({});

  async function fetchDrinks() {
    try {
      setMessage("");

      const res = await fetch("http://localhost:8080/src/API/state.php");
      const data = await res.json();
      const nextDrinks = data.drinks ?? [];

      const nextFlashStates = {};

      nextDrinks.forEach((drink) => {
        const id = String(drink.id);
        const newPrice = Number(drink.price);
        const oldPrice = previousPricesRef.current[id];

        if (typeof oldPrice === "number" && newPrice !== oldPrice) {
          nextFlashStates[id] = newPrice > oldPrice ? "flash-up" : "flash-down";
        }
      });

      const nextPreviousPrices = {};
      nextDrinks.forEach((drink) => {
        nextPreviousPrices[String(drink.id)] = Number(drink.price);
      });

      previousPricesRef.current = nextPreviousPrices;
      setDrinks(nextDrinks);

      Object.entries(nextFlashStates).forEach(([id, flashClass]) => {
        setFlashStates((prev) => ({
          ...prev,
          [id]: flashClass,
        }));

        if (flashTimeoutsRef.current[id]) {
          window.clearTimeout(flashTimeoutsRef.current[id]);
        }

        flashTimeoutsRef.current[id] = window.setTimeout(() => {
          setFlashStates((prev) => {
            const updated = { ...prev };
            delete updated[id];
            return updated;
          });
        }, FLASH_DURATION_MS);
      });
    } catch (err) {
      console.error("fetchDrinks Fehler:", err);
      setMessage("Fehler beim Laden der Preisliste.");
    }
  }

  useEffect(() => {
    fetchDrinks();

    const intervalId = window.setInterval(fetchDrinks, POLL_INTERVAL_MS);

    return () => {
      window.clearInterval(intervalId);

      Object.values(flashTimeoutsRef.current).forEach((timeoutId) => {
        window.clearTimeout(timeoutId);
      });
    };
  }, []);

  const boardSizeClass = useMemo(() => {
    const count = drinks.length;

    if (count <= 80) return "board-size-xl";
    if (count <= 3) return "board-size-lg";
    if (count <= 2) return "board-size-md";
    if (count <= 1) return "board-size-sm";
    return "board-size-xs";
  }, [drinks.length]);

  return (
    <div className="pricelist-page">
      <div className="pricelist-shell">
        <div className="pricelist-header">
          <div>
            <div className="pricelist-kicker">LIVE MARKET</div>
            <h1 className="pricelist-title">Preisliste</h1>
          </div>

          <button className="pricelist-back-button" onClick={onBack}>
            Zurück
          </button>
        </div>

        <div className={`pricelist-board ${boardSizeClass}`}>
          <div className="pricelist-head pricelist-row">
            <div className="pricelist-name">Getränk</div>
            <div className="pricelist-price">Preis</div>
          </div>

          <div className="pricelist-body">
            {drinks.map((drink) => (
              <div className="pricelist-row" key={drink.id}>
                <div className="pricelist-name drink-name" title={drink.name}>
                  {drink.name}
                </div>

                <div
                  className={`pricelist-price drink-price ${
                    flashStates[String(drink.id)] ?? ""
                  }`}
                >
                  {Number(drink.price).toFixed(2)} €
                </div>
              </div>
            ))}

            {drinks.length === 0 && (
              <div className="pricelist-empty">Keine Getränke vorhanden.</div>
            )}
          </div>
        </div>

        {message && <div className="pricelist-message">{message}</div>}
      </div>
    </div>
  );
}

export default Pricelist;