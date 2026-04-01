# BarTradeEngine
BarTradeEngine is a dynamic pricing engine for bars. It processes real-time POS sales data, visualizes drink demand, and automatically adjusts prices based on supply and demand.

# vorinstallation
docker compose exec backend composer install    //um die .env im backend 

# tests
docker compose exec backend php /var/www/html/src/test_db.php
docker compose exec backend php /var/www/html/src/pricebuilding/test_pricebuilding.php
docker compose exec backend php /var/www/html/src/scenario/test_button.php

# DB zugriff
docker compose exec db mysql -u <user> -p <db_name>

