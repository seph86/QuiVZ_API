# QuiVS API

## Metacognition

### 11. 
QuiVZ front end was built using InfernoJS (A fork of ReactJS built for speed).  InfernoJS (or ReactJS) was chosen due to the JSX style of JS coding and almost seemless ability to create an PWA as well as a bunch of other useful features for developing app like functionality.

### 12.
InfernoJS was the framework that intrested me the most, however Vue and Angular were also considered.  While both of these frameworks looked promising, I personally preferred the JSX style inherit with InfernoJS as mentioned in part 11.

### 13.
When in development mode (by running the server using the ./debug_start.sh) any new accounts created are automatically set to admin permissions for testing purposes.  However to use any of the admin features a user must login on localhost currently or any other IP specified in $admin_ip_whitelist. To see how this works view api/user/user.api:81
Otherwise only a admin (or someone with access to the database) can set a specific user to administrator.

### 14.
Only the password is encrypted using php's built in hashing algorithm.  By default the algorithm is set to bcrypt which is still recognized as a strong encrpytion standard.

## Requirements 
- Bash environment
- php
- sqlite3 php module

## Setup

No setup required if running application on a *nix based device.  Provided you have the requirements installed on your machine.

## Running

Simply run start.sh

```
./start.sh
```
or
```
./debug_start.sh
```

## Testing

Run tests.sh to unit test the API. 
```
./tests.sh -r -k
```

Help is available if you wish to learn more on how to use the testing script
```
./tests.sh -h
```

