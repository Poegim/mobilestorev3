# MobileStore v3 — Setup & Commands

sail artisan migrate
sail artisan import:legacy --fresh
sail artisan db:seed --class=AdminSeeder

//Not sure is it used, problably should be deleted
sail artisan import:devices

// Not used anymore - remove files when not needed
sail artisan seed:products-from-legacy --limit=1000 --min-items=5 --years=2
