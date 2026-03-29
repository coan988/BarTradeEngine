import { useEffect, useState } from "react";

export default function App() {
  const [drinks, setDrinks] = useState([]);

  async function loadDrinks() {
    const res = await fetch("http://localhost:8080/api/drinks");
    const data = await res.json();
    setDrinks(data);
  }

  async function orderDrink(name) {
    await fetch("http://localhost:8080/api/order", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ name }),
    });

    loadDrinks();
  }

  useEffect(() => {
    loadDrinks();
  }, []);

  return (
    <div>
      <h1>BarTradeEngine</h1>
      {drinks.map((drink) => (
        <div key={drink.id}>
          {drink.name} - {drink.price} € - Orders: {drink.order_count}
          <button onClick={() => orderDrink(drink.name)}>Bestellen</button>
        </div>
      ))}
    </div>
  );
}