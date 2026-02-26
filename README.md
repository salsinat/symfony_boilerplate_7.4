# Backoffice Symfony 7.4

A custom-built Backoffice application using Symfony 7.4 and Tailwind CSS.

## Prerequisites
- PHP 8.2+
- Composer
- Symfony CLI
- MySQL / PostgreSQL

## Installation

1.  **Clone & Install Dependencies**
    ```bash
    composer install
    ```

2.  **Database Setup**
    Configure your database credentials in `.env.local` (create it if it doesn't exist):
    ```dotenv
    DATABASE_URL="mysql://root:@127.0.0.1:3306/site_ecommerce?serverVersion=8&charset=utf8mb4"
    ```

    Then run migration and fixtures:
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load
    ```

3.  **Tailwind CSS**
    Ensure Tailwind is built:
    ```bash
    php bin/console tailwind:build
    ```

4.  **Run Server**
    ```bash
    php -S 127.0.0.1:8000 -t public
    # OR
    symfony server:start
    ```

## Default Credentials

The project comes with 3 pre-configured users (Password is same as usage role/name):

| Role | Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@site.com` | `admin` | Full Access (User Management) |
| **Manager** | `manager@site.com` | `manager` | Product Management |
| **User** | `user@site.com` | `user` | Basic Access |

## Features
- **Authentication**: Secure login with Role manipulation.
- **Dashboard**: Tailwind CSS sidebar layout.
- **User Management**: (Coming in Step 2)