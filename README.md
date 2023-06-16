# John Task List

## Running locally
### Requirements
- Docker and docker-compose

```bash
docker run --rm -u "$(id -u):$(id -g)" -v $(pwd):/opt -w /opt laravelsail/php82-composer:latest composer install --ignore-platform-reqs
cp .env.example .env
docker-compose up -d
```

```bash
 ./vendor/bin/sail artisan migrate
 ./vendor/bin/sail artisan storage:link
```

## Running tests
```bash
cp .env.example .env.testing
./vendor/bin/sail artisan test
```

## API docs:
Generate API docs
```bash
./vendor/bin/sail artisan l5-swagger:generate
```
Access it in: http://localhost/api/docs

## Deployment
This project is deployed in an AWS EC2 instance, and it is accessible in: http://3.238.18.132/api/docs.

This project's Docker image can be found at: https://hub.docker.com/r/tiagoa/john-task-list

### Deployment instructions
Clean the application
```bash
./vendor/bin/sail artisan cache:clear
rm -rf vendor
```
```bash
docker build -t <DOCKER_HUB_USERNAME>/john-task-list .
docker push <DOCKER_HUB_USERNAME>/john-task-list
```
Upload the **docker-compose-production.yml** in your production machine then spin up the containers:
```bash
docker-compose up -d
```
Run the migrations:
```bash
docker-compose exec app php artisan migrate
```
