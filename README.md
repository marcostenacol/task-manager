# Task Manager

A modern, feature-rich task management application built with Laravel and Vue.js.

## Features

- **User Authentication**: Secure login and registration system.
- **Task Management**: Create, read, update, and delete tasks.
- **Task Prioritization**: Mark tasks as high priority.
- **Task Status**: Track task completion status.
- **Responsive Design**: Beautiful UI that works on desktop and mobile.

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Vue.js 3, Tailwind CSS
- **Database**: MySQL
- **Testing**: Pest

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL

### Steps

1.  **Clone the repository**
    ```bash
    git clone <repository-url>
    cd task-manager
    ```

2.  **Install Backend Dependencies**
    ```bash
    cd task-manager-api
    composer install
    ```

3.  **Configure Environment**
    Copy the example environment file and fill in your database credentials:
    ```bash
    cp .env.example .env
    ```
    Edit `.env` with your database configuration:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=task_manager
    DB_USERNAME=root
    DB_PASSWORD=
    ```

4.  **Generate Application Key**
    ```bash
    php artisan key:generate
    ```

5.  **Run Migrations**
    ```bash
    php artisan migrate
    ```

6.  **Install Frontend Dependencies**
    ```bash
    cd ../task-manager-ui
    npm install
    ```

7.  **Build Frontend**
    ```bash
    npm run build
    ```

8.  **Run the Server**
    Start the Laravel development server:
    ```bash
    cd ../task-manager-api
    php artisan serve
    ```
    The application will be available at `http://localhost:8000`.

## Running Tests

### Backend Tests

Run the Pest test suite:
```bash
cd task-manager-api
pest
```

## Project Structure

```
task-manager/
├── task-manager-api/     # Laravel backend
│   ├── app/              # Application code
│   ├── database/         # Migrations and seeds
│   ├── routes/           # API routes
│   └── tests/            # Pest tests
└── task-manager-ui/      # Vue.js frontend
    ├── src/              # Vue components and logic
    └── public/           # Compiled assets
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).