# Novel Echoes web

This repository contains the source code of the [Novel Echoes](https://www.novelsaints.ugent.be/) database.

The Novel Echoes database consists of a Symphony back-end connected to a MariaDB database and Elasticsearch search engine.
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

After the containers are up and running, you can access the Novel Echoes database on [localhost:8080](http://localhost:8080).

## Database

In the `initdb` folder, you can find the necessary scripts to create the database schema and a minimum test dataset. The
sql scripts are run when when the database container is first created.

You can add additional scripts to the `initdb` folder if required.

## Indexing

During the first run, the startup script will create (if needed) initial indexes for the Elasticsearch search engine (
100 records max).

To index more records, run the following command:

```sh
docker exec -it novel-echoes-dev-symfony-1 bin/console app:elasticsearch:index text [max limit]
```

## Credits

Development by [Ghent Centre for Digital Humanities - Ghent University](https://www.ghentcdh.ugent.be/). Funded by the [GhentCDH research projects](https://www.ghentcdh.ugent.be/projects).

<img src="https://www.ghentcdh.ugent.be/ghentcdh_logo_blue_text_transparent_bg_landscape.svg" alt="Landscape" width="500">
