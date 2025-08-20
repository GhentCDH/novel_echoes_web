# Diplomata Belgica

This repository contains the source code of the [Diplomata Belgica](https://www.diplomata-belgica.be/) database.

The Diplomata Belgica database consists of a Symphony back-end connected to a MariaDB database and Elasticsearch search engine.
The search and edit pages consist of Vue.js applications.

## Getting Started

First, check that `.env` contains the correct default configuration (see `example.env`).

Run the following command to run the docker services:

- PHP Symfony
- Elasticsearch
- MariaDB database
- Node.js

```sh
docker compose up --build 
```

After the containers are up and running, you can access the Diplomata Belgica database on [localhost:8080](http://localhost:8080).

## Database

In the `initdb` folder, you can find the necessary scripts to create the database schema and a minimum test dataset. The
sql scripts are run when when the database container is first created.

You can add additional scripts to the `initdb` folder if required.

## Indexing

During the first run, the startup script will create (if needed) initial indexes for the Elasticsearch search engine (
100 records max).

To index more records, run the following command:

```sh
docker exec -it dibe-dev-symfony-1 bin/console app:elasticsearch:index charter [max limit]
docker exec -it dibe-dev-symfony-1 bin/console app:elasticsearch:index tradition [max limit]
```