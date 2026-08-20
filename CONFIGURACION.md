# Configuración de MAXCitas (backend, Google Calendar y Firebase)

Esta guía es lo que debes completar para que el check **Vincular Google Calendar**, las notificaciones y el login funcionen de punta a punta.

## 1. Backend Laravel (SQLite)

Archivo: `backend/.env`

```
APP_NAME=MAXCitas
APP_URL=https://agenda.fftechcontadores.com
APP_LOCALE=es
DB_CONNECTION=sqlite

GOOGLE_CLIENT_ID=TU_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=TU_CLIENT_SECRET
GOOGLE_REDIRECT_URI=https://agenda.fftechcontadores.com/api/google/callback

FIREBASE_PROJECT_ID=maxcitas-remax
```

La base SQLite está en `backend/database/database.sqlite`. No hace falta MySQL.

Arranque:

```bash
cd backend
php artisan migrate:fresh --seed
php artisan serve --host=0.0.0.0 --port=8000
```

En otra terminal (recordatorios cada hora, un día antes de la cita/tarea):

```bash
php artisan schedule:work
```

El comando que envía los push es:

```bash
php artisan maxcitas:send-reminders
```

### URLs de la API

| Método | Ruta | Uso |
|---|---|---|
| POST | `/api/login` | Iniciar sesión |
| GET | `/api/dashboard` | Inicio del agente |
| GET/PUT | `/api/profile` | Perfil |
| GET/POST | `/api/contacts` | Clientes |
| POST | `/api/contacts/import` | Contactos del teléfono |
| GET/POST | `/api/appointments` | Citas |
| GET/POST/DELETE | `/api/tasks` | Tareas |
| POST | `/api/tasks/{id}/google-sync` | Crear la tarea en Google Calendar |
| DELETE | `/api/tasks/{id}/google-sync` | Borrar la tarea de Google Calendar |
| GET/POST | `/api/availability` | Rangos de citas |
| GET | `/api/google/connect` | Devuelve la URL OAuth |
| GET | `/api/google/status` | Si está vinculado |
| POST | `/api/google/disconnect` | Quita el check y revoca el token |
| POST | `/api/device/fcm` | Guarda el token Firebase del celular |

Login de prueba:

```bash
curl -X POST http://localhost:8000/api/login ^
  -H "Accept: application/json" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"maria.santos@remax.com\",\"password\":\"Remax2026!\"}"
```

---

## 2. Google Cloud / Google Calendar (obligatorio para el check)

La app **no** usa una cuenta de servicio para el calendario del agente. Cada agente vincula **su** Google Calendar con OAuth. Así puede agregar o eliminar eventos desde MAXCitas.

### Paso A — Proyecto

