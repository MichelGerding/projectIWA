# Project IWA



## setup 
to start the server you first need to do a couple of steps. 
first you need to install all compopser depencies using the following command in the backend-app folder.  

``` 
composer i
```


### configure .env
once that is complete the next step would be to verify the env variables

to configure the environment variables you need to first rename `backend-app/.env.example` to `backend-app/.env`.
after that you can configure the env variable in the file. 
if using only features in this repo you dont need to change any of the variables.

the server automaticaly restarts after you save the file so there is no need to restart the docker container after editing them.


## start the server
to start the server you need to have docker installed. to start the server you can run the following command. to run it in the background you can simple append the -d tag.

```
docker compose up
```

when the server has succesfully started it is accesable at `http://localhost:8000`

## stopping server 
to stop the sserver you can use the command 
```
docker compose down 
```

## executing commands
to execute commands on the server you can use the `ds.sh` ort the `ds.ps1` file. 
this file passes the args as command to the laravel container. 
if you are on windows you need to use the ds.ps1 file.

for example
```
ds.sh php artisan list
```

converts to 
```
docker compose exec laravel-iwa php artisan list
```


## accessing logs 
to access the logs of the docker processes you can use the command. the following command shows you the 50 most recent entrie sin the log. 
```
docker compose logs -f --tail 50
```

to access a single service you can append the service to the end