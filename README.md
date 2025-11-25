# Package "ctw/ctw-middleware-responsetime"

[![Latest Stable Version](https://poser.pugx.org/ctw/ctw-middleware-responsetime/v/stable)](https://packagist.org/packages/ctw/ctw-middleware-responsetime)
[![GitHub Actions](https://github.com/jonathanmaron/ctw-middleware-responsetime/actions/workflows/tests.yml/badge.svg)](https://github.com/jonathanmaron/ctw-middleware-responsetime/actions/workflows/tests.yml)
[![Scrutinizer Build](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-responsetime/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-responsetime/build-status/master)
[![Scrutinizer Quality](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-responsetime/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-responsetime/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-responsetime/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-responsetime/?branch=master)

PSR-15 middleware that adds an `X-Response-Time` header showing request processing duration in milliseconds.

## Introduction

### Why This Library Exists

Understanding how long your application takes to process requests is essential for performance monitoring and optimization. While external tools can measure total response time, they include network latency. Measuring at the application level provides the true processing time.

This middleware adds an `X-Response-Time` header to every response showing:

- **Processing duration**: Time from request arrival to response generation
- **Millisecond precision**: Three decimal places for accurate measurement
- **Consistent format**: Standardized header for easy parsing by monitoring tools
- **Zero overhead**: Uses PHP's native `microtime()` for minimal performance impact

### Problems This Library Solves

1. **Invisible performance**: Without timing data, slow requests go unnoticed
2. **External tool inaccuracy**: Network-based measurements include latency, not just processing time
3. **Manual instrumentation**: Adding timing code to every handler is tedious and error-prone
4. **Inconsistent measurement**: Different timing implementations across the application
5. **Missing production visibility**: Development profilers aren't available in production

### Where to Use This Library

- **Performance monitoring**: Track response times across your application
- **SLA verification**: Ensure responses meet required time thresholds
- **Debugging slowness**: Quickly identify slow requests from response headers
- **Load testing**: Analyze response time distribution under load
- **Client-side monitoring**: JavaScript can read the header for real-user monitoring
- **Log correlation**: Include response time in access logs for analysis

### Design Goals

1. **High precision**: Milliseconds with three decimal places (microsecond resolution)
2. **Accurate timing**: Uses `REQUEST_TIME_FLOAT` as start time for precision
3. **Standard format**: `X.XXX ms` format is human-readable and parseable
4. **Minimal overhead**: Simple subtraction and header addition
5. **Universal application**: Works for HTML, JSON, and all response types

## Requirements

- PHP 8.3 or higher
- ctw/ctw-middleware ^4.0

## Installation

Install by adding the package as a [Composer](https://getcomposer.org) requirement:

```bash
composer require ctw/ctw-middleware-responsetime
```

## Usage Examples

### Basic Pipeline Registration (Mezzio)

```php
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddleware;

// In config/pipeline.php - place early in the pipeline for accurate timing
$app->pipe(ResponseTimeMiddleware::class);
```

### ConfigProvider Registration

```php
// config/config.php
return [
    // ...
    \Ctw\Middleware\ResponseTimeMiddleware\ConfigProvider::class,
];
```

### Response Header Output

```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
X-Response-Time: 45.123 ms
```

### Inspecting with cURL

```bash
curl -I https://example.com/

# Response includes:
# X-Response-Time: 45.123 ms
```

### Inspecting in Browser DevTools

1. Open Developer Tools (F12)
2. Navigate to the Network tab
3. Select a request
4. View Response Headers
5. Look for `X-Response-Time`

### Header Format

| Component | Description |
|-----------|-------------|
| Value | Processing time in milliseconds |
| Precision | Three decimal places (microsecond resolution) |
| Format | `X.XXX ms` |
| Example | `45.123 ms` |

### Timing Calculation

The middleware calculates response time as:

```
Response Time = (end_time - start_time) × 1000
```

Where:
- `start_time` = `$_SERVER['REQUEST_TIME_FLOAT']` (request arrival)
- `end_time` = `microtime(true)` (response generation complete)

### Use Cases

#### Log Analysis

```bash
# Extract response times from access logs
grep "X-Response-Time" /var/log/nginx/access.log | awk '{print $NF}'
```

#### Performance Alerting

```javascript
// Client-side monitoring
const responseTime = parseFloat(
  response.headers.get('X-Response-Time')
);
if (responseTime > 1000) {
  console.warn('Slow response:', responseTime, 'ms');
}
```

#### Load Testing

```bash
# Apache Bench with response time analysis
ab -n 1000 -c 10 https://example.com/ | grep "Time per request"
```
