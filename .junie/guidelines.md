# Development Guidelines for Bracket Validation Service

This document provides essential information for developers working on this project.

## Build/Configuration Instructions

### Development Environment Setup

1. **Prerequisites**:
   - Docker and Docker Compose
   - Make (optional, but recommended)

2. **Starting the Development Environment**:
   ```bash
   make dev-up
   ```
   Or without Make:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
   ```

3. **Stopping the Development Environment**:
   ```bash
   make dev-down
   ```
   Or without Make:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml down
   ```

4. **Rebuilding the Development Environment**:
   ```bash
   make dev-build
   ```
   Or for a clean rebuild:
   ```bash
   make dev-rebuild
   ```

### Production Environment Setup

1. **Building and Pushing Docker Images**:
   ```bash
   make prod-build
   ```
   This builds multi-architecture images (amd64, arm64) and pushes them to Docker Hub.

2. **Starting the Production Environment**:
   ```bash
   make prod-up
   ```

3. **Stopping the Production Environment**:
   ```bash
   make prod-down
   ```

## Project Structure

- **Backend**:
  - `src/` - PHP source code
  - `public/` - Public files and entry point
  - `nginx/` - NGINX configuration for backend
  - `balancer/` - Load balancer configuration

- **Frontend**:
  - `frontend/` - Vue.js frontend application
  - `frontend/src/` - Vue.js source code
  - `frontend/src/utils/` - Utility functions

## Testing Information

### Setting Up Testing Environment

1. **Add PHPUnit as a Development Dependency**:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml exec php-fpm1 composer require --dev phpunit/phpunit
   ```

2. **Configure PHPUnit**:
   Create a `phpunit.xml` file in the project root:
   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
            bootstrap="vendor/autoload.php"
            colors="true"
            verbose="true">
       <testsuites>
           <testsuite name="Unit">
               <directory>tests/Unit</directory>
           </testsuite>
       </testsuites>
       <coverage>
           <include>
               <directory suffix=".php">src</directory>
           </include>
       </coverage>
       <php>
           <ini name="error_reporting" value="-1"/>
           <ini name="display_errors" value="On"/>
       </php>
   </phpunit>
   ```

3. **Update Docker Configuration**:
   Ensure the `tests` directory is mounted in the PHP container:
   ```yaml
   volumes:
     - ./src:/app/src
     - ./tests:/app/tests
   ```

### Running Tests

1. **Using Make**:
   ```bash
   make test
   ```

2. **Directly with Docker Compose**:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml exec php-fpm1 vendor/bin/phpunit
   ```

3. **With Code Coverage**:
   ```bash
   make test-coverage
   ```

### Creating New Tests

1. **Unit Tests**:
   - Create test files in the `tests/Unit` directory
   - Name test classes with the suffix `Test` (e.g., `ValidatorTest`)
   - Extend `PHPUnit\Framework\TestCase`

2. **Example Test**:
   ```php
   <?php
   
   namespace Tests\Unit;
   
   use App\Validator;
   use PHPUnit\Framework\TestCase;
   
   class ValidatorTest extends TestCase
   {
       /**
        * Test that valid bracket strings are correctly validated.
        */
       public function testValidBracketString(): void
       {
           $this->assertTrue(Validator::validate('()'));
           $this->assertTrue(Validator::validate('(())'));
           $this->assertTrue(Validator::validate('()()'));
       }
   
       /**
        * Test that invalid bracket strings are correctly rejected.
        */
       public function testInvalidBracketString(): void
       {
           $this->assertFalse(Validator::validate('('));
           $this->assertFalse(Validator::validate(')'));
           $this->assertFalse(Validator::validate(')('));
           $this->assertFalse(Validator::validate('(a)'));
       }
   
       /**
        * Test that empty input throws an exception.
        */
       public function testEmptyInputThrowsException(): void
       {
           $this->expectException(\InvalidArgumentException::class);
           $this->expectExceptionMessage('Empty input.');
           
           Validator::validate('');
       }
   }
   ```

3. **Manual Testing**:
   If PHPUnit is not available, you can create a simple PHP script for testing:
   ```php
   <?php
   
   require __DIR__ . '/../vendor/autoload.php';
   
   use App\Validator;
   
   // Test cases
   $testCases = [
       // Valid bracket strings
       ['()' => true],
       ['(())' => true],
       // Add more test cases as needed
   ];
   
   // Run tests
   foreach ($testCases as $testCase) {
       foreach ($testCase as $input => $expected) {
           try {
               $result = Validator::validate($input);
               echo "Input: '$input', Expected: " . ($expected ? 'true' : 'false') . 
                    ", Actual: " . ($result ? 'true' : 'false') . 
                    ", Result: " . ($result === $expected ? 'PASS' : 'FAIL') . "\n";
           } catch (\Exception $e) {
               echo "Input: '$input', Exception: " . $e->getMessage() . "\n";
           }
       }
   }
   ```

## Additional Development Information

### Code Style

- **PHP**:
  - Follow PSR-12 coding standards
  - Use type hints for method parameters and return types
  - Document classes and methods with PHPDoc comments

- **Vue.js**:
  - Use Vue 3 Composition API
  - Follow Vue.js style guide (Priority A and B rules)

### API Endpoints

- **POST /api/validate**:
  - Validates a bracket string
  - Request body: `{"string": "()"}`
  - Response: `{"status": "success", "message": "Valid bracket sequence", "valid": true}`
  - Error response: `{"status": "error", "message": "Invalid bracket sequence", "error_code": "INVALID_SEQUENCE", "valid": false}`

- **GET /api/status**:
  - Returns the service status
  - Response: `{"status": "OK", "service": "bracket-validator", "version": "1.0.0", "timestamp": "2023-06-06T18:45:00+00:00", "server": "hostname"}`

### Redis Cluster

The application uses a Redis Cluster for session storage and statistics collection. The Redis Cluster is expected to be available on the `redis-cluster-net` network with nodes at:
- `redis-node1:6379`
- `redis-node2:6379`

### Debugging

1. **Viewing Logs**:
   ```bash
   make logs
   ```
   Or for specific environments:
   ```bash
   make dev-logs
   make prod-logs
   ```

2. **Checking Container Status**:
   ```bash
   make ps
   ```