1. Entra a [Google Cloud Console](https://console.cloud.google.com/).
2. Crea un proyecto, por ejemplo `maxcitas-remax`.
3. APIs y servicios → **Biblioteca** → habilita **Google Calendar API**.
4. También habilita **Google Identity / People API** o al menos OAuth (el endpoint `oauth2/v2/userinfo` se usa para guardar el correo vinculado).

### Paso B — Pantalla de consentimiento OAuth

1. APIs y servicios → **Pantalla de consentimiento OAuth**.
2. Tipo: **Externo** (o Interno si usas Google Workspace).
3. Nombre de la app: `MAXCitas RE/MAX`.
4. Correo de soporte: el tuyo.
5. Dominios autorizados: no hace falta en desarrollo con `localhost`.
6. En **Ámbitos** agrega:
   - `https://www.googleapis.com/auth/calendar`
   - `https://www.googleapis.com/auth/calendar.events`
   - `openid`
   - `email`
   - `profile`
7. En modo Prueba, agrega como **usuarios de prueba** los Gmail de los agentes que van a vincular.

### Paso C — Credenciales OAuth (tipo Aplicación web)

1. APIs y servicios → **Credenciales** → Crear credenciales → **ID de cliente de OAuth 2.0**.
2. Tipo: **Aplicación web** (no elige Android; el login de Google lo abre el backend y luego regresa a la app con `maxcitas://oauth`).
3. Orígenes JavaScript autorizados:
   - `http://localhost:8000`
   - `http://127.0.0.1:8000`
4. **URIs de redirección autorizados** (deben coincidir al carácter con `GOOGLE_REDIRECT_URI`):
   - `https://agenda.fftechcontadores.com/api/google/callback`
   - `http://localhost:8000/api/google/callback` (solo si pruebas en local)
5. Copia **Client ID** y **Client secret** al `.env`.
6. Reinicia el PHP-FPM o `php artisan config:clear`.

Si más adelante publicas el API en un dominio HTTPS, ya está previsto:

- Producción: `https://agenda.fftechcontadores.com/api/google/callback`

y cambia `APP_URL` + `GOOGLE_REDIRECT_URI`.

### Paso D — Qué hace el check en la app

En **Ajustes → Vincular Google Calendar**:

- **ON**: la app pide `/api/google/connect`, abre Chrome Custom Tab, el agente acepta permisos, Google vuelve a `/api/google/callback`, Laravel guarda `access_token`, `refresh_token` y `google_email`, y redirige a `maxcitas://oauth?google=connected`.
- **OFF**: llama `/api/google/disconnect`, revoca el token y deja de crear eventos.

Al crear una **tarea** con el switch “Agregar también a Google Calendar”, se inserta un evento en el calendario `primary` del agente, con recordatorio popup **1 día antes** y 30 minutos antes.

Desde la lista de tareas:

- Switch ON → `POST /api/tasks/{id}/google-sync` (crea el evento).
- Switch OFF o icono borrar → elimina el evento en Google (`events.delete`) y luego en MAXCitas.

Las **citas** también se copian a Google Calendar si el agente ya está vinculado.

### Paso E — Calendario que se usa

Por defecto `google_calendar_id = primary` (el calendario principal de esa cuenta Google). No hace falta pegar un Calendar ID a menos que quieras uno secundario.

---

## 3. Firebase Cloud Messaging

Los recordatorios salen del backend (no del teléfono) **un día antes**.

### En Firebase Console

1. Crea un proyecto (puede ser el mismo `maxcitas-remax`).
2. Agrega una app **Android** con paquete `com.remax.maxcitas`.
3. En **SHA-1 certificate fingerprint** pega esta huella del keystore de depuración:

```
22:6B:85:60:31:2B:1A:30:43:74:17:91:3F:EA:10:33:1A:16:7A:24
```

Si Firebase la pide otra vez, en PowerShell:

```powershell
keytool -list -v -keystore "$env:USERPROFILE\.android\debug.keystore" -alias androiddebugkey -storepass android -keypass android
```

4. Descarga `google-services.json` y colócalo en:
   - `android/app/google-services.json`
5. En `android/app/build.gradle.kts` el plugin de Google Services ya está activo.
6. En Firebase → Cuentas de servicio → **Generar nueva clave privada**.
7. Guarda el JSON en:

```
backend/storage/app/firebase/service-account.json
```

8. Pon `FIREBASE_PROJECT_ID` igual al `project_id` de ese JSON.

Sin ese archivo el API sigue funcionando; solo no envía push (queda registrado en `storage/logs`).

### En el teléfono

- Android 13+ debe aceptar el permiso de notificaciones.
- Al tocar el aviso, MAXCitas abre la ficha del **cliente que debes llamar**, con el botón **Llamar ahora**.

Canal de notificación: `maxcitas_reminders`.

---

## 4. App Android

- Package: `com.remax.maxcitas`
- Deep link de Google: `maxcitas://oauth`
- Deep link de cliente: `maxcitas://client/{id}`
- API de producción: `https://agenda.fftechcontadores.com/api/`
- SHA-1 debug: `22:6B:85:60:31:2B:1A:30:43:74:17:91:3F:EA:10:33:1A:16:7A:24`

Si el check de Google no abre, revisa que `GOOGLE_CLIENT_ID` no esté vacío y que la URI de redirección sea exactamente la del paso 2.C.

---

## 5. Checklist rápido

1. `php artisan migrate:fresh --seed`
2. `php artisan serve --host=0.0.0.0 --port=8000`
3. Login con `maria.santos@remax.com` / `Remax2026!`
4. Pegar Client ID/Secret de Google en `.env`
5. Activar el check de Google Calendar en Ajustes
6. Crear o marcar una tarea para sincronizarla
7. Colocar `service-account.json` de Firebase
8. `php artisan schedule:work` para avisos de un día antes
