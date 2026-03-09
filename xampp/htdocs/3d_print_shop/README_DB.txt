========================================
3D Print Shop – juhend andmebaasi loomiseks
========================================

1. Projekti lühikirjeldus
-------------------------

"3D Print Shop" on 3D-printimisteenuste veebipood.
Kasutaja saab:
  – vaadata toodete kataloogi,
  – luua kasutajakonto ja sisse logida,
  – tellida valmis tooteid,
  – esitada individuaalse 3D-printimise päringu oma failiga,
  – võtta ühendust teenusepakkujaga kontaktivormi kaudu.

Backendi tehnoloogia: PHP 8 + MySQL.
Andmebaasi haldamiseks kasutatakse XAMPP-i (MySQL + phpMyAdmin).

2. Projekti struktuur (lühidalt)
--------------------------------

3d_print_shop/
  css/
    style.css                 – kujundus ja üldine CSS
  db/
    install.sql               – SQL-skript andmebaasi ja tabelite loomiseks
  includes/
    db.php                    – ühendus MySQL andmebaasiga
    header.php, footer.php    – ühtne päis ja jalus
    mail_config.php           – e-posti saatmise seadistus (vajadusel)
  uploads/
    images/                   – toodete pildid
    models/                   – üleslaetud 3D-failid
  index.php                   – avaleht
  catalog.php                 – toodete kataloog
  product.php                 – üksiku toote vaade
  register.php, login.php,
  logout.php, profile.php     – kasutajakonto loomine ja profiil
  custom_order.php            – individuaalne 3D-tellimus (faili üleslaadimine)
  contacts.php                – kontaktivorm (salvestab sõnumi andmebaasi)
  services.php, portfolio.php,
  blog.php                    – sisulehed
  install.php                 – ühekordne skript, mis käivitab db/install.sql

3. MySQL andmebaasi loomine (XAMPP-is)
--------------------------------------

Eeldused:
  – XAMPP on paigaldatud;
  – Apache ja MySQL teenused on käivitatud.

Sammud:

1) Kopeeri projekti kaust "3d_print_shop" XAMPP-i htdocs kausta:
   Windows:  C:\xampp\htdocs\3d_print_shop
   (või D:\xampp\htdocs\3d_print_shop – vastavalt paigaldusele)

2) Ava brauseris aadress:
   http://localhost/3d_print_shop/install.php

   Skript:
     – kustutab vana andmebaasi "3d_print_shop" (kui see olemas on),
     – loob uue andmebaasi ja kõik vajalikud tabelid,
     – lisab näidiskategooriad ja tooted.

3) Kui ekraanile ilmub tekst:
   "Baza dannyh i tablitsy uspeshno sozdany / obnovleny"
   siis andmebaas on korras.

4) Ava seejärel veebirakendus:
   http://localhost/3d_print_shop/index.php


4. Andmebaasi struktuur
-----------------------

DB nimi: 3d_print_shop

Peamised tabelid:

1) users
   - id (INT, PK, AUTO_INCREMENT)
   - name (VARCHAR)
   - email (VARCHAR, UNIQUE)
   - phone (VARCHAR)
   - password_hash (VARCHAR)
   - role (ENUM('user','admin'))
   - created_at (DATETIME)

   Kirjeldus: salvestab kasutajad ja nende rollid (tavakasutaja / admin).

2) categories
   - id (INT, PK)
   - name (VARCHAR)
   - description (TEXT)
   - created_at (DATETIME)

   Kirjeldus: toodete kategooriad (mänguasjad, prototüübid, detailid jne).

3) products
   - id (INT, PK)
   - category_id (INT, FK -> categories.id, ON DELETE SET NULL)
   - name (VARCHAR)
   - description (TEXT)
   - price (DECIMAL)
   - image_path (VARCHAR)
   - is_active (TINYINT)
   - created_at (DATETIME)

   Kirjeldus: poe tooted (valmis mudelid).

4) orders
   - id (INT, PK)
   - user_id (INT, FK -> users.id, ON DELETE SET NULL)
   - customer_name, customer_email, customer_phone
   - total_amount (DECIMAL)
   - status (ENUM('new','processing','done','cancelled'))
   - created_at (DATETIME)

   Kirjeldus: valmis toodete tellimused.

5) order_items
   - id (INT, PK)
   - order_id (INT, FK -> orders.id, ON DELETE CASCADE)
   - product_id (INT, FK -> products.id)
   - quantity (INT)
   - unit_price (DECIMAL)

   Kirjeldus: iga tellimuse rea-kaubad (mitu toodet, mis hinnaga).

6) custom_orders
   - id (INT, PK)
   - user_id (INT, FK -> users.id, ON DELETE SET NULL)
   - customer_name, customer_email, customer_phone
   - material, color
   - layer_height (DECIMAL)
   - infill (INT)
   - estimated_price (DECIMAL, võib olla NULL)
   - status (ENUM('new','processing','done','cancelled'))
   - model_file (VARCHAR) – tee üleslaetud 3D-failini
   - comment (TEXT)
   - created_at (DATETIME)

   Kirjeldus: individuaalsed 3D-printimise päringud koos kliendi failiga.

7) contacts
   - id (INT, PK)
   - name, email, subject, message
   - created_at (DATETIME)

   Kirjeldus: kontaktivormi kaudu saadetud sõnumid.


5. Lühike seletus install.php kohta
-----------------------------------

install.php loeb faili db/install.sql ja täidab selle käsud MySQL-is.
Skript sobib kasutamiseks:
  – esmakordsel projekti paigaldamisel,
  – andmebaasi struktuuri taastamisel demo jaoks.

Kui andmebaasis on juba väärtuslikud andmed, EI TOHI install.php uuesti käivitada,
sest skript kustutab olemasoleva andmebaasi "3d_print_shop".
