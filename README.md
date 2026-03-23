# BarTradeEngine
BarTradeEngine is a dynamic pricing engine for bars. It processes real-time POS sales data, visualizes drink demand, and automatically adjusts prices based on supply and demand.

# Struktur
-Backend
    - Im Ordner scenario ist die Logik hinter einem Physischen Knopf. Wenn dieser gedrückt wird wird random eine Funktion ausgeführt, welche die Preise der Getränke anpasst.
    - im Ordner pricebuilding ist die Logik hinter der Standartpreisschwankund die mittels Faktoren Nachfrage und Zeit beeinflusst wird. Der Faktor Nachfrage kommt dabei als input von dem Kassensystem