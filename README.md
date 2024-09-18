# 🌿 CO2 Measurement Application 🌡️

## Table of Contents
- [🎯 Description](#-description)
- [✨ Features](#-features)
- [🛠️ Requirements](#️-requirements)
- [🚀 Installation](#-installation)
- [🏃‍♂️ Running the Application](#️-running-the-application)
- [🧪 Running Tests](#-running-tests)
- [📚 API Documentation](#-api-documentation)

## 🎯 Description

The CO2 Measurement Application is a robust Laravel-based API designed to collect and analyze CO2 measurements from hundreds of thousands of sensors. It provides real-time monitoring, alerting, and statistical analysis to ensure safe CO2 levels in various environments.

## ✨ Features

- 📡 Receive measurements from sensors at a rate of 1 per minute
- 🚨 Automatic status updates (OK, WARN, ALERT) based on CO2 levels
- 📊 Calculate and provide metrics (30-day average and maximum CO2 levels)
- 🔔 Store and retrieve alerts for critical CO2 levels
- 🚦 Intelligent state management for sensor status
- 🔒 Rate limiting to ensure data integrity

## 🛠️ Requirements

- 🐳 Docker
- 🐙 Docker Compose
- 🌳 Git

## 🚀 Installation

1. Clone the repository:
   ```
   git clone https://github.com/jonathanpereira/co2.git
   cd co2
   ```

2. Create a copy of the `.env.example` file and name it `.env`:
   ```
   cp .env.example .env
   ```

3. Update the following database configuration in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=sail
   DB_PASSWORD=password
   ```

4. Run the following command to download and install Laravel Sail:
   ```
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php82-composer:latest \
       composer install --ignore-platform-reqs
   ```

5. Build and start the Docker containers using Laravel Sail:
   ```
   ./vendor/bin/sail up -d
   ```

6. Generate an application key:
   ```
   ./vendor/bin/sail artisan key:generate
   ```

7. Run database migrations:
   ```
   ./vendor/bin/sail artisan migrate
   ```

Now your application is set up and ready to go! 🎉

## 🏃‍♂️ Running the Application

Start the application using Laravel Sail:

```
./vendor/bin/sail up -d
```

The API will be available at `http://localhost` 🌐

To stop the application:

```
./vendor/bin/sail down
```

## 🧪 Running Tests

To run the application tests:

```
./vendor/bin/sail test
```

## 📚 API Documentation

This project includes an OpenAPI (formerly Swagger) specification file `openapi.json` in the root directory. This file provides a detailed description of the API endpoints, request/response structures, and other important details.

You can use this file with various tools:
- To generate interactive API documentation (e.g., using Swagger UI)
- To import into API testing tools like Postman
- To generate client SDKs in various programming languages

For a quick view of the API documentation, you can copy the contents of `openapi.json` and paste them into the [Swagger Editor](https://editor.swagger.io/).

### Base URL

All URLs referenced in the documentation have the following base:

```
http://localhost/api/v1
```

### Endpoints

#### 🩺 Health Check

```
GET /sensors/health
```

Checks the health status of the API.

**Response**

```json
{
  "status": "ok"
}
```

**Response Codes:**
- 200 OK: The API is functioning correctly
- 500 Internal Server Error: There's an issue with the API

#### 📝 Store Measurement

```
POST /sensors/{uuid}/measurements
```

Stores a new CO2 measurement for a specific sensor. Rate-limited to one per minute per sensor.

**Request Body**

```json
{
  "co2": 2000,
  "time": "2024-09-18T10:00:00+00:00"
}
```

**Validation:**
- `co2`: Required, integer, minimum value of 0
- `time`: Required, must be a valid date-time string in ISO 8601 format

**Response**

```json
{
  "message": "Measurement stored successfully",
  "data": {
    "id": 1,
    "sensor": "123e4567-e89b-12d3-a456-426614174000",
    "co2": 2000,
    "time": "2024-09-18T10:00:00+00:00",
    "status": "WARN"
  }
}
```

**Response Codes:**
- 201 Created: Measurement stored successfully
- 400 Bad Request: Invalid input data
- 422 Unprocessable Entity: Validation errors
- 429 Too Many Requests: Rate limit exceeded

**Example**
```
curl -X POST \
  'http://localhost/api/v1/sensors/ac3e3b69-8f84-45e8-88df-9223a98d6922/measurements' \
  -H 'Accept: */*' \
  -H 'Content-Type: application/json' \
  -d '{
  "co2": 2000,
  "time": "2024-09-18T10:00:00+00:00"
}'
``` 

#### 🔍 Get Sensor Status

```
GET /sensors/{uuid}
```

Retrieves the current status of a specific sensor.

**Response**

```json
{
  "status": "OK"  // Possible statuses: OK, WARN, ALERT
}
```

**Response Codes:**
- 200 OK: Status retrieved successfully
- 404 Not Found: Sensor not found

#### 📊 Get Sensor Metrics

```
GET /sensors/{uuid}/metrics
```

Retrieves metrics for a specific sensor.

**Response**

```json
{
  "maxLast30Days": 2100,
  "avgLast30Days": 1250.5
}
```

**Response Codes:**
- 200 OK: Metrics retrieved successfully
- 404 Not Found: Sensor not found

#### 🚨 Get Sensor Alerts

```
GET /sensors/{uuid}/alerts
```

Retrieves alerts for a specific sensor.

**Response**

```json
[
  {
    "startTime": "2024-09-15T14:30:00Z",
    "endTime": "2024-09-15T15:00:00Z",
    "measurement1": 2100,
    "measurement2": 2200,
    "measurement3": 2100
  }
]
```

**Response Codes:**
- 200 OK: Alerts retrieved successfully
- 404 Not Found: Sensor not found

### 🌐 Accessing Swagger Documentation

To view the interactive API documentation:

1. Ensure your application is running locally.
2. Open your web browser and navigate to:

   ```
   http://localhost/swagger
   ```

3. You'll see the Swagger UI, which provides a comprehensive and interactive view of all API endpoints.

### 🛑 Error Responses

The API uses conventional HTTP response codes to indicate the success or failure of an API request.

### ⏱️ Rate Limiting

- Measurements: 1 request per minute per sensor
- Other endpoints: 100 requests per minute per IP address, 1000 requests per hour per IP address

## 🔧 Technology Stack

- 💻 PHP 8.2
- 🚀 Laravel 11
- 🐳 Docker & Laravel Sail
- 🗃️ MySQL Database
- 🧪 PHPUnit for testing

---

🌟 Built with Laravel 11 and PHP 8.2 | Developed by Jonathan Pereira
