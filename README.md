# MAXCitas API

Backend Laravel de **MAXCitas** (agenda para agentes RE/MAX).

- API: `https://agenda.fftechcontadores.com/api`
- Repositorio: https://github.com/maxinetheker/agenda
- Guía de Google Calendar y Firebase: [CONFIGURACION.md](CONFIGURACION.md)

## Despliegue en el servidor

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

En `.env` del servidor:

```
APP_URL=https://agenda.fftechcontadores.com
GOOGLE_REDIRECT_URI=https://agenda.fftechcontadores.com/api/google/callback
FIREBASE_PROJECT_ID=maxcitas-remax
```

Coloca la cuenta de servicio de Firebase en `storage/app/firebase/service-account.json`.

Cron (recordatorios un día antes):

```
* * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Usuarios de demostración

- `maria.santos@remax.com` / `Remax2026!`
- `carlos.herrera@remax.com` / `Remax2026!`
