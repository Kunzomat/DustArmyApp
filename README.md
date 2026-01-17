# Dust 1947 – Web App (PHP + JavaScript)

Diese Anwendung besteht aus:

* **Frontend:** statische HTML- und JavaScript-Dateien
* **Backend:** PHP-Dateien als API für die MySQL/MariaDB-Datenbank

Kein Framework, kein Build-Step – einfach, schnell und transparent.

---

## 📁 Projektstruktur

```
dust1947-app/
├─ public/              # Webroot (Frontend)
│  ├─ index.html
│  ├─ js/
│  ├─ css/
│  └─ images/
├─ api/                 # PHP API Endpoints
├─ config/              # Konfiguration & DB
├─ database/            # SQL Schema / Seeds
├─ scripts/             # Setup & Deployment
├─ .env.example
├─ .gitignore
└─ README.md
```

---

## 🖥 Lokale Entwicklungsumgebung (Raspberry Pi)

### Voraussetzungen

* Apache oder PHP ≥ 8
* MariaDB / MySQL
* Git
* (optional) FileZilla oder Double Commander

---

### 1️⃣ Repository klonen

```bash
git clone <REPO-URL>
cd dust1947-app
```

---

### 2️⃣ Environment-Datei anlegen

```bash
cp .env.example .env
```

`.env` **nicht committen**
(Beispielinhalt:)

```
APP_ENV=dev
DB_HOST=localhost
DB_NAME=dust1947
DB_USER=andreas
DB_PASS=DEIN_PASSWORT
```

---

### 3️⃣ Datenbank anlegen

```bash
sudo mysql
```

```sql
CREATE DATABASE dust1947 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

(Optional)

```bash
mysql dust1947 < database/schema.sql
```

---

### 4️⃣ Lokal starten (Variante A – Apache, empfohlen)

Repository **nicht direkt** in `/var/www/html` legen, sondern verlinken:

```bash
sudo ln -s /home/kunzomat/projects/dust1947-app /var/www/html/dust1947
```

Im Browser:

```
http://localhost/dust1947/public/
```

API-Test:

```
http://localhost/dust1947/api/armies.list.php
```

---

### 5️⃣ Lokal starten (Variante B – PHP Built-in Server)

```bash
php -S localhost:8000 -t public
```

Browser:

```
http://localhost:8000
```

---

## 🚀 Deployment zu Strato

### Grundannahmen

* Strato Webroot: `htdocs/`
* FTP/SFTP-Zugang vorhanden
* PHP + MySQL aktiv

---

### 1️⃣ Was wird deployt?

Auf Strato werden **nur diese Ordner benötigt**:

```
public/
api/
config/
```

❌ **Nicht hochladen:**

* `.env.example`
* `database/`
* `scripts/`
* `.git/`

---

### 2️⃣ Deployment-Paket erstellen

```bash
bash scripts/deploy-strato.sh
```

Das Script erzeugt:

```
deploy/strato/
```

👉 **Diesen Inhalt** per FileZilla nach Strato in `htdocs/` hochladen.

---

### 3️⃣ `.env` auf Strato anlegen

Auf dem Server eine `.env` mit **Live-Daten** erstellen:

```
APP_ENV=prod
DB_HOST=rdbms.strato.de
DB_NAME=STRATO_DB
DB_USER=STRATO_USER
DB_PASS=STRATO_PASS
```

⚠️ **Diese Datei niemals committen**

---

### 4️⃣ Live-URL testen

```
https://deine-domain.de/
https://deine-domain.de/api/armies.list.php
```

---

## 🔐 Sicherheitshinweise

* `config/` enthält keine Zugangsdaten im Klartext
* `.env` ist nicht versioniert
* Keine sensiblen Dateien im `public/`-Ordner

---

## 🧪 Debugging

In `APP_ENV=dev`:

* PHP-Errors sichtbar
* JSON-Ausgaben direkt testbar

In `APP_ENV=prod`:

* Fehler nicht ausgeben
* Nur loggen

---

## 🧭 Typischer Workflow

1. Entwicklung lokal auf dem Raspberry
2. Commit ins Git-Repository
3. `deploy-strato.sh` ausführen
4. Upload nach Strato
5. Fertig ✅

---

## 📌 Empfehlung

* API-Endpunkte klein & klar halten
* Einheitliche JSON-Struktur verwenden
* Views (z.B. `v_army_points`) in SQL versionieren

---

**Happy coding 🚀**
