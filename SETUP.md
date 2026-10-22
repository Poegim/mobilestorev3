# MobileStore v3 — Setup & Commands

sail artisan migrate
sail artisan import:legacy --fresh
sail artisan db:seed --class=AdminSeeder
sail artisan import:devices
sail artisan seed:products-from-legacy --limit=1000 --min-items=5 --years=2
