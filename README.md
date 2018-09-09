## Requirements ##
1. PHP 7.1 or higher
2. Composer
3. MySQL database

## Setting up the project ##

### Install packages ##
`composer install`

### Checking for Security Vulnerabilities ###
`composer require sensiolabs/security-checker --dev`

### Configure database connection ###
Open `.env` file. Find and edit line that is responsible for database connection:  
`DATABASE_URL=mysql://user:password@host:3306/databasename`

### Create initial database ###
`php bin/console doctrine:migrations:migrate`  
`php bin/console doctrine:fixtures:load`

### Run server ###
`php bin/console server:run`

## Using blog API ##

### Posts ###
Get post list  
`curl http://localhost:8000/api/category`

### Categories ###
### Media ###
