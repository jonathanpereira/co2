# Sensor Measurement Application

## Table of Contents
- [Description](#description)
- [Requirements](#requirements)
- [Installation](#installation)
- [Running the Application](#running-the-application)
- [Running Tests](#running-tests)
- [API Documentation](#api-documentation)

## Description

The Sensor Measurement Application is a Laravel-based API that allows storing and retrieving CO2 measurements from sensors. It provides endpoints for storing measurements, retrieving sensor status, metrics, and alerts.

## Requirements

- Docker
- Docker Compose
- Git

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/sensor-measurement-app.git
   cd sensor-measurement-app
   ```

2. Install Laravel Sail:
   ```
   composer require laravel/sail --dev
   ```

3. Create a copy of the `.env.example` file and name it `.env`:
   ```
   cp .env.example .env
   ```

4. Configure the `.env` file with your preferred settings. The defaults should work for a local setup.

5. Build and start the Docker containers using Laravel Sail:
   ```
   ./vendor/bin/sail up -d
   ```

6. Install PHP dependencies:
   ```
   ./vendor/bin/sail composer install
   ```

7. Generate an application key:
   ```
   ./vendor/bin/sail artisan key:generate
   ```

8. Run database migrations:
   ```
   ./vendor/bin/sail artisan migrate
   ```

## Running the Application

Once installed, you can start the application using Laravel Sail:

```
./vendor/bin/sail up -d
```

The API will be available at `http://localhost` (or the port you've configured in your `.env` file).

To stop the application:

```
./vendor/bin/sail down
```

## Running Tests

To run the application tests:

```
./vendor/bin/sail test
```

## API Documentation

### Base URL

All URLs referenced in the documentation have the following base:

```
http://localhost/api/v1
```

### Endpoints

#### Health Check

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

#### Store Measurement

```
POST /sensors/{sensor_uuid}/measurements
```

Stores a new CO2 measurement for a specific sensor. Note that measurements are rate-limited to one per minute per sensor.

**Parameters**

| Name | Type | Description |
|------|------|-------------|
| sensor_uuid | string | The UUID of the sensor |

**Request Body**

```json
{
  "co2": 1000,
  "time": "2024-09-18T10:00:00+00:00"
}
```

**Response**

```json
{
  "message": "Measurement stored successfully",
  "data": {
    "id": 1,
    "sensor": "123e4567-e89b-12d3-a456-426614174000",
    "co2": 1000,
    "time": "2024-09-18T10:00:00+00:00",
    "status": "OK"
  }
}
```

**Error Response (Rate Limit Exceeded)**

```json
{
  "error": "Rate limit exceeded. Only one measurement per minute is allowed."
}
```

#### Get Sensor Status

```
GET /sensors/{sensor_uuid}
```

Retrieves the current status of a specific sensor.

**Parameters**

| Name | Type | Description |
|------|------|-------------|
| sensor_uuid | string | The UUID of the sensor |

**Response**

```json
{
  "status": "OK"
}
```

#### Get Sensor Metrics

```
GET /sensors/{sensor_uuid}/metrics
```

Retrieves metrics for a specific sensor.

**Parameters**

| Name | Type | Description |
|------|------|-------------|
| sensor_uuid | string | The UUID of the sensor |

**Response**

```json
{
  "maxLast30Days": 2100,
  "avgLast30Days": 1250.5
}
```

#### Get Sensor Alerts

```
GET /sensors/{sensor_uuid}/alerts
```

Retrieves alerts for a specific sensor.

**Parameters**

| Name | Type | Description |
|------|------|-------------|
| sensor_uuid | string | The UUID of the sensor |

**Response**

```json
[
  {
    "startTime": "2024-09-15T14:30:00Z",
    "endTime": "2024-09-15T15:00:00Z",
    "measurement1": 2100,
    "measurement2": 2200,
    "measurement3": 2150
  },
  {
    "startTime": "2024-09-17T09:00:00Z",
    "endTime": null,
    "measurement1": 2300,
    "measurement2": 2250,
    "measurement3": 2400
  }
]
```

### Error Responses

The API uses conventional HTTP response codes to indicate the success or failure of an API request. In general:

- 2xx range indicate success
- 4xx range indicate an error that failed given the information provided (e.g., a required parameter was omitted, etc.)
- 5xx range indicate an error with our servers

### Example Error Response

```json
{
  "error": "Invalid sensor UUID format"
}
```

### Rate Limiting

The API implements rate limiting to prevent abuse:

- Measurements: 1 request per minute per sensor (implemented using Laravel's RateLimiter)
- Other endpoints: 100 requests per minute per IP address, 1000 requests per hour per IP address

If you exceed these limits, you'll receive a 429 Too Many Requests response.

### Authentication

Currently, this API does not require authentication. However, it's recommended to implement proper authentication and authorization mechanisms before deploying to a production environment.
