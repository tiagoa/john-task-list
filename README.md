# John Task List

## Running locally
### Requirements
- Docker and docker-compose

```bash
cp .env.example .env
docker-compose up -d
```

```bash
docker-compose exec laravel php artisan migrate
docker-compose exec laravel php artisan storage:link
docker-compose exec laravel php artisan key:generate
```

## Running tests
```bash
cp .env.example .env.testing
docker-compose exec laravel php artisan test
```

## API docs:
Generate API docs
```bash
docker-compose exec laravel php artisan l5-swagger:generate
```
Access it in: http://localhost/api/docs

## Deployment
This project is deployed in an AWS EC2 instance, and it is accessible in: http://3.238.18.132/api/docs.

This project's Docker image can be found at: https://hub.docker.com/r/tiagoa/john-task-list

### Deployment instructions
Clean the application
```bash
cp .env .env.production
docker-compose exec laravel php artisan cache:clear
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

## Notes
The PUT method needs to be POST with _method=PUT query parameter due to a bug in Laravel/Symfony.
