# Progetto backend: SoccerBall - sistema di gestione tornei di calcio

## Comandi Utili

```bash
# Installa dipendenze
composer install

# Aggiorna autoload dopo aggiunta classi
composer dump-autoload

# Avvia server di sviluppo (PHP built-in)
php -S localhost:8000 -t public
```

## 1. Architettura del progetto
Il cuore pulsante di SoccerBall è sviluppato in PHP 8.2 seguendo rigorosamente il paradigma Object Oriented Programming (OOP). 
L'obiettivo principale è fornire un'infrastruttura solida per gestire la logica di un torneo a eliminazione diretta, esponendo i dati tramite API REST in formato JSON.

- **Struttura del database:**
Il sistema utilizza PostgresSQL come motore di database. La struttura è stata progettata per garantire l'integrità dei dati attraverso:
    - **Vincoli di integrità:** Una squadra non può essere eliminata se ha già preso parte a tornei passati
    - **Relazioi complesse:** Utilizzo di chiavi esterne per collegare tornei, partite e squadre permettendo una navigazione dei dati fluida dal primo round alla finale


- **Configura la connessione al database:** 
su `config/` rinomina il file `database.example.php` in `database.php` e inserisci i dati necessari per collegare il DB al backend

- **Configura le route** in `routes/index.php`


## 2. Struttura del Progetto

```
backend/
├── config/
│   ├── database.example.php            # Configurazione database
│   └── cors.php                        # Configurazione CORS
├── routes/                             
│   ├── games.php         
│   ├── index.php                       # Definizione delle rotte
│   ├── teams.php        
│   ├── tournament_teams.php        
│   ├── tournaments.php        
├── public/
│   └── index.php                       # Entry point
├── src/
│   ├── bootstrap.php                   # Bootstrap dell'applicazione
│   ├── Database/
│   ├── ├── DB.php                      # Classe DB
│   │   └── JSONDB.php                  # Classe JSONDB
│   ├── Models/
│   │   ├── BaseModel.php               # Classe BaseModel
│   │   ├── Game.php                    # Classe Game
│   │   ├── Team.php                    # Classe Team
│   │   ├── Tournament.php              # Classe Tournament
│   │   ├── TournamentTeam.php          # Classe TournamentTeam
│   └── Utils/
│       ├── Request.php                 # Classe Request
│       └── Response.php                # Gestione risposte JSON
├── composer.json                       # Dipendenze Composer
└── README.md                           # Questo file
```