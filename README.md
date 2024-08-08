### Guide for Cloning and Setting Up a Symfony Project

This guide will help developers clone and set up a Symfony project from a Git repository.

Please follow these steps carefully to ensure a smooth setup.

#### Prerequisites

1. **PHP**: Ensure PHP is installed (version 8.0 or higher).
2. **Composer**: Dependency manager for PHP.
3. **Symfony CLI**: Optional but recommended for managing Symfony projects.
4. **Database**: MySQL or PostgreSQL, depending on your project.
5. **Git**: For cloning the repository.

#### Steps to Clone and Set Up the Project

1. **Clone the Repository**

   Open your terminal and run the following command:

   ```sh
   git clone https://github.com/filjoseph1989/import-customer.git
   ```

   Navigate into the project directory:

   ```sh
   cd import-customer
   ```

2. **Install PHP Dependencies**

   Run the following command to install the project dependencies:

   ```sh
   composer install
   ```

   This will create the `vendor` directory and download all the necessary packages.

3. **Set Up Environment Variables**

   Copy the `.env.example` file to `.env`:

   ```sh
   cp .env.example .env
   ```

   Open the `.env` file and configure your database and other environment variables. For example:

   ```dotenv
   DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name
   ```

4. **Create the Database**

   Ensure your database server is running, then create the database using the following command:

   ```sh
   php bin/console doctrine:database:create
   ```

5. **Run Database Migrations**

   Apply the migrations to set up the database schema:

   ```sh
   php bin/console doctrine:migrations:migrate
   ```

6. **Start the Symfony Server**

   You can start the Symfony built-in server using the Symfony CLI:

   ```sh
   symfony server:start
   ```

   Or using PHP’s built-in server:

   ```sh
   php -S 127.0.0.1:8000 -t public
   ```

   configure apache2 or nginx

9. **Access the Application**

   Open your browser and navigate to:

   ```
   http://127.0.0.1:8000
   ```

   You should see your Symfony application running.

#### Additional Tips

- **Clearing Cache**: If you encounter issues, try clearing the cache:

  ```sh
  php bin/console cache:clear
  ```

- **Symfony CLI**: The Symfony CLI provides useful commands for managing your project. You can view them using:

  ```sh
  symfony help
  ```

#### Importing Customers

- **import:customers**: Run the following command:

  ```sh
  php bin/console import:customers
  ```

  To import customers


#### Routes

| Name                 | Method | Scheme | Host | Path                                      |
|----------------------|--------|--------|------|-------------------------------------------|
| app_customer         | GET    | ANY    | ANY  | /customers                                |
| app_customer_show    | GET    | ANY    | ANY  | /customer/{id}                            |
| app_customer_create  | GET    | ANY    | ANY  | /import-customer/{nationality}/{results}  |