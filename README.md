# Blog API Service
Basic blog API service with JWT user authorization with Symfony.

Blog features:
* Category CRUD
* Post CRUD
* Media management
* Admin and redactor roles
* JWT API Authorization

## Requirements ##
1. PHP 7.1 or higher
2. Composer
3. MySQL database

## Setting up the project ##

### Install packages ##
`composer install`

### Checking for Security Vulnerabilities ###
`composer require sensiolabs/security-checker --dev`

### Generate JWT keys ###
````
cd config/jwt
ssh-keygen -t rsa -b 4096 -m PEM -f jwt.key
# don't add passphrase
openssl rsa -in jwt.key -pubout -outform PEM -out jwt.key.pub
````
### Configure database connection ###
Copy `.env.dist` file and rename it to `.env`. Open file, find and edit line that is responsible for database connection:  
`DATABASE_URL=mysql://user:password@host:3306/databasename`

### Create initial database ###
`php bin/console doctrine:migrations:migrate`  
`php bin/console doctrine:fixtures:load`

### Run server ###
`php bin/console server:run`

## Using blog API ##

### Default users ###
````
// username:password
   admin:lajka1
   redactor:felicette2
````

### API documentation ###
Full API documentation is available at `http://localhost:8000/api/doc`

### User authentication ###
Blog API uses token authentication, send request to obtain token:
````
curl -X POST http://localhost:8000/api/login_check -H 'Cache-Control: no-cache' -H 'Content-Type: application/json' -d '{"username":"admin","password":"H9Lb9xeqIL470V8"}'
````
This request returns API token that is valid for 1 hour. Put the token in the request headers (see authenticated user requests).

### Example anonymous requests ###
````
// get all categories
curl -X GET "http://localhost:8000/api/category" -H "accept: application/json"

// get single category
curl -X GET "http://localhost:8000/api/category/1" -H "accept: application/json"

// get all posts
curl -X GET "http://localhost:8000/api/post" -H "accept: application/json"

// get single post
curl -X GET "http://localhost:8000/api/post/1" -H "accept: application/json"
````

### Example authenticated user requests ###
````
// create new category
curl -X POST http://localhost:8000/api/category -H 'accept: application/json' -H 'Content-Type: application/json' -H 'Authorization: Bearer {token}' -d '{"name": "new category", "description": "new category description"}'

// create new post
curl -X POST "http://localhost:8000/api/post" -H "accept: application/json" -H "Authorization: Bearer {token}" -H "Content-Type: application/json" -d "{ \"title\": \"Post title\", \"content\": \"Lorem ipsum dolor...\", \"image\": 1, \"categories\": [ 1 ], \"tags\": [ \"my tag\", \"another tag\" ]}"
````

#### Uploading media ####
Media files should be uploaded as data binary body of POST request. Example:
````
curl -X POST http://localhost:8000/api/media -H 'Authorization: Bearer {token}' -H 'Content-Type: image/jpeg' --data-binary "@D:\image.jpg" 
````
All uploaded media are available in `/uploads/media` directory.