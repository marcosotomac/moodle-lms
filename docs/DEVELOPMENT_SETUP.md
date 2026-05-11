# Entorno de desarrollo Moodle + MCP

Este proyecto usa Moodle Open Source como runtime de desarrollo, pero no versiona el core de Moodle dentro del repositorio principal. El código propio/versionado vive en este repo; el core se clona localmente.

## Estructura local

```text
moodle-lms/
├── README.md              # Documento de análisis y diseño LMS
├── RFP CMC.pdf            # TDR con alcance LMS + CRM
├── mcp/                   # Plugin webservice_mcp versionado
├── moodle/                # Checkout local ignorado de Moodle MOODLE_502_STABLE
└── moodle-docker/         # Checkout local ignorado de moodle-docker
```

## Setup base

```bash
git clone --depth 1 -b MOODLE_502_STABLE https://github.com/moodle/moodle.git moodle
git clone --depth 1 https://github.com/moodlehq/moodle-docker.git moodle-docker
cp -R mcp moodle/public/webservice/mcp
cp moodle-docker/config.docker-template.php moodle/config.php
```

Moodle 5.2 usa `public/` como webroot. Para moodle-docker, crear `moodle-docker/local.yml`:

```yaml
services:
  webserver:
    command: >
      bash -lc "sed -ri -e 's!/var/www/html!/var/www/html/public!g'
      /etc/apache2/sites-available/*.conf
      /etc/apache2/apache2.conf
      /etc/apache2/conf-available/*.conf
      && apache2-foreground"
```

## Levantar Moodle

```bash
cd moodle-docker
MOODLE_DOCKER_WWWROOT="../moodle" \
MOODLE_DOCKER_DB=mariadb \
MOODLE_DOCKER_PHP_VERSION=8.3 \
MOODLE_DOCKER_WEB_PORT=8000 \
bin/moodle-docker-compose up -d

MOODLE_DOCKER_WWWROOT="../moodle" \
MOODLE_DOCKER_DB=mariadb \
MOODLE_DOCKER_PHP_VERSION=8.3 \
MOODLE_DOCKER_WEB_PORT=8000 \
bin/moodle-docker-wait-for-db
```

## Instalar base de datos

```bash
MOODLE_DOCKER_WWWROOT="../moodle" \
MOODLE_DOCKER_DB=mariadb \
MOODLE_DOCKER_PHP_VERSION=8.3 \
MOODLE_DOCKER_WEB_PORT=8000 \
bin/moodle-docker-compose exec webserver php admin/cli/install_database.php \
  --agree-license \
  --fullname="Moodle MCP Dev" \
  --shortname="moodle_mcp_dev" \
  --summary="Moodle development site with MCP webservice plugin" \
  --adminuser=admin \
  --adminpass="test" \
  --adminemail="admin@example.com"
```

## Activar MCP

```bash
MOODLE_DOCKER_WWWROOT="../moodle" MOODLE_DOCKER_DB=mariadb MOODLE_DOCKER_PHP_VERSION=8.3 MOODLE_DOCKER_WEB_PORT=8000 \
bin/moodle-docker-compose exec webserver php admin/cli/cfg.php --name=enablewebservices --set=1

MOODLE_DOCKER_WWWROOT="../moodle" MOODLE_DOCKER_DB=mariadb MOODLE_DOCKER_PHP_VERSION=8.3 MOODLE_DOCKER_WEB_PORT=8000 \
bin/moodle-docker-compose exec webserver php admin/cli/cfg.php --name=webserviceprotocols --set=mcp
```

El endpoint local queda en:

```text
http://localhost:8000/webservice/mcp/server.php
```

> No commitear tokens. Generarlos localmente por ambiente.
