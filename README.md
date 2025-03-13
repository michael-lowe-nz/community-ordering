# Laravel Restaurant Directory: A Modern Web Application for Restaurant Management and Discovery

Laravel Restaurant Directory is a robust web application that helps users discover and manage restaurant information. The application provides a comprehensive platform for storing, displaying, and managing restaurant details including locations, menus, and business information, with integration to Google Places API for enhanced data accuracy.

The application is built using Laravel 12.0, featuring a modern tech stack that includes Tailwind CSS for styling, Docker for containerization, and AWS CDK for infrastructure deployment. It offers a RESTful API for restaurant data management, real-time search capabilities, and a responsive web interface. The application supports both local development with Docker Compose and production deployment to AWS using ECS Fargate, making it suitable for both development and production environments.

## Repository Structure
```
.
├── app/                          # Core application code
│   ├── Http/Controllers/         # Request handlers for restaurants and menu items
│   └── Models/                   # Eloquent models for Restaurant and MenuItem
├── bootstrap/                    # Application bootstrapping files
├── config/                      # Configuration files for auth, cache, database, etc.
├── database/                    # Database migrations, seeders, and factories
├── docker/                      # Docker configuration files
├── infrastructure/              # AWS CDK infrastructure code
│   └── src/                    # TypeScript source for AWS infrastructure
├── resources/                   # Frontend assets and views
│   ├── css/                    # Stylesheets including Tailwind CSS
│   ├── js/                     # JavaScript files
│   └── views/                  # Blade template files
├── routes/                     # Application routes
└── tests/                     # Test files for Feature and Unit tests
```

## Usage Instructions
### Prerequisites
- PHP 8.2 or higher
- Composer 2.x
- Node.js 16.x or higher
- Docker and Docker Compose
- AWS CLI (for deployment)
- Google Places API key
- Visual Studio Code (recommended for development)

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd laravel-restaurant-directory
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Set up environment variables:
```bash
cp .env.example .env
php artisan key:generate
```

5. Start the Docker environment:
```bash
docker-compose up -d
```

6. Run database migrations and seeders:
```bash
docker-compose exec laravel.test php artisan migrate
docker-compose exec laravel.test php artisan db:seed
```

### Development with VSCode Dev Containers

For the best development experience, you can use Visual Studio Code with the Dev Containers extension:

1. Install the "Remote - Containers" extension in VSCode:
   - Open VSCode
   - Press `Ctrl+P` (Windows/Linux) or `Cmd+P` (macOS)
   - Type `ext install ms-vscode-remote.remote-containers`

2. Open the project in a dev container:
   - Open the project folder in VSCode
   - Click the green button in the lower-left corner or press `F1`
   - Select "Remote-Containers: Reopen in Container"
   - VSCode will build and start the development container

3. The dev container provides:
   - Pre-configured PHP development environment
   - Debug configuration
   - Extension recommendations
   - Integrated terminal with all required tools
   - Automatic port forwarding

4. Once the container is running, you can:
   - Use the integrated terminal to run artisan commands
   - Debug your application with Xdebug
   - Access the application at `http://localhost`
   - Make changes to the code with full IDE support

### Quick Start
1. Access the application:
```bash
# Local development
http://localhost:80
```

2. View restaurant listings:
```bash
http://localhost/restaurant
```

3. View individual restaurant details:
```bash
http://localhost/restaurant/{id}
```

### More Detailed Examples

1. Adding a new restaurant via API:
```php
POST /api/restaurants
{
    "name": "Sample Restaurant",
    "address": "123 Main St",
    "cuisine_type": "Italian",
    "price_range": "$$",
    "opening_hours": "9:00 AM - 10:00 PM"
}
```

2. Updating restaurant information:
```php
PUT /api/restaurants/{id}
{
    "description": "Updated description",
    "phone": "+1234567890"
}
```

### Troubleshooting

1. Database Connection Issues
```bash
# Check database connection
php artisan db:monitor

# View logs
docker-compose logs -f laravel.test
```

2. Permission Issues
```bash
# Fix storage permissions
chmod -R 777 storage bootstrap/cache
```

3. Container Issues
```bash
# Rebuild containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## Data Flow
The application follows a standard MVC architecture with additional integration to external services.

```ascii
[Client] -> [Load Balancer] -> [Laravel App Container]
                                      |
                                      v
[Google Places API] <-------- [Restaurant Controller]
                                      |
                                      v
                               [Database Layer]
```

Component interactions:
1. Client requests are received through the load balancer
2. Requests are routed to the appropriate controller
3. Controllers interact with models for data operations
4. External data is fetched from Google Places API when needed
5. Database operations are performed through Eloquent ORM
6. Views are rendered using Blade templates
7. Response is returned to the client

## Infrastructure

![Infrastructure diagram](./docs/infra.svg)
The application is deployed on AWS using the following resources:

Lambda:
- Laravel application running on ECS Fargate
- Task definitions with ARM64 architecture

VPC:
- Custom VPC with public and private subnets
- Application Load Balancer for request distribution

Security:
- Secrets Manager for API key storage
- IAM roles for task execution and Google Places API access

Monitoring:
- CloudWatch Logs for application logging
- Health checks with 60-second grace period

## Deployment

Prerequisites:
- AWS CLI configured with appropriate credentials
- CDK CLI installed
- Docker installed and logged in to AWS ECR

Deployment steps:
1. Build and push Docker image:
```bash
docker build -t laravel-restaurant-directory .
```

2. Deploy infrastructure:
```bash
cd infrastructure
npm install
npx cdk deploy
```

3. Configure environment:
```bash
# Set up environment variables in AWS Systems Manager
aws ssm put-parameter --name "/app/google-places-api-key" --value "your-api-key" --type SecureString
```

4. Monitor deployment:
```bash
# View CloudWatch logs
aws logs tail /aws/ecs/Laravel
```