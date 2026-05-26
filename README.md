# Car Management App

Aplicatie web pentru management auto, construita cu:

- HTML
- CSS
- JavaScript
- PHP
- MySQL

## Cerinte

Pentru a rula aplicatia local, ai nevoie de:

- PHP instalat si disponibil in terminal prin comanda `php`
- MySQL instalat si pornit
- extensia PHP `pdo_mysql` activa

Poti verifica extensia MySQL cu:

```bash
php -m | findstr mysql
```

Ar trebui sa vezi ceva de forma:

```text
mysqli
mysqlnd
pdo_mysql
```

## Configurare Baza De Date

Din folderul principal al proiectului, ruleaza:

```bash
mysql -u root -p < database.sql
```

Daca utilizatorul `root` nu are parola, poti rula:

```bash
mysql -u root < database.sql
```

Fisierul `database.sql` creeaza baza de date `car_management` si tabelele:

- `users`
- `vehicles`
- `vehicle_documents`

Conexiunea foloseste implicit aceste valori:

```text
DB_HOST=localhost
DB_PORT=3306
DB_NAME=car_management
DB_USER=root
DB_PASS=root
```

Daca ai alt utilizator sau alta parola in MySQL, actualizeaza variabilele de mediu sau valorile implicite din `src/db.php`.

## Pornire Aplicatie

Din folderul principal al proiectului, ruleaza:

```bash
php -S 127.0.0.1:8000 -t public
```

Apoi deschide in browser:

```text
http://127.0.0.1:8000/
```

Pagina principala redirectioneaza automat catre login daca nu esti autentificat.
Dupa autentificare, aplicatia deschide dashboard-ul:

```text
http://127.0.0.1:8000/dashboard.php
```

## Structura Proiectului

```text
  public/
  index.php              redirectioneaza catre login sau dashboard
  dashboard.php          pagina principala cu statistici reale din baza de date
  auth/
    login.php            pagina de autentificare
    register.php         pagina de creare cont
    logout.php           delogare
  vehicles/
    index.php            pagina pentru vehicule, momentan goala
  assets/
    css/styles.css       stilurile aplicatiei
    js/auth.js           validare simpla pentru formulare

src/
  db.php                 conexiunea PDO la MySQL
  DashboardRepository.php interogari SQL pentru vehicule si documente
  DashboardService.php   agregare date pentru dashboard
  logger.php             logger simplu pentru erori

database.sql             scriptul pentru baza de date
logs/app.log             fisier generat automat pentru erori locale
```

## Dezvoltare

Pentru modificari normale in fisiere PHP, CSS sau JavaScript, nu trebuie repornit serverul PHP. Este suficient sa dai refresh in browser.

Trebuie repornit serverul doar daca:

- modifici `php.ini`
- activezi/dezactivezi extensii PHP
- schimbi variabile de mediu pentru baza de date
- schimbi portul sau comanda de pornire
- serverul PHP s-a oprit cu eroare

## Debugging

Erorile de baza de date pentru login si register sunt scrise in:

```text
logs/app.log
```

In browser se afiseaza mesaje simple pentru utilizator, iar detaliile tehnice raman in log pentru dezvoltatori.

Exemplu:

```bash
Get-Content logs\app.log
```
