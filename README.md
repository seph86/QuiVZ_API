# QuiVS API

## Requirements 
- Bash environment
- php
- PDO Compatible database (MySQL or sqlite3 recommended currently)

## packages

- (php-shared-memory)[https://github.com/ninsuo/php-shared-memory] v2.0

## Installation

Clone the repo into a web directory
```sh
git clone https://github.com/seph86/QuiVZ_API.git
cd QuiVZ_APP # Or whichever directory you may have forced the clone into
```

Install packages
```sh
composer i
```

### Fontend

Go to (QuiVZ_APP)[https://github.com/seph86/QuiVZ_APP] to install the front end


## Setup

No setup required if running application on a *nix based device.  Provided you have the requirements installed on your machine.

See .settings.EXAMPLE on configuration

By default all files will be accessable to the internet when the app is installed.  To mitigate this please configure your web server to disallow access to any file with the exception of api.php and sse.php

An example of this with apache is a .htaccess file in the root of both the APP and API.
```ApacheConfig
<IfModule mod_rewrite.c> 
  RewriteEngine On
  
  # Rewrite api requests to the correct subdir
  RewriteCond %{REQUEST_URI} ^/api [NC]
  RewriteRule ^.*$ QuiVZ_API/router_prod.php [END]
  
  # Rewrite SSE to the correct subdir
  RewriteCond %{REQUEST_URI} ^/sse
  RewriteRule ^.*$ QuiVZ_API/router_prod.php [END]
  
  # Rewrite everything else to APP subdir
  RewriteCond %{REQUEST_URI} !^/QuiVZ_APP/build
  RewriteRule ^(.*)$ QuiVZ_APP/build/$1 [END]

  # forbid access to the subdirectory themselves
  #RewriteCond %{REQUEST_URI} ^/QuiVZ_APP/build
  #RewriteCond %{REQUEST_URI} ^/QuiVZ_API
  #RewriteRule . - [F,END]
</IfModule>
```

## Development testing running

Simply run start_development.sh (does not support SSE)

```
./start_development.sh
```

# metacognition

## Things to fix
- Reject and accept quizes do not verify recipient has send a requst, can be abused.
- Admin secret key needs to be moved to a config file for both api and front end
- sse.php logger is not loggging reponse code

## Things not included from design proposal (Proj1)
- Creating quiz questions
- Rating quiz questions
- Reporting quiz questions

## Things to do differnetly
- SSE is too slow for real time events, should of used websockets

## Roadmap

### Code changes
- [ ] Include inline commenting
- [ ] Refactor code to be cleaner 
- [ ] Migrate to a new UI Framework
- [ ] Move from SSE to websockets

### Visual changes
- [ ] Implement visual feedback that menu segments are interactable
- [ ] Move popups away from ugly toast messages
- [ ] Implement sound design

## https proof
See Screenshots/Screenshot1-https.png

## 10 unique tests
See Screenshots/Screenshot2-10Tests.png

Functionally there is no issue with the API according to manual testing however there is still some issues with the ./tests.sh script which do actions incorrectly such as testing password changes